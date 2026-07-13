<?php

namespace Taxi\Controllers;

use Taxi\Services\PaymentService;
use Taxi\Utils\ApiResponse;

class PaymentController
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function processPayment(int $paymentId): void
    {
        try {
            $success = $this->paymentService->processPayment($paymentId);

            if (!$success) {
                ApiResponse::error('Payment processing failed', 400);
                return;
            }

            $payment = $this->paymentService->getPayment($paymentId);
            ApiResponse::success(['payment' => $payment->toArray()]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getPayment(int $paymentId): void
    {
        try {
            $payment = $this->paymentService->getPayment($paymentId);

            if (!$payment) {
                ApiResponse::error("Payment not found: $paymentId", 404);
                return;
            }

            ApiResponse::success(['payment' => $payment->toArray()]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function refundPayment(int $paymentId): void
    {
        try {
            $success = $this->paymentService->refundPayment($paymentId);

            if (!$success) {
                ApiResponse::error('Payment refund failed', 400);
                return;
            }

            $payment = $this->paymentService->getPayment($paymentId);
            ApiResponse::success(['payment' => $payment->toArray()]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }
}
