<?php
/**
 * Digital Crazy Hub - Application Bootstrap & PSR-4 Autoloader
 */

declare(strict_types=1);

// Prevent multiple bootstrap calls
if (defined('APP_BOOTSTRAPPED')) {
    return;
}
define('APP_BOOTSTRAPPED', true);

define('APP_ROOT', dirname(__DIR__));
define('APP_DIR', __DIR__);
define('STORAGE_DIR', APP_ROOT . '/storage');
define('PUBLIC_DIR', APP_ROOT . '/public');

// Simple native .env parser
if (!function_exists('loadEnv')) {
    function loadEnv(string $path): void {
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);
                // Strip surrounding quotes
                if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                    (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                    $val = substr($val, 1, -1);
                }
                // Unescape newlines in private keys
                $val = str_replace('\n', "\n", $val);
                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    putenv("{$key}={$val}");
                    $_ENV[$key] = $val;
                    $_SERVER[$key] = $val;
                }
            }
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        if ($val === null) {
            return $default;
        }
        if (is_string($val)) {
            $lower = strtolower($val);
            if ($lower === 'true') return true;
            if ($lower === 'false') return false;
            if ($lower === 'null') return null;
        }
        return $val;
    }
}

// Load .env
loadEnv(APP_ROOT . '/.env');
if (!file_exists(APP_ROOT . '/.env') && file_exists(APP_ROOT . '/.env.example')) {
    loadEnv(APP_ROOT . '/.env.example');
}

// Native PSR-4 Autoloader with smart class-to-filename fallback for App\*
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    // Explicit mappings for prompt-specified file structures
    $classMap = [
        'App\Config\AppConfig'                     => APP_DIR . '/Config/app.php',
        'App\Config\Database'                      => APP_DIR . '/Config/database.php',
        'App\Config\RazorpayConfig'                => APP_DIR . '/Config/razorpay.php',
        'App\Config\GoogleSheetsConfig'            => APP_DIR . '/Config/google-sheets.php',
        'App\Config\MailConfig'                    => APP_DIR . '/Config/mail.php',
        'App\Templates\Invoice\InvoicePdfTemplate' => APP_DIR . '/Templates/invoice/invoice-template.php'
    ];

    if (isset($classMap[$class]) && file_exists($classMap[$class])) {
        require_once $classMap[$class];
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $parts = explode('\\', $relativeClass);
    $className = array_pop($parts);
    $subDir = !empty($parts) ? implode('/', $parts) . '/' : '';

    // Candidate 1: Standard PSR-4
    $candidate1 = APP_DIR . '/' . $subDir . $className . '.php';
    if (file_exists($candidate1)) {
        require_once $candidate1;
        return;
    }

    // Candidate 2: lowercase filename (e.g. app.php, database.php)
    $candidate2 = APP_DIR . '/' . $subDir . strtolower($className) . '.php';
    if (file_exists($candidate2)) {
        require_once $candidate2;
        return;
    }

    // Candidate 3: kebab-case filename (e.g. invoice-template.php)
    $kebab = strtolower((string)preg_replace('/([a-z])([A-Z])/', '$1-$2', $className));
    $candidate3 = APP_DIR . '/' . $subDir . $kebab . '.php';
    if (file_exists($candidate3)) {
        require_once $candidate3;
        return;
    }
});

