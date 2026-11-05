<?php
/**
 * Digital Crazy Hub - Razorpay Webhook Handler
 * Method: POST
 * Headers: X-Razorpay-Signature
 * Security: Validates HMAC-SHA256 signature using RAZORPAY_WEBHOOK_SECRET.
 * Idempotently captures payments and synchronizes MySQL, Google Sheets, Invoice & Emails.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

try {
    $config = require __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/EnrollmentService.php';

    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

    if (empty($payload)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Empty webhook payload.']);
        exit;
    }

    $result = EnrollmentService::processWebhook($payload, $signature, $config);

    if (!($result['success'] ?? false)) {
        http_response_code($result['code'] ?? 400);
        echo json_encode($result);
        exit;
    }

    http_response_code(200);
    echo json_encode($result);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    error_log('Webhook exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal webhook server error']);
    exit;
}
