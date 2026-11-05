<?php
/**
 * Digital Crazy Hub - Root Gateway
 * Transparently delivers /public/index.html when the web server document root points to the repository root.
 */

declare(strict_types=1);

$publicIndex = __DIR__ . '/public/index.html';

if (file_exists($publicIndex)) {
    // If accessed directly via browser, redirect or serve
    if (php_sapi_name() !== 'cli') {
        readfile($publicIndex);
        exit;
    }
} else {
    http_response_code(500);
    echo "Application error: public directory missing.";
    exit;
}

