<?php
/**
 * Digital Crazy Hub - Email Service (PHPMailer / SMTP / MIME Engine)
 * Sends student payment confirmation email with attached PDF invoice
 * and sends company lead notification email.
 * Triggered ONLY AFTER successful payment verification.
 */

declare(strict_types=1);

class EmailService {
    /**
     * Send Student Payment Confirmation Email with Attached PDF Invoice
     */
    public static function sendStudentConfirmation(
        array $enrollment,
        string $pdfInvoicePath,
        array $config
    ): bool {
        $toEmail = trim((string)($enrollment['email'] ?? ''));
        $studentName = trim((string)($enrollment['name'] ?? 'Learner'));
        $enrollmentId = (string)($enrollment['enrollment_id'] ?? 'DCH-2026-000000');
        $phone = (string)($enrollment['phone'] ?? '');
        $location = (string)($enrollment['location'] ?? ($enrollment['city'] ?? ''));
        $profile = (string)($enrollment['current_profile'] ?? ($enrollment['profession'] ?? ''));
        $paymentId = (string)($enrollment['payment_id'] ?? ($enrollment['razorpay_payment_id'] ?? 'PAID'));
        $amountPaid = '₹' . number_format((float)($enrollment['amount_paid'] ?? 49.00), 2);
        
        $workshopDate = (string)($config['workshop']['date'] ?? 'November 5, 2026');
        $workshopTime = (string)($config['workshop']['time'] ?? '7:00 PM IST');
        $whatsappUrl = (string)($config['whatsapp_community_url'] ?? 'https://chat.whatsapp.com/digitalcrazyhub-dm-workshop');
        $supportEmail = (string)($config['app']['support_email'] ?? 'info@digitalcrazyhub.com');

        $subject = "Your Enrollment is Confirmed – Digital Crazy Hub";

        // 1. Plain text fallback version (as strictly specified in prompt)
        $plainText = <<<TEXT
Dear {$studentName},

Congratulations!
Your enrollment for the Digital Marketing Training Workshop has been successfully confirmed.

Enrollment Details:
Enrollment ID: {$enrollmentId}
Student Name: {$studentName}
Email: {$toEmail}
Phone: {$phone}
Location: {$location}
Current Profile: {$profile}

Workshop Details:
Workshop Date: {$workshopDate}
Workshop Time: {$workshopTime}

Payment Details:
Payment ID: {$paymentId}
Amount Paid: {$amountPaid}
Payment Status: PAID

Your seat is officially confirmed.
Please find your payment invoice attached to this email as a PDF.

Join VIP WhatsApp Community for Zoom Access:
{$whatsappUrl}

Thank you for choosing Digital Crazy Hub.

Regards,
Digital Crazy Hub Team
https://digitalcrazyhub.com
TEXT;

        // 2. Responsive Branded HTML Template
        $safeName = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
        $safeEnrollmentId = htmlspecialchars($enrollmentId, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');
        $safePhone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
        $safeLocation = htmlspecialchars($location, ENT_QUOTES, 'UTF-8');
        $safeProfile = htmlspecialchars($profile, ENT_QUOTES, 'UTF-8');
        $safePaymentId = htmlspecialchars($paymentId, ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$subject}</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #0f172a; }
    .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(15,23,42,0.06); }
    .email-header { background: linear-gradient(135deg, #070c18 0%, #1e3a8a 100%); padding: 30px 24px; text-align: center; color: #ffffff; }
    .email-header h1 { margin: 0; font-size: 24px; font-weight: 800; }
    .email-header p { margin: 8px 0 0; color: #93c5fd; font-size: 14px; }
    .email-body { padding: 30px 26px; }
    .badge-paid { display: inline-block; background: #dcfce7; color: #166534; font-weight: 800; font-size: 13px; padding: 6px 14px; border-radius: 9999px; border: 1px solid #86efac; margin-bottom: 18px; }
    .details-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; margin: 20px 0; }
    .details-row { display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px dashed #cbd5e1; font-size: 14px; }
    .details-row:last-child { border-bottom: none; }
    .label { color: #64748b; font-weight: 600; }
    .val { color: #0f172a; font-weight: 700; text-align: right; }
    .whatsapp-btn { display: block; background: #25d366; color: #ffffff !important; text-decoration: none; font-weight: 800; font-size: 16px; text-align: center; padding: 15px 24px; border-radius: 8px; margin: 24px 0 16px; box-shadow: 0 4px 12px rgba(37,211,102,0.3); }
    .attachment-notice { background: #eff6ff; border-left: 4px solid #2563eb; padding: 14px 16px; font-size: 13px; color: #1e40af; border-radius: 4px; margin-top: 22px; line-height: 1.5; }
    .email-footer { background: #0f172a; padding: 22px; text-align: center; color: #94a3b8; font-size: 12px; }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="email-header">
      <h1>Digital Crazy Hub</h1>
      <p>100% Practical Digital Marketing & AI Workshop</p>
    </div>
    <div class="email-body">
      <div class="badge-paid">✔ Seat Confirmed & Payment Verified</div>
      <h2 style="margin-top: 0; font-size: 20px; color: #0f172a;">Dear {$safeName},</h2>
      <p style="font-size: 15px; line-height: 1.6; color: #334155;">
        Congratulations! Your enrollment for the <strong>Digital Marketing Training Workshop</strong> has been successfully confirmed.
      </p>

      <div class="details-box">
        <div style="font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.05em;">Enrollment & Payment Details</div>
        <div class="details-row"><span class="label">Enrollment ID:</span><span class="val">{$safeEnrollmentId}</span></div>
        <div class="details-row"><span class="label">Student Name:</span><span class="val">{$safeName}</span></div>
        <div class="details-row"><span class="label">Email:</span><span class="val">{$safeEmail}</span></div>
        <div class="details-row"><span class="label">Phone:</span><span class="val">{$safePhone}</span></div>
        <div class="details-row"><span class="label">Location:</span><span class="val">{$safeLocation}</span></div>
        <div class="details-row"><span class="label">Current Profile:</span><span class="val">{$safeProfile}</span></div>
        <div class="details-row"><span class="label">Workshop Date:</span><span class="val">{$workshopDate}</span></div>
        <div class="details-row"><span class="label">Workshop Time:</span><span class="val">{$workshopTime}</span></div>
        <div class="details-row"><span class="label">Payment ID:</span><span class="val">{$safePaymentId}</span></div>
        <div class="details-row"><span class="label">Amount Paid:</span><span class="val" style="color: #059669;">{$amountPaid}</span></div>
        <div class="details-row"><span class="label">Payment Status:</span><span class="val" style="color: #059669;">PAID</span></div>
      </div>

      <p style="font-size: 14px; font-weight: 600; color: #0f172a; margin-bottom: 6px;">Your seat is officially confirmed!</p>
      <p style="font-size: 13px; color: #64748b; margin-top: 0; line-height: 1.5;">
        The Zoom meeting join link, digital checklist, and workshop alerts will be shared directly in our private VIP WhatsApp Community:
      </p>

      <a href="{$whatsappUrl}" class="whatsapp-btn" target="_blank" rel="noopener">
        👉 Join VIP WhatsApp Group Now
      </a>

      <div class="attachment-notice">
        <strong>📎 Official Tax Invoice Attached:</strong> Your payment invoice and workshop admission pass PDF has been generated and attached to this email.
      </div>
    </div>
    <div class="email-footer">
      <p style="margin: 0 0 6px;">Digital Crazy Hub • Govt. of India MSME Registered</p>
      <p style="margin: 0;">Support: <a href="mailto:{$supportEmail}" style="color: #60a5fa;">{$supportEmail}</a> | <a href="https://digitalcrazyhub.com" style="color: #60a5fa;">https://digitalcrazyhub.com</a></p>
    </div>
  </div>
</body>
</html>
HTML;

        return self::deliverEmail($toEmail, $subject, $html, $plainText, $pdfInvoicePath, $config, $enrollmentId);
    }

    /**
     * Send Company Lead Notification Email (Triggered ONLY after payment)
     */
    public static function sendCompanyNotification(
        array $enrollment,
        array $config
    ): bool {
        $companyEmail = trim((string)($config['smtp']['company_notification_email'] ?? ($config['app']['support_email'] ?? 'info@digitalcrazyhub.com')));
        $studentName = trim((string)($enrollment['name'] ?? 'Learner'));
        $enrollmentId = (string)($enrollment['enrollment_id'] ?? 'DCH-2026-000000');
        $email = (string)($enrollment['email'] ?? '');
        $phone = (string)($enrollment['phone'] ?? '');
        $location = (string)($enrollment['location'] ?? ($enrollment['city'] ?? ''));
        $profile = (string)($enrollment['current_profile'] ?? ($enrollment['profession'] ?? ''));
        $paymentId = (string)($enrollment['payment_id'] ?? ($enrollment['razorpay_payment_id'] ?? 'PAID'));
        $amountPaid = '₹' . number_format((float)($enrollment['amount_paid'] ?? 49.00), 2);
        
        $workshopDate = (string)($config['workshop']['date'] ?? 'November 5, 2026');
        $workshopTime = (string)($config['workshop']['time'] ?? '7:00 PM IST');

        $subject = "New Paid Workshop Enrollment – {$studentName}";

        $plainText = <<<TEXT
New Paid Workshop Enrollment Received!

Enrollment ID: {$enrollmentId}
Student Name: {$studentName}
Email: {$email}
Phone: {$phone}
Location: {$location}
Current Profile: {$profile}

Payment ID: {$paymentId}
Workshop Date: {$workshopDate}
Workshop Time: {$workshopTime}
Amount Paid: {$amountPaid}
Payment Status: PAID

Timestamp: {$enrollment['paid_at']}
TEXT;

        $safeName = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
        $safeEnrollmentId = htmlspecialchars($enrollmentId, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safePhone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
        $safeLocation = htmlspecialchars($location, ENT_QUOTES, 'UTF-8');
        $safeProfile = htmlspecialchars($profile, ENT_QUOTES, 'UTF-8');
        $safePaymentId = htmlspecialchars($paymentId, ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; padding: 20px; color: #0f172a; }
    .box { max-width: 580px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; }
    h2 { color: #166534; margin-top: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 14px; }
    td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
    td.label { color: #64748b; font-weight: 600; width: 40%; }
    td.val { font-weight: 700; color: #0f172a; }
  </style>
</head>
<body>
  <div class="box">
    <h2>✔ New Paid Workshop Enrollment</h2>
    <p>A new student has successfully completed payment for the ₹49 Workshop:</p>
    <table>
      <tr><td class="label">Enrollment ID:</td><td class="val">{$safeEnrollmentId}</td></tr>
      <tr><td class="label">Student Name:</td><td class="val">{$safeName}</td></tr>
      <tr><td class="label">Email:</td><td class="val"><a href="mailto:{$safeEmail}">{$safeEmail}</a></td></tr>
      <tr><td class="label">Phone:</td><td class="val"><a href="https://wa.me/{$safePhone}">{$safePhone}</a></td></tr>
      <tr><td class="label">Location:</td><td class="val">{$safeLocation}</td></tr>
      <tr><td class="label">Current Profile:</td><td class="val">{$safeProfile}</td></tr>
      <tr><td class="label">Payment ID:</td><td class="val">{$safePaymentId}</td></tr>
      <tr><td class="label">Workshop Date:</td><td class="val">{$workshopDate}</td></tr>
      <tr><td class="label">Workshop Time:</td><td class="val">{$workshopTime}</td></tr>
      <tr><td class="label">Amount Paid:</td><td class="val" style="color: #166534;">{$amountPaid}</td></tr>
      <tr><td class="label">Payment Status:</td><td class="val" style="color: #166534;">PAID</td></tr>
    </table>
  </div>
</body>
</html>
HTML;

        return self::deliverEmail($companyEmail, $subject, $html, $plainText, null, $config, "NOTIF-{$enrollmentId}");
    }

    /**
     * Dispatch email via SMTP or native mail() with multipart MIME & PDF attachment
     */
    private static function deliverEmail(
        string $toEmail,
        string $subject,
        string $htmlContent,
        string $plainText,
        ?string $attachmentPath,
        array $config,
        string $logIdentifier
    ): bool {
        $fromEmail = (string)($config['smtp']['from_email'] ?? 'info@digitalcrazyhub.com');
        $fromName = (string)($config['smtp']['from_name'] ?? 'Digital Crazy Hub');

        $boundary = "==DCH_Multipart_Boundary_" . md5(uniqid((string)time(), true));

        $headers = [
            "MIME-Version: 1.0",
            "From: {$fromName} <{$fromEmail}>",
            "Reply-To: {$fromEmail}",
            "X-Mailer: DCH-MailEngine/2.0",
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

        // Archive and audit log
        $emailDir = __DIR__ . '/../data/emails';
        if (!is_dir($emailDir)) {
            @mkdir($emailDir, 0755, true);
        }

        $hasPdf = (!empty($attachmentPath) && file_exists($attachmentPath)) ? "YES" : "NO";
        $logLine = "[" . date('Y-m-d H:i:s') . "] ID: {$logIdentifier} | To: {$toEmail} | Subject: {$subject} | PDF: {$hasPdf}\n";
        @file_put_contents($emailDir . '/email_log.log', $logLine, FILE_APPEND);
        @file_put_contents($emailDir . "/{$logIdentifier}.eml", implode("\r\n", $headers) . "\r\n\r\n" . $message);

        // Send via SMTP if configured, else fallback to PHP mail()
        $smtp = $config['smtp'] ?? [];
        if (!empty($smtp['host']) && !empty($smtp['user']) && !empty($smtp['pass'])) {
            try {
                $smtpSent = self::sendViaSmtp($toEmail, $subject, $message, $headers, $smtp);
                if ($smtpSent) return true;
            } catch (Throwable $e) {
                error_log("EmailService SMTP Error: " . $e->getMessage());
            }
        }

        // Native mail() fallback
        $sent = false;
        try {
            $sent = @mail($toEmail, $subject, $message, implode("\r\n", $headers));
        } catch (Throwable $e) {
            error_log("EmailService mail() Error: " . $e->getMessage());
        }

        return $sent;
    }

    /**
     * Direct Socket SMTP Client with SSL/TLS and AUTH LOGIN/PLAIN
     */
    private static function sendViaSmtp(string $to, string $subject, string $data, array $headers, array $smtp): bool {
        $host = (string)($smtp['host'] ?? 'smtp.hostinger.com');
        $port = (int)($smtp['port'] ?? 465);
        $user = (string)($smtp['user'] ?? '');
        $pass = (string)($smtp['pass'] ?? '');
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
        $res = fgets($socket, 512);
        if (!str_starts_with((string)$res, '334')) {
            fclose($socket);
            return false;
        }

        fputs($socket, base64_encode($user) . "\r\n");
        $res = fgets($socket, 512);
        fputs($socket, base64_encode($pass) . "\r\n");
        $res = fgets($socket, 512);
        if (!str_starts_with((string)$res, '235')) {
            fclose($socket);
            return false;
        }

        fputs($socket, "MAIL FROM: <{$user}>\r\n");
        fgets($socket, 512);

        fputs($socket, "RCPT TO: <{$to}>\r\n");
        fgets($socket, 512);

        fputs($socket, "DATA\r\n");
        fgets($socket, 512);

        $payload = implode("\r\n", $headers) . "\r\nSubject: {$subject}\r\n\r\n" . $data . "\r\n.\r\n";
        fputs($socket, $payload);
        $res = fgets($socket, 512);

        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return str_starts_with((string)$res, '250');
    }
}

