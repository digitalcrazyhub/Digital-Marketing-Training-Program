<?php
/**
 * Digital Crazy Hub - Google Sheets Offline Synchronization Cron Job
 * 
 * Usage:
 *   CLI: php backend/cron/sync-google-sheets.php
 *   Web/Cron: curl "https://example.com/backend/cron/sync-google-sheets.php?secret=CRON_SECRET"
 * 
 * Processes failed or queued Google Sheets sync operations idempotently without duplicating rows.
 */

declare(strict_types=1);

// Prevent unauthorized browser execution if secret is set
$isCli = (php_sapi_name() === 'cli');

$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../integrations/GoogleSheets.php';

$cronSecret = getEnvVar('CRON_SECRET', 'dch_cron_sync_secure_key_2026');

if (!$isCli) {
    header('Content-Type: application/json; charset=UTF-8');
    $providedKey = $_GET['secret'] ?? $_GET['key'] ?? '';
    if (empty($providedKey) || !hash_equals($cronSecret, (string)$providedKey)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied: Invalid cron secret key.']);
        exit;
    }
}

$startTime = microtime(true);
$tasks = DatabaseManager::getPendingSyncTasks(50);
$total = count($tasks);
$synced = 0;
$failed = 0;
$logEntries = [];

$log = function(string $msg) use ($isCli, &$logEntries) {
    $formatted = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    $logEntries[] = $formatted;
    if ($isCli) {
        echo $formatted . PHP_EOL;
    }
};

$log("Starting Google Sheets sync runner. Found {$total} pending tasks.");

foreach ($tasks as $task) {
    $taskId       = $task['id'];
    $enrollmentId = $task['enrollment_id'];
    $action       = strtoupper($task['action']);
    $payload      = is_array($task['payload']) ? $task['payload'] : (json_decode((string)$task['payload'], true) ?: []);

    $enrollment = DatabaseManager::getEnrollment($enrollmentId);
    if (!$enrollment) {
        $log("Task #{$taskId} skipped: Enrollment {$enrollmentId} not found in database.");
        DatabaseManager::markSyncFailure($taskId, "Enrollment {$enrollmentId} not found in DB");
        $failed++;
        continue;
    }

    try {
        if ($action === 'INSERT') {
            // First check if already present in Google Sheets to prevent duplicate rows
            $existing = GoogleSheets::findRowByEnrollmentId($enrollmentId, $config);
            if ($existing['found']) {
                $log("Task #{$taskId}: {$enrollmentId} already exists at row {$existing['row_index']}. Updating status instead.");
                DatabaseManager::setGoogleSheetsSynced($enrollmentId, $existing['row_index']);
                DatabaseManager::markSyncSuccess($taskId, $existing['row_index']);
                $synced++;
                continue;
            }

            $res = GoogleSheets::appendEnrollment($enrollment, $config);
            if ($res['success']) {
                $rowIndex = (int)($res['row_index'] ?? 0);
                DatabaseManager::setGoogleSheetsSynced($enrollmentId, $rowIndex);
                DatabaseManager::markSyncSuccess($taskId, $rowIndex);
                $log("Task #{$taskId}: Successfully inserted {$enrollmentId} into Google Sheets.");
                $synced++;
            } else {
                $errMsg = $res['error'] ?? 'Append failed';
                DatabaseManager::markSyncFailure($taskId, $errMsg);
                $log("Task #{$taskId} failed for {$enrollmentId}: {$errMsg}");
                $failed++;
            }
        } elseif ($action === 'UPDATE') {
            $paymentId  = (string)($payload['payment_id'] ?? ($enrollment['payment_id'] ?? 'PAID'));
            $amountPaid = (float)($payload['amount_paid'] ?? ($enrollment['amount_paid'] ?? 49.00));
            $rowIndex   = (int)($enrollment['google_sheets_row'] ?? 0);

            $res = GoogleSheets::updatePaymentStatus($enrollmentId, $paymentId, $amountPaid, $config, $rowIndex);
            if ($res['success']) {
                DatabaseManager::markSyncSuccess($taskId);
                $log("Task #{$taskId}: Successfully updated payment status for {$enrollmentId}.");
                $synced++;
            } else {
                $errMsg = $res['error'] ?? 'Update failed';
                DatabaseManager::markSyncFailure($taskId, $errMsg);
                $log("Task #{$taskId} update failed for {$enrollmentId}: {$errMsg}");
                $failed++;
            }
        } else {
            $log("Task #{$taskId}: Unknown action {$action}");
            DatabaseManager::markSyncFailure($taskId, "Unknown action: {$action}");
            $failed++;
        }
    } catch (Throwable $e) {
        $errMsg = $e->getMessage();
        DatabaseManager::markSyncFailure($taskId, $errMsg);
        $log("Task #{$taskId} exception for {$enrollmentId}: {$errMsg}");
        $failed++;
    }
}

$elapsed = round(microtime(true) - $startTime, 2);
$summary = "Completed sync run in {$elapsed}s. Total: {$total}, Synced: {$synced}, Failed: {$failed}.";
$log($summary);

// Write to sync log
$logDir = __DIR__ . '/../data/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
@file_put_contents($logDir . '/sheets_sync.log', implode(PHP_EOL, $logEntries) . PHP_EOL, FILE_APPEND | LOCK_EX);

if (!$isCli) {
    echo json_encode([
        'success'   => true,
        'total'     => $total,
        'synced'    => $synced,
        'failed'    => $failed,
        'elapsed_s' => $elapsed,
        'messages'  => $logEntries
    ], JSON_PRETTY_PRINT);
}

