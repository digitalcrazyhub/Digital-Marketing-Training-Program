<?php
/**
 * Backend API: Download Invoice PDF
 * Method: GET
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Controllers\EnrollmentController;
use App\Helpers\Response;
use App\Middleware\SecurityMiddleware;

SecurityMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method Not Allowed', 405);
}

$identifier = trim((string)($_GET['enrollment_id'] ?? ($_GET['uuid'] ?? '')));
$token      = trim((string)($_GET['token'] ?? ''));

$controller = new EnrollmentController();
$controller->downloadInvoice($identifier, $token);
