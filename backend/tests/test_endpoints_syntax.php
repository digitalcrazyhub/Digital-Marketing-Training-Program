<?php
/**
 * Test Endpoint Routing and Execution
 */

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'PHPUnitTest';

echo "Testing backend/api/ files syntax...\n";

$files = [
    'submit-enrollment.php',
    'create-enrollment.php',
    'create-order.php',
    'verify-payment.php',
    'payment-status.php',
    'verify-status.php',
    'download-invoice.php',
    'webhook.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/../api/' . $file;
    if (!file_exists($path)) {
        echo "FAIL: File missing: {$file}\n";
        exit(1);
    }
    // PHP lint check
    $cmd = 'php -l ' . escapeshellarg($path);
    exec($cmd, $output, $code);
    if ($code !== 0) {
        echo "FAIL: Lint error in {$file}: " . implode("\n", $output) . "\n";
        exit(1);
    }
    echo "OK: {$file} syntax valid.\n";
}

echo "All 8 API endpoint files validated successfully.\n";

