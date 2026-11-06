<?php
/**
 * Digital Crazy Hub - Dedicated Google Sheets Configuration
 * Exact 10-Column Architecture (Columns A through J)
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
            
            // Exact 10 Columns (A to J) matching master prompt specifications
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
                'J' => 'Amount Paid Status'
            ],
            
            // Conditional formatting colors for Column J
            // PAID: Light Green background (#D4EDDA), Dark Green bold text (#155724)
            // UNPAID: Light Red background (#F8D7DA), Dark Red bold text (#721C24)
            'status_colors'         => [
                'PAID' => [
                    'bg_red'   => 0.831, // #D4
                    'bg_green' => 0.929, // #ED
                    'bg_blue'  => 0.855, // #DA
                    'fg_red'   => 0.082, // #15
                    'fg_green' => 0.341, // #57
                    'fg_blue'  => 0.141, // #24
                    'bold'     => true
                ],
                'UNPAID' => [
                    'bg_red'   => 0.973, // #F8
                    'bg_green' => 0.843, // #D7
                    'bg_blue'  => 0.855, // #DA
                    'fg_red'   => 0.447, // #72
                    'fg_green' => 0.110, // #1C
                    'fg_blue'  => 0.141, // #24
                    'bold'     => true
                ]
            ]
        ];
    }
}
