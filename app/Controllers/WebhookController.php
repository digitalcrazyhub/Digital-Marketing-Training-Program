<?php
/**
 * Digital Crazy Hub - Webhook Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\EnrollmentService;
use Throwable;

class WebhookController {
    /**
     * POST /api/webhook.php
     */
    public function handleWebhook(string $payload, string $signature): void {
        if (empty($payload)) {
            Response::error('Empty webhook payload.', 400);
        }

        try {
            $result = EnrollmentService::processWebhook($payload, $signature);
            if (!($result['success'] ?? false)) {
                Response::error($result['error'] ?? 'Webhook verification failed', $result['code'] ?? 400);
            }
            Response::json($result, 200);
        } catch (Throwable $e) {
            error_log("WebhookController exception: " . $e->getMessage());
            Response::error('Internal webhook error', 500);
        }
    }
}

