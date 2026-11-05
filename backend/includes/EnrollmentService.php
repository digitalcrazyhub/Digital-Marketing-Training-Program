<?php
/**
 * Digital Crazy Hub - Central Enrollment & Payment Orchestration Service
 * Manages full lifecycle: form validation, unique Enrollment ID generation,
 * MySQL insertion (NOT PAID), Google Sheets sync, Razorpay order, signature verification,
 * status transition to PAID, instant Tax Invoice PDF generation, and dual email notifications.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Razorpay.php';
require_once __DIR__ . '/../integrations/GoogleSheets.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/InvoiceService.php';
require_once __DIR__ . '/EmailService.php';

class EnrollmentService {
    /**
     * Handle initial form submission:
     * Validates input, saves to MySQL (NOT PAID), appends to Google Sheets (NOT PAID),
     * creates Razorpay order.
     */
    public static function createEnrollmentAndOrder(array $input, array $config): array {
        $ip = Security::getClientIp();

        // 1. Rate limiting check
        if (!Security::checkRateLimit($ip, 20, 60)) {
            throw new InvalidArgumentException('Too many requests. Please wait a minute before trying again.');
        }

        // 2. reCAPTCHA v3 verification
        $recaptchaToken = trim((string)($input['recaptcha_token'] ?? ''));
        if (!Security::verifyRecaptcha($recaptchaToken, $ip, $config)) {
            throw new InvalidArgumentException('Spam protection verification failed. Please refresh and try again.');
        }

        // 3. Extract & Validate Fields
        $name = Security::sanitize((string)($input['name'] ?? ''), 100);
        $email = Security::validateEmail((string)($input['email'] ?? ''));
        $phone = Security::validateIndianPhone((string)($input['phone'] ?? ''));
        $location = Security::sanitize((string)($input['location'] ?? ($input['city'] ?? '')), 100);
        $currentProfile = Security::sanitize((string)($input['current_profile'] ?? ($input['profession'] ?? '')), 100);

        if (empty($name) || mb_strlen($name) < 2) {
            throw new InvalidArgumentException('Please enter your full name (at least 2 characters).');
        }

        if (empty($phone)) {
            throw new InvalidArgumentException('Please enter a valid 10-digit Indian WhatsApp mobile number starting with 6, 7, 8, or 9.');
        }

        if (empty($email)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }

        if (empty($location)) {
            throw new InvalidArgumentException('Location is mandatory. Please enter your city or location.');
        }

        if (empty($currentProfile)) {
            throw new InvalidArgumentException('Current Profile is mandatory. Please select your current profile.');
        }

        // 4. STEP 2: Generate Unique Enrollment ID (e.g. DCH-2026-000001) or reuse existing
        $providedId = trim((string)($input['enrollment_id'] ?? ($input['enrollment_uuid'] ?? '')));
        $existing = !empty($providedId) ? DatabaseManager::getEnrollment($providedId) : null;

        if ($existing) {
            $enrollmentId = $existing['enrollment_id'];
            $record = $existing;
        } else {
            $pdo = DatabaseManager::getConnection();
            $enrollmentId = Security::generateEnrollmentId($pdo);

            // 5. STEP 3: Save to MySQL immediately with status NOT PAID
            $enrollmentData = [
                'name'            => $name,
                'email'           => $email,
                'phone'           => $phone,
                'location'        => $location,
                'current_profile' => $currentProfile,
                'workshop_date'   => $config['workshop']['date'],
                'workshop_time'   => $config['workshop']['time']
            ];

            $record = DatabaseManager::createEnrollment($enrollmentData, $enrollmentId);

            // 6. STEP 4: Insert immediately into Google Sheets with status NOT PAID
            $gsConfigured = !empty($config['google_sheets']['spreadsheet_id']);
            if ($gsConfigured) {
                try {
                    $gsResult = GoogleSheets::appendEnrollment($record, $config);
                    if ($gsResult['success'] && !empty($gsResult['row_index'])) {
                        DatabaseManager::setGoogleSheetsSynced($enrollmentId, (int)$gsResult['row_index']);
                    } else {
                        // Record synchronization failure in sync queue for retry
                        DatabaseManager::addSyncTask($enrollmentId, 'INSERT', $record, $gsResult['error'] ?? 'Unknown append error');
                    }
                } catch (Throwable $e) {
                    error_log("EnrollmentService: GoogleSheets exception: " . $e->getMessage());
                    DatabaseManager::addSyncTask($enrollmentId, 'INSERT', $record, $e->getMessage());
                }
            } else {
                // Queue task so when credentials are configured, sync picks it up
                DatabaseManager::addSyncTask($enrollmentId, 'INSERT', $record, 'Google Sheets credentials not yet configured in .env');
            }
        }

        // 7. Create Razorpay Order using trusted server-side price (₹49)
        $razorpay = new RazorpayClient($config['razorpay']['key_id'], $config['razorpay']['key_secret']);
        $amountPaise = (int)$config['workshop']['amount_in_paise'];
        $currency = (string)$config['workshop']['currency'];

        $notes = [
            'enrollment_id' => $enrollmentId,
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone,
            'workshop'      => $config['workshop']['title']
        ];

        $order = $razorpay->createOrder($amountPaise, $currency, $enrollmentId, $notes);
        $orderId = (string)$order['id'];

        // Associate order ID in database
        DatabaseManager::setOrderId($enrollmentId, $orderId);

        DatabaseManager::logEvent($orderId, 'ENROLLMENT_CREATED', [
            'enrollment_id' => $enrollmentId,
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone,
            'amount_paise'  => $amountPaise
        ], $enrollmentId);

        return [
            'success'            => true,
            'enrollment_id'      => $enrollmentId,
            'order_id'           => $orderId,
            'key_id'             => $config['razorpay']['key_id'],
            'amount'             => $amountPaise,
            'currency'           => $currency,
            'verification_token' => $record['verification_token'],
            'user'               => [
                'name'     => $name,
                'email'    => $email,
                'phone'    => $phone,
                'location' => $location
            ],
            'workshop'           => [
                'title' => $config['workshop']['title'],
                'date'  => $config['workshop']['date'],
                'time'  => $config['workshop']['time']
            ],
            'is_mock'            => !empty($order['is_mock'])
        ];
    }

    /**
     * Handle payment verification from frontend checkout callback
     */
    public static function processPaymentVerification(array $input, array $config): array {
        $orderId = trim((string)($input['razorpay_order_id'] ?? ($input['order_id'] ?? '')));
        $paymentId = trim((string)($input['razorpay_payment_id'] ?? ($input['payment_id'] ?? '')));
        $signature = trim((string)($input['razorpay_signature'] ?? ($input['signature'] ?? '')));
        $enrollmentId = trim((string)($input['enrollment_id'] ?? ($input['enrollment_uuid'] ?? '')));

        if (empty($orderId) || empty($paymentId) || empty($signature)) {
            throw new InvalidArgumentException('Missing required payment parameters.');
        }

        // If enrollment ID was not passed, locate by order ID
        if (empty($enrollmentId)) {
            $recByOrder = DatabaseManager::getEnrollmentByOrderId($orderId);
            if ($recByOrder) {
                $enrollmentId = $recByOrder['enrollment_id'];
            }
        }

        if (empty($enrollmentId)) {
            throw new InvalidArgumentException('Unable to locate enrollment record for order.');
        }

        // 1. Verify Razorpay Signature server-side using HMAC-SHA256
        $razorpay = new RazorpayClient($config['razorpay']['key_id'], $config['razorpay']['key_secret']);
        $isValidSignature = $razorpay->verifyPaymentSignature($orderId, $paymentId, $signature);

        if (!$isValidSignature) {
            DatabaseManager::logEvent($orderId, 'PAYMENT_SIGNATURE_MISMATCH', [
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

        return self::completePayment($enrollmentId, $paymentId, $orderId, $signature, $config);
    }

    /**
     * Complete verified payment (common for both direct API verification and Webhook)
     * Idempotently updates DB, Google Sheets, generates PDF, sends dual emails.
     */
    public static function completePayment(string $enrollmentId, string $paymentId, string $orderId, string $signature, array $config): array {
        // Fetch record
        $record = DatabaseManager::getEnrollment($enrollmentId);
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
                'invoice_download_url' => "backend/api/download-invoice.php?enrollment_id=" . urlencode($enrollmentId) . "&token=" . urlencode($token)
            ];
        }

        // 1. Update Database payment status to PAID
        $price = (float)$config['workshop']['price'];
        $updatedRecord = DatabaseManager::markAsPaid($enrollmentId, $paymentId, $signature, $orderId, $price);

        if (!$updatedRecord) {
            throw new RuntimeException("Failed to update enrollment to PAID.");
        }

        DatabaseManager::logEvent($orderId, 'PAYMENT_SUCCESS', [
            'enrollment_id' => $enrollmentId,
            'payment_id'    => $paymentId,
            'amount'        => $price,
            'signature_ref' => substr($signature, 0, 10) . '...'
        ], $enrollmentId, $paymentId);

        // 2. Update Google Sheets row to PAID (Green)
        try {
            $gsRes = GoogleSheets::updatePaymentStatus($enrollmentId, $paymentId, $price, $config, (int)($record['google_sheets_row'] ?? 0));
            if (!$gsRes['success']) {
                DatabaseManager::addSyncTask($enrollmentId, 'UPDATE', [
                    'payment_id'  => $paymentId,
                    'amount_paid' => $price,
                    'status'      => 'PAID'
                ], $gsRes['error'] ?? 'Google Sheets row update failed');
            }
        } catch (Throwable $ge) {
            error_log("EnrollmentService: GoogleSheets update error: " . $ge->getMessage());
            DatabaseManager::addSyncTask($enrollmentId, 'UPDATE', [
                'payment_id'  => $paymentId,
                'amount_paid' => $price,
                'status'      => 'PAID'
            ], $ge->getMessage());
        }

        // 3. Generate Invoice PDF ONLY AFTER verified payment
        $invoicePdfPath = '';
        try {
            $invoicePdfPath = InvoiceService::generate($updatedRecord, $config);
        } catch (Throwable $pe) {
            error_log("EnrollmentService: Invoice PDF generation error: " . $pe->getMessage());
        }

        // 4. Send Student Payment Confirmation Email with PDF attached
        if (!empty($updatedRecord['email'])) {
            try {
                EmailService::sendStudentConfirmation($updatedRecord, $invoicePdfPath, $config);
            } catch (Throwable $ee) {
                error_log("EnrollmentService: Student confirmation email error: " . $ee->getMessage());
            }
        }

        // 5. Send Company Lead Notification Email
        try {
            EmailService::sendCompanyNotification($updatedRecord, $config);
        } catch (Throwable $ce) {
            error_log("EnrollmentService: Company lead notification error: " . $ce->getMessage());
        }

        $token = $updatedRecord['verification_token'];
        $redirectUrl = "thank-you.html?enrollment_id=" . urlencode($enrollmentId) . "&token=" . urlencode($token);
        $downloadUrl = "backend/api/download-invoice.php?enrollment_id=" . urlencode($enrollmentId) . "&token=" . urlencode($token);

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
    public static function processWebhook(string $payload, string $signature, array $config): array {
        $webhookSecret = $config['razorpay']['webhook_secret'] ?? '';

        if (!empty($webhookSecret)) {
            $razorpay = new RazorpayClient($config['razorpay']['key_id'], $config['razorpay']['key_secret']);
            $isValid = $razorpay->verifyWebhookSignature($payload, $signature, $webhookSecret);
            if (!$isValid) {
                DatabaseManager::logEvent(null, 'WEBHOOK_SIGNATURE_MISMATCH', [
                    'signature_received' => $signature
                ]);
                return ['success' => false, 'error' => 'Invalid webhook signature', 'code' => 400];
            }
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

        DatabaseManager::logEvent($orderId, 'WEBHOOK_' . strtoupper(str_replace('.', '_', $eventType)), $event, $enrollmentId, $paymentId);

        if (in_array($eventType, ['payment.captured', 'order.paid'], true)) {
            if (empty($enrollmentId) && !empty($orderId)) {
                $rec = DatabaseManager::getEnrollmentByOrderId($orderId);
                if ($rec) {
                    $enrollmentId = $rec['enrollment_id'];
                }
            }

            if (!empty($enrollmentId) && !empty($paymentId)) {
                $res = self::completePayment($enrollmentId, $paymentId, $orderId, 'WEBHOOK_CAPTURED', $config);
                return ['success' => true, 'event' => $eventType, 'enrollment_id' => $enrollmentId, 'details' => $res];
            }
        } elseif ($eventType === 'payment.failed') {
            DatabaseManager::logEvent($orderId, 'WEBHOOK_PAYMENT_FAILED', [
                'error_code' => $payment['error_code'] ?? null,
                'error_description' => $payment['error_description'] ?? null
            ], $enrollmentId, $paymentId);
        }

        return ['success' => true, 'event' => $eventType, 'status' => 'handled'];
    }
}
