<?php
/**
 * Backend API: Verify Razorpay Payment Signature
 * Endpoint: POST /backend/api/verify-payment.php (or /api/verify-payment.php)
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Controllers\PaymentController;
use App\Helpers\Response;
use App\Middleware\SecurityMiddleware;

SecurityMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method Not Allowed', 405);
}

$raw = file_get_contents('php://input');
$input = json_decode((string)$raw, true);
if (!is_array($input)) {
    $input = $_POST;
}

$controller = new PaymentController();
$controller->verifyPayment($input);

