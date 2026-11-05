<?php
/**
 * Digital Crazy Hub - Email Service
 * Sends student payment confirmation email with attached PDF invoice
 * and sends company lead notification email.
 * Triggered ONLY AFTER successful payment verification.
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\AppConfig;
use App\Config\MailConfig;
use Throwable;

class EmailService {
    public static function sendStudentConfirmation(array $enrollment, string $pdfInvoicePath): bool {
        $appConfig = AppConfig::get();
        $mailConfig = MailConfig::get();

        $toEmail = trim((string)($enrollment['email'] ?? ''));
        $enrollmentId = (string)($enrollment['enrollment_id'] ?? 'DCH-2026-000000');

        $templateData = require APP_DIR . '/Templates/emails/student-confirmation.php';

        $subject   = $templateData['subject'];
        $plainText = $templateData['plain'];
        $html      = $templateData['html'];

        return self::deliverEmail($toEmail, $subject, $html, $plainText, $pdfInvoicePath, $mailConfig, $enrollmentId);
    }

    public static function sendCompanyNotification(array $enrollment): bool {
        $appConfig = AppConfig::get();
        $mailConfig = MailConfig::get();

        $companyEmail = trim((string)($mailConfig['company_notification_email'] ?? 'info@digitalcrazyhub.com'));
        $enrollmentId = (string)($enrollment['enrollment_id'] ?? 'DCH-2026-000000');

        $templateData = require APP_DIR . '/Templates/emails/company-notification.php';

        $subject   = $templateData['subject'];
        $plainText = $templateData['plain'];
        $html      = $templateData['html'];

        return self::deliverEmail($companyEmail, $subject, $html, $plainText, null, $mailConfig, "NOTIF-{$enrollmentId}");
    }

    private static function deliverEmail(
        string $toEmail,
        string $subject,
        string $htmlContent,
        string $plainText,
        ?string $attachmentPath,
        array $mailConfig,
        string $logIdentifier
    ): bool {
        $fromEmail = (string)($mailConfig['from_email'] ?? 'info@digitalcrazyhub.com');
        $fromName  = (string)($mailConfig['from_name'] ?? 'Digital Crazy Hub');

        $boundary = "==DCH_Multipart_Boundary_" . md5(uniqid((string)time(), true));

        $headers = [
            "MIME-Version: 1.0",
            "From: {$fromName} <{$fromEmail}>",
            "Reply-To: {$fromEmail}",
            "X-Mailer: DCH-MailEngine/3.0",
            "Content-Type: multipart/mixed; boundary=\"{$boundary}\""
        ];

        // Part 1: Multipart alternative (Plain Text + HTML)
        $altBoundary = "==DCH_Alt_Boundary_" . md5(uniqid("alt", true));
        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: multipart/alternative; boundary=\"{$altBoundary}\"\r\n\r\n";

        // 1A: Plain text
        $message .= "--{$altBoundary}\r\n";
        $message .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $plainText . "\r\n\r\n";

        // 1B: HTML
        $message .= "--{$altBoundary}\r\n";
        $message .= "Content-Type: text/html; charset=\"UTF-8\"\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $htmlContent . "\r\n\r\n";
        $message .= "--{$altBoundary}--\r\n\r\n";

        // Part 2: PDF Attachment (if attached)
        if (!empty($attachmentPath) && file_exists($attachmentPath)) {
            $pdfBytes = file_get_contents($attachmentPath);
            $base64 = chunk_split(base64_encode($pdfBytes));
            $filename = basename($attachmentPath);

            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: application/pdf; name=\"{$filename}\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
            $message .= $base64 . "\r\n\r\n";
        }

        $message .= "--{$boundary}--";

        // Audit Logging & EML Archival
        $logDir = STORAGE_DIR . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $hasPdf = (!empty($attachmentPath) && file_exists($attachmentPath)) ? "YES" : "NO";
        $logLine = "[" . date('Y-m-d H:i:s') . "] ID: {$logIdentifier} | To: {$toEmail} | Subject: {$subject} | PDF: {$hasPdf}\n";
        @file_put_contents($logDir . '/email_log.log', $logLine, FILE_APPEND);
        @file_put_contents($logDir . "/{$logIdentifier}.eml", implode("\r\n", $headers) . "\r\n\r\n" . $message);

        // SMTP Delivery
        if (!empty($mailConfig['host']) && !empty($mailConfig['user']) && !empty($mailConfig['pass'])) {
            try {
                $smtpSent = self::sendViaSmtp($toEmail, $subject, $message, $headers, $mailConfig);
                if ($smtpSent) {
                    return true;
                }
            } catch (Throwable $e) {
                error_log("EmailService SMTP Error: " . $e->getMessage());
            }
        }

        // Native mail() fallback (skip if Windows without configured sendmail)
        $sent = false;
        if (PHP_OS_FAMILY !== 'Windows' || !empty(ini_get('sendmail_path'))) {
            try {
                $sent = @mail($toEmail, $subject, $message, implode("\r\n", $headers));
            } catch (Throwable $e) {
                error_log("EmailService mail() Error: " . $e->getMessage());
            }
        }

        return $sent;
    }

    private static function sendViaSmtp(string $to, string $subject, string $data, array $headers, array $smtp): bool {
        $host   = (string)($smtp['host'] ?? 'smtp.hostinger.com');
        $port   = (int)($smtp['port'] ?? 465);
        $user   = (string)($smtp['user'] ?? '');
        $pass   = (string)($smtp['pass'] ?? '');
        $secure = strtolower((string)($smtp['secure'] ?? 'ssl'));

        $prefix = ($secure === 'ssl' || $port === 465) ? 'ssl://' : '';
        $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 5);

        if (!$socket) {
            error_log("SMTP Connection failed: $errstr ($errno)");
            return false;
        }

        $res = fgets($socket, 512);
        if (!str_starts_with((string)$res, '220')) {
            fclose($socket);
            return false;
        }

        fputs($socket, "EHLO " . gethostname() . "\r\n");
        while ($line = fgets($socket, 512)) {
            if (substr($line, 3, 1) === ' ') break;
        }

        // AUTH LOGIN
        fputs($socket, "AUTH LOGIN\r\n");
        fgets($socket, 512);
        fputs($socket, base64_encode($user) . "\r\n");
        fgets($socket, 512);
        fputs($socket, base64_encode($pass) . "\r\n");
        $authRes = fgets($socket, 512);

        if (!str_starts_with((string)$authRes, '235')) {
            fclose($socket);
            error_log("SMTP Authentication failed: " . trim((string)$authRes));
            return false;
        }

        $from = (string)($smtp['from_email'] ?? $user);
        fputs($socket, "MAIL FROM: <{$from}>\r\n");
        fgets($socket, 512);

        fputs($socket, "RCPT TO: <{$to}>\r\n");
        fgets($socket, 512);

        fputs($socket, "DATA\r\n");
        fgets($socket, 512);

        $payload = "To: {$to}\r\nSubject: {$subject}\r\n" . implode("\r\n", $headers) . "\r\n\r\n" . $data . "\r\n.\r\n";
        fputs($socket, $payload);
        $dataRes = fgets($socket, 512);

        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return str_starts_with((string)$dataRes, '250');
    }
}

