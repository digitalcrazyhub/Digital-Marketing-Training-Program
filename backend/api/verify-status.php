<?php
/**
 * Digital Crazy Hub - Verify Enrollment Status API Endpoint
 * Method: GET
 * Params: uuid, token
 * Used by thank-you.html to validate that the user has a confirmed PAID enrollment.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['verified' => false, 'message' => 'Method Not Allowed']);
    exit;
}

try {
    $config = require __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';

    $uuid  = trim((string)($_GET['uuid'] ?? ''));
    $token = trim((string)($_GET['token'] ?? ''));

    if (empty($uuid) || empty($token)) {
        echo json_encode([
            'verified' => false,
            'message'  => 'Missing enrollment verification credentials.'
        ]);
        exit;
    }

    $record = DatabaseManager::getEnrollment($uuid);

    if (!$record) {
        echo json_encode([
            'verified' => false,
            'message'  => 'Enrollment record not found.'
        ]);
        exit;
    }

    // Verify token & status
    if (($record['status'] ?? '') !== 'PAID' || !hash_equals((string)($record['verification_token'] ?? ''), $token)) {
        echo json_encode([
            'verified' => false,
            'message'  => 'Invalid or unverified enrollment session.'
        ]);
        exit;
    }

    // Mask phone & email for security
    $rawPhone = (string)($record['phone'] ?? '');
    $maskedPhone = strlen($rawPhone) >= 4 ? '******' . substr($rawPhone, -4) : $rawPhone;

    $rawEmail = (string)($record['email'] ?? '');
    $parts = explode('@', $rawEmail);
    $maskedEmail = (count($parts) === 2 && strlen($parts[0]) > 2)
        ? substr($parts[0], 0, 2) . '***@' . $parts[1]
        : $rawEmail;

    // Generate Google Calendar Add Event Link
    // Event: Nov 5, 2026, 19:00 - 22:00 IST (UTC: 20261105T133000Z / 20261105T163000Z)
    $calStart = '20261105T133000Z';
    $calEnd   = '20261105T163000Z';
    $calTitle = urlencode('Digital Crazy Hub - ₹49 AI-Integrated Digital Marketing Workshop');
    $calDetails = urlencode("100% Practical Digital Marketing Workshop Live on Zoom.\nMeeting link will be shared in the VIP WhatsApp Community.\nSupport: info@digitalcrazyhub.com");
    $calLocation = urlencode('Live on Zoom');
    $gcalUrl = "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$calTitle}&dates={$calStart}/{$calEnd}&details={$calDetails}&location={$calLocation}";

    echo json_encode([
        'verified'       => true,
        'enrollment'     => [
            'uuid'       => $record['enrollment_uuid'],
            'name'       => $record['name'],
            'masked_phone' => $maskedPhone,
            'masked_email' => $maskedEmail,
            'payment_id' => $record['razorpay_payment_id'] ?? 'PAID',
            'paid_at'    => $record['paid_at'] ?? date('Y-m-d H:i:s'),
            'amount'     => $config['workshop']['display_price']
        ],
        'workshop'       => [
            'title'      => $config['workshop']['title'],
            'date'       => $config['workshop']['date'],
            'day'        => $config['workshop']['day'],
            'time'       => $config['workshop']['time'],
            'language'   => $config['workshop']['language'],
            'platform'   => $config['workshop']['platform'],
            'price'      => $config['workshop']['display_price']
        ],
        'links'          => [
            'whatsapp_community' => $config['app']['whatsapp_community_url'],
            'google_calendar'    => $gcalUrl,
            'support_whatsapp'   => 'https://wa.me/' . $config['app']['whatsapp_support_number'] . '?text=' . urlencode('Hi Digital Crazy Hub, I enrolled with ID: ' . $record['enrollment_uuid'])
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['verified' => false, 'message' => 'Internal server error: ' . $e->getMessage()]);
}

