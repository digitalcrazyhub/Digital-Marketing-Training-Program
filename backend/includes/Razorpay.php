<?php
/**
 * Digital Crazy Hub - Native Razorpay API Integration Helper
 * Zero external dependencies. Uses standard PHP cURL & OpenSSL HMAC-SHA256.
 */

class RazorpayClient {
    private string $keyId;
    private string $keySecret;
    private string $apiBase = 'https://api.razorpay.com/v1';

    public function __construct(string $keyId, string $keySecret) {
        $this->keyId = $keyId;
        $this->keySecret = $keySecret;
    }

    /**
     * Create Razorpay Order
     */
    public function createOrder(int $amountPaise, string $currency, string $receipt, array $notes = []): array {
        // If placeholder credentials are used in development mode, create a simulated test order
        if ($this->keyId === 'rzp_test_placeholder_key' || empty($this->keyId) || empty($this->keySecret)) {
            $simulatedOrderId = 'order_test_' . substr(md5(uniqid($receipt, true)), 0, 14);
            return [
                'id'       => $simulatedOrderId,
                'entity'   => 'order',
                'amount'   => $amountPaise,
                'currency' => $currency,
                'receipt'  => $receipt,
                'status'   => 'created',
                'is_test_simulated' => true
            ];
        }

        $payload = [
            'amount'   => $amountPaise,
            'currency' => $currency,
            'receipt'  => $receipt,
            'notes'    => $notes,
            'payment_capture' => 1 // auto capture
        ];

        $ch = curl_init($this->apiBase . '/orders');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_USERPWD        => "{$this->keyId}:{$this->keySecret}",
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'User-Agent: DigitalCrazyHub-Workshop/1.0'
            ],
            CURLOPT_TIMEOUT        => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("Razorpay cURL connection error: " . $curlError);
        }

        $decoded = json_decode($response, true);

        if ($httpCode !== 200 && $httpCode !== 201) {
            $msg = $decoded['error']['description'] ?? 'Razorpay API returned HTTP ' . $httpCode;
            throw new Exception("Razorpay Order Failed: " . $msg);
        }

        return $decoded;
    }

    /**
     * Verify Payment Signature server-side using HMAC SHA-256
     */
    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool {
        // If simulated test mode
        if (str_starts_with($orderId, 'order_test_') && ($this->keyId === 'rzp_test_placeholder_key' || empty($this->keySecret))) {
            return !empty($paymentId) && !empty($signature);
        }

        $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->keySecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Webhook Signature
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $webhookSecret): bool {
        if (empty($webhookSecret) || empty($signature)) {
            return false;
        }
        $expected = hash_hmac('sha256', $payload, $webhookSecret);
        return hash_equals($expected, $signature);
    }
}

