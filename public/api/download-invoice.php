<?php
/**
 * Digital Crazy Hub - Thin API Entry Point: Download Tax Invoice PDF
 * Method: GET
 * Query Params: enrollment_id (or uuid), token
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Controllers\EnrollmentController;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method Not Allowed', 405);
}

$identifier = trim((string)($_GET['enrollment_id'] ?? ($_GET['uuid'] ?? '')));
$token      = trim((string)($_GET['token'] ?? ''));

$controller = new EnrollmentController();
$controller->downloadInvoice($identifier, $token);

