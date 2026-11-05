<?php
/**
 * Digital Crazy Hub - Mail / SMTP Configuration
 */

declare(strict_types=1);

namespace App\Config;

class MailConfig {
    public static function get(): array {
        return [
            'host'                       => (string)env('SMTP_HOST', 'smtp.hostinger.com'),
            'port'                       => (int)env('SMTP_PORT', 465),
            'secure'                     => (string)env('SMTP_SECURE', 'ssl'),
            'user'                       => (string)env('SMTP_USER', 'info@digitalcrazyhub.com'),
            'pass'                       => (string)env('SMTP_PASS', ''),
            'from_email'                 => (string)env('SMTP_FROM_EMAIL', 'info@digitalcrazyhub.com'),
            'from_name'                  => (string)env('SMTP_FROM_NAME', 'Digital Crazy Hub'),
            'company_notification_email' => (string)env('COMPANY_NOTIFICATION_EMAIL', 'info@digitalcrazyhub.com')
        ];
    }
}

