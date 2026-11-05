<?php
/**
 * Digital Crazy Hub - Centralized Backend Bootstrap & Autoloader
 */

declare(strict_types=1);

if (defined('APP_BOOTSTRAPPED')) {
    return;
}
define('APP_BOOTSTRAPPED', true);

define('APP_ROOT', dirname(__DIR__));
define('BACKEND_DIR', __DIR__);
define('STORAGE_DIR', BACKEND_DIR . '/storage');
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

// Load .env from project root
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

    // Explicit mappings for centralized backend architecture
    $classMap = [
        'App\Config\AppConfig'                     => BACKEND_DIR . '/config/app.php',
        'App\Config\Database'                      => BACKEND_DIR . '/config/database.php',
        'App\Config\PaymentConfig'                 => BACKEND_DIR . '/config/payment.php',
        'App\Config\RazorpayConfig'                => BACKEND_DIR . '/config/payment.php',
        'App\Config\EmailConfig'                   => BACKEND_DIR . '/config/email.php',
        'App\Config\MailConfig'                    => BACKEND_DIR . '/config/email.php',
        'App\Config\GoogleSheetsConfig'            => BACKEND_DIR . '/config/google-sheets.php',
        'App\Templates\Invoice\InvoicePdfTemplate' => BACKEND_DIR . '/templates/invoices/invoice-template.php'
    ];

    if (isset($classMap[$class]) && file_exists($classMap[$class])) {
        require_once $classMap[$class];
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $parts = explode('\\', $relativeClass);
    $className = array_pop($parts);
    $subDir = !empty($parts) ? strtolower(implode('/', $parts)) . '/' : '';

    // Candidate 1: standard directory (lowercase subDir + exact ClassName.php)
    $candidate1 = BACKEND_DIR . '/' . $subDir . $className . '.php';
    if (file_exists($candidate1)) {
        require_once $candidate1;
        return;
    }

    // Candidate 2: lowercase filename (e.g. app.php, database.php)
    $candidate2 = BACKEND_DIR . '/' . $subDir . strtolower($className) . '.php';
    if (file_exists($candidate2)) {
        require_once $candidate2;
        return;
    }

    // Candidate 3: kebab-case filename (e.g. invoice-template.php, google-sheets.php)
    $kebab = strtolower((string)preg_replace('/([a-z])([A-Z])/', '$1-$2', $className));
    $candidate3 = BACKEND_DIR . '/' . $subDir . $kebab . '.php';
    if (file_exists($candidate3)) {
        require_once $candidate3;
        return;
    }
});
