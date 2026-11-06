<?php
/**
 * Digital Crazy Hub - Invoice PDF Template Entry Point
 */

declare(strict_types=1);

namespace App\Templates;

require_once __DIR__ . '/invoices/invoice-template.php';

use App\Templates\Invoice\InvoicePdfTemplate;

class InvoiceTemplate extends InvoicePdfTemplate {}

