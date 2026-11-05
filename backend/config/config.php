<?php
/**
 * Digital Crazy Hub - Configuration Loader
 * Loads settings from .env file or environment variables with fallbacks.
 */

// Enable strict error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Helper to parse .env file
if (!function_exists('loadEnv')) {
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
}

// Load .env from project root
loadEnv(__DIR__ . '/../../.env');

if (!function_exists('getEnvVar')) {
    function getEnvVar($key, $default = null) {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        return $val;
    }
}

// Central Configuration Dictionary
return [
    'app_env' => getEnvVar('APP_ENV', 'development'),
    'app_url' => getEnvVar('APP_URL', 'http://localhost/training-digitalcrazyhub'),
    
    // App info
    'app' => [
        'name'                    => 'Digital Crazy Hub',
        'support_email'           => getEnvVar('SUPPORT_EMAIL', 'info@digitalcrazyhub.com'),
        'whatsapp_support_number' => getEnvVar('WHATSAPP_SUPPORT_NUMBER', '919047700201'),
    ],

    // Razorpay Credentials
    'razorpay' => [
        'key_id'         => getEnvVar('RAZORPAY_KEY_ID', 'rzp_test_placeholder_key'),
        'key_secret'     => getEnvVar('RAZORPAY_KEY_SECRET', 'placeholder_secret_key'),
        'webhook_secret' => getEnvVar('RAZORPAY_WEBHOOK_SECRET', 'placeholder_webhook_secret'),
    ],

    // Workshop Pricing (₹49 only)
    'workshop' => [
        'title'           => '₹49 AI-Integrated Digital Marketing Paid Workshop',
        'price'           => 49,
        'price_inr'       => 49,
        'display_price'   => '₹49',
        'amount_paise'    => 4900,
        'amount_in_paise' => 4900,
        'currency'        => 'INR',
        'date'            => getEnvVar('WORKSHOP_DATE', 'November 5, 2026'),
        'day'             => getEnvVar('WORKSHOP_DAY', 'Thursday'),
        'time'            => getEnvVar('WORKSHOP_TIME', '7:00 PM IST'),
        'language'        => getEnvVar('WORKSHOP_LANGUAGE', 'Tamil'),
        'platform'        => getEnvVar('WORKSHOP_PLATFORM', 'Live on Zoom'),
        'timezone'        => getEnvVar('WORKSHOP_TIMEZONE', 'Asia/Kolkata'),
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
    ],

    // Google Sheets Integration
    'google_sheets' => [
        'enabled'                => (bool)getEnvVar('GOOGLE_SHEETS_ENABLED', true),
        'spreadsheet_id'         => getEnvVar('GOOGLE_SHEETS_SPREADSHEET_ID', ''),
        'sheet_name'             => getEnvVar('GOOGLE_SHEETS_SHEET_NAME', 'Enrollments'),
        'service_account_email'  => getEnvVar('GOOGLE_SERVICE_ACCOUNT_EMAIL', ''),
        'service_account_pk'     => getEnvVar('GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY', ''),
        'credentials_file'       => getEnvVar('GOOGLE_APPLICATION_CREDENTIALS', __DIR__ . '/google-service-account.json'),
    ],

    // SMTP / Email Delivery
    'smtp' => [
        'host'                       => getEnvVar('SMTP_HOST', 'smtp.hostinger.com'),
        'port'                       => (int)getEnvVar('SMTP_PORT', 465),
        'user'                       => getEnvVar('SMTP_USER', ''),
        'pass'                       => getEnvVar('SMTP_PASS', ''),
        'secure'                     => getEnvVar('SMTP_SECURE', 'ssl'), // 'ssl' or 'tls'
        'from_email'                 => getEnvVar('SMTP_FROM_EMAIL', 'info@digitalcrazyhub.com'),
        'from_name'                  => getEnvVar('SMTP_FROM_NAME', 'Digital Crazy Hub'),
        'company_notification_email' => getEnvVar('COMPANY_NOTIFICATION_EMAIL', 'info@digitalcrazyhub.com'),
    ],

    // Google reCAPTCHA v3
    'recaptcha' => [
        'enabled'    => (bool)getEnvVar('RECAPTCHA_ENABLED', false),
        'site_key'   => getEnvVar('RECAPTCHA_SITE_KEY', ''),
        'secret_key' => getEnvVar('RECAPTCHA_SECRET_KEY', ''),
        'min_score'  => (float)getEnvVar('RECAPTCHA_MIN_SCORE', 0.5),
    ]
];

