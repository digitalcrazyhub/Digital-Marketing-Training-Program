<?php
/**
 * Digital Crazy Hub - Security & Request Middleware
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Response;
use App\Helpers\Security;

class SecurityMiddleware {
    public static function handle(): void {
        // Enforce JSON header if API request
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Handle CORS preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            exit(0);
        }

        // Rate limit check
        $ip = Security::getClientIp();
        if (!Security::checkRateLimit($ip, 60, 60)) {
            Response::error('Too many requests. Please wait a moment and try again.', 429);
        }
    }
}
