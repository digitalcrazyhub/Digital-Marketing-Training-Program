<?php
/**
 * Digital Crazy Hub - Google reCAPTCHA v3 Verification Service
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\AppConfig;
use Throwable;

class RecaptchaService {
    public static function verify(string $token, string $ip = ''): bool {
        $config = AppConfig::get();
        $secret = (string)($config['recaptcha']['secret_key'] ?? '');

        // If secret key is not configured, permit submission
        if (empty($secret)) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        try {
            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $data = [
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $ip
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            if (!$response) {
                return false;
            }

            $json = json_decode((string)$response, true);
            return (bool)($json['success'] ?? false) && ((float)($json['score'] ?? 0.0) >= 0.3);
        } catch (Throwable $e) {
            error_log("RecaptchaService: verification error: " . $e->getMessage());
            return true; // Soft fail on network error
        }
    }
}

