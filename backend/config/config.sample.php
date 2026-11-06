<?php
/**
 * Digital Crazy Hub - Sample Configuration File
 * Use this as reference when setting up environment variables or custom overrides.
 */

return [
    'app' => [
        'env'   => 'production',
        'debug' => false,
        'url'   => 'https://training.digitalcrazyhub.com',
    ],

    'workshop' => [
        'title'           => '₹49 AI-Integrated Digital Marketing Paid Workshop',
        'date'            => 'November 24, 2026',
        'day'             => 'Tuesday',
        'time'            => '7:00 PM IST',
        'price'           => 49.00,
        'amount_in_paise' => 4900,
        'currency'        => 'INR',
        'platform'        => 'Live on Zoom',
        'language'        => 'Tamil',
        'seats_total'     => 100,
    ],

    'database' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'database' => 'dch_workshop',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    'razorpay' => [
        'key_id'         => 'rzp_test_placeholder_key',
        'key_secret'     => 'dch_test_secret_replace_in_production',
        'webhook_secret' => 'dch_webhook_secret_replace_in_production',
    ],

    'google_sheets' => [
        'spreadsheet_id'        => '',
        'sheet_name'            => 'Enrollments',
        'service_account_email' => '',
        'private_key'           => '',
    ],

    'smtp' => [
        'host'                       => 'smtp.hostinger.com',
        'port'                       => 465,
        'secure'                     => 'ssl',
        'user'                       => 'info@digitalcrazyhub.com',
        'pass'                       => '',
        'from_email'                 => 'info@digitalcrazyhub.com',
        'from_name'                  => 'Digital Crazy Hub',
        'company_notification_email' => 'info@digitalcrazyhub.com',
    ],

    'recaptcha' => [
        'site_key'   => '',
        'secret_key' => '',
    ],
];

