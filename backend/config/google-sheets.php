<?php
/**
 * Digital Crazy Hub - Dedicated Google Sheets Configuration
 */

declare(strict_types=1);

namespace App\Config;

class GoogleSheetsConfig {
    public static function get(): array {
        return [
            'spreadsheet_id'        => (string)env('GOOGLE_SHEETS_SPREADSHEET_ID', ''),
            'sheet_name'            => (string)env('GOOGLE_SHEETS_SHEET_NAME', 'Enrollments'),
            'service_account_email' => (string)env('GOOGLE_SERVICE_ACCOUNT_EMAIL', ''),
            'private_key'           => (string)env('GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY', ''),
            'columns'               => [
                'A' => 'Enrollment ID',
                'B' => 'Name',
                'C' => 'Email',
                'D' => 'Phone Number',
                'E' => 'Location',
                'F' => 'Current Profile',
                'G' => 'Payment ID',
                'H' => 'Workshop Date',
                'I' => 'Workshop Time',
                'J' => 'Amount Paid',
                'K' => 'Payment Status'
            ],
            'status_colors'         => [
                'NOT PAID' => [
                    'bg_red'   => 0.97,
                    'bg_green' => 0.44,
                    'bg_blue'  => 0.44,
                    'fg_red'   => 1.0,
                    'fg_green' => 1.0,
                    'fg_blue'  => 1.0
                ],
                'PAID' => [
                    'bg_red'   => 0.20,
                    'bg_green' => 0.83,
                    'bg_blue'  => 0.60,
                    'fg_red'   => 1.0,
                    'fg_green' => 1.0,
                    'fg_blue'  => 1.0
                ]
            ]
        ];
    }
}
