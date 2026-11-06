<?php
/**
 * Digital Crazy Hub - Razorpay Configuration Entry Point
 */

declare(strict_types=1);

namespace App\Config;

require_once __DIR__ . '/payment.php';

return RazorpayConfig::get();

