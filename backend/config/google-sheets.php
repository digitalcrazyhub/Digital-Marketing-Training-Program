<?php
/**
 * Digital Crazy Hub - Google Sheets Configuration
 * Returns Google Sheets specific configuration and OAuth2 scopes.
 */

declare(strict_types=1);

$config = require __DIR__ . '/config.php';
$gs = $config['google_sheets'] ?? [];

return [
    'enabled'                => (bool)($gs['enabled'] ?? true),
    'spreadsheet_id'         => (string)($gs['spreadsheet_id'] ?? ''),
    'sheet_name'             => (string)($gs['sheet_name'] ?? 'Enrollments'),
    'service_account_email'  => (string)($gs['service_account_email'] ?? ''),
    'service_account_pk'     => (string)($gs['service_account_pk'] ?? ''),
    'credentials_file'       => (string)($gs['credentials_file'] ?? __DIR__ . '/google-service-account.json'),
    'oauth_token_uri'        => 'https://oauth2.googleapis.com/token',
    'sheets_api_base'        => 'https://sheets.googleapis.com/v4/spreadsheets',
    'scope'                  => 'https://www.googleapis.com/auth/spreadsheets',
    'columns'                => [
        'Enrollment ID',
        'Name',
        'Email',
        'Phone Number',
        'Location',
        'Current Profile',
        'Payment ID',
        'Workshop Date',
        'Workshop Time',
        'Amount Paid',
        'Payment Status'
    ]
];

