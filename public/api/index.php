<?php
/**
 * Digital Crazy Hub - Public API Gateway
 * Minimal entry point: forwards all public API requests directly to /backend/router.php
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/router.php';
