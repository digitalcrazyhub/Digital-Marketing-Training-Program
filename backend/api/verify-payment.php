<?php
/**
 * Digital Crazy Hub - Verify Razorpay Payment API Endpoint
 * Method: POST
 * Input: JSON { razorpay_order_id, razorpay_payment_id, razorpay_signature, enrollment_id }
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

try {
    $config = require __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/EnrollmentService.php';

    $raw = file_get_contents('php://input');
    $input = json_decode((string)$raw, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $result = EnrollmentService::processPaymentVerification($input, $config);

    if (!$result['success']) {
        http_response_code(400);
    }

    echo json_encode($result);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log("verify-payment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment verification encountered an internal error. Please contact support.']);
}
