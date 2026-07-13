<?php

namespace Taxi\Models;

class Passenger
{
    public int $id;
    public string $name;
    public float $rating;
    public int $total_rides;
    public string $payment_method; // card, cash, wallet
    public string $created_at;

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
            'name' => $this->name,
            'rating' => $this->rating,
            'total_rides' => $this->total_rides,
            'payment_method' => $this->payment_method,
            'created_at' => $this->created_at,
        ];
    }
}
