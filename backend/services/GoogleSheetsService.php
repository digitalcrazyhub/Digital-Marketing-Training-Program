<?php
/**
 * Digital Crazy Hub - Pure PHP Google Sheets API v4 Integration Service
 * Uses Google Service Account OAuth2 with native OpenSSL RS256 JWT.
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

        $b64Header = self::base64UrlEncode(json_encode($header));
        $b64Claim  = self::base64UrlEncode(json_encode($claim));
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

        $rowValues = [
            $enrollment['enrollment_id'],
            $enrollment['name'],
            $enrollment['email'],
            $enrollment['phone'],
            $enrollment['location'] ?? ($enrollment['city'] ?? ''),
            $enrollment['current_profile'] ?? ($enrollment['profession'] ?? ''),
            '', // Payment ID initially empty
            $enrollment['workshop_date'],
            $enrollment['workshop_time'],
            '₹0', // Initial amount 0
            'NOT PAID'
        ];

        $range = urlencode("{$sheetName}!A:K");
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

    public static function updatePaymentStatus(
        string $enrollmentId,
        string $paymentId,
        float $amountPaid,
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

        $range = urlencode("{$sheetName}!G{$targetRow}:K{$targetRow}");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}?valueInputOption=USER_ENTERED";

        $amountFormatted = '₹' . number_format($amountPaid, 2);

        $getRange = urlencode("{$sheetName}!H{$targetRow}:I{$targetRow}");
        $existingMeta = self::getRangeValues($getRange, $accessToken, $spreadsheetId);
        $workshopDate = $existingMeta[0][0] ?? 'November 5, 2026';
        $workshopTime = $existingMeta[0][1] ?? '7:00 PM IST';

        $values = [
            [
                $paymentId,
                $workshopDate,
                $workshopTime,
                $amountFormatted,
                'PAID'
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['values' => $values]));
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

