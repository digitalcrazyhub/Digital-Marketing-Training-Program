<?php
/**
 * Digital Crazy Hub - Automated Invoice PDF Service
 * Generates official Tax Invoice & Admission Pass PDF for PAID enrollments only.
 * Stores PDFs securely in storage/invoices/ with strict access control.
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\AppConfig;
use App\Templates\Invoice\InvoicePdfTemplate;
use RuntimeException;

class InvoiceService {
    public static function generate(array $enrollment): string {
        $status = strtoupper((string)($enrollment['payment_status'] ?? ($enrollment['status'] ?? 'NOT PAID')));
        if ($status !== 'PAID') {
            throw new RuntimeException("Cannot generate invoice for unpaid enrollment (Status: {$status}).");
        }

        $enrollmentId = (string)($enrollment['enrollment_id'] ?? ($enrollment['enrollment_uuid'] ?? 'DCH-PASS'));
        $invoiceDir = STORAGE_DIR . '/invoices';
        if (!is_dir($invoiceDir)) {
            @mkdir($invoiceDir, 0755, true);
        }

        $pdfPath = $invoiceDir . "/INV-{$enrollmentId}.pdf";

        $appConfig = AppConfig::get();
        $paidAt = !empty($enrollment['paid_at']) ? date('d M Y, h:i A', strtotime($enrollment['paid_at'])) : date('d M Y, h:i A');

        $data = [
            'enrollment_id'   => $enrollmentId,
            'name'            => $enrollment['name'] ?? 'Learner',
            'email'           => $enrollment['email'] ?? '',
            'phone'           => $enrollment['phone'] ?? '',
            'location'        => $enrollment['location'] ?? ($enrollment['city'] ?? ''),
            'current_profile' => $enrollment['current_profile'] ?? ($enrollment['profession'] ?? ''),
            'payment_id'      => $enrollment['payment_id'] ?? ($enrollment['razorpay_payment_id'] ?? 'PAID'),
            'amount_paid'     => (float)($enrollment['amount_paid'] ?? 49.00),
            'workshop_date'   => $enrollment['workshop_date'] ?? $appConfig['workshop']['date'],
            'workshop_time'   => $enrollment['workshop_time'] ?? $appConfig['workshop']['time'],
            'paid_at'         => $paidAt,
            'title'           => $appConfig['workshop']['title'] ?? '₹49 AI-Integrated Digital Marketing Paid Workshop'
        ];

        InvoicePdfTemplate::generate($data, $pdfPath);

        return $pdfPath;
    }

    public static function getInvoicePath(string $enrollmentId): ?string {
        $path = STORAGE_DIR . "/invoices/INV-{$enrollmentId}.pdf";
        return file_exists($path) ? $path : null;
    }
}

