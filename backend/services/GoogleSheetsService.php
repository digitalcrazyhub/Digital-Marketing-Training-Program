<?php
/**
 * Digital Crazy Hub - Pure PHP Google Sheets API v4 Integration Service
 * Uses Google Service Account OAuth2 with native OpenSSL RS256 JWT.
 * Strict 10-Column Architecture (Columns A through J):
 *  - Col A: Enrollment ID
 *  - Col B: Name
 *  - Col C: Email
 *  - Col D: Phone Number
 *  - Col E: Location
 *  - Col F: Current Profile
 *  - Col G: Payment ID
 *  - Col H: Workshop Date
 *  - Col I: Workshop Time
 *  - Col J: Amount Paid Status
 * Zero external Composer dependencies required.
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\GoogleSheetsConfig;
use Exception;
use Throwable;

class GoogleSheetsService {
    private static ?string $cachedAccessToken = null;
    private static int $tokenExpiresAt = 0;

    /**
     * Generate OAuth2 Access Token using RS256 signed JWT
     */
    public static function getAccessToken(array $config): ?string {
        if (self::$cachedAccessToken !== null && time() < (self::$tokenExpiresAt - 60)) {
            return self::$cachedAccessToken;
        }

        $email = $config['service_account_email'] ?? '';
        $privateKey = $config['private_key'] ?? '';

        if (empty($email) || empty($privateKey)) {
            return null;
        }

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claim = [
            'iss'   => $email,
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now
        ];

        $b64Header = self::base64UrlEncode((string)json_encode($header));
        $b64Claim  = self::base64UrlEncode((string)json_encode($claim));
        $signatureInput = $b64Header . '.' . $b64Claim;

        $binarySignature = '';
        $success = openssl_sign($signatureInput, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$success) {
            error_log("GoogleSheetsService: OpenSSL signature generation failed.");
            return null;
        }

        $jwt = $signatureInput . '.' . self::base64UrlEncode($binarySignature);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$res) {
            error_log("GoogleSheetsService: OAuth token request failed (HTTP {$httpCode}): {$res}");
            return null;
        }

        $json = json_decode((string)$res, true);
        if (empty($json['access_token'])) {
            return null;
        }

        self::$cachedAccessToken = $json['access_token'];
        self::$tokenExpiresAt = $now + (int)($json['expires_in'] ?? 3600);

        return self::$cachedAccessToken;
    }

    /**
     * Append new enrollment immediately upon form submission.
     * Inserts exact 10 columns: Payment ID = blank, Amount Paid Status = UNPAID
     */
    public static function appendEnrollment(array $enrollment): array {
        $config = GoogleSheetsConfig::get();
        $spreadsheetId = $config['spreadsheet_id'];
        $sheetName = $config['sheet_name'];

        if (empty($spreadsheetId)) {
            return ['success' => false, 'error' => 'Spreadsheet ID not configured'];
        }

        $accessToken = self::getAccessToken($config);
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Unable to authenticate with Google Sheets Service Account'];
        }

        // Auto-check and initialize header row if first entry
        self::ensureHeaders($spreadsheetId, $sheetName, $accessToken);

        // Exact 10 Columns: A to J
        $rowValues = [
            $enrollment['enrollment_id'],
            $enrollment['name'],
            $enrollment['email'],
            $enrollment['phone'],
            $enrollment['location'] ?? ($enrollment['city'] ?? ''),
            $enrollment['current_profile'] ?? ($enrollment['profession'] ?? ''),
            '', // Col G: Payment ID blank
            $enrollment['workshop_date'], // Col H: Workshop Date
            $enrollment['workshop_time'], // Col I: Workshop Time
            'UNPAID' // Col J: Amount Paid Status = UNPAID
        ];

        $range = urlencode("{$sheetName}!A:J");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}:append?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['values' => [$rowValues]]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "Google Sheets API returned HTTP {$httpCode}: {$response}"];
        }

        $resJson = json_decode((string)$response, true);
        $updatedRange = $resJson['updates']['updatedRange'] ?? '';
        $rowIndex = null;
        if (preg_match('/!A(\d+):/i', $updatedRange, $matches)) {
            $rowIndex = (int)$matches[1];
        }

        return [
            'success'   => true,
            'row_index' => $rowIndex,
            'range'     => $updatedRange
        ];
    }

    /**
     * Update existing row upon payment verification:
     * Sets Column G (Payment ID) and Column J (Amount Paid Status = PAID).
     * Does NOT create duplicate rows.
     */
    public static function updatePaymentStatus(
        string $enrollmentId,
        string $paymentId,
        float $amountPaid = 49.00,
        ?int $knownRowIndex = null
    ): array {
        $config = GoogleSheetsConfig::get();
        $spreadsheetId = $config['spreadsheet_id'];
        $sheetName = $config['sheet_name'];

        if (empty($spreadsheetId)) {
            return ['success' => false, 'error' => 'Spreadsheet ID not configured'];
        }

        $accessToken = self::getAccessToken($config);
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Unable to authenticate with Google Service Account'];
        }

        $targetRow = $knownRowIndex;
        if (!$targetRow || $targetRow < 2) {
            $search = self::findRowByEnrollmentId($enrollmentId);
            if (!$search['found']) {
                return ['success' => false, 'error' => "Enrollment ID {$enrollmentId} not found in Google Sheets"];
            }
            $targetRow = $search['row_index'];
        }

        // Single batch update to modify Column G (Payment ID) and Column J (Amount Paid Status)
        $batchUrl = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values:batchUpdate";
        $batchData = [
            'valueInputOption' => 'USER_ENTERED',
            'data' => [
                [
                    'range'  => "{$sheetName}!G{$targetRow}",
                    'values' => [[$paymentId]]
                ],
                [
                    'range'  => "{$sheetName}!J{$targetRow}",
                    'values' => [['PAID']]
                ]
            ]
        ];

        $ch = curl_init($batchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($batchData));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "Google Sheets update failed (HTTP {$httpCode}): {$response}"];
        }

        return ['success' => true, 'row_index' => $targetRow, 'payment_status' => 'PAID'];
    }

    /**
     * Search Column A for Enrollment ID
     */
    public static function findRowByEnrollmentId(string $enrollmentId): array {
        $config = GoogleSheetsConfig::get();
        $spreadsheetId = $config['spreadsheet_id'];
        $sheetName = $config['sheet_name'];

        $accessToken = self::getAccessToken($config);
        if (!$accessToken) {
            return ['found' => false, 'error' => 'No access token'];
        }

        $range = urlencode("{$sheetName}!A:A");
        $values = self::getRangeValues($range, $accessToken, $spreadsheetId);

        if (empty($values)) {
            return ['found' => false];
        }

        foreach ($values as $index => $row) {
            if (isset($row[0]) && trim((string)$row[0]) === $enrollmentId) {
                return ['found' => true, 'row_index' => $index + 1];
            }
        }

        return ['found' => false];
    }

    /**
     * Ensure Header row with exact 10 columns exists
     */
    public static function ensureHeaders(string $spreadsheetId, string $sheetName, string $accessToken): void {
        $range = urlencode("{$sheetName}!A1:J1");
        $headers = self::getRangeValues($range, $accessToken, $spreadsheetId);

        if (!empty($headers) && !empty($headers[0])) {
            return; // Headers already present
        }

        $expectedHeaders = [
            'Enrollment ID',
            'Name',
            'Email',
            'Phone Number',
            'Location',
            'Current Profile',
            'Payment ID',
            'Workshop Date',
            'Workshop Time',
            'Amount Paid Status'
        ];

        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}?valueInputOption=USER_ENTERED";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['values' => [$expectedHeaders]]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_exec($ch);
        curl_close($ch);

        // Apply conditional formatting on Column J
        self::applyConditionalFormatting($spreadsheetId, $sheetName, $accessToken);
    }

    /**
     * Set conditional formatting rules for Column J (Amount Paid Status):
     * PAID: Light Green background (#D4EDDA), Dark Green bold text (#155724)
     * UNPAID: Light Red background (#F8D7DA), Dark Red bold text (#721C24)
     */
    public static function applyConditionalFormatting(string $spreadsheetId, string $sheetName, string $accessToken): void {
        $sheetId = self::getSheetIdByName($spreadsheetId, $sheetName, $accessToken);
        if ($sheetId === null) {
            return;
        }

        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}:batchUpdate";

        $requests = [
            // Rule 1: PAID -> Light Green #D4EDDA, Dark Green #155724, Bold
            [
                'addConditionalFormatRule' => [
                    'rule' => [
                        'ranges' => [
                            [
                                'sheetId'          => $sheetId,
                                'startColumnIndex' => 9, // Column J (0-indexed: 9)
                                'endColumnIndex'   => 10,
                                'startRowIndex'    => 1 // Skip header row
                            ]
                        ],
                        'booleanRule' => [
                            'condition' => [
                                'type'   => 'TEXT_EQ',
                                'values' => [['userEnteredValue' => 'PAID']]
                            ],
                            'format' => [
                                'backgroundColor' => [
                                    'red'   => 0.831,
                                    'green' => 0.929,
                                    'blue'  => 0.855
                                ],
                                'textFormat' => [
                                    'foregroundColor' => [
                                        'red'   => 0.082,
                                        'green' => 0.341,
                                        'blue'  => 0.141
                                    ],
                                    'bold' => true
                                ]
                            ]
                        ]
                    ],
                    'index' => 0
                ]
            ],
            // Rule 2: UNPAID -> Light Red #F8D7DA, Dark Red #721C24, Bold
            [
                'addConditionalFormatRule' => [
                    'rule' => [
                        'ranges' => [
                            [
                                'sheetId'          => $sheetId,
                                'startColumnIndex' => 9, // Column J
                                'endColumnIndex'   => 10,
                                'startRowIndex'    => 1
                            ]
                        ],
                        'booleanRule' => [
                            'condition' => [
                                'type'   => 'TEXT_EQ',
                                'values' => [['userEnteredValue' => 'UNPAID']]
                            ],
                            'format' => [
                                'backgroundColor' => [
                                    'red'   => 0.973,
                                    'green' => 0.843,
                                    'blue'  => 0.855
                                ],
                                'textFormat' => [
                                    'foregroundColor' => [
                                        'red'   => 0.447,
                                        'green' => 0.110,
                                        'blue'  => 0.141
                                    ],
                                    'bold' => true
                                ]
                            ]
                        ]
                    ],
                    'index' => 1
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['requests' => $requests]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Helper to fetch integer sheetId by tab title
     */
    private static function getSheetIdByName(string $spreadsheetId, string $sheetName, string $accessToken): ?int {
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}?fields=sheets.properties";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$accessToken}"]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $res = curl_exec($ch);
        curl_close($ch);

        $json = json_decode((string)$res, true);
        if (!empty($json['sheets'])) {
            foreach ($json['sheets'] as $sheet) {
                if (($sheet['properties']['title'] ?? '') === $sheetName) {
                    return (int)$sheet['properties']['sheetId'];
                }
            }
            // Default to first sheet if name matches approximately
            return (int)($json['sheets'][0]['properties']['sheetId'] ?? 0);
        }
        return 0;
    }

    private static function getRangeValues(string $urlEncodedRange, string $accessToken, string $spreadsheetId): array {
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$urlEncodedRange}";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$accessToken}"]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $res = curl_exec($ch);
        curl_close($ch);

        $json = json_decode((string)$res, true);
        return $json['values'] ?? [];
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
