<?php
/**
 * Digital Crazy Hub - Automated Invoice PDF Generator Service
 * Generates official Tax Invoice & Workshop Pass PDF for PAID enrollments only.
 * Stores PDFs securely in backend/data/invoices/.
 */

declare(strict_types=1);

require_once __DIR__ . '/InvoicePdf.php';

class InvoiceService {
    /**
     * Generate PDF Tax Invoice for verified enrollment
     * Throws Exception if enrollment is NOT PAID.
     */
    public static function generate(array $enrollment, array $config): string {
        $status = strtoupper((string)($enrollment['payment_status'] ?? ($enrollment['status'] ?? 'NOT PAID')));
        if ($status !== 'PAID') {
            throw new RuntimeException("Cannot generate invoice for unpaid enrollment (Status: {$status}).");
        }

        $enrollmentId = (string)($enrollment['enrollment_id'] ?? ($enrollment['enrollment_uuid'] ?? 'DCH-PASS'));
        $invoiceDir = __DIR__ . '/../data/invoices';
        if (!is_dir($invoiceDir)) {
            @mkdir($invoiceDir, 0755, true);
        }

        // Ensure .htaccess protection
        $htaccess = $invoiceDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            @file_put_contents($htaccess, "Require all denied\nDeny from all\n");
        }

        $pdfPath = $invoiceDir . "/INV-{$enrollmentId}.pdf";

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
            'workshop_date'   => $enrollment['workshop_date'] ?? $config['workshop']['date'],
            'workshop_time'   => $enrollment['workshop_time'] ?? $config['workshop']['time'],
            'paid_at'         => $paidAt,
            'title'           => $config['workshop']['title'] ?? '₹49 AI-Integrated Digital Marketing Paid Workshop'
        ];

        InvoicePdf::generate($data, $pdfPath);

        return $pdfPath;
    }

    /**
     * Check if invoice exists for enrollment
     */
    public static function getInvoicePath(string $enrollmentId): ?string {
        $path = __DIR__ . "/../data/invoices/INV-{$enrollmentId}.pdf";
        return file_exists($path) ? $path : null;
    }
}

