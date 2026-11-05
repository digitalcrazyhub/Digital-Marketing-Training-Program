<?php
/**
 * Digital Crazy Hub - Student Confirmation Email Template
 *
 * Variables provided:
 * @var array $enrollment
 * @var array $appConfig
 * @var string $pdfFilename
 */

declare(strict_types=1);

$studentName  = htmlspecialchars($enrollment['name'] ?? 'Learner', ENT_QUOTES, 'UTF-8');
$enrollmentId = htmlspecialchars($enrollment['enrollment_id'] ?? '', ENT_QUOTES, 'UTF-8');
$email        = htmlspecialchars($enrollment['email'] ?? '', ENT_QUOTES, 'UTF-8');
$phone        = htmlspecialchars($enrollment['phone'] ?? '', ENT_QUOTES, 'UTF-8');
$location     = htmlspecialchars($enrollment['location'] ?? '', ENT_QUOTES, 'UTF-8');
$profile      = htmlspecialchars($enrollment['current_profile'] ?? '', ENT_QUOTES, 'UTF-8');
$workshopDate = htmlspecialchars($enrollment['workshop_date'] ?? $appConfig['workshop']['date'], ENT_QUOTES, 'UTF-8');
$workshopTime = htmlspecialchars($enrollment['workshop_time'] ?? $appConfig['workshop']['time'], ENT_QUOTES, 'UTF-8');
$paymentId    = htmlspecialchars($enrollment['payment_id'] ?? 'PAID', ENT_QUOTES, 'UTF-8');
$amountPaid   = '₹' . number_format((float)($enrollment['amount_paid'] ?? 49.00), 2);
$whatsappUrl  = htmlspecialchars($appConfig['whatsapp_community_url'], ENT_QUOTES, 'UTF-8');
$supportEmail = htmlspecialchars($appConfig['support_email'], ENT_QUOTES, 'UTF-8');

$subject = "Your Enrollment is Confirmed – Digital Crazy Hub";

$plain = <<<TEXT
Dear {$enrollment['name']},

Congratulations!
Your enrollment for the Digital Marketing Training Workshop has been successfully confirmed.

Enrollment Details:
Enrollment ID: {$enrollment['enrollment_id']}
Student Name: {$enrollment['name']}
Email: {$enrollment['email']}
Phone: {$enrollment['phone']}
Location: {$enrollment['location']}
Current Profile: {$enrollment['current_profile']}

Workshop Details:
Workshop Date: {$workshopDate}
Workshop Time: {$workshopTime}

Payment Details:
Payment ID: {$paymentId}
Amount Paid: {$amountPaid}
Payment Status: PAID

Your seat is officially confirmed.
Please find your payment invoice attached to this email as a PDF.

VIP WhatsApp Group Join Link:
{$appConfig['whatsapp_community_url']}

Thank you for choosing Digital Crazy Hub.

Regards,
Digital Crazy Hub Team
https://training.digitalcrazyhub.com
TEXT;

$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{$subject}</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px 0; color: #0f172a; }
    .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 12px rgba(15,23,42,0.06); }
    .email-header { background: #0f172a; padding: 26px 30px; text-align: center; border-bottom: 3px solid #2563eb; }
    .email-header h1 { color: #ffffff; margin: 0; font-size: 20px; font-weight: 800; letter-spacing: 0.04em; }
    .email-body { padding: 32px 30px; }
    .congrats-badge { display: inline-block; background: #ecfdf5; color: #059669; font-weight: 700; font-size: 13px; padding: 4px 12px; border-radius: 20px; border: 1px solid #a7f3d0; margin-bottom: 16px; text-transform: uppercase; }
    .salutation { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 10px; }
    .details-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px 20px; margin: 24px 0; }
    .details-row { display: flex; justify-content: space-between; padding: 7px 0; font-size: 14px; border-bottom: 1px dashed #e2e8f0; }
    .details-row:last-child { border-bottom: none; }
    .label { color: #64748b; font-weight: 500; }
    .val { color: #0f172a; font-weight: 700; }
    .whatsapp-btn { display: inline-block; background: #25d366; color: #ffffff !important; text-decoration: none; padding: 14px 28px; font-size: 15px; font-weight: 700; border-radius: 8px; text-align: center; margin: 20px 0; }
    .attachment-notice { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px 16px; font-size: 13px; color: #1e40af; margin-top: 20px; }
    .email-footer { background: #0f172a; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="email-header">
      <h1>DIGITAL CRAZY HUB</h1>
    </div>
    <div class="email-body">
      <div class="congrats-badge">✔ Seat Confirmed</div>
      <div class="salutation">Dear {$studentName},</div>
      <p style="font-size: 15px; line-height: 1.6; color: #334155; margin-top: 0;">
        <strong>Congratulations!</strong> Your enrollment for the <strong>Digital Marketing Training Workshop</strong> has been successfully confirmed.
      </p>

      <div class="details-box">
        <div style="font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.05em;">Enrollment & Payment Details</div>
        <div class="details-row"><span class="label">Enrollment ID:</span><span class="val">{$enrollmentId}</span></div>
        <div class="details-row"><span class="label">Student Name:</span><span class="val">{$studentName}</span></div>
        <div class="details-row"><span class="label">Email:</span><span class="val">{$email}</span></div>
        <div class="details-row"><span class="label">Phone:</span><span class="val">{$phone}</span></div>
        <div class="details-row"><span class="label">Location:</span><span class="val">{$location}</span></div>
        <div class="details-row"><span class="label">Current Profile:</span><span class="val">{$profile}</span></div>
        <div class="details-row"><span class="label">Workshop Date:</span><span class="val">{$workshopDate}</span></div>
        <div class="details-row"><span class="label">Workshop Time:</span><span class="val">{$workshopTime}</span></div>
        <div class="details-row"><span class="label">Payment ID:</span><span class="val">{$paymentId}</span></div>
        <div class="details-row"><span class="label">Amount Paid:</span><span class="val" style="color: #059669;">{$amountPaid}</span></div>
        <div class="details-row"><span class="label">Payment Status:</span><span class="val" style="color: #059669;">PAID</span></div>
      </div>

      <p style="font-size: 14px; font-weight: 600; color: #0f172a; margin-bottom: 6px;">Your seat is officially confirmed!</p>
      <p style="font-size: 13px; color: #64748b; margin-top: 0; line-height: 1.5;">
        The Zoom meeting join link, digital checklist, and workshop alerts are shared exclusively in our private VIP WhatsApp Community:
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
      <p style="margin: 0;">Support: <a href="mailto:{$supportEmail}" style="color: #60a5fa;">{$supportEmail}</a> | <a href="https://training.digitalcrazyhub.com" style="color: #60a5fa;">https://training.digitalcrazyhub.com</a></p>
    </div>
  </div>
</body>
</html>
HTML;

return [
    'subject' => $subject,
    'plain'   => $plain,
    'html'    => $html
];

