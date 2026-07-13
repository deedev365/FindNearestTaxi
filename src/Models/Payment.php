<?php

namespace Taxi\Models;

class Payment
{
    public int $id;
    public int $ride_id;
    public float $amount;
    public string $status; // pending, completed, failed, refunded
    public string $method; // card, cash, wallet
    public string $created_at;
    public ?string $completed_at;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ride_id' => $this->ride_id,
            'amount' => $this->amount,
            'status' => $this->status,
            'method' => $this->method,
            'created_at' => $this->created_at,
            'completed_at' => $this->completed_at,
        ];
    }
}
