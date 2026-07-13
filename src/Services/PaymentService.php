<?php

namespace Taxi\Services;

use Taxi\Storage\JsonStorage;
use Taxi\Models\Payment;

class PaymentService
{
    private JsonStorage $storage;

    public function __construct(JsonStorage $storage)
    {
        $this->storage = $storage;
    }

    public function createPayment(int $rideId, float $amount, string $method): Payment
    {
        $id = $this->storage->getNextId('payments.json');

        $payment = [
            'id' => $id,
            'ride_id' => $rideId,
            'amount' => round($amount, 2),
            'status' => 'pending',
            'method' => $method,
            'created_at' => date('Y-m-d H:i:s'),
            'completed_at' => null,
        ];

        $this->storage->append('payments.json', $payment);
        return new Payment($payment);
    }

    public function processPayment(int $paymentId): bool
    {
        // Simulating payment processing - always succeeds
        if (rand(0, 100) < 5) { // 5% failure rate
            return $this->storage->update('payments.json', $paymentId, [
                'status' => 'failed',
            ]);
        }

        return $this->storage->update('payments.json', $paymentId, [
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function refundPayment(int $paymentId): bool
    {
        $payment = $this->storage->findById('payments.json', $paymentId);
        if (!$payment || $payment['status'] !== 'completed') {
            return false;
        }

        return $this->storage->update('payments.json', $paymentId, [
            'status' => 'refunded',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getPayment(int $paymentId): ?Payment
    {
        $payment = $this->storage->findById('payments.json', $paymentId);
        return $payment ? new Payment($payment) : null;
    }

    public function getRidePayment(int $rideId): ?Payment
    {
        $data = $this->storage->read('payments.json');
        foreach ($data as $payment) {
            if ($payment['ride_id'] === $rideId) {
                return new Payment($payment);
            }
        }
        return null;
    }
}
