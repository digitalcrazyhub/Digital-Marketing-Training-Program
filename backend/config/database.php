<?php
/**
 * Digital Crazy Hub - Database Connection & Storage Manager
 * Uses PDO MySQL with prepared statements.
 * Includes automatic JSON storage fallback for local development or zero-setup environments.
 */

class DatabaseManager {
    private static ?PDO $pdo = null;
    private static bool $fallbackActive = false;
    private static string $fallbackFile = __DIR__ . '/../data/enrollments.json';

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
            } catch (Exception $e) {
                // If database connection fails (e.g. database not imported yet), enable resilient JSON store fallback
                self::$fallbackActive = true;
                error_log("DatabaseManager: MySQL unavailable (" . $e->getMessage() . "). Falling back to resilient JSON storage.");
                return null;
            }
        }

        return self::$pdo;
    }

    // Save pending enrollment
    public static function createEnrollment(array $data): string {
        $pdo = self::getConnection();

        $uuid = 'DCH-' . strtoupper(bin2hex(random_bytes(6)));
        $now = date('Y-m-d H:i:s');

        if ($pdo) {
            $sql = "INSERT INTO enrollments (enrollment_uuid, name, phone, email, city, profession, amount, currency, status, razorpay_order_id, created_at)
                    VALUES (:uuid, :name, :phone, :email, :city, :profession, :amount, :currency, 'PENDING', :order_id, :created_at)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':uuid'       => $uuid,
                ':name'       => $data['name'],
                ':phone'      => $data['phone'],
                ':email'      => $data['email'],
                ':city'       => $data['city'] ?? '',
                ':profession' => $data['profession'] ?? '',
                ':amount'     => $data['amount'] ?? 49.00,
                ':currency'   => $data['currency'] ?? 'INR',
                ':order_id'   => $data['razorpay_order_id'] ?? null,
                ':created_at' => $now,
            ]);
        } else {
            // File-based fallback
            $records = self::readJsonFallback();
            $records[$uuid] = [
                'enrollment_uuid'   => $uuid,
                'name'              => $data['name'],
                'phone'             => $data['phone'],
                'email'             => $data['email'],
                'city'              => $data['city'] ?? '',
                'profession'        => $data['profession'] ?? '',
                'amount'            => $data['amount'] ?? 49.00,
                'currency'          => $data['currency'] ?? 'INR',
                'status'            => 'PENDING',
                'razorpay_order_id' => $data['razorpay_order_id'] ?? null,
                'razorpay_payment_id' => null,
                'razorpay_signature' => null,
                'verification_token' => null,
                'created_at'        => $now,
                'paid_at'           => null
            ];
            self::writeJsonFallback($records);
        }

        return $uuid;
    }

    // Update enrollment with Razorpay Order ID
    public static function setOrderId(string $uuid, string $orderId): bool {
        $pdo = self::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE enrollments SET razorpay_order_id = :order_id WHERE enrollment_uuid = :uuid");
            return $stmt->execute([':order_id' => $orderId, ':uuid' => $uuid]);
        } else {
            $records = self::readJsonFallback();
            if (isset($records[$uuid])) {
                $records[$uuid]['razorpay_order_id'] = $orderId;
                self::writeJsonFallback($records);
                return true;
            }
            return false;
        }
    }

    // Verify and update enrollment to PAID
    public static function markAsPaid(string $uuid, string $paymentId, string $signature, string $orderId): ?array {
        $pdo = self::getConnection();
        $token = bin2hex(random_bytes(16));
        $now = date('Y-m-d H:i:s');

        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE enrollment_uuid = :uuid LIMIT 1");
            $stmt->execute([':uuid' => $uuid]);
            $record = $stmt->fetch();

            if (!$record) return null;

            $update = $pdo->prepare("UPDATE enrollments SET
                status = 'PAID',
                razorpay_payment_id = :payment_id,
                razorpay_signature = :sig,
                verification_token = :token,
                paid_at = :paid_at
                WHERE enrollment_uuid = :uuid");

            $update->execute([
                ':payment_id' => $paymentId,
                ':sig'        => $signature,
                ':token'      => $token,
                ':paid_at'    => $now,
                ':uuid'       => $uuid
            ]);

            $record['verification_token'] = $token;
            $record['status'] = 'PAID';
            return $record;
        } else {
            $records = self::readJsonFallback();
            if (!isset($records[$uuid])) return null;

            $records[$uuid]['status'] = 'PAID';
            $records[$uuid]['razorpay_payment_id'] = $paymentId;
            $records[$uuid]['razorpay_signature'] = $signature;
            $records[$uuid]['verification_token'] = $token;
            $records[$uuid]['paid_at'] = $now;

            self::writeJsonFallback($records);
            return $records[$uuid];
        }
    }

    // Get enrollment by UUID
    public static function getEnrollment(string $uuid): ?array {
        $pdo = self::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE enrollment_uuid = :uuid LIMIT 1");
            $stmt->execute([':uuid' => $uuid]);
            $row = $stmt->fetch();
            return $row ?: null;
        } else {
            $records = self::readJsonFallback();
            return $records[$uuid] ?? null;
        }
    }

    // Log payment events
    public static function logEvent(?string $orderId, string $eventType, array $payload): void {
        $pdo = self::getConnection();
        $now = date('Y-m-d H:i:s');
        $json = json_encode($payload);

        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO payment_logs (order_id, event_type, payload, created_at) VALUES (:order, :type, :payload, :time)");
                $stmt->execute([
                    ':order'   => $orderId,
                    ':type'    => $eventType,
                    ':payload' => $json,
                    ':time'    => $now
                ]);
            } catch (Exception $e) {
                // Non-fatal logging
            }
        } else {
            $logFile = __DIR__ . '/../data/payment_logs.log';
            $entry = "[$now] [$eventType] [Order: $orderId] $json\n";
            @file_put_contents($logFile, $entry, FILE_APPEND);
        }
    }

    private static function readJsonFallback(): array {
        if (!file_exists(self::$fallbackFile)) {
            @mkdir(dirname(self::$fallbackFile), 0755, true);
            file_put_contents(self::$fallbackFile, json_encode([]));
            return [];
        }
        $data = file_get_contents(self::$fallbackFile);
        $decoded = json_decode($data, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function writeJsonFallback(array $records): void {
        @mkdir(dirname(self::$fallbackFile), 0755, true);
        file_put_contents(self::$fallbackFile, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}

