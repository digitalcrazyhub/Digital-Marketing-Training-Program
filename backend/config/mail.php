<?php
/**
 * Digital Crazy Hub - Mail / SMTP Configuration Entry Point
 */

declare(strict_types=1);

namespace App\Config;

require_once __DIR__ . '/email.php';

return MailConfig::get();

