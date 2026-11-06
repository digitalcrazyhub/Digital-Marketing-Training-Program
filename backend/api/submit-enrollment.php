<?php
/**
 * Backend API: Submit Enrollment & Create Order
 * Endpoint: POST /backend/api/submit-enrollment.php (or /api/submit-enrollment.php)
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Controllers\EnrollmentController;
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

$controller = new EnrollmentController();
$controller->createEnrollment($input);

