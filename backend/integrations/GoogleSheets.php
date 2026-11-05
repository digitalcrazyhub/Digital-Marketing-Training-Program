<?php
/**
 * Digital Crazy Hub - Google Sheets API Integration Service
 * Uses Google Service Account JWT authentication (RFC 7519 / RFC 7523) and Sheets API v4.
 * Zero external dependencies; uses standard PHP openssl & curl extensions.
 */

declare(strict_types=1);

class GoogleSheets {
    private static ?string $cachedToken = null;
    private static int $tokenExpiresAt = 0;

    /**
     * Get OAuth2 Access Token using Google Service Account JWT Bearer Grant
     */
    public static function getAccessToken(array $config): ?string {
        $now = time();
        if (self::$cachedToken !== null && self::$tokenExpiresAt > ($now + 60)) {
            return self::$cachedToken;
        }

        $gs = $config['google_sheets'] ?? [];
        $email = trim((string)($gs['service_account_email'] ?? ''));
        $privateKey = trim((string)($gs['service_account_pk'] ?? ''));
        $credentialsFile = (string)($gs['credentials_file'] ?? '');

        // If credentials file exists and private key is empty, read from JSON file
        if (empty($privateKey) && !empty($credentialsFile) && file_exists($credentialsFile)) {
            $jsonContent = @file_get_contents($credentialsFile);
            $parsed = json_decode((string)$jsonContent, true);
            if (is_array($parsed)) {
                $email = $parsed['client_email'] ?? $email;
                $privateKey = $parsed['private_key'] ?? $privateKey;
            }
        }

        if (empty($email) || empty($privateKey)) {
            return null;
        }

        // Fix newline characters in private key if stored as single line in .env
        $privateKey = str_replace(['\\n', "\r"], ["\n", ''], $privateKey);

        // 1. Build JWT Header & Claims
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss'   => $email,
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now
        ];

        $base64Header = self::base64UrlEncode(json_encode($header));
        $base64Claims = self::base64UrlEncode(json_encode($claims));
        $signingInput = $base64Header . '.' . $base64Claims;

        // 2. Sign with OpenSSL RS256
        $binarySignature = '';
        $keyResource = openssl_pkey_get_private($privateKey);
        if (!$keyResource) {
            error_log("GoogleSheets: Invalid Private Key provided for Service Account ($email).");
            return null;
        }

        $signed = openssl_sign($signingInput, $binarySignature, $keyResource, OPENSSL_ALGO_SHA256);
        if (!$signed) {
            error_log("GoogleSheets: Failed to sign JWT with OpenSSL.");
            return null;
        }

        $jwt = $signingInput . '.' . self::base64UrlEncode($binarySignature);

