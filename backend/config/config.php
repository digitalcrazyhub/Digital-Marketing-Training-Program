<?php
/**
 * Digital Crazy Hub - Unified Configuration Entry Point
 * Exposes core configuration settings and maps AppConfig.
 */

declare(strict_types=1);

namespace App\Config;

require_once __DIR__ . '/app.php';

class Config extends AppConfig {
    // Inherits all static configuration methods from AppConfig
}

return AppConfig::get();

