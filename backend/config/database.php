<?php
/**
 * Digital Crazy Hub - Database Connection & Storage Manager
 * Uses PDO MySQL with prepared statements.
 * Includes automatic resilient storage fallback for offline / development environments.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/Security.php';

class DatabaseManager {
    private static ?PDO $pdo = null;
    private static bool $fallbackActive = false;
    private static string $fallbackFile = __DIR__ . '/../data/enrollments.json';
    private static string $syncQueueFile = __DIR__ . '/../data/sync_queue.json';

    public static function getConnection(): ?PDO {
        if (self::$fallbackActive) {
            return null;
        }

        if (self::$pdo === null) {
            $config = require __DIR__ . '/config.php';
            $db = $config['db'];

            $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";
            
            try {
                self::$pdo = new PDO($dsn, $db['user'], $db['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_TIMEOUT            => 2,
                ]);
            } catch (Throwable $e) {
                // If database connection fails, enable resilient store fallback
                self::$fallbackActive = true;
                error_log("DatabaseManager: MySQL unavailable (" . $e->getMessage() . "). Falling back to resilient storage.");
                return null;
            }
        }

        return self::$pdo;
    }

    /**
     * Create Enrollment record in database immediately on form submission (Status: NOT PAID)
     */
    public static function createEnrollment(array $data, ?string $enrollmentId = null): array {
        $pdo = self::getConnection();

        $eid = $enrollmentId ?: Security::generateEnrollmentId($pdo);
        $token = Security::generateToken();
        $now = date('Y-m-d H:i:s');

        $name = Security::sanitize($data['name'] ?? '', 100);
        $email = Security::validateEmail($data['email'] ?? '') ?? '';
        $phone = Security::validateIndianPhone($data['phone'] ?? '') ?? '';
        $location = Security::sanitize($data['location'] ?? ($data['city'] ?? ''), 100);
        $currentProfile = Security::sanitize($data['current_profile'] ?? ($data['profession'] ?? 'Working Professional'), 100);
        $workshopDate = (string)($data['workshop_date'] ?? 'November 5, 2026');
        $workshopTime = (string)($data['workshop_time'] ?? '7:00 PM IST');
        $amountPaid = 0.00;
        $paymentStatus = 'NOT PAID';
        $orderId = (string)($data['order_id'] ?? ($data['razorpay_order_id'] ?? ''));

        $record = [
            'enrollment_id'        => $eid,
            'enrollment_uuid'      => $eid, // Backward-compat alias
            'name'                 => $name,
            'email'                => $email,
            'phone'                => $phone,
            'location'             => $location,
            'city'                 => $location,
            'current_profile'      => $currentProfile,
            'profession'           => $currentProfile,
            'payment_id'           => null,
            'razorpay_payment_id'  => null,
            'workshop_date'        => $workshopDate,
            'workshop_time'        => $workshopTime,
            'amount_paid'          => $amountPaid,
            'amount'               => $amountPaid,
            'payment_status'       => $paymentStatus,
            'status'               => $paymentStatus,
            'order_id'             => $orderId ?: null,
            'razorpay_order_id'    => $orderId ?: null,
            'signature'            => null,
            'razorpay_signature'   => null,
            'verification_token'   => $token,
            'google_sheets_synced' => 0,
            'google_sheets_row'    => null,
            'created_at'           => $now,
            'updated_at'           => $now,
            'paid_at'              => null
        ];

        if ($pdo) {
            $sql = "INSERT INTO enrollments (
                        enrollment_id, name, email, phone, location, current_profile,
                        payment_id, workshop_date, workshop_time, amount_paid, payment_status,
                        order_id, signature, verification_token, google_sheets_synced, google_sheets_row,
                        created_at
                    ) VALUES (
                        :eid, :name, :email, :phone, :location, :current_profile,
                        NULL, :w_date, :w_time, :amount_paid, 'NOT PAID',
                        :order_id, NULL, :token, 0, NULL,
                        :created_at
                    )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':eid'             => $eid,
                ':name'            => $name,
                ':email'           => $email,
                ':phone'           => $phone,
                ':location'        => $location,
                ':current_profile' => $currentProfile,
                ':w_date'          => $workshopDate,
                ':w_time'          => $workshopTime,
                ':amount_paid'     => $amountPaid,
                ':order_id'        => $orderId ?: null,
                ':token'           => $token,
                ':created_at'      => $now
            ]);
            $record['id'] = (int)$pdo->lastInsertId();
        } else {
            $records = self::readJsonFallback();
            $records[$eid] = $record;
            self::writeJsonFallback($records);
        }

        return $record;
    }

    /**
     * Associate Razorpay Order ID with enrollment
     */
    public static function setOrderId(string $enrollmentId, string $orderId): bool {
        $pdo = self::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE enrollments SET order_id = :order_id WHERE enrollment_id = :eid");
            return $stmt->execute([':order_id' => $orderId, ':eid' => $enrollmentId]);
        } else {
            $records = self::readJsonFallback();
            if (isset($records[$enrollmentId])) {
                $records[$enrollmentId]['order_id'] = $orderId;
                $records[$enrollmentId]['razorpay_order_id'] = $orderId;
                self::writeJsonFallback($records);
                return true;
            }
            return false;
        }
    }

    /**
     * Mark enrollment as PAID upon verified payment
     */
    public static function markAsPaid(
        string $enrollmentId,
        string $paymentId,
        string $signature,
        string $orderId,
        float $amountPaid = 49.00
    ): ?array {
        $pdo = self::getConnection();
        $now = date('Y-m-d H:i:s');

        $record = self::getEnrollment($enrollmentId);
        if (!$record) {
            return null;
        }

        // Idempotency: if already paid, return existing verified record
        if (($record['payment_status'] ?? '') === 'PAID') {
            return $record;
        }

        $token = $record['verification_token'] ?: Security::generateToken();

        if ($pdo) {
            $sql = "UPDATE enrollments SET
                        payment_status = 'PAID',
                        payment_id = :payment_id,
                        order_id = :order_id,
                        signature = :signature,
                        amount_paid = :amount_paid,
                        verification_token = :token,
                        paid_at = :paid_at
                    WHERE enrollment_id = :eid";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':payment_id'  => $paymentId,
                ':order_id'    => $orderId,
                ':signature'   => $signature,
                ':amount_paid' => $amountPaid,
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
            $records[$enrollmentId]['status'] = 'PAID';
            $records[$enrollmentId]['payment_id'] = $paymentId;
            $records[$enrollmentId]['razorpay_payment_id'] = $paymentId;
            $records[$enrollmentId]['order_id'] = $orderId;
            $records[$enrollmentId]['razorpay_order_id'] = $orderId;
            $records[$enrollmentId]['signature'] = $signature;
            $records[$enrollmentId]['amount_paid'] = $amountPaid;
            $records[$enrollmentId]['verification_token'] = $token;
            $records[$enrollmentId]['paid_at'] = $now;
            self::writeJsonFallback($records);
        }

        return self::getEnrollment($enrollmentId);
    }

    /**
     * Update Google Sheets sync status
     */
    public static function setGoogleSheetsSynced(string $enrollmentId, int $rowIndex): void {
        $pdo = self::getConnection();
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

    /**
     * Get enrollment by Enrollment ID
     */
    public static function getEnrollment(string $enrollmentId): ?array {
        $pdo = self::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE enrollment_id = :eid LIMIT 1");
            $stmt->execute([':eid' => $enrollmentId]);
            $row = $stmt->fetch();
            if ($row) {
                // Compatibility aliases
                $row['enrollment_uuid'] = $row['enrollment_id'];
                $row['city'] = $row['location'];
                $row['profession'] = $row['current_profile'];
                $row['status'] = $row['payment_status'];
                $row['razorpay_order_id'] = $row['order_id'];
                $row['razorpay_payment_id'] = $row['payment_id'];
                $row['amount'] = $row['amount_paid'];
            }
            return $row ?: null;
        } else {
            $records = self::readJsonFallback();
            return $records[$enrollmentId] ?? null;
        }
    }

    /**
     * Get enrollment by Order ID
     */
    public static function getEnrollmentByOrderId(string $orderId): ?array {
        $pdo = self::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE order_id = :order_id LIMIT 1");
            $stmt->execute([':order_id' => $orderId]);
            $row = $stmt->fetch();
            if ($row) {
                $row['enrollment_uuid'] = $row['enrollment_id'];
                $row['city'] = $row['location'];
                $row['profession'] = $row['current_profile'];
                $row['status'] = $row['payment_status'];
                $row['razorpay_order_id'] = $row['order_id'];
                $row['razorpay_payment_id'] = $row['payment_id'];
            }
            return $row ?: null;
        } else {
            $records = self::readJsonFallback();
            foreach ($records as $r) {
                if (($r['order_id'] ?? '') === $orderId || ($r['razorpay_order_id'] ?? '') === $orderId) {
                    return $r;
                }
            }
            return null;
        }
    }

    /**
     * Log audit and payment events
     */
    public static function logEvent(
        ?string $orderId,
        string $eventType,
        array $payload,
        ?string $enrollmentId = null,
        ?string $paymentId = null
    ): void {
        $pdo = self::getConnection();
        $now = date('Y-m-d H:i:s');
        $ip = Security::getClientIp();
        $json = json_encode($payload);

        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO payment_logs (enrollment_id, order_id, payment_id, event_type, payload, ip_address, created_at)
                                       VALUES (:eid, :order, :payment, :type, :payload, :ip, :time)");
                $stmt->execute([
                    ':eid'     => $enrollmentId,
                    ':order'   => $orderId,
                    ':payment' => $paymentId,
                    ':type'    => $eventType,
                    ':payload' => $json,
                    ':ip'      => $ip,
                    ':time'    => $now
                ]);
            } catch (Throwable $e) {
                error_log("DatabaseManager: Failed to write payment_log to MySQL: " . $e->getMessage());
            }
        }

        // File-based log
        $logFile = __DIR__ . '/../data/payment_logs.log';
        $logLine = sprintf("[%s] IP: %s | EID: %s | Order: %s | Pay: %s | %s | %s\n",
            $now, $ip, $enrollmentId ?? '-', $orderId ?? '-', $paymentId ?? '-', $eventType, $json);
        @file_put_contents($logFile, $logLine, FILE_APPEND);
    }

    /**
     * Queue Google Sheets synchronization task for offline retry
     */
    public static function addSyncTask(string $enrollmentId, string $action, array $payload, ?string $error = null): void {
        $pdo = self::getConnection();
        $now = date('Y-m-d H:i:s');
        $jsonPayload = json_encode($payload);

        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO sync_queue (enrollment_id, action, payload, status, last_error, created_at)
                                       VALUES (:eid, :action, :payload, 'PENDING', :err, :now)");
                $stmt->execute([
                    ':eid'     => $enrollmentId,
                    ':action'  => $action,
                    ':payload' => $jsonPayload,
                    ':err'     => $error,
                    ':now'     => $now
                ]);
                return;
            } catch (Throwable $e) {
                error_log("DatabaseManager sync_queue DB error: " . $e->getMessage());
            }
        }

        // Resilient JSON queue
        $tasks = self::readJsonSyncQueue();
        $tasks[] = [
            'id'            => time() . '_' . mt_rand(100, 999),
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
        $pdo = self::getConnection();
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
        $pdo = self::getConnection();
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
        $pdo = self::getConnection();
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
        if (!file_exists(self::$fallbackFile)) return [];
        $content = @file_get_contents(self::$fallbackFile);
        return json_decode((string)$content, true) ?: [];
    }

    private static function writeJsonFallback(array $records): void {
        $dir = dirname(self::$fallbackFile);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents(self::$fallbackFile, json_encode($records, JSON_PRETTY_PRINT), LOCK_EX);
    }

    private static function readJsonSyncQueue(): array {
        if (!file_exists(self::$syncQueueFile)) return [];
        $content = @file_get_contents(self::$syncQueueFile);
        return json_decode((string)$content, true) ?: [];
    }

    private static function writeJsonSyncQueue(array $tasks): void {
        $dir = dirname(self::$syncQueueFile);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents(self::$syncQueueFile, json_encode($tasks, JSON_PRETTY_PRINT), LOCK_EX);
    }
}
