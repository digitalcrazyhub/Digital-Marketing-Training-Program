<?php
/**
 * Digital Crazy Hub - Security & Input Validation Helper
 * Provides server-side validation, anti-spam rate limiting, reCAPTCHA v3,
 * XSS sanitization, and cryptographic token generation.
 */

declare(strict_types=1);

class Security {
    /**
     * Clean and validate 10-digit Indian mobile number
     */
    public static function validateIndianPhone(string $rawPhone): ?string {
        $clean = preg_replace('/[^0-9]/', '', $rawPhone);
        
        // Remove country code prefix if 12 digits starting with 91
        if (strlen($clean) === 12 && str_starts_with($clean, '91')) {
            $clean = substr($clean, 2);
        } elseif (strlen($clean) === 11 && str_starts_with($clean, '0')) {
            $clean = substr($clean, 1);
        }

        // Indian mobile numbers must be 10 digits and start with 6, 7, 8, or 9
        if (preg_match('/^[6-9]\d{9}$/', $clean)) {
            return $clean;
        }

        return null;
    }

    /**
     * Validate and normalize email address
     */
    public static function validateEmail(string $rawEmail): ?string {
        $email = trim(strtolower($rawEmail));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 150) {
            return $email;
        }
        return null;
    }

    /**
     * Sanitize string against XSS and control characters
     */
    public static function sanitize(string $val, int $maxLength = 100): string {
        $clean = trim(strip_tags($val));
        // Remove invisible control characters
        $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $clean);
        return mb_substr($clean, 0, $maxLength, 'UTF-8');
    }

    /**
     * Verify Google reCAPTCHA v3 token server-side
     */
    public static function verifyRecaptcha(string $token, string $clientIp, array $config): bool {
        $recaptchaCfg = $config['recaptcha'] ?? [];
        $enabled = (bool)($recaptchaCfg['enabled'] ?? false);
        $secretKey = trim((string)($recaptchaCfg['secret_key'] ?? ''));
        $minScore = (float)($recaptchaCfg['min_score'] ?? 0.5);

        // If not enabled or no secret key configured in environment, permit in dev
        if (!$enabled || empty($secretKey)) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $postData = http_build_query([
            'secret'   => $secretKey,
            'response' => $token,
            'remoteip' => $clientIp
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr || !$response) {
            error_log("reCAPTCHA cURL Error: " . $curlErr);
            // Allow in case Google API is temporarily unreachable to not lose candidate
            return true;
        }

        $result = json_decode($response, true);
        if (!is_array($result) || !($result['success'] ?? false)) {
            return false;
        }

        $score = (float)($result['score'] ?? 0.0);
        return $score >= $minScore;
    }

    /**
     * File-based IP rate limiter to protect enrollment endpoints against spam
     */
    public static function checkRateLimit(string $clientIp, int $maxAttempts = 15, int $decaySeconds = 60): bool {
        $tmpDir = sys_get_temp_dir() . '/dch_rate_limits';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }

        $safeIp = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $clientIp);
        $file = $tmpDir . '/rate_' . $safeIp . '.json';
        $now = time();

        $data = ['count' => 0, 'first_attempt' => $now];
        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            $parsed = json_decode((string)$raw, true);
            if (is_array($parsed)) {
                $data = $parsed;
            }
        }

        // Reset if window expired
        if (($now - $data['first_attempt']) > $decaySeconds) {
            $data = ['count' => 1, 'first_attempt' => $now];
        } else {
            $data['count']++;
        }

        @file_put_contents($file, json_encode($data), LOCK_EX);

        return $data['count'] <= $maxAttempts;
    }

    /**
     * Generate unique Enrollment ID in format: DCH-2026-000001
     */
    public static function generateEnrollmentId(?PDO $pdo = null): string {
        $prefix = 'DCH-2026-';
        
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM enrollments");
                $row = $stmt->fetch();
                $nextNum = ((int)($row['max_id'] ?? 0)) + 1;
                return $prefix . str_pad((string)$nextNum, 6, '0', STR_PAD_LEFT);
            } catch (Throwable $e) {
                // Fallback to random unique sequence
            }
        }

        // Random 6-digit sequence fallback
        try {
            $randomNum = random_int(100000, 999999);
        } catch (Throwable $e) {
            $randomNum = mt_rand(100000, 999999);
        }
        return $prefix . (string)$randomNum;
    }

    /**
     * Generate secure 64-character verification token
     */
    public static function generateToken(): string {
        return bin2hex(random_bytes(32));
    }

    /**
     * Get real client IP address
     */
    public static function getClientIp(): string {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return (string)$_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return (string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    }
}

