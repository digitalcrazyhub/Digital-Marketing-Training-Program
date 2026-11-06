<?php
/**
 * Backend API: Download Official Tax Invoice & Admission Pass PDF
 * Endpoint: GET /backend/api/download-invoice.php (or /api/download-invoice.php)
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

$rawId = trim((string)($_GET['enrollment_id'] ?? ($_GET['uuid'] ?? '')));
$token = trim((string)($_GET['token'] ?? ''));

// Validate and sanitize identifier to prevent path traversal
if (empty($rawId) || !preg_match('/^DCH-2026-[A-Za-z0-9_-]{3,20}$/', $rawId)) {
    Response::error('Invalid or missing enrollment identifier.', 400);
}

$enrollmentId = (string)preg_replace('/[^A-Za-z0-9_-]/', '', basename($rawId));

$controller = new EnrollmentController();
$controller->downloadInvoice($enrollmentId, $token);
