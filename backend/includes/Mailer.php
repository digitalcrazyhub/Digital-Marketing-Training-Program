<?php
/**
 * Digital Crazy Hub - Payment Confirmation & PDF Invoice Mailer
 * Sends multi-part MIME email with HTML body and base64-encoded PDF attachment.
 */

class Mailer {
    public static function sendConfirmationWithInvoice(
        string $toEmail,
        string $recipientName,
        string $pdfPath,
        array $enrollment,
        array $config
    ): bool {
        $uuid = $enrollment['enrollment_uuid'] ?? 'DCH-PASS';
        $workshopTitle = $config['workshop']['title'] ?? '₹49 AI-Integrated Digital Marketing Workshop';
        $workshopDate = $config['workshop']['date'] ?? 'November 5, 2026';
        $workshopTime = $config['workshop']['time'] ?? '7:00 PM IST';
        $whatsappCommunity = $config['whatsapp_community_url'] ?? 'https://chat.whatsapp.com/digitalcrazyhub-dm-workshop';
        $supportEmail = $config['app']['support_email'] ?? 'info@digitalcrazyhub.com';
        $supportPhone = $config['app']['whatsapp_support_number'] ?? '919047700201';

        $subject = "Confirmed: Your ₹49 AI-Integrated Digital Marketing Workshop Pass & Tax Invoice [ID: $uuid]";

        // HTML Body
        $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #0f172a; }
    .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(15,23,42,0.06); }
    .email-header { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); padding: 30px 24px; text-align: center; color: #ffffff; }
    .email-header h1 { margin: 0; font-size: 24px; font-weight: 700; }
    .email-header p { margin: 8px 0 0; color: #bfdbfe; font-size: 14px; }
    .email-body { padding: 30px 26px; }
    .badge-success { display: inline-block; background: #ecfdf5; color: #059669; font-weight: 700; font-size: 13px; padding: 6px 14px; border-radius: 9999px; border: 1px solid #a7f3d0; margin-bottom: 16px; }
    .details-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px 20px; margin: 20px 0; }
    .details-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #cbd5e1; font-size: 14px; }
    .details-row:last-child { border-bottom: none; }
    .label { color: #64748b; font-weight: 500; }
    .val { color: #0f172a; font-weight: 700; text-align: right; }
    .whatsapp-btn { display: block; background: #25d366; color: #ffffff !important; text-decoration: none; font-weight: 700; font-size: 16px; text-align: center; padding: 15px 24px; border-radius: 8px; margin: 24px 0 16px; }
    .attachment-notice { background: #eff6ff; border-left: 4px solid #2563eb; padding: 12px 16px; font-size: 13px; color: #1e40af; border-radius: 4px; margin-top: 20px; }
    .email-footer { background: #0f172a; padding: 20px; text-align: center; color: #94a3b8; font-size: 12px; }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="email-header">
      <h1>Digital Crazy Hub</h1>
      <p>100% Practical Digital Marketing & AI Training Ecosystem</p>
    </div>
    <div class="email-body">
      <div class="badge-success">✔ Seat Confirmed & Payment Verified</div>
      <h2 style="margin-top: 0; font-size: 20px;">Hello {$recipientName},</h2>
      <p style="font-size: 15px; line-height: 1.6; color: #334155;">
        Congratulations! Your enrollment for the <strong>{$workshopTitle}</strong> is successfully confirmed. We are excited to have you join this live practical masterclass.
      </p>

      <div class="details-box">
        <div class="details-row"><span class="label">Enrollment ID:</span><span class="val">{$uuid}</span></div>
        <div class="details-row"><span class="label">Date:</span><span class="val">{$workshopDate}</span></div>
        <div class="details-row"><span class="label">Time:</span><span class="val">{$workshopTime}</span></div>
        <div class="details-row"><span class="label">Language:</span><span class="val">Tamil (Simple & Practical)</span></div>
        <div class="details-row"><span class="label">Platform:</span><span class="val">Live on Zoom</span></div>
        <div class="details-row"><span class="label">Amount Paid:</span><span class="val" style="color: #059669;">₹49.00 (Paid)</span></div>
      </div>

      <h3 style="font-size: 16px; color: #0f172a; margin-bottom: 6px;">Next Step: Join Private VIP WhatsApp Community</h3>
      <p style="font-size: 14px; color: #64748b; margin-top: 0; line-height: 1.5;">
        The Zoom meeting join link, workbook checklists, and reminder alerts will be delivered inside this official community:
      </p>

      <a href="{$whatsappCommunity}" class="whatsapp-btn" target="_blank">
        👉 Join VIP WhatsApp Group Now
      </a>

      <div class="attachment-notice">
        <strong>📎 Official Tax Invoice Attached:</strong> Your official GST tax invoice and workshop admission pass PDF has been generated and attached to this email for your records.
      </div>
    </div>
    <div class="email-footer">
      <p style="margin: 0 0 6px;">Digital Crazy Hub • Govt. of India MSME Registered</p>
      <p style="margin: 0;">Support: <a href="mailto:{$supportEmail}" style="color: #60a5fa;">{$supportEmail}</a> | WhatsApp: +{$supportPhone}</p>
    </div>
  </div>
</body>
</html>
HTML;

        // If PDF file exists, build multipart MIME attachment
        $boundary = "==Multipart_Boundary_x" . md5((string)time()) . "x";

        $headers = [
            "MIME-Version: 1.0",
            "From: Digital Crazy Hub <" . $supportEmail . ">",
            "Reply-To: " . $supportEmail,
            "X-Mailer: PHP/" . phpversion(),
            "Content-Type: multipart/mixed; boundary=\"$boundary\""
        ];

        // Part 1: HTML Body
        $message = "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=\"UTF-8\"\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $htmlBody . "\r\n\r\n";

        // Part 2: PDF Attachment
        if (file_exists($pdfPath)) {
            $pdfContent = file_get_contents($pdfPath);
            $base64Pdf = chunk_split(base64_encode($pdfContent));
            $filename = "DigitalCrazyHub_Invoice_{$uuid}.pdf";

            $message .= "--$boundary\r\n";
            $message .= "Content-Type: application/pdf; name=\"$filename\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
            $message .= $base64Pdf . "\r\n\r\n";
        }

        $message .= "--$boundary--";

        // Log email to file for verification & resilient audit trail
        $emailDir = __DIR__ . '/../data/emails';
        @mkdir($emailDir, 0755, true);
        $logEntry = "[" . date('Y-m-d H:i:s') . "] To: $toEmail | UUID: $uuid | PDF Attached: " . (file_exists($pdfPath) ? "YES" : "NO") . "\n";
        @file_put_contents($emailDir . '/email_log.log', $logEntry, FILE_APPEND);
        @file_put_contents($emailDir . "/invoice_email_{$uuid}.eml", implode("\r\n", $headers) . "\r\n\r\n" . $message);

        // Attempt sending via native mail()
        $sent = false;
        try {
            $sent = @mail($toEmail, $subject, $message, implode("\r\n", $headers));
        } catch (Throwable $e) {
            error_log("Mailer mail() exception: " . $e->getMessage());
        }

        return $sent;
    }
}

