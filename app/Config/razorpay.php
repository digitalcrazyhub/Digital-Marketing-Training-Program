<?php
/**
 * Digital Crazy Hub - Razorpay Configuration
 */

declare(strict_types=1);

namespace App\Config;

class RazorpayConfig {
    public static function get(): array {
        return [
            'key_id'         => (string)env('RAZORPAY_KEY_ID', 'rzp_test_placeholder_key'),
            'key_secret'     => (string)env('RAZORPAY_KEY_SECRET', 'dch_test_secret_replace_in_production'),
            'webhook_secret' => (string)env('RAZORPAY_WEBHOOK_SECRET', 'dch_webhook_secret_replace_in_production')
        ];
    }
}

