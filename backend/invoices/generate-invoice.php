<?php
/**
 * Digital Crazy Hub - Generate Invoice CLI / Controller Entrypoint
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/InvoiceService.php';

function generateInvoiceForEnrollment(array $enrollment, array $config): string {
    return InvoiceService::generate($enrollment, $config);
}

