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
    } elseif (preg_match('#(?:/public)?/api/(create-enrollment|create-order|verify-payment|verify-status|webhook|download-invoice)(?:\.php)?#i', $uri, $matches)) {
        $route = trim($matches[1], '/');
    }
}

// Strip .php extension if present in route
$route = preg_replace('/\.php$/i', '', $route);

switch ($route) {
    case 'create-enrollment':
    case 'enrollment/create':
        require __DIR__ . '/api/enrollment/create.php';
        break;

    case 'create-order':
    case 'payment/create-order':
        require __DIR__ . '/api/payment/create-order.php';
        break;

    case 'verify-payment':
    case 'payment/verify':
        require __DIR__ . '/api/payment/verify.php';
        break;

    case 'webhook':
    case 'webhook/razorpay':
        require __DIR__ . '/api/webhook/razorpay.php';
        break;

    case 'verify-status':
    case 'status/verify':
        require __DIR__ . '/api/status/verify.php';
        break;

    case 'download-invoice':
    case 'status/download-invoice':
        require __DIR__ . '/api/status/download-invoice.php';
        break;

    default:
        Response::error('API Endpoint Not Found: ' . htmlspecialchars($route), 404);
        break;
}
