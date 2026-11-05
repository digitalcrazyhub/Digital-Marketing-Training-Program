<?php
/**
 * Digital Crazy Hub - Razorpay Payment Service
 * Handles server-side order generation, HMAC-SHA256 signature verification, and webhook signature verification.
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\RazorpayConfig;
use Exception;

class RazorpayService {
    private string $keyId;
    private string $keySecret;
    private string $webhookSecret;

    public function __construct(?string $keyId = null, ?string $keySecret = null, ?string $webhookSecret = null) {
        $cfg = RazorpayConfig::get();
        $this->keyId = $keyId ?? $cfg['key_id'];
        $this->keySecret = $keySecret ?? $cfg['key_secret'];
        $this->webhookSecret = $webhookSecret ?? $cfg['webhook_secret'];
    }

    public function createOrder(int $amountInPaise, string $currency, string $receipt, array $notes = []): array {
        // If mock test credentials are active and mock is desired
        if ($this->keyId === 'rzp_test_placeholder_key' || empty($this->keyId)) {
            return [
                'id'       => 'order_test_' . bin2hex(random_bytes(8)),
                'amount'   => $amountInPaise,
                'currency' => $currency,
                'receipt'  => $receipt,
                'status'   => 'created',
                'is_mock'  => true
            ];
        }

        $url = 'https://api.razorpay.com/v1/orders';
        $payload = [
            'amount'   => $amountInPaise,
            'currency' => $currency,
            'receipt'  => $receipt,
            'notes'    => $notes
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->keyId . ':' . $this->keySecret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("Razorpay cURL connection error: " . $curlError);
        }

        $decoded = json_decode((string)$response, true);

        if ($httpCode !== 200 && $httpCode !== 201) {
            $msg = $decoded['error']['description'] ?? 'Razorpay API returned HTTP ' . $httpCode;
            throw new Exception("Razorpay Order Failed: " . $msg);
        }

        return $decoded;
    }

    /**
     * Strict HMAC-SHA256 Payment Signature Verification
     */
    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool {
        if (empty($this->keySecret) || empty($orderId) || empty($paymentId) || empty($signature)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->keySecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Webhook HMAC-SHA256 Signature Verification
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool {
        if (empty($this->webhookSecret) || empty($signature)) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expected, $signature);
    }

    public function getKeyId(): string {
        return $this->keyId;
    }
}

