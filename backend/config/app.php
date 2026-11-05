<?php
/**
 * Digital Crazy Hub - Application & Workshop Configuration
 */

declare(strict_types=1);

namespace App\Config;

class AppConfig {
    public static function get(): array {
        return [
            'env'         => (string)env('APP_ENV', 'production'),
            'debug'       => (bool)env('APP_DEBUG', false),
            'url'         => (string)env('APP_URL', 'https://training.digitalcrazyhub.com'),
            'api_base'    => '/api',
            'support_email' => (string)env('COMPANY_NOTIFICATION_EMAIL', 'info@digitalcrazyhub.com'),
            'whatsapp_community_url' => (string)env('WHATSAPP_COMMUNITY_URL', 'https://chat.whatsapp.com/digitalcrazyhub-dm-workshop'),

            'workshop' => [
                'title'           => (string)env('WORKSHOP_TITLE', '₹49 AI-Integrated Digital Marketing Paid Workshop'),
                'headline'        => '49% of Businesses Need Digital Marketing to Grow.',
                'selling_point'   => '100% PRACTICAL DIGITAL MARKETING TRAINING',
                'date'            => (string)env('WORKSHOP_DATE', 'November 5, 2026'),
                'day'             => (string)env('WORKSHOP_DAY', 'Thursday'),
                'time'            => (string)env('WORKSHOP_TIME', '7:00 PM IST'),
                'duration'        => '3 Hours',
                'price'           => (float)env('WORKSHOP_PRICE', 49.00),
                'amount_in_paise' => (int)env('WORKSHOP_AMOUNT_PAISE', 4900),
                'currency'        => (string)env('WORKSHOP_CURRENCY', 'INR'),
                'platform'        => (string)env('WORKSHOP_PLATFORM', 'Live on Zoom'),
                'language'        => (string)env('WORKSHOP_LANGUAGE', 'Tamil'),
                'seats_total'     => 100
            ],

            'recaptcha' => [
                'site_key'   => (string)env('RECAPTCHA_SITE_KEY', ''),
                'secret_key' => (string)env('RECAPTCHA_SECRET_KEY', '')
            ],

            'cron' => [
                'secret' => (string)env('CRON_SECRET', 'dch_cron_sync_secure_key_2026')
            ]
        ];
    }
}
