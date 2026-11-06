<?php
/**
 * Backend API: Verify & Retrieve Enrollment Payment Status
 * Endpoint: GET /backend/api/payment-status.php (or /api/payment-status.php)
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Controllers\EnrollmentController;
use App\Helpers\Response;
use App\Middleware\SecurityMiddleware;

SecurityMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method Not Allowed', 405);
}

$enrollmentId = (string)($_GET['enrollment_id'] ?? ($_GET['uuid'] ?? ''));
$token        = (string)($_GET['token'] ?? '');

$controller = new EnrollmentController();
$controller->verifyStatus($enrollmentId, $token);

