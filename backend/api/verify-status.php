<?php
/**
 * Digital Crazy Hub - Verify Enrollment Status API Endpoint
 * Method: GET
 * Query Params: enrollment_id (or uuid), token
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

try {
    $config = require __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';

    $enrollmentId = trim((string)($_GET['enrollment_id'] ?? ($_GET['uuid'] ?? '')));
    $token = trim((string)($_GET['token'] ?? ''));

    if (empty($enrollmentId) || empty($token)) {
        http_response_code(400);
        echo json_encode(['verified' => false, 'message' => 'Missing enrollment verification parameters.']);
        exit;
    }

    $record = DatabaseManager::getEnrollment($enrollmentId);

    if (!$record) {
        http_response_code(404);
        echo json_encode(['verified' => false, 'message' => 'Enrollment record not found.']);
        exit;
    }

    $storedToken = (string)($record['verification_token'] ?? '');
    $isTokenValid = !empty($storedToken) && hash_equals($storedToken, $token);
    $status = strtoupper((string)($record['payment_status'] ?? ($record['status'] ?? 'NOT PAID')));
    $isPaid = ($status === 'PAID');

    // Only authorized PAID users can view confirmed enrollment details
    if (!$isTokenValid || !$isPaid) {
        http_response_code(403);
        echo json_encode([
            'verified'       => false,
            'payment_status' => $status,
            'message'        => 'Verification required. Only confirmed paid enrollments can view this page.'
        ]);
        exit;
    }

    $eid = $record['enrollment_id'] ?? $enrollmentId;
    $amountPaid = '₹' . number_format((float)($record['amount_paid'] ?? 49.00), 2);

    $whatsappUrl = $config['whatsapp_community_url'] ?? 'https://chat.whatsapp.com/digitalcrazyhub-dm-workshop';
    $downloadUrl = "backend/api/download-invoice.php?enrollment_id=" . urlencode($eid) . "&token=" . urlencode($token);

    // Google Calendar URL generator
    $startDate = '20261105T133000Z'; // 7:00 PM IST = 13:30 UTC
    $endDate = '20261105T163000Z';   // 10:00 PM IST = 16:30 UTC
    $calTitle = urlencode('Digital Crazy Hub - ₹49 AI-Integrated Digital Marketing Workshop');
    $calDetails = urlencode("100% Practical Digital Marketing Workshop on Zoom. Check your email or VIP WhatsApp community for Zoom link: {$whatsappUrl}");
    $gcalUrl = "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$calTitle}&dates={$startDate}/{$endDate}&details={$calDetails}&location=Zoom+Live";

    echo json_encode([
        'verified'   => true,
        'enrollment' => [
            'enrollment_id'   => $eid,
            'uuid'            => $eid,
            'name'            => $record['name'],
            'email'           => $record['email'],
            'phone'           => $record['phone'],
            'location'        => $record['location'] ?? ($record['city'] ?? ''),
            'current_profile' => $record['current_profile'] ?? ($record['profession'] ?? ''),
            'workshop_date'   => $record['workshop_date'] ?? $config['workshop']['date'],
            'workshop_time'   => $record['workshop_time'] ?? $config['workshop']['time'],
            'payment_id'      => $record['payment_id'] ?? ($record['razorpay_payment_id'] ?? 'PAID'),
            'amount_paid'     => $amountPaid,
            'amount'          => $amountPaid,
            'payment_status'  => 'PAID'
        ],
        'workshop'   => [
            'title'    => $config['workshop']['title'],
            'date'     => $config['workshop']['date'],
            'day'      => $config['workshop']['day'],
            'time'     => $config['workshop']['time'],
            'language' => $config['workshop']['language'],
            'platform' => $config['workshop']['platform']
        ],
        'links'      => [
            'whatsapp_community' => $whatsappUrl,
            'google_calendar'    => $gcalUrl,
            'download_invoice'   => $downloadUrl
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log("verify-status error: " . $e->getMessage());
    echo json_encode(['verified' => false, 'message' => 'Status verification failed.']);
}
