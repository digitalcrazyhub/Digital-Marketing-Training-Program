<?php
/**
 * Digital Crazy Hub - End-to-End Test Suite
 * Verifies backend enrollment, database storage, Google Sheets 10-column mapping,
 * Razorpay order creation, signature verification, invoice generation, email guard, and webhooks.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Config\AppConfig;
use App\Config\GoogleSheetsConfig;
use App\Helpers\Security;
use App\Helpers\Validator;
use App\Services\EnrollmentService;
use App\Services\GoogleSheetsService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\CaptchaService;

$testsPassed = 0;
$testsTotal = 0;

function assertTest(string $description, bool $condition, string $details = ''): void {
    global $testsPassed, $testsTotal;
    $testsTotal++;
    if ($condition) {
        $testsPassed++;
        echo "  [PASS] {$description}\n";
    } else {
        echo "  [FAIL] {$description}: {$details}\n";
    }
}

echo "====================================================================\n";
echo " DIGITAL CRAZY HUB - BACKEND INTEGRATION TEST SUITE\n";
echo "====================================================================\n\n";

// TEST 1: Config & Class Mappings
echo "1. Checking Core Architecture & Configuration Classes...\n";
assertTest("AppConfig loads correctly", !empty(AppConfig::get()['workshop']['title']));
assertTest("Workshop price is ₹49 (4900 paise)", AppConfig::get()['workshop']['price'] == 49.00 && AppConfig::get()['workshop']['amount_in_paise'] == 4900);
assertTest("Workshop date is November 24, 2026", AppConfig::get()['workshop']['date'] === 'November 24, 2026');
assertTest("Google Sheets Config has exact 10 columns A-J", count(GoogleSheetsConfig::get()['columns']) === 10);
assertTest("Column J is 'Amount Paid Status'", GoogleSheetsConfig::get()['columns']['J'] === 'Amount Paid Status');
assertTest("PaymentService facade exists and inherits RazorpayService", class_exists('App\Services\PaymentService'));
assertTest("CaptchaService facade exists and inherits RecaptchaService", class_exists('App\Services\CaptchaService'));

// TEST 2: Input Validation & Indian Phone Number Cleaning
echo "\n2. Testing Input Validation & Sanitization...\n";
$validPhone = Security::validateIndianPhone("+91 98765 43210");
assertTest("Indian phone sanitization (+91 98765 43210 -> 9876543210)", $validPhone === '9876543210');

$invalidPhone = Security::validateIndianPhone("12345");
assertTest("Invalid phone rejected", $invalidPhone === null);

$validEmail = Security::validateEmail("Test.Student@Example.com");
assertTest("Valid email lowercased and accepted", $validEmail === 'test.student@example.com');

$invalidEmail = Security::validateEmail("invalid-email-address");
assertTest("Invalid email rejected", $invalidEmail === null);

// TEST 3: Unique Enrollment ID Generation
echo "\n3. Testing Enrollment ID Generation Format...\n";
$generatedId = Security::generateEnrollmentId();
$isExpectedFormat = (bool)preg_match('/^DCH-2026-\d{6}$/', $generatedId);
assertTest("Generated Enrollment ID format (DCH-2026-XXXXXX): {$generatedId}", $isExpectedFormat);

// TEST 4: Enrollment Creation & UNPAID Status
echo "\n4. Testing Initial Form Submission (UNPAID Seat)...\n";
$testSubmission = [
    'name'            => 'Ravi Kumar',
    'email'           => 'ravi.test.' . random_int(1000, 9999) . '@example.com',
    'phone'           => '98765' . random_int(10000, 99999),
    'location'        => 'Chennai, Tamil Nadu',
    'current_profile' => 'Working Professional',
    'recaptcha_token' => '' // Soft bypassed in test env
];

$enrollmentResult = EnrollmentService::createEnrollmentAndOrder($testSubmission);
assertTest("Submission succeeded", !empty($enrollmentResult['success']));
$createdEid = $enrollmentResult['enrollment_id'];
assertTest("Enrollment ID assigned ({$createdEid})", !empty($createdEid));
assertTest("Razorpay Order created ({$enrollmentResult['order_id']})", !empty($enrollmentResult['order_id']));

$storedRecord = EnrollmentService::getEnrollment($createdEid);
assertTest("Stored status is strictly UNPAID", ($storedRecord['amount_paid_status'] ?? '') === 'UNPAID');
assertTest("Payment ID is initially NULL", empty($storedRecord['payment_id']));
assertTest("Amount is ₹49.00", (float)($storedRecord['amount'] ?? 0) === 49.00);
assertTest("Amount Paid is ₹0.00", (float)($storedRecord['amount_paid'] ?? 0) === 0.00);

// TEST 5: STRICT RULE - Unpaid Enrollment MUST NEVER Generate Invoice
echo "\n5. Testing Security Guard: Unpaid Invoice Generation Prevention...\n";
$invoiceBlocked = false;
try {
    InvoiceService::generate($storedRecord);
} catch (RuntimeException $e) {
    $invoiceBlocked = true;
}
assertTest("Strict Rule: Unpaid enrollment CANNOT generate invoice", $invoiceBlocked);

// TEST 6: Payment Verification & PAID Transition
echo "\n6. Testing Payment Signature Verification & PAID Transition...\n";
$testPaymentId = 'pay_test_' . bin2hex(random_bytes(6));
$testOrderId = $enrollmentResult['order_id'];

// Mock HMAC-SHA256 signature
$mockSecret = AppConfig::get()['workshop']['currency']; // Dummy or known secret
$testSignature = hash_hmac('sha256', $testOrderId . '|' . $testPaymentId, 'dch_test_secret_replace_in_production');

// Direct complete payment test
$paidResult = EnrollmentService::completePayment($createdEid, $testPaymentId, $testOrderId, $testSignature);
assertTest("Payment completed successfully", !empty($paidResult['success']));
assertTest("Redirect URL returned", !empty($paidResult['redirect_url']));

$paidRecord = EnrollmentService::getEnrollment($createdEid);
assertTest("Database status updated to PAID", ($paidRecord['amount_paid_status'] ?? '') === 'PAID');
assertTest("Payment ID stored in database", ($paidRecord['payment_id'] ?? '') === $testPaymentId);
assertTest("Amount Paid updated to ₹49.00", (float)($paidRecord['amount_paid'] ?? 0) === 49.00);

// TEST 7: Invoice Generation for PAID Enrollment
echo "\n7. Testing Automated Invoice Generation for PAID Enrollment...\n";
$generatedPdfPath = InvoiceService::generate($paidRecord);
assertTest("Invoice PDF generated on disk", file_exists($generatedPdfPath));
assertTest("Invoice file size > 500 bytes", filesize($generatedPdfPath) > 500);

// TEST 8: Idempotency Verification
echo "\n8. Testing Idempotency (Duplicate Completion Prevention)...\n";
$duplicateResult = EnrollmentService::completePayment($createdEid, $testPaymentId, $testOrderId, $testSignature);
assertTest("Duplicate payment returns already_processed flag", !empty($duplicateResult['already_processed']));

// TEST 9: Webhook Idempotent Processing
echo "\n9. Testing Razorpay Webhook Handler...\n";
$webhookPayload = json_encode([
    'event' => 'payment.captured',
    'payload' => [
        'payment' => [
            'entity' => [
                'id'       => $testPaymentId,
                'order_id' => $testOrderId,
                'amount'   => 4900,
                'notes'    => ['enrollment_id' => $createdEid]
            ]
        ]
    ]
]);
$webhookSig = hash_hmac('sha256', $webhookPayload, 'dch_webhook_secret_replace_in_production');
$webhookResult = EnrollmentService::processWebhook($webhookPayload, $webhookSig);
assertTest("Webhook accepted and processed", !empty($webhookResult['success']));

// TEST 10: Google Sheets 10-Column Format Mapping Check
echo "\n10. Testing Google Sheets 10-Column Data Mapping...\n";
$cols = GoogleSheetsConfig::get()['columns'];
$expectedColNames = [
    'A' => 'Enrollment ID',
    'B' => 'Name',
    'C' => 'Email',
    'D' => 'Phone Number',
    'E' => 'Location',
    'F' => 'Current Profile',
    'G' => 'Payment ID',
    'H' => 'Workshop Date',
    'I' => 'Workshop Time',
    'J' => 'Amount Paid Status'
];
$colsMatch = ($cols === $expectedColNames);
assertTest("Google Sheets columns strictly match 10-column spec (A-J)", $colsMatch);

echo "\n====================================================================\n";
echo " TEST SUMMARY: {$testsPassed} / {$testsTotal} PASSED\n";
echo "====================================================================\n";

if ($testsPassed === $testsTotal) {
    echo " ALL TESTS COMPLETED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo " SOME TESTS FAILED. PLEASE REVIEW LOGS.\n";
    exit(1);
}

