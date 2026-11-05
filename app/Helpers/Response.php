<?php
/**
 * Digital Crazy Hub - Response Helper
 */

declare(strict_types=1);

namespace App\Helpers;

class Response {
    public static function json(array $data, int $statusCode = 200): void {
        while (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');

        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error(string $message, int $statusCode = 400, array $extra = []): void {
        $payload = array_merge([
            'success' => false,
            'message' => $message,
            'error'   => $message
        ], $extra);

        self::json($payload, $statusCode);
    }

    public static function streamFile(string $filePath, string $filename, string $contentType = 'application/pdf'): void {
        if (!file_exists($filePath)) {
            self::error('Requested file not found.', 404);
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($filePath);
        exit;
    }
}

