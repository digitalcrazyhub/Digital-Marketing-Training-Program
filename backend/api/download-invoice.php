<?php
/**
 * Digital Crazy Hub - Download Invoice PDF API
 * Method: GET
 * Params: enrollment_id (or uuid), token
 * Security: Verified token check, payment_status === 'PAID' check. Direct file access is blocked by .htaccess.
 */

declare(strict_types=1);

try {
    $config = require __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/InvoiceService.php';

    $identifier = trim((string)($_GET['enrollment_id'] ?? $_GET['uuid'] ?? ''));
    $token      = trim((string)($_GET['token'] ?? ''));

    if (empty($identifier) || empty($token)) {
        http_response_code(400);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'error' => 'Missing enrollment identifier or security token.']);
        exit;
    }

    $record = DatabaseManager::getEnrollment($identifier);

    if (!$record) {
        http_response_code(404);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'error' => 'Enrollment record not found.']);
        exit;
    }

    $status = strtoupper((string)($record['payment_status'] ?? ($record['status'] ?? 'NOT PAID')));

    // Enforce paid status
    if ($status !== 'PAID') {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'error' => 'Access denied: Invoice is only available after verified payment.']);
        exit;
    }

    // Enforce token security
    $expectedToken = (string)($record['verification_token'] ?? '');
    if (empty($expectedToken) || !hash_equals($expectedToken, $token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'error' => 'Invalid or expired download token.']);
        exit;
    }

    $enrollmentId = (string)($record['enrollment_id'] ?? ($record['enrollment_uuid'] ?? $identifier));
    $invoiceFile = InvoiceService::getInvoicePath($enrollmentId);

    // If file doesn't exist yet, generate it on demand
    if (!$invoiceFile || !file_exists($invoiceFile)) {
        $invoiceFile = InvoiceService::generate($record, $config);
    }

    if (!file_exists($invoiceFile)) {
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'error' => 'Unable to generate invoice file.']);
        exit;
    }

    // Clean any prior output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="DigitalCrazyHub_Invoice_' . preg_replace('/[^A-Za-z0-9_-]/', '', $enrollmentId) . '.pdf"');
    header('Content-Length: ' . filesize($invoiceFile));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    readfile($invoiceFile);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
}
