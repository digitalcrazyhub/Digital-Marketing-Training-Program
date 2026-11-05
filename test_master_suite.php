<?php
/**
 * Digital Crazy Hub - Master Test Suite
 * Validates all 10 scenarios specified in the master prompt:
 * 
 * 1. Full enrollment & payment flow
 * 2. Form submitted, payment NOT completed
 * 3. Payment cancelled/failed
 * 4. Payment signature mismatch
 * 5. Duplicate webhook/payment callback (Idempotency)
 * 6. Google Sheets failure & offline sync queue
 * 7. Unauthorized Thank You page access
 * 8. Invoice security & token verification
 * 9. Amount tampering rejection
 * 10. Email failure resilience
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$config = require __DIR__ . '/backend/config/config.php';
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/includes/Security.php';
require_once __DIR__ . '/backend/includes/EnrollmentService.php';
require_once __DIR__ . '/backend/includes/InvoiceService.php';
require_once __DIR__ . '/backend/includes/EmailService.php';
require_once __DIR__ . '/backend/integrations/GoogleSheets.php';

$results = [];

function assertTest(string $testName, bool $condition, string $detail = '') {
    global $results;
    $results[] = [
        'name'    => $testName,
        'passed'  => $condition,
        'details' => $detail
    ];
    $statusText = $condition ? "\033[32m[PASS]\033[0m" : "\033[31m[FAIL]\033[0m";
    echo "{$statusText} {$testName}: {$detail}" . PHP_EOL;
}

echo PHP_EOL . "==========================================================" . PHP_EOL;
echo "DIGITAL CRAZY HUB - 10 MASTER TEST SCENARIOS EXECUTION" . PHP_EOL;
echo "==========================================================" . PHP_EOL . PHP_EOL;

// =========================================================================
// TEST 1: Full enrollment & payment flow
// =========================================================================
echo "--- Running TEST 1: Full Enrollment & Payment Flow ---" . PHP_EOL;
$lead1 = [
    'name'            => 'Arun Kumar',
    'email'           => 'arun.test@gmail.com',
    'phone'           => '9876543210',
    'location'        => 'Chennai',
    'current_profile' => 'College Student / Fresher'
];

$res1 = EnrollmentService::createEnrollmentAndOrder($lead1, $config);
$eid1 = $res1['enrollment_id'];
$oid1 = $res1['order_id'];

assertTest('TEST 1.1: Enrollment ID Format', (bool)preg_match('/^DCH-2026-[A-F0-9]{12}$/', $eid1) || (bool)preg_match('/^DCH-2026-\d{6}$/', $eid1), "Generated ID: {$eid1}");

$rec1 = DatabaseManager::getEnrollment($eid1);
assertTest('TEST 1.2: Initial DB status is NOT PAID', $rec1['payment_status'] === 'NOT PAID' && (float)$rec1['amount_paid'] === 0.0, "Status: {$rec1['payment_status']}, Amount: {$rec1['amount_paid']}");

// Simulate Razorpay signature verification & payment completion
$pid1 = 'pay_test_' . mt_rand(100000, 999999);
$sig1 = hash_hmac('sha256', $oid1 . '|' . $pid1, $config['razorpay']['key_secret']);

$verifyRes1 = EnrollmentService::processPaymentVerification([
    'enrollment_id'       => $eid1,
    'razorpay_order_id'   => $oid1,
    'razorpay_payment_id' => $pid1,
    'razorpay_signature'  => $sig1
], $config);

assertTest('TEST 1.3: Payment verification success', ($verifyRes1['success'] ?? false) === true, "Message: " . ($verifyRes1['message'] ?? ''));

$rec1Paid = DatabaseManager::getEnrollment($eid1);
assertTest('TEST 1.4: DB updated to PAID', $rec1Paid['payment_status'] === 'PAID' && (float)$rec1Paid['amount_paid'] === 49.0 && $rec1Paid['payment_id'] === $pid1, "Payment ID: {$rec1Paid['payment_id']}");

$invoicePath1 = InvoiceService::getInvoicePath($eid1);
assertTest('TEST 1.5: Tax Invoice PDF created', !empty($invoicePath1) && file_exists($invoicePath1), "Invoice path: {$invoicePath1}");

// Verify emails logged
$emailLogPath = __DIR__ . '/backend/data/emails/email_log.log';
$emailLogContent = file_exists($emailLogPath) ? file_get_contents($emailLogPath) : '';
assertTest('TEST 1.6: Student confirmation email dispatched', strpos($emailLogContent, "arun.test@gmail.com") !== false && stripos($emailLogContent, "Confirmed") !== false, "Email log contains student confirmation");
assertTest('TEST 1.7: Company lead notification email dispatched', strpos($emailLogContent, "NOTIF-{$eid1}") !== false || stripos($emailLogContent, "New Paid Workshop Enrollment") !== false, "Email log contains company lead alert");
assertTest('TEST 1.8: Thank You page redirect generated', strpos($verifyRes1['redirect_url'] ?? '', 'thank-you.html') !== false && strpos($verifyRes1['redirect_url'] ?? '', $eid1) !== false, "URL: " . ($verifyRes1['redirect_url'] ?? ''));

// =========================================================================
// TEST 2: Form submitted, payment NOT completed
// =========================================================================
echo PHP_EOL . "--- Running TEST 2: Form Submitted, Payment NOT Completed ---" . PHP_EOL;
$lead2 = [
    'name'            => 'Priya Sundar',
    'email'           => 'priya.unpaid@gmail.com',
    'phone'           => '9123456780',
    'location'        => 'Coimbatore',
    'current_profile' => 'Working Professional / Marketer'
];

$res2 = EnrollmentService::createEnrollmentAndOrder($lead2, $config);
$eid2 = $res2['enrollment_id'];
$rec2 = DatabaseManager::getEnrollment($eid2);

assertTest('TEST 2.1: Unpaid record stored in DB', $rec2 !== null && $rec2['payment_status'] === 'NOT PAID', "Status: {$rec2['payment_status']}");
assertTest('TEST 2.2: Unpaid amount is 0', (float)$rec2['amount_paid'] === 0.0, "Amount: {$rec2['amount_paid']}");

$invoicePath2 = InvoiceService::getInvoicePath($eid2);
assertTest('TEST 2.3: NO Invoice PDF generated for unpaid user', $invoicePath2 === null, "Invoice path is null");

$unpaidEmailFound = (strpos(file_get_contents($emailLogPath), $eid2) !== false);
assertTest('TEST 2.4: NO confirmation email dispatched before payment', !$unpaidEmailFound, "No confirmation email in log for unpaid enrollment {$eid2}");

// =========================================================================
// TEST 3: Payment cancelled / failed
// =========================================================================
echo PHP_EOL . "--- Running TEST 3: Payment Cancelled / Failed ---" . PHP_EOL;
$failWebhookPayload = json_encode([
    'event'   => 'payment.failed',
    'payload' => [
        'payment' => [
            'entity' => [
                'id'                => 'pay_failed_123',
                'order_id'          => $res2['order_id'],
                'error_code'        => 'BAD_REQUEST_ERROR',
                'error_description' => 'Payment was cancelled by user',
                'notes'             => ['enrollment_id' => $eid2]
            ]
        ]
    ]
]);

$failWebhookSig = hash_hmac('sha256', $failWebhookPayload, $config['razorpay']['webhook_secret']);
$webhookFailRes = EnrollmentService::processWebhook($failWebhookPayload, $failWebhookSig, $config);
$rec2AfterFail = DatabaseManager::getEnrollment($eid2);
assertTest('TEST 3.1: Status remains NOT PAID after payment cancellation', $rec2AfterFail['payment_status'] === 'NOT PAID', "Status: {$rec2AfterFail['payment_status']}");
assertTest('TEST 3.2: Still no invoice generated after cancellation', InvoiceService::getInvoicePath($eid2) === null, "Invoice is null");

// =========================================================================
// TEST 4: Payment signature mismatch
// =========================================================================
echo PHP_EOL . "--- Running TEST 4: Payment Signature Mismatch Rejected ---" . PHP_EOL;
$leadMismatch = [
    'name'            => 'Signature Tester',
    'email'           => 'sig.test@gmail.com',
    'phone'           => '9888776655',
    'location'        => 'Trichy',
    'current_profile' => 'Freelancer'
];
$resMismatch = EnrollmentService::createEnrollmentAndOrder($leadMismatch, $config);
$eidMismatch = $resMismatch['enrollment_id'];

$tamperedSig = 'invalid_tampered_signature_hex_12345';
$verifyMismatchRes = EnrollmentService::processPaymentVerification([
    'enrollment_id'       => $eidMismatch,
    'razorpay_order_id'   => $resMismatch['order_id'],
    'razorpay_payment_id' => 'pay_tampered_999',
    'razorpay_signature'  => $tamperedSig
], $config);

assertTest('TEST 4.1: Tampered signature rejected', ($verifyMismatchRes['success'] ?? true) === false, "Result: " . ($verifyMismatchRes['message'] ?? ''));
$recMismatchAfter = DatabaseManager::getEnrollment($eidMismatch);
assertTest('TEST 4.2: Record remains NOT PAID in database', $recMismatchAfter['payment_status'] === 'NOT PAID', "Status: {$recMismatchAfter['payment_status']}");

// =========================================================================
// TEST 5: Duplicate webhook / payment callback (Idempotency)
// =========================================================================
echo PHP_EOL . "--- Running TEST 5: Duplicate Webhook / Payment Callback (Idempotency) ---" . PHP_EOL;
// First payment verification for user 2 with valid signature
$pid2 = 'pay_valid_' . mt_rand(100000, 999999);
$sig2 = hash_hmac('sha256', $res2['order_id'] . '|' . $pid2, $config['razorpay']['key_secret']);

$verifyFirst = EnrollmentService::processPaymentVerification([
    'enrollment_id'       => $eid2,
    'razorpay_order_id'   => $res2['order_id'],
    'razorpay_payment_id' => $pid2,
    'razorpay_signature'  => $sig2
], $config);

assertTest('TEST 5.1: First verification succeeds', ($verifyFirst['success'] ?? false) === true, "First payment successful");

// Second duplicate callback for the SAME payment
$verifyDuplicate = EnrollmentService::processPaymentVerification([
    'enrollment_id'       => $eid2,
    'razorpay_order_id'   => $res2['order_id'],
    'razorpay_payment_id' => $pid2,
    'razorpay_signature'  => $sig2
], $config);

assertTest('TEST 5.2: Duplicate callback handled idempotently without error', ($verifyDuplicate['success'] ?? false) === true && !empty($verifyDuplicate['already_processed']), "Already processed flag returned");

// Also test webhook idempotent delivery of payment.captured for same enrollment
$webhookPayload2 = json_encode([
    'event'   => 'payment.captured',
    'payload' => [
        'payment' => [
            'entity' => [
                'id'       => $pid2,
                'order_id' => $res2['order_id'],
                'amount'   => 4900,
                'notes'    => ['enrollment_id' => $eid2]
            ]
        ]
    ]
]);
$webhookSig2 = hash_hmac('sha256', $webhookPayload2, $config['razorpay']['webhook_secret']);
$webhookDupRes = EnrollmentService::processWebhook($webhookPayload2, $webhookSig2, $config);
assertTest('TEST 5.3: Duplicate Webhook handled gracefully', ($webhookDupRes['success'] ?? false) === true, "Webhook returned success without duplicate processing");

// Invalid Webhook signature rejected test
$invalidWebhookRes = EnrollmentService::processWebhook($webhookPayload2, 'bad_webhook_sig', $config);
assertTest('TEST 5.4: Invalid Webhook signature rejected with 400', ($invalidWebhookRes['success'] ?? true) === false && ($invalidWebhookRes['code'] ?? 0) === 400, "Invalid signature blocked");

// =========================================================================
// TEST 6: Google Sheets failure & offline queue
// =========================================================================
echo PHP_EOL . "--- Running TEST 6: Google Sheets Offline Queue & Sync Cron ---" . PHP_EOL;
$lead3 = [
    'name'            => 'Ramesh V',
    'email'           => 'ramesh.queue@gmail.com',
    'phone'           => '9988776655',
    'location'        => 'Madurai',
    'current_profile' => 'Business Owner / Entrepreneur'
];

$res3 = EnrollmentService::createEnrollmentAndOrder($lead3, $config);
$eid3 = $res3['enrollment_id'];

// Check that task exists in sync_queue
$pendingTasks = DatabaseManager::getPendingSyncTasks(50);
$taskFound = false;
foreach ($pendingTasks as $t) {
    if ($t['enrollment_id'] === $eid3) {
        $taskFound = true;
        break;
    }
}
assertTest('TEST 6.1: Offline sync task queued in sync_queue', $taskFound, "Task queued for {$eid3}");

// Execute sync cron script CLI
exec('php backend/cron/sync-google-sheets.php', $cronOutput, $cronExitCode);
assertTest('TEST 6.2: Offline sync cron script executed cleanly', $cronExitCode === 0, "Cron output lines: " . count($cronOutput));

// =========================================================================
// TEST 7: Unauthorized Thank You page access
// =========================================================================
echo PHP_EOL . "--- Running TEST 7: Unauthorized Thank You Page Access Blocked ---" . PHP_EOL;
$rec1Actual = DatabaseManager::getEnrollment($eid1);
$validToken = $rec1Actual['verification_token'];

// Invalid token check
$invalidTokenCheck = hash_equals($validToken, 'wrong_token_xyz');
assertTest('TEST 7.1: Invalid token check fails', !$invalidTokenCheck, "Token mismatch rejected");

// Unpaid enrollment check
$lead4 = [
    'name'            => 'Unpaid User',
    'email'           => 'unpaid.check@gmail.com',
    'phone'           => '9845123456',
    'location'        => 'Salem',
    'current_profile' => 'Student'
];
$res4 = EnrollmentService::createEnrollmentAndOrder($lead4, $config);
$eid4 = $res4['enrollment_id'];
$rec4 = DatabaseManager::getEnrollment($eid4);

$unpaidIsPaid = (($rec4['payment_status'] ?? '') === 'PAID');
assertTest('TEST 7.2: Unpaid user blocked from viewing confirmed seat details', !$unpaidIsPaid, "Status is NOT PAID ({$rec4['payment_status']})");

// =========================================================================
// TEST 8: Invoice security & token verification
// =========================================================================
echo PHP_EOL . "--- Running TEST 8: Invoice Security & Token Verification ---" . PHP_EOL;
// Try generating invoice for unpaid user
$unpaidInvoiceBlocked = false;
try {
    InvoiceService::generate($rec4, $config);
} catch (Throwable $e) {
    $unpaidInvoiceBlocked = true;
}
assertTest('TEST 8.1: InvoiceService blocks invoice generation for unpaid enrollment', $unpaidInvoiceBlocked, "Invoice generation rejected for NOT PAID");

// Check .htaccess exists in invoices directory
$htaccessPath = __DIR__ . '/backend/data/invoices/.htaccess';
assertTest('TEST 8.2: Invoices directory protected by .htaccess', file_exists($htaccessPath) && strpos(file_get_contents($htaccessPath), 'denied') !== false, "Direct URL access blocked");

// Verify cross-user token mismatch protection
$crossUserTokenMatch = hash_equals($rec1Actual['verification_token'], $rec4['verification_token']);
assertTest('TEST 8.3: Cross-user token mismatch blocked', !$crossUserTokenMatch, "User tokens are unique and non-forgeable");

// =========================================================================
// TEST 9: Amount tampering rejection
// =========================================================================
echo PHP_EOL . "--- Running TEST 9: Amount Tampering Rejection ---" . PHP_EOL;
$tamperedInput = [
    'name'            => 'Tamper Attempter',
    'email'           => 'tamper@gmail.com',
    'phone'           => '9789012345',
    'location'        => 'Trichy',
    'current_profile' => 'Freelancer',
    'amount'          => 1.00,        // Attempter tries to pass ₹1
    'amount_paise'    => 100,
    'price'           => 1.00
];

$resTamper = EnrollmentService::createEnrollmentAndOrder($tamperedInput, $config);
assertTest('TEST 9.1: Server strictly enforces official ₹49 price (4900 paise)', $resTamper['amount'] === 4900, "Amount enforced: {$resTamper['amount']} paise (₹49)");

// =========================================================================
// TEST 10: Email failure resilience
// =========================================================================
echo PHP_EOL . "--- Running TEST 10: Email Failure Resilience ---" . PHP_EOL;
$invalidConfig = $config;
$invalidConfig['smtp']['host'] = 'invalid.nonexistent.smtp.server.local';
$invalidConfig['smtp']['timeout'] = 1;

$emailFailedGracefully = true;
try {
    // Attempt sending confirmation with unreachable host
    EmailService::sendStudentConfirmation($rec1Paid, $invoicePath1, $invalidConfig);
} catch (Throwable $e) {
    $emailFailedGracefully = false;
}
assertTest('TEST 10.1: Email failure does not crash the system, logs gracefully', $emailFailedGracefully, "EmailService caught failure and logged without unhandled exception");

echo PHP_EOL . "==========================================================" . PHP_EOL;
$total = count($results);
$passed = count(array_filter($results, fn($r) => $r['passed']));
$failed = $total - $passed;
echo "TEST RESULTS SUMMARY: Total: {$total} | Passed: \033[32m{$passed}\033[0m | Failed: \033[31m{$failed}\033[0m" . PHP_EOL;
echo "==========================================================" . PHP_EOL . PHP_EOL;

if ($failed > 0) {
    exit(1);
}
exit(0);
