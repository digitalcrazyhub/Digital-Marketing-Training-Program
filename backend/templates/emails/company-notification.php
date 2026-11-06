<?php
/**
 * Digital Crazy Hub - Company Lead Notification Email Template
 *
 * Variables provided:
 * @var array $enrollment
 * @var array $appConfig
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

$subject = "New Paid Enrollment – {$studentName} – {$enrollmentId}";

$plain = <<<TEXT
New Paid Workshop Enrollment – {$enrollment['name']}

Enrollment ID: {$enrollment['enrollment_id']}
Student Name: {$enrollment['name']}
Email: {$enrollment['email']}
Phone: {$enrollment['phone']}
Location: {$enrollment['location']}
Current Profile: {$enrollment['current_profile']}
Payment ID: {$paymentId}
Workshop Date: {$workshopDate}
Workshop Time: {$workshopTime}
Amount Paid: {$amountPaid}
Payment Status: PAID
TEXT;

$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{$subject}</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; padding: 20px; color: #0f172a; margin: 0; }
    .box { max-width: 580px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; box-shadow: 0 4px 12px rgba(15,23,42,0.06); }
    h2 { color: #166534; margin-top: 0; font-size: 18px; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 14px; }
    td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
    td.label { color: #64748b; font-weight: 600; width: 40%; }
    td.val { font-weight: 700; color: #0f172a; }
  </style>
</head>
<body>
  <div class="box">
    <h2>✔ New Paid Workshop Enrollment</h2>
    <p style="color: #475569; font-size: 14px; line-height: 1.5;">A new student has successfully completed payment for the ₹49 Digital Marketing Workshop:</p>
    <table>
      <tr><td class="label">Enrollment ID:</td><td class="val">{$enrollmentId}</td></tr>
      <tr><td class="label">Student Name:</td><td class="val">{$studentName}</td></tr>
      <tr><td class="label">Email:</td><td class="val"><a href="mailto:{$email}">{$email}</a></td></tr>
      <tr><td class="label">Phone:</td><td class="val"><a href="https://wa.me/{$phone}">{$phone}</a></td></tr>
      <tr><td class="label">Location:</td><td class="val">{$location}</td></tr>
      <tr><td class="label">Current Profile:</td><td class="val">{$profile}</td></tr>
      <tr><td class="label">Payment ID:</td><td class="val">{$paymentId}</td></tr>
      <tr><td class="label">Workshop Date:</td><td class="val">{$workshopDate}</td></tr>
      <tr><td class="label">Workshop Time:</td><td class="val">{$workshopTime}</td></tr>
      <tr><td class="label">Amount Paid:</td><td class="val" style="color: #166534;">{$amountPaid}</td></tr>
      <tr><td class="label">Payment Status:</td><td class="val" style="color: #166534;">PAID</td></tr>
    </table>
  </div>
</body>
</html>
HTML;

return [
    'subject' => $subject,
    'plain'   => $plain,
    'html'    => $html
];

