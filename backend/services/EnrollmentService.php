<?php
/**
 * Digital Crazy Hub - Central Enrollment Service
 * Manages full lifecycle: form validation, unique ID generation,
 * MySQL insertion (NOT PAID), Google Sheets sync, Razorpay order, signature verification,
 * status transition to PAID, Tax Invoice PDF generation, and dual email dispatch.
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\AppConfig;
use App\Config\Database;
use App\Helpers\Security;
use App\Helpers\Validator;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

class EnrollmentService {
    /**
     * Handle initial form submission:
     * Saves to MySQL (NOT PAID), appends to Google Sheets (NOT PAID), creates Razorpay order.
     */
    public static function createEnrollmentAndOrder(array $input): array {
        $appConfig = AppConfig::get();
        $ip = Security::getClientIp();

        // 1. Rate limiting
        if (!Security::checkRateLimit($ip, 30, 60)) {
            throw new InvalidArgumentException('Too many requests. Please wait a minute before trying again.');
        }

        // 2. reCAPTCHA v3
        $recaptchaToken = trim((string)($input['recaptcha_token'] ?? ''));
        if (!RecaptchaService::verify($recaptchaToken, $ip)) {
            throw new InvalidArgumentException('Spam protection verification failed. Please refresh and try again.');
        }

        // 3. Validation
        $validated = Validator::validateEnrollment($input);

        // 4. Enrollment ID generation (or reuse if already initiated)
        $providedId = trim((string)($input['enrollment_id'] ?? ($input['enrollment_uuid'] ?? '')));
        $existing = !empty($providedId) ? self::getEnrollment($providedId) : null;

        if ($existing) {
            $enrollmentId = $existing['enrollment_id'];
            $record = $existing;
        } else {
            $pdo = Database::getConnection();
            $enrollmentId = Security::generateEnrollmentId($pdo);

            $enrollmentData = [
                'name'            => $validated['name'],
                'email'           => $validated['email'],
                'phone'           => $validated['phone'],
                'location'        => $validated['location'],
                'current_profile' => $validated['current_profile'],
                'workshop_date'   => $appConfig['workshop']['date'],
                'workshop_time'   => $appConfig['workshop']['time']
            ];

            $record = self::saveNewEnrollment($enrollmentData, $enrollmentId);

            // 5. Append immediately to Google Sheets (NOT PAID)
            try {
                $gsResult = GoogleSheetsService::appendEnrollment($record);
                if ($gsResult['success'] && !empty($gsResult['row_index'])) {
                    self::setGoogleSheetsSynced($enrollmentId, (int)$gsResult['row_index']);
                } else {
                    self::addSyncTask($enrollmentId, 'INSERT', $record, $gsResult['error'] ?? 'Append failed');
                }
            } catch (Throwable $e) {
                error_log("EnrollmentService: GoogleSheets exception: " . $e->getMessage());
                self::addSyncTask($enrollmentId, 'INSERT', $record, $e->getMessage());
            }
        }

        // 6. Create Razorpay Order using trusted server-side price (₹49)
        $razorpay = new RazorpayService();
        $amountPaise = (int)$appConfig['workshop']['amount_in_paise'];
        $currency    = (string)$appConfig['workshop']['currency'];

        $notes = [
            'enrollment_id' => $enrollmentId,
            'name'          => $record['name'],
            'email'         => $record['email'],
            'phone'         => $record['phone'],
            'workshop'      => $appConfig['workshop']['title']
        ];

        $order = $razorpay->createOrder($amountPaise, $currency, $enrollmentId, $notes);
        $orderId = (string)$order['id'];

        self::setOrderId($enrollmentId, $orderId);

        self::logEvent($orderId, 'ENROLLMENT_CREATED', [
            'enrollment_id' => $enrollmentId,
            'name'          => $record['name'],
            'email'         => $record['email'],
            'phone'         => $record['phone'],
            'amount_paise'  => $amountPaise
        ], $enrollmentId);

        return [
            'success'            => true,
            'enrollment_id'      => $enrollmentId,
            'order_id'           => $orderId,
            'key_id'             => $razorpay->getKeyId(),
            'amount'             => $amountPaise,
            'currency'           => $currency,
            'verification_token' => $record['verification_token'],
            'user'               => [
                'name'     => $record['name'],
                'email'    => $record['email'],
                'phone'    => $record['phone'],
                'location' => $record['location']
            ],
            'workshop'           => [
                'title' => $appConfig['workshop']['title'],
                'date'  => $appConfig['workshop']['date'],
                'time'  => $appConfig['workshop']['time']
            ],
            'is_mock'            => !empty($order['is_mock'])
        ];
    }

    /**
     * Handle payment verification from frontend checkout callback
     */
    public static function processPaymentVerification(array $input): array {
        $validated = Validator::validatePaymentVerification($input);

        $orderId      = $validated['order_id'];
        $paymentId    = $validated['payment_id'];
        $signature    = $validated['signature'];
        $enrollmentId = $validated['enrollment_id'];

        if (empty($enrollmentId)) {
            $recByOrder = self::getEnrollmentByOrderId($orderId);
            if ($recByOrder) {
                $enrollmentId = $recByOrder['enrollment_id'];
            }
        }

        if (empty($enrollmentId)) {
            throw new InvalidArgumentException('Unable to locate enrollment record for order.');
        }

        // 1. Verify Razorpay Signature server-side using strict HMAC-SHA256
        $razorpay = new RazorpayService();
        $isValidSignature = $razorpay->verifyPaymentSignature($orderId, $paymentId, $signature);

        if (!$isValidSignature) {
            self::logEvent($orderId, 'PAYMENT_SIGNATURE_MISMATCH', [
                'enrollment_id' => $enrollmentId,
                'payment_id'    => $paymentId,
                'signature'     => $signature
            ], $enrollmentId, $paymentId);

            return [
                'success'      => false,
                'message'      => 'Payment signature verification failed. Our team will verify your transaction shortly.',
                'redirect_url' => 'payment-failed.html?error=signature_mismatch&order=' . urlencode($orderId)
            ];
        }

        return self::completePayment($enrollmentId, $paymentId, $orderId, $signature);
    }

    /**
     * Complete verified payment (common for both direct API verification and Webhook)
     * Idempotently updates DB, Google Sheets, generates PDF, sends dual emails.
     */
    public static function completePayment(string $enrollmentId, string $paymentId, string $orderId, string $signature): array {
        $appConfig = AppConfig::get();
        $record = self::getEnrollment($enrollmentId);
        if (!$record) {
            throw new RuntimeException("Enrollment record not found for ID: {$enrollmentId}");
        }

        // Idempotency Check: if already marked PAID, return existing verified confirmation
        if (($record['payment_status'] ?? '') === 'PAID') {
            $token = $record['verification_token'];
            return [
                'success'              => true,
                'message'              => 'Enrollment already confirmed.',
                'already_processed'    => true,
                'enrollment_id'        => $enrollmentId,
                'verification_token'   => $token,
                'redirect_url'         => "thank-you.html?enrollment_id=" . urlencode($enrollmentId) . "&token=" . urlencode($token),
                'invoice_download_url' => "api/download-invoice.php?enrollment_id=" . urlencode($enrollmentId) . "&token=" . urlencode($token)
            ];
        }

        // 1. Update Database payment status to PAID
        $price = (float)$appConfig['workshop']['price'];
        $updatedRecord = self::markAsPaid($enrollmentId, $paymentId, $signature, $orderId, $price);

        if (!$updatedRecord) {
            throw new RuntimeException("Failed to update enrollment to PAID.");
        }

        self::logEvent($orderId, 'PAYMENT_SUCCESS', [
            'enrollment_id' => $enrollmentId,
            'payment_id'    => $paymentId,
            'amount'        => $price,
            'signature_ref' => substr($signature, 0, 10) . '...'
        ], $enrollmentId, $paymentId);

        // 2. Update Google Sheets row to PAID (Green)
        try {
            $gsRes = GoogleSheetsService::updatePaymentStatus($enrollmentId, $paymentId, $price, (int)($record['google_sheets_row'] ?? 0));
            if (!$gsRes['success']) {
                self::addSyncTask($enrollmentId, 'UPDATE', [
                    'payment_id'  => $paymentId,
                    'amount_paid' => $price,
                    'status'      => 'PAID'
                ], $gsRes['error'] ?? 'Google Sheets row update failed');
            }
        } catch (Throwable $ge) {
            error_log("EnrollmentService: GoogleSheets update error: " . $ge->getMessage());
            self::addSyncTask($enrollmentId, 'UPDATE', [
                'payment_id'  => $paymentId,
                'amount_paid' => $price,
                'status'      => 'PAID'
            ], $ge->getMessage());
        }

        // 3. Generate Invoice PDF ONLY AFTER verified payment
        $invoicePdfPath = '';
        try {
            $invoicePdfPath = InvoiceService::generate($updatedRecord);
        } catch (Throwable $pe) {
            error_log("EnrollmentService: Invoice PDF generation error: " . $pe->getMessage());
        }

        // 4. Send Student Confirmation Email with PDF attached
        if (!empty($updatedRecord['email'])) {
            try {
                EmailService::sendStudentConfirmation($updatedRecord, $invoicePdfPath);
            } catch (Throwable $ee) {
                error_log("EnrollmentService: Student confirmation email error: " . $ee->getMessage());
            }
        }

        // 5. Send Company Lead Notification Email
        try {
            EmailService::sendCompanyNotification($updatedRecord);
        } catch (Throwable $ce) {
            error_log("EnrollmentService: Company lead notification error: " . $ce->getMessage());
        }

        $token = $updatedRecord['verification_token'];
        $redirectUrl = "thank-you.html?enrollment_id=" . urlencode($enrollmentId) . "&token=" . urlencode($token);
        $downloadUrl = "api/download-invoice.php?enrollment_id=" . urlencode($enrollmentId) . "&token=" . urlencode($token);

        return [
            'success'              => true,
            'message'              => 'Congratulations! Your workshop seat has been confirmed.',
            'enrollment_id'        => $enrollmentId,
            'verification_token'   => $token,
            'redirect_url'         => $redirectUrl,
            'invoice_download_url' => $downloadUrl
        ];
    }

    /**
     * Process Razorpay Asynchronous Webhooks
     */
    public static function processWebhook(string $payload, string $signature): array {
        $razorpay = new RazorpayService();
        $isValid = $razorpay->verifyWebhookSignature($payload, $signature);

        if (!$isValid) {
            self::logEvent(null, 'WEBHOOK_SIGNATURE_MISMATCH', [
                'signature_received' => $signature
            ]);
            return ['success' => false, 'error' => 'Invalid webhook signature', 'code' => 400];
        }

        $event = json_decode($payload, true);
        if (!is_array($event) || !isset($event['event'])) {
            return ['success' => false, 'error' => 'Invalid webhook payload JSON', 'code' => 400];
        }

        $eventType = (string)$event['event'];
        $payment = $event['payload']['payment']['entity'] ?? [];
        $order = $event['payload']['order']['entity'] ?? [];
        $paymentId = $payment['id'] ?? '';
        $orderId = $payment['order_id'] ?? ($order['id'] ?? '');

        $notes = $payment['notes'] ?? ($order['notes'] ?? []);
        $enrollmentId = $notes['enrollment_id'] ?? ($notes['enrollment_uuid'] ?? '');

        self::logEvent($orderId, 'WEBHOOK_' . strtoupper(str_replace('.', '_', $eventType)), $event, $enrollmentId, $paymentId);

        if (in_array($eventType, ['payment.captured', 'order.paid'], true)) {
            if (empty($enrollmentId) && !empty($orderId)) {
                $rec = self::getEnrollmentByOrderId($orderId);
                if ($rec) {
                    $enrollmentId = $rec['enrollment_id'];
                }
            }

            if (!empty($enrollmentId) && !empty($paymentId)) {
                $res = self::completePayment($enrollmentId, $paymentId, $orderId, 'WEBHOOK_CAPTURED');
                return ['success' => true, 'event' => $eventType, 'enrollment_id' => $enrollmentId, 'details' => $res];
            }
        } elseif ($eventType === 'payment.failed') {
            self::logEvent($orderId, 'WEBHOOK_PAYMENT_FAILED', [
                'error_code' => $payment['error_code'] ?? null,
                'error_description' => $payment['error_description'] ?? null
            ], $enrollmentId, $paymentId);
        }

        return ['success' => true, 'event' => $eventType, 'status' => 'handled'];
    }

    // -------------------------------------------------------------
    // Database Operations with Resilient JSON Fallback
    // -------------------------------------------------------------

    public static function saveNewEnrollment(array $data, string $enrollmentId): array {
        $token = Security::generateToken();
        $pdo = Database::getConnection();
        $ip = Security::getClientIp();

        if ($pdo) {
            $stmt = $pdo->prepare("
                INSERT INTO enrollments (
                    enrollment_id, name, email, phone, location, current_profile,
                    amount, amount_paid, payment_status, amount_paid_status,
                    workshop_date, workshop_time, verification_token, ip_address, created_at
                ) VALUES (
                    :eid, :name, :email, :phone, :location, :current_profile,
                    49.00, 0.00, 'UNPAID', 'UNPAID',
                    :w_date, :w_time, :token, :ip, NOW()
                )
            ");
            $stmt->execute([
                ':eid'             => $enrollmentId,
                ':name'            => $data['name'],
                ':email'           => $data['email'],
                ':phone'           => $data['phone'],
                ':location'        => $data['location'],
                ':current_profile' => $data['current_profile'],
                ':w_date'          => $data['workshop_date'],
                ':w_time'          => $data['workshop_time'],
                ':token'           => $token,
                ':ip'              => $ip
            ]);
        } else {
            $records = self::readJsonFallback();
            $records[$enrollmentId] = [
                'id'                   => count($records) + 1,
                'enrollment_id'        => $enrollmentId,
                'enrollment_uuid'      => $enrollmentId,
                'name'                 => $data['name'],
                'email'                => $data['email'],
                'phone'                => $data['phone'],
                'location'             => $data['location'],
                'city'                 => $data['location'],
                'current_profile'      => $data['current_profile'],
                'profession'           => $data['current_profile'],
                'workshop_date'        => $data['workshop_date'],
                'workshop_time'        => $data['workshop_time'],
                'amount'               => 49.00,
                'amount_paid'          => 0.00,
                'order_id'             => null,
                'razorpay_order_id'    => null,
                'payment_id'           => null,
                'razorpay_payment_id'  => null,
                'payment_status'       => 'UNPAID',
                'amount_paid_status'   => 'UNPAID',
                'status'               => 'UNPAID',
                'invoice_number'       => null,
                'invoice_url'          => null,
                'verification_token'   => $token,
                'ip_address'           => $ip,
                'google_sheets_synced' => 0,
                'google_sheets_row'    => null,
                'created_at'           => date('Y-m-d H:i:s')
            ];
            self::writeJsonFallback($records);
        }

        return self::getEnrollment($enrollmentId);
    }

    public static function setOrderId(string $enrollmentId, string $orderId): void {
        $pdo = Database::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE enrollments SET order_id = :oid WHERE enrollment_id = :eid");
            $stmt->execute([':oid' => $orderId, ':eid' => $enrollmentId]);
        } else {
            $records = self::readJsonFallback();
            if (isset($records[$enrollmentId])) {
                $records[$enrollmentId]['order_id'] = $orderId;
                $records[$enrollmentId]['razorpay_order_id'] = $orderId;
                self::writeJsonFallback($records);
            }
        }
    }

    public static function markAsPaid(string $enrollmentId, string $paymentId, string $signature, string $orderId, float $amountPaid = 49.00): ?array {
        $token = Security::generateToken();
        $now = date('Y-m-d H:i:s');
        $invoiceNum = 'INV-' . $enrollmentId;
        $invoiceUrl = 'backend/storage/invoices/' . $invoiceNum . '.pdf';
        $pdo = Database::getConnection();

        if ($pdo) {
            $stmt = $pdo->prepare("
                UPDATE enrollments SET
                    payment_status     = 'PAID',
                    amount_paid_status = 'PAID',
                    payment_id         = :pid,
                    order_id           = :oid,
                    signature          = :sig,
                    amount_paid        = :amount_paid,
                    invoice_number     = :inv_num,
                    invoice_url        = :inv_url,
                    verification_token = :token,
                    paid_at            = :paid_at
                WHERE enrollment_id = :eid
            ");
            $stmt->execute([
                ':pid'         => $paymentId,
                ':oid'         => $orderId,
                ':sig'         => $signature,
                ':amount_paid' => $amountPaid,
                ':inv_num'     => $invoiceNum,
                ':inv_url'     => $invoiceUrl,
                ':token'       => $token,
                ':paid_at'     => $now,
                ':eid'         => $enrollmentId
            ]);
        } else {
            $records = self::readJsonFallback();
            if (!isset($records[$enrollmentId])) {
                return null;
            }
            $records[$enrollmentId]['payment_status'] = 'PAID';
            $records[$enrollmentId]['amount_paid_status'] = 'PAID';
            $records[$enrollmentId]['status'] = 'PAID';
            $records[$enrollmentId]['payment_id'] = $paymentId;
            $records[$enrollmentId]['razorpay_payment_id'] = $paymentId;
            $records[$enrollmentId]['order_id'] = $orderId;
            $records[$enrollmentId]['razorpay_order_id'] = $orderId;
            $records[$enrollmentId]['signature'] = $signature;
            $records[$enrollmentId]['amount_paid'] = $amountPaid;
            $records[$enrollmentId]['invoice_number'] = $invoiceNum;
            $records[$enrollmentId]['invoice_url'] = $invoiceUrl;
            $records[$enrollmentId]['verification_token'] = $token;
            $records[$enrollmentId]['paid_at'] = $now;
            self::writeJsonFallback($records);
        }

        return self::getEnrollment($enrollmentId);
    }

    public static function setGoogleSheetsSynced(string $enrollmentId, int $rowIndex): void {
        $pdo = Database::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE enrollments SET google_sheets_synced = 1, google_sheets_row = :row WHERE enrollment_id = :eid");
            $stmt->execute([':row' => $rowIndex, ':eid' => $enrollmentId]);
        } else {
            $records = self::readJsonFallback();
            if (isset($records[$enrollmentId])) {
                $records[$enrollmentId]['google_sheets_synced'] = 1;
                $records[$enrollmentId]['google_sheets_row'] = $rowIndex;
                self::writeJsonFallback($records);
            }
        }
    }

    public static function getEnrollment(string $enrollmentId): ?array {
        $pdo = Database::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE enrollment_id = :eid LIMIT 1");
            $stmt->execute([':eid' => $enrollmentId]);
            $row = $stmt->fetch();
            if ($row) {
                $row['enrollment_uuid'] = $row['enrollment_id'];
                $row['city'] = $row['location'];
                $row['profession'] = $row['current_profile'];
                $status = $row['amount_paid_status'] ?? ($row['payment_status'] ?? 'UNPAID');
                $row['amount_paid_status'] = $status;
                $row['payment_status'] = $status;
                $row['status'] = $status;
                $row['razorpay_order_id'] = $row['order_id'];
                $row['razorpay_payment_id'] = $row['payment_id'];
                $row['amount'] = $row['amount'] ?? 49.00;
                $row['amount_paid'] = $row['amount_paid'] ?? 0.00;
                $row['invoice_number'] = $row['invoice_number'] ?? ($status === 'PAID' ? 'INV-' . $row['enrollment_id'] : null);
                $row['invoice_url'] = $row['invoice_url'] ?? ($status === 'PAID' ? 'backend/storage/invoices/INV-' . $row['enrollment_id'] . '.pdf' : null);
            }
            return $row ?: null;
        } else {
            $records = self::readJsonFallback();
            $rec = $records[$enrollmentId] ?? null;
            if ($rec) {
                $status = $rec['amount_paid_status'] ?? ($rec['payment_status'] ?? 'UNPAID');
                $rec['amount_paid_status'] = $status;
                $rec['payment_status'] = $status;
                $rec['status'] = $status;
            }
            return $rec;
        }
    }

    public static function getEnrollmentByOrderId(string $orderId): ?array {
        $pdo = Database::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE order_id = :oid LIMIT 1");
            $stmt->execute([':oid' => $orderId]);
            $row = $stmt->fetch();
            if ($row) {
                $row['enrollment_uuid'] = $row['enrollment_id'];
                $row['city'] = $row['location'];
                $row['profession'] = $row['current_profile'];
                $status = $row['amount_paid_status'] ?? ($row['payment_status'] ?? 'UNPAID');
                $row['amount_paid_status'] = $status;
                $row['payment_status'] = $status;
                $row['status'] = $status;
            }
            return $row ?: null;
        } else {
            $records = self::readJsonFallback();
            foreach ($records as $r) {
                if (($r['order_id'] ?? '') === $orderId || ($r['razorpay_order_id'] ?? '') === $orderId) {
                    $status = $r['amount_paid_status'] ?? ($r['payment_status'] ?? 'UNPAID');
                    $r['amount_paid_status'] = $status;
                    $r['payment_status'] = $status;
                    $r['status'] = $status;
                    return $r;
                }
            }
            return null;
        }
    }

    public static function logEvent(?string $orderId, string $eventType, $payload = null, ?string $enrollmentId = null, ?string $paymentId = null): void {
        $pdo = Database::getConnection();
        $ip = Security::getClientIp();
        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? 'CLI'), 0, 255);
        $payloadStr = is_string($payload) ? $payload : json_encode($payload);

        if ($pdo) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO payment_logs (enrollment_id, order_id, payment_id, event_type, payload, ip_address, user_agent, created_at)
                    VALUES (:eid, :oid, :pid, :event, :payload, :ip, :ua, NOW())
                ");
                $stmt->execute([
                    ':eid'     => $enrollmentId,
                    ':oid'     => $orderId,
                    ':pid'     => $paymentId,
                    ':event'   => $eventType,
                    ':payload' => $payloadStr,
                    ':ip'      => $ip,
                    ':ua'      => $ua
                ]);
                return;
            } catch (Throwable $e) {
                error_log("Database logEvent error: " . $e->getMessage());
            }
        }

        $logFile = STORAGE_DIR . '/logs/payment_logs.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logEntry = "[" . date('Y-m-d H:i:s') . "] [{$eventType}] [Order: {$orderId}] [Enroll: {$enrollmentId}] [Pay: {$paymentId}] [IP: {$ip}] " . $payloadStr . PHP_EOL;
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    public static function addSyncTask(string $enrollmentId, string $action, array $payload, ?string $error = null): void {
        $pdo = Database::getConnection();
        $now = date('Y-m-d H:i:s');
        $payloadJson = json_encode($payload);

        if ($pdo) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO sync_queue (enrollment_id, action, payload, status, attempts, last_error, created_at, updated_at)
                    VALUES (:eid, :action, :payload, 'PENDING', 0, :err, :now, :now)
                ");
                $stmt->execute([
                    ':eid'     => $enrollmentId,
                    ':action'  => $action,
                    ':payload' => $payloadJson,
                    ':err'     => $error,
                    ':now'     => $now
                ]);
                return;
            } catch (Throwable $e) {
                error_log("Database sync_queue error: " . $e->getMessage());
            }
        }

        $tasks = self::readJsonSyncQueue();
        $tasks[] = [
            'id'            => time() . '_' . random_int(100, 999),
            'enrollment_id' => $enrollmentId,
            'action'        => $action,
            'payload'       => $payload,
            'status'        => 'PENDING',
            'attempts'      => 0,
            'last_error'    => $error,
            'created_at'    => $now
        ];
        self::writeJsonSyncQueue($tasks);
    }

    public static function getPendingSyncTasks(int $limit = 20): array {
        $pdo = Database::getConnection();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM sync_queue WHERE status = 'PENDING' AND attempts < 5 ORDER BY id ASC LIMIT :limit");
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchAll() ?: [];
            } catch (Throwable $e) {
                return [];
            }
        } else {
            $tasks = self::readJsonSyncQueue();
            $pending = [];
            foreach ($tasks as $t) {
                if ($t['status'] === 'PENDING' && ($t['attempts'] ?? 0) < 5) {
                    $pending[] = $t;
                    if (count($pending) >= $limit) break;
                }
            }
            return $pending;
        }
    }

    public static function markSyncSuccess($taskId, ?int $rowIndex = null): void {
        $pdo = Database::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE sync_queue SET status = 'SUCCESS', updated_at = NOW() WHERE id = :id");
            $stmt->execute([':id' => $taskId]);
        } else {
            $tasks = self::readJsonSyncQueue();
            foreach ($tasks as &$t) {
                if ((string)$t['id'] === (string)$taskId) {
                    $t['status'] = 'SUCCESS';
                }
            }
            self::writeJsonSyncQueue($tasks);
        }
    }

    public static function markSyncFailure($taskId, string $error): void {
        $pdo = Database::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE sync_queue SET attempts = attempts + 1, last_error = :err, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':err' => $error, ':id' => $taskId]);
        } else {
            $tasks = self::readJsonSyncQueue();
            foreach ($tasks as &$t) {
                if ((string)$t['id'] === (string)$taskId) {
                    $t['attempts'] = ($t['attempts'] ?? 0) + 1;
                    $t['last_error'] = $error;
                }
            }
            self::writeJsonSyncQueue($tasks);
        }
    }

    // JSON Fallbacks
    private static function readJsonFallback(): array {
        $path = Database::getFallbackPath();
        if (!file_exists($path)) return [];
        $content = @file_get_contents($path);
        return json_decode((string)$content, true) ?: [];
    }

    private static function writeJsonFallback(array $records): void {
        $path = Database::getFallbackPath();
        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents($path, json_encode($records, JSON_PRETTY_PRINT), LOCK_EX);
    }

    private static function readJsonSyncQueue(): array {
        $path = Database::getSyncQueuePath();
        if (!file_exists($path)) return [];
        $content = @file_get_contents($path);
        return json_decode((string)$content, true) ?: [];
    }

    private static function writeJsonSyncQueue(array $tasks): void {
        $path = Database::getSyncQueuePath();
        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents($path, json_encode($tasks, JSON_PRETTY_PRINT), LOCK_EX);
    }
}

