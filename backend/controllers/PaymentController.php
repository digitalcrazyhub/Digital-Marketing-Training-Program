<?php
/**
 * Digital Crazy Hub - Payment Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\EnrollmentService;
use InvalidArgumentException;
use Throwable;

class PaymentController {
    /**
     * POST /api/create-order.php
     */
    public function createOrder(array $input): void {
        try {
            $result = EnrollmentService::createEnrollmentAndOrder($input);
            Response::json($result, 200);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (Throwable $e) {
            error_log("PaymentController::createOrder error: " . $e->getMessage());
            Response::error('Unable to initiate workshop order. ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/verify-payment.php
     */
    public function verifyPayment(array $input): void {
        try {
            $result = EnrollmentService::processPaymentVerification($input);
            $statusCode = ($result['success'] ?? false) ? 200 : 400;
            Response::json($result, $statusCode);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (Throwable $e) {
            error_log("PaymentController::verifyPayment error: " . $e->getMessage());
            Response::error('Payment verification failed. Please contact support.', 500);
        }
    }
}

