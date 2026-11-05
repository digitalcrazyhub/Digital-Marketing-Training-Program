<?php
/**
 * Digital Crazy Hub - Thin API Entry Point: Razorpay Asynchronous Webhook
 * Method: POST
 * Headers: X-Razorpay-Signature
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Controllers\WebhookController;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method Not Allowed', 405);
}

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

$controller = new WebhookController();
$controller->handleWebhook($payload, $signature);

