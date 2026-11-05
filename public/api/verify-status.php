<?php
/**
 * Digital Crazy Hub - Thin API Entry Point: Verify Enrollment Status
 * Method: GET
 * Query Params: enrollment_id, token
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Controllers\EnrollmentController;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method Not Allowed', 405);
}

$enrollmentId = trim((string)($_GET['enrollment_id'] ?? ($_GET['uuid'] ?? '')));
$token        = trim((string)($_GET['token'] ?? ''));

$controller = new EnrollmentController();
$controller->verifyStatus($enrollmentId, $token);

