<?php
/**
 * Digital Crazy Hub - Enrollment Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\AppConfig;
use App\Helpers\Response;
use App\Services\EnrollmentService;
use App\Services\InvoiceService;
use InvalidArgumentException;
use Throwable;

class EnrollmentController {
    /**
     * POST /api/create-enrollment.php
     */
    public function createEnrollment(array $input): void {
        try {
            $result = EnrollmentService::createEnrollmentAndOrder($input);
            Response::json($result, 200);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (Throwable $e) {
            error_log("EnrollmentController::createEnrollment error: " . $e->getMessage());
            Response::error('Unable to initiate workshop enrollment. Please try again.', 500);
        }
    }

    /**
     * GET /api/verify-status.php?enrollment_id=...&token=...
     */
    public function verifyStatus(string $enrollmentId, string $token): void {
        if (empty($enrollmentId) || empty($token)) {
            Response::error('Missing enrollment verification parameters.', 400, ['verified' => false]);
        }

        $record = EnrollmentService::getEnrollment($enrollmentId);
        if (!$record) {
            Response::error('Enrollment record not found.', 404, ['verified' => false]);
        }

        $storedToken = (string)($record['verification_token'] ?? '');
        $isTokenValid = !empty($storedToken) && hash_equals($storedToken, $token);
        $status = strtoupper((string)($record['payment_status'] ?? ($record['status'] ?? 'NOT PAID')));
        $isPaid = ($status === 'PAID');

        if (!$isTokenValid || !$isPaid) {
            Response::error('Verification required. Only confirmed paid enrollments can view this page.', 403, [
                'verified'       => false,
                'payment_status' => $status
            ]);
        }

        $appConfig = AppConfig::get();
        $eid = $record['enrollment_id'] ?? $enrollmentId;
        $amountPaid = '₹' . number_format((float)($record['amount_paid'] ?? 49.00), 2);

        $whatsappUrl = $appConfig['whatsapp_community_url'];
        $downloadUrl = "api/download-invoice.php?enrollment_id=" . urlencode($eid) . "&token=" . urlencode($token);

        // Google Calendar URL generator
        $startDate = '20261105T133000Z';
        $endDate   = '20261105T163000Z';
        $calTitle  = urlencode('Digital Crazy Hub - ₹49 AI-Integrated Digital Marketing Workshop');
        $calDetails = urlencode("100% Practical Digital Marketing Workshop on Zoom. Check your email or VIP WhatsApp community for Zoom link: {$whatsappUrl}");
        $gcalUrl   = "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$calTitle}&dates={$startDate}/{$endDate}&details={$calDetails}&location=Zoom+Live";

        Response::json([
            'verified'   => true,
            'enrollment' => [
                'enrollment_id'   => $eid,
                'uuid'            => $eid,
                'name'            => $record['name'],
                'email'           => $record['email'],
                'phone'           => $record['phone'],
                'location'        => $record['location'] ?? ($record['city'] ?? ''),
                'current_profile' => $record['current_profile'] ?? ($record['profession'] ?? ''),
                'workshop_date'   => $record['workshop_date'] ?? $appConfig['workshop']['date'],
                'workshop_time'   => $record['workshop_time'] ?? $appConfig['workshop']['time'],
                'payment_id'      => $record['payment_id'] ?? ($record['razorpay_payment_id'] ?? 'PAID'),
                'amount_paid'     => $amountPaid,
                'amount'          => $amountPaid,
                'payment_status'  => 'PAID'
            ],
            'workshop'   => [
                'title'    => $appConfig['workshop']['title'],
                'date'     => $appConfig['workshop']['date'],
                'day'      => $appConfig['workshop']['day'],
                'time'     => $appConfig['workshop']['time'],
                'language' => $appConfig['workshop']['language'],
                'platform' => $appConfig['workshop']['platform']
            ],
            'links'      => [
                'whatsapp_community' => $whatsappUrl,
                'google_calendar'    => $gcalUrl,
                'download_invoice'   => $downloadUrl
            ]
        ]);
    }

    /**
     * GET /api/download-invoice.php?enrollment_id=...&token=...
     */
    public function downloadInvoice(string $identifier, string $token): void {
        $safeId = (string)preg_replace('/[^A-Za-z0-9_-]/', '', basename($identifier));
        if (empty($safeId) || empty($token)) {
            Response::error('Missing or invalid enrollment identifier or security token.', 400);
        }

        $record = EnrollmentService::getEnrollment($safeId);
        if (!$record) {
            Response::error('Enrollment record not found.', 404);
        }

        $status = strtoupper((string)($record['amount_paid_status'] ?? ($record['payment_status'] ?? ($record['status'] ?? 'NOT PAID'))));
        if ($status !== 'PAID') {
            Response::error('Access denied: Invoice is only available after verified payment.', 403);
        }

        $expectedToken = (string)($record['verification_token'] ?? '');
        if (empty($expectedToken) || !hash_equals($expectedToken, $token)) {
            Response::error('Invalid or expired download token.', 403);
        }

        $enrollmentId = (string)($record['enrollment_id'] ?? $safeId);
        $invoiceFile = InvoiceService::getInvoicePath($enrollmentId);

        if (!$invoiceFile || !file_exists($invoiceFile)) {
            $invoiceFile = InvoiceService::generate($record);
        }

        if (!$invoiceFile || !file_exists($invoiceFile)) {
            Response::error('Unable to generate invoice file.', 500);
        }

        $safeName = 'DigitalCrazyHub_Invoice_' . preg_replace('/[^A-Za-z0-9_-]/', '', $enrollmentId) . '.pdf';
        Response::streamFile($invoiceFile, $safeName, 'application/pdf');
    }
}

