<?php
/**
 * Digital Crazy Hub - Security Helper
 */

declare(strict_types=1);

namespace App\Helpers;

use PDO;
use Throwable;

class Security {
    public static function sanitize(string $input, int $maxLength = 255): string {
        $clean = strip_tags(trim($input));
        $clean = htmlspecialchars($clean, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return mb_substr($clean, 0, $maxLength, 'UTF-8');
    }

    public static function validateIndianPhone(string $phone): ?string {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($digits, '91') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = substr($digits, 1);
        }
        if (preg_match('/^[6-9]\d{9}$/', $digits)) {
            return $digits;
        }
        return null;
    }

    public static function validateEmail(string $email): ?string {
        $clean = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        if ($clean && filter_var($clean, FILTER_VALIDATE_EMAIL)) {
            return strtolower($clean);
        }
        return null;
    }

    public static function generateEnrollmentId(?PDO $pdo = null): string {
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM enrollments");
                $row = $stmt->fetch();
                $nextSeq = (int)($row['max_id'] ?? 0) + 1;
                return sprintf('DCH-2026-%06d', $nextSeq);
            } catch (Throwable $e) {
                error_log("Enrollment ID sequence query failed: " . $e->getMessage());
            }
        }
        $random = random_int(100000, 999999);
        return sprintf('DCH-2026-%06d', $random);
    }

    public static function generateToken(): string {
        return bin2hex(random_bytes(32));
    }

    public static function getClientIp(): string {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', (string)$_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '127.0.0.1';
    }

    public static function checkRateLimit(string $ip, int $maxRequests = 30, int $decaySeconds = 60): bool {
        $file = STORAGE_DIR . '/logs/rate_limits.json';
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $now = time();
        $limits = [];
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            $limits = json_decode((string)$content, true) ?: [];
        }

        $limits = array_filter($limits, function ($data) use ($now, $decaySeconds) {
            return ($now - ($data['time'] ?? 0)) < $decaySeconds;
        });

        if (isset($limits[$ip])) {
            if ($limits[$ip]['count'] >= $maxRequests) {
                return false;
            }
            $limits[$ip]['count']++;
        } else {
            $limits[$ip] = ['count' => 1, 'time' => $now];
        }

        @file_put_contents($file, json_encode($limits), LOCK_EX);
        return true;
    }
}

