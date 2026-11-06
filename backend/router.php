<?php
/**
 * Digital Crazy Hub - Centralized Backend Router
 * Dispatches API requests to their respective backend handlers.
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Helpers\Response;
use App\Middleware\SecurityMiddleware;

// Apply middleware
SecurityMiddleware::handle();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$route = trim((string)($_GET['route'] ?? ''), '/');

// If route is not passed via query param, extract relative to /api/
if (empty($route)) {
    if (preg_match('#(?:/public)?/api(?:/index\.php)?/(.*)$#i', $uri, $matches)) {
        $route = trim($matches[1], '/');
    } elseif (preg_match('#(?:/public)?/api/(submit-enrollment|create-enrollment|create-order|verify-payment|payment-status|verify-status|webhook|download-invoice)(?:\.php)?#i', $uri, $matches)) {
        $route = trim($matches[1], '/');
    }
}

// Strip .php extension if present in route
$route = preg_replace('/\.php$/i', '', $route);

switch ($route) {
    case 'submit-enrollment':
    case 'create-enrollment':
    case 'enrollment/create':
        require __DIR__ . '/api/submit-enrollment.php';
        break;

    case 'create-order':
    case 'payment/create-order':
        require __DIR__ . '/api/create-order.php';
        break;

    case 'verify-payment':
    case 'payment/verify':
        require __DIR__ . '/api/verify-payment.php';
        break;

    case 'webhook':
    case 'webhook/razorpay':
        require __DIR__ . '/api/webhook.php';
        break;

    case 'payment-status':
    case 'verify-status':
    case 'status/verify':
        require __DIR__ . '/api/payment-status.php';
        break;

    case 'download-invoice':
    case 'status/download-invoice':
        require __DIR__ . '/api/download-invoice.php';
        break;

    default:
        Response::error('API Endpoint Not Found: ' . htmlspecialchars($route), 404);
        break;
}