        // 3. Exchange JWT for Google OAuth2 Access Token
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || !$response) {
            error_log("GoogleSheets Token Exchange Error: " . $err);
            return null;
        }

        $data = json_decode($response, true);
        if (!empty($data['access_token'])) {
            self::$cachedToken = (string)$data['access_token'];
            self::$tokenExpiresAt = $now + (int)($data['expires_in'] ?? 3600);
            return self::$cachedToken;
        }

        error_log("GoogleSheets Token Error: " . ($data['error_description'] ?? $response));
        return null;
    }

    /**
     * Append new enrollment row to Google Sheet (Status: NOT PAID)
     */
    public static function appendEnrollment(array $enrollment, array $config): array {
        $spreadsheetId = trim((string)($config['google_sheets']['spreadsheet_id'] ?? ''));
        $sheetName = trim((string)($config['google_sheets']['sheet_name'] ?? 'Enrollments'));

        if (empty($spreadsheetId)) {
            return ['success' => false, 'error' => 'Google Spreadsheet ID not configured in .env'];
        }

        $token = self::getAccessToken($config);
        if (!$token) {
            return ['success' => false, 'error' => 'Unable to obtain Google Service Account access token. Verify credentials.'];
        }

        // Ensure headers exist on row 1
        self::ensureHeaderRow($spreadsheetId, $sheetName, $token);

        // Row values in exact specified order:
        // Enrollment ID | Name | Email | Phone Number | Location | Current Profile | Payment ID | Workshop Date | Workshop Time | Amount Paid | Payment Status
        $row = [
            $enrollment['enrollment_id'] ?? '',
            $enrollment['name'] ?? '',
            $enrollment['email'] ?? '',
            $enrollment['phone'] ?? '',
            $enrollment['location'] ?? ($enrollment['city'] ?? ''),
            $enrollment['current_profile'] ?? ($enrollment['profession'] ?? ''),
            '', // Payment ID initially empty
            $enrollment['workshop_date'] ?? $config['workshop']['date'],
            $enrollment['workshop_time'] ?? $config['workshop']['time'],
            '₹0', // Initial amount paid
            'NOT PAID' // Initial payment status
        ];

        $range = urlencode($sheetName . '!A:K');
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}:append?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS";

        $res = self::apiPost($url, ['values' => [$row]], $token);

        if ($res['status'] === 200) {
            $updatedRange = $res['data']['updates']['updatedRange'] ?? '';
            $rowIndex = null;
            if (preg_match('/!A(\d+):/', $updatedRange, $m)) {
                $rowIndex = (int)$m[1];
            }
            return [
                'success'   => true,
                'row_index' => $rowIndex,
                'range'     => $updatedRange
            ];
        }

        return [
            'success' => false,
            'error'   => $res['data']['error']['message'] ?? 'Failed to append row to Google Sheets.'
        ];
    }

    /**
     * Update existing enrollment row to PAID when payment completes
     */
    public static function updatePaymentStatus(
        string $enrollmentId,
        string $paymentId,
        float $amount,
        array $config,
        ?int $knownRowIndex = null
    ): array {
        $spreadsheetId = trim((string)($config['google_sheets']['spreadsheet_id'] ?? ''));
        $sheetName = trim((string)($config['google_sheets']['sheet_name'] ?? 'Enrollments'));

        if (empty($spreadsheetId)) {
            return ['success' => false, 'error' => 'Google Spreadsheet ID not configured'];
        }

        $token = self::getAccessToken($config);
        if (!$token) {
            return ['success' => false, 'error' => 'Unable to obtain Google access token'];
        }

        $targetRow = $knownRowIndex;

        // If known row is not passed, search column A for Enrollment ID
        if ($targetRow === null || $targetRow < 2) {
            $targetRow = self::findRowByEnrollmentId($spreadsheetId, $sheetName, $enrollmentId, $token);
        }

        if (!$targetRow) {
            return [
                'success' => false,
                'error'   => "Enrollment ID {$enrollmentId} not found in Google Sheet for update."
            ];
        }

        // Update Columns G through K:
        // Column G: Payment ID
        // Column H: Workshop Date
        // Column I: Workshop Time
        // Column J: Amount Paid
        // Column K: Payment Status
        $updateValues = [
            $paymentId,
            $config['workshop']['date'],
            $config['workshop']['time'],
            '₹' . number_format($amount, 2),
            'PAID'
        ];

        $range = urlencode("{$sheetName}!G{$targetRow}:K{$targetRow}");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}?valueInputOption=USER_ENTERED";

        $res = self::apiPut($url, ['values' => [$updateValues]], $token);

        if ($res['status'] === 200) {
            return [
                'success'   => true,
                'row_index' => $targetRow,
                'updated'   => true
            ];
        }

        return [
            'success' => false,
            'error'   => $res['data']['error']['message'] ?? 'Failed to update Google Sheet row.'
        ];
    }

    /**
     * Find row index by matching Enrollment ID in Column A
     */
    public static function findRowByEnrollmentId(string $spreadsheetId, string $sheetName, string $enrollmentId, string $token): ?int {
        $range = urlencode("{$sheetName}!A:A");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}";

        $res = self::apiGet($url, $token);
        if ($res['status'] !== 200 || empty($res['data']['values'])) {
            return null;
        }

        $values = $res['data']['values'];
        foreach ($values as $index => $row) {
            $val = trim((string)($row[0] ?? ''));
            if (strcasecmp($val, $enrollmentId) === 0) {
                return $index + 1; // 1-based row index in Sheets
            }
        }

        return null;
    }

    /**
     * Ensure Header Row exists in Google Sheet
     */
    private static function ensureHeaderRow(string $spreadsheetId, string $sheetName, string $token): void {
        $range = urlencode("{$sheetName}!A1:K1");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}";

        $res = self::apiGet($url, $token);
        if ($res['status'] === 200 && !empty($res['data']['values'][0])) {
            return; // Headers already present
        }

        $headers = [
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
        ];

        self::apiPut($url . '?valueInputOption=USER_ENTERED', ['values' => [$headers]], $token);
    }

    /**
     * HTTP GET helper
     */
    private static function apiGet(string $url, string $token): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$token}",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $status, 'data' => json_decode((string)$body, true) ?? []];
    }

    /**
     * HTTP POST helper
     */
    private static function apiPost(string $url, array $payload, string $token): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$token}",
            "Content-Type: application/json",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $status, 'data' => json_decode((string)$body, true) ?? []];
    }

    /**
     * HTTP PUT helper
     */
    private static function apiPut(string $url, array $payload, string $token): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$token}",
            "Content-Type: application/json",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $status, 'data' => json_decode((string)$body, true) ?? []];
    }

    /**
     * Base64Url encode helper for JWT
     */
    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

