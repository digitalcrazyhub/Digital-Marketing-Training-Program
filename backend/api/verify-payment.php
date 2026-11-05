<?php
/**
 * Digital Crazy Hub - Verify Razorpay Payment API Endpoint
 * Method: POST
 * Input: JSON { razorpay_order_id, razorpay_payment_id, razorpay_signature, enrollment_uuid }
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
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/Razorpay.php';

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $orderId   = trim((string)($input['razorpay_order_id'] ?? ''));
    $paymentId = trim((string)($input['razorpay_payment_id'] ?? ''));
    $signature = trim((string)($input['razorpay_signature'] ?? ''));
    $uuid      = trim((string)($input['enrollment_uuid'] ?? ''));

    if (empty($orderId) || empty($paymentId) || empty($signature) || empty($uuid)) {
        throw new InvalidArgumentException('Missing required payment verification parameters.');
    }

    // Verify signature with Razorpay client
    $razorpay = new RazorpayClient($config['razorpay']['key_id'], $config['razorpay']['key_secret']);
    $isValid = $razorpay->verifyPaymentSignature($orderId, $paymentId, $signature);

    if (!$isValid) {
        DatabaseManager::logEvent($orderId, 'PAYMENT_SIGNATURE_MISMATCH', [
            'uuid'       => $uuid,
            'payment_id' => $paymentId,
            'signature'  => $signature
        ]);
        http_response_code(400);
        echo json_encode([
            'success'      => false,
            'message'      => 'Payment signature verification failed. If your money was deducted, our team will verify it shortly.',
            'redirect_url' => 'payment-failed.html?error=verification_failed&order=' . urlencode($orderId)
        ]);
        exit;
    }

    // Mark enrollment as PAID in database
    $record = DatabaseManager::markAsPaid($uuid, $paymentId, $signature, $orderId);

    if (!$record) {
        throw new RuntimeException('Enrollment record not found for UUID: ' . htmlspecialchars($uuid));
    }

    DatabaseManager::logEvent($orderId, 'PAYMENT_SUCCESS', [
        'uuid'       => $uuid,
        'payment_id' => $paymentId,
        'amount'     => $config['workshop']['amount_in_paise'],
        'email'      => $record['email'] ?? null,
        'phone'      => $record['phone'] ?? null
    ]);

    $redirectUrl = 'thank-you.html?uuid=' . urlencode($uuid) . '&token=' . urlencode($record['verification_token']);

    echo json_encode([
        'success'            => true,
        'message'            => 'Congratulations! Your seat for the ₹49 AI-Integrated Digital Marketing Workshop is confirmed.',
        'enrollment_uuid'    => $uuid,
        'verification_token' => $record['verification_token'],
        'redirect_url'       => $redirectUrl
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    error_log('verify-payment error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to verify payment. ' . $e->getMessage()]);
}

