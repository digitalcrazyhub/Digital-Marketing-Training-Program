<?php
/**
 * Digital Crazy Hub - Razorpay Webhook Handler
 * Method: POST
 * Headers: X-Razorpay-Signature
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'ignored', 'message' => 'Method Not Allowed']);
    exit;
}

try {
    $config = require __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/Razorpay.php';

    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

    $razorpay = new RazorpayClient($config['razorpay']['key_id'], $config['razorpay']['key_secret']);

    // Validate signature if webhook secret is configured
    if (!empty($config['razorpay']['webhook_secret'])) {
        $isValid = $razorpay->verifyWebhookSignature($payload, $signature, $config['razorpay']['webhook_secret']);
        if (!$isValid) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid webhook signature']);
            exit;
        }
    }

    $event = json_decode($payload, true);
    if (!is_array($event) || !isset($event['event'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid webhook JSON']);
        exit;
    }

    $eventType = $event['event'];
    $orderId = $event['payload']['payment']['entity']['order_id'] ?? ($event['payload']['order']['entity']['id'] ?? null);

    DatabaseManager::logEvent($orderId, 'WEBHOOK_' . strtoupper(str_replace('.', '_', $eventType)), $event);

    // If payment captured or order paid
    if (in_array($eventType, ['payment.captured', 'order.paid'], true)) {
        $payment = $event['payload']['payment']['entity'] ?? [];
        $paymentId = $payment['id'] ?? '';
        $orderId = $payment['order_id'] ?? $orderId;
        $notes = $payment['notes'] ?? ($event['payload']['order']['entity']['notes'] ?? []);
        $uuid = $notes['enrollment_uuid'] ?? null;

        if ($uuid && $paymentId && $orderId) {
            DatabaseManager::markAsPaid($uuid, $paymentId, 'WEBHOOK_CAPTURED', $orderId);
        }
    }

    echo json_encode(['status' => 'success', 'event' => $eventType]);
} catch (Exception $e) {
    http_response_code(500);
    error_log('webhook error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

