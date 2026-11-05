<?php
/**
 * Backend API: Razorpay Webhook Handler
 * Method: POST
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Controllers\WebhookController;
use App\Helpers\Response;
use App\Middleware\SecurityMiddleware;

SecurityMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method Not Allowed', 405);
}

$payload = (string)file_get_contents('php://input');
$signature = (string)($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '');

$controller = new WebhookController();
$controller->handleWebhook($payload, $signature);
