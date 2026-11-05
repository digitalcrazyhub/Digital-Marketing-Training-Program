<?php
/**
 * Digital Crazy Hub - Create Razorpay Order API Endpoint
 * Method: POST
 * Input: JSON or x-www-form-urlencoded { name, phone, email, city, profession }
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Allow CORS if configured or same origin
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

try {
    $config = require __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/Razorpay.php';

    // Parse input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    // Sanitize & Validate fields
    $name = trim((string)($input['name'] ?? ''));
    $phone = trim((string)($input['phone'] ?? ''));
    $email = trim((string)($input['email'] ?? ''));
    $city = trim((string)($input['city'] ?? ''));
    $profession = trim((string)($input['profession'] ?? ''));

    // Validation rules
    if (empty($name) || mb_strlen($name) < 2) {
        throw new InvalidArgumentException('Please provide your full name (minimum 2 characters).');
    }
    if (mb_strlen($name) > 100) {
        throw new InvalidArgumentException('Name is too long (maximum 100 characters).');
    }

    // Clean phone number (strip spaces, dashes, +91 or 91 country code prefix if 12 digits)
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($cleanPhone) === 12 && str_starts_with($cleanPhone, '91')) {
        $cleanPhone = substr($cleanPhone, 2);
    } elseif (strlen($cleanPhone) === 11 && str_starts_with($cleanPhone, '0')) {
        $cleanPhone = substr($cleanPhone, 1);
    }

    if (!preg_match('/^[6-9]\d{9}$/', $cleanPhone)) {
        throw new InvalidArgumentException('Please provide a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Please provide a valid email address.');
    }

    $city = mb_substr(strip_tags($city), 0, 80);
    $profession = mb_substr(strip_tags($profession), 0, 80);

    // Save pending enrollment
    $enrollmentData = [
        'name'       => $name,
        'phone'      => $cleanPhone,
        'email'      => strtolower($email),
        'city'       => $city,
        'profession' => $profession,
        'amount'     => (float)$config['workshop']['price'],
        'currency'   => $config['workshop']['currency']
    ];

    $uuid = DatabaseManager::createEnrollment($enrollmentData);

    // Create Razorpay Order
    $razorpay = new RazorpayClient($config['razorpay']['key_id'], $config['razorpay']['key_secret']);
    $amountPaise = (int)$config['workshop']['amount_in_paise'];
    $currency = (string)$config['workshop']['currency'];

    $notes = [
        'enrollment_uuid' => $uuid,
        'name'            => $name,
        'phone'           => $cleanPhone,
        'email'           => $email,
        'workshop'        => $config['workshop']['title']
    ];

    $order = $razorpay->createOrder($amountPaise, $currency, $uuid, $notes);

    // Update order ID in database
    DatabaseManager::setOrderId($uuid, $order['id']);
    DatabaseManager::logEvent($order['id'], 'ORDER_CREATED', [
        'uuid'   => $uuid,
        'amount' => $amountPaise,
        'email'  => $email,
        'phone'  => $cleanPhone
    ]);

    echo json_encode([
        'success'         => true,
        'order_id'        => $order['id'],
        'amount'          => $amountPaise,
        'currency'        => $currency,
        'key_id'          => $config['razorpay']['key_id'],
        'enrollment_uuid' => $uuid,
        'is_mock'         => !empty($order['is_test_simulated']),
        'user'            => [
            'name'  => $name,
            'phone' => $cleanPhone,
            'email' => $email
        ],
        'workshop'        => [
            'title'    => $config['workshop']['title'],
            'date'     => $config['workshop']['date'],
            'time'     => $config['workshop']['time'],
            'price'    => $config['workshop']['display_price'],
            'platform' => $config['workshop']['platform']
        ]
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    error_log('create-order error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to initiate enrollment order. ' . $e->getMessage()]);
}

