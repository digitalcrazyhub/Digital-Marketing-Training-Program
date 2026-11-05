<?php
/**
 * Digital Crazy Hub - Validator Helper
 */

declare(strict_types=1);

namespace App\Helpers;

use InvalidArgumentException;

class Validator {
    public static function validateEnrollment(array $input): array {
        $name = Security::sanitize((string)($input['name'] ?? ''), 100);
        $phoneRaw = (string)($input['phone'] ?? '');
        $emailRaw = (string)($input['email'] ?? '');
        $location = Security::sanitize((string)($input['location'] ?? ($input['city'] ?? '')), 100);
        $profile = Security::sanitize((string)($input['current_profile'] ?? ($input['profession'] ?? '')), 100);

        if (empty($name) || mb_strlen($name) < 2) {
            throw new InvalidArgumentException('Please enter your full name (at least 2 characters).');
        }

        $phone = Security::validateIndianPhone($phoneRaw);
        if (empty($phone)) {
            throw new InvalidArgumentException('Please enter a valid 10-digit Indian WhatsApp mobile number starting with 6, 7, 8, or 9.');
        }

        $email = Security::validateEmail($emailRaw);
        if (empty($email)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }

        if (empty($location)) {
            throw new InvalidArgumentException('Location is mandatory. Please enter your city or location.');
        }

        if (empty($profile)) {
            throw new InvalidArgumentException('Current Profile is mandatory. Please select your current profile.');
        }

        return [
            'name'            => $name,
            'email'           => $email,
            'phone'           => $phone,
            'location'        => $location,
            'current_profile' => $profile
        ];
    }

    public static function validatePaymentVerification(array $input): array {
        $orderId = trim((string)($input['razorpay_order_id'] ?? ($input['order_id'] ?? '')));
        $paymentId = trim((string)($input['razorpay_payment_id'] ?? ($input['payment_id'] ?? '')));
        $signature = trim((string)($input['razorpay_signature'] ?? ($input['signature'] ?? '')));
        $enrollmentId = trim((string)($input['enrollment_id'] ?? ($input['enrollment_uuid'] ?? '')));

        if (empty($orderId) || empty($paymentId) || empty($signature)) {
            throw new InvalidArgumentException('Missing required payment parameters.');
        }

        return [
            'order_id'      => $orderId,
            'payment_id'    => $paymentId,
            'signature'     => $signature,
            'enrollment_id' => $enrollmentId
        ];
    }
}

