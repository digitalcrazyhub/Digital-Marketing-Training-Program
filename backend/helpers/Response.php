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
        // Enforce safe filename
        $safeFilename = basename($filename);
        $safeFilename = (string)preg_replace('/[^A-Za-z0-9_.-]/', '_', $safeFilename);

        // Enforce strict path traversal prevention
        $realPath = realpath($filePath);
        $storageRoot = defined('STORAGE_DIR') ? realpath(STORAGE_DIR) : realpath(dirname(__DIR__, 2) . '/backend/storage');

        if ($realPath === false || $storageRoot === false || !str_starts_with($realPath, $storageRoot)) {
            self::error('Access denied: Unauthorized file path.', 403);
        }

        if (!is_file($realPath) || !is_readable($realPath)) {
            self::error('Requested file not found.', 404);
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Content-Length: ' . filesize($realPath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($realPath);
        exit;
    }
}

