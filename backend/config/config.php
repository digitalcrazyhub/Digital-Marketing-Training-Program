<?php
/**
 * Digital Crazy Hub - Configuration Loader
 * Loads settings from .env file or environment variables with fallbacks.
 */

// Enable strict error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Helper to parse .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            // Strip surrounding quotes
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// Load .env from project root
loadEnv(__DIR__ . '/../../.env');

function getEnvVar($key, $default = null) {
    $val = getenv($key);
    if ($val === false) {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
    return $val;
}

// Central Configuration Dictionary
return [
    'app_env' => getEnvVar('APP_ENV', 'development'),
    'app_url' => getEnvVar('APP_URL', 'http://localhost/training-digitalcrazyhub'),
    
    // Razorpay Credentials
    'razorpay' => [
        'key_id'         => getEnvVar('RAZORPAY_KEY_ID', 'rzp_test_placeholder_key'),
        'key_secret'     => getEnvVar('RAZORPAY_KEY_SECRET', 'placeholder_secret_key'),
        'webhook_secret' => getEnvVar('RAZORPAY_WEBHOOK_SECRET', 'placeholder_webhook_secret'),
    ],

    // Workshop Pricing (₹49 only)
    'workshop' => [
        'price_inr'      => 49,
        'amount_paise'   => 4900,
        'currency'       => 'INR',
        'date'           => getEnvVar('WORKSHOP_DATE', 'November 5, 2026'),
        'day'            => getEnvVar('WORKSHOP_DAY', 'Thursday'),
        'time'           => getEnvVar('WORKSHOP_TIME', '7:00 PM IST'),
        'language'       => getEnvVar('WORKSHOP_LANGUAGE', 'Tamil'),
        'platform'       => getEnvVar('WORKSHOP_PLATFORM', 'Live on Zoom'),
        'timezone'       => getEnvVar('WORKSHOP_TIMEZONE', 'Asia/Kolkata'),
    ],

    // Community Invite URL
    'whatsapp_community_url' => getEnvVar('WHATSAPP_COMMUNITY_URL', 'https://chat.whatsapp.com/digitalcrazyhub-dm-workshop'),

    // Database Credentials
    'db' => [
        'host' => getEnvVar('DB_HOST', '127.0.0.1'),
        'port' => getEnvVar('DB_PORT', '3306'),
        'name' => getEnvVar('DB_NAME', 'dch_workshop'),
        'user' => getEnvVar('DB_USER', 'root'),
        'pass' => getEnvVar('DB_PASS', ''),
    ]
];

