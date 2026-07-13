<?php

namespace Taxi\Models;

class Ride
{
    public int $id;
    public int $passenger_id;
    public ?int $driver_id;
    public string $status; // pending, assigned, picked_up, in_progress, completed, cancelled
    public int $pickup_position;
    public int $dropoff_position;
    public float $distance;
    public float $price;
    public float $base_fare;
    public float $price_per_km;
    public ?float $surge_coefficient;
    public int $zone_id;
    public ?int $shared_with_ride_id;
    public string $created_at;
    public ?string $assigned_at;
    public ?string $completed_at;
    public ?string $cancelled_at;

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
            'passenger_id' => $this->passenger_id,
            'driver_id' => $this->driver_id,
            'status' => $this->status,
            'pickup_position' => $this->pickup_position,
            'dropoff_position' => $this->dropoff_position,
            'distance' => $this->distance,
            'price' => $this->price,
            'base_fare' => $this->base_fare,
            'price_per_km' => $this->price_per_km,
            'surge_coefficient' => $this->surge_coefficient,
            'zone_id' => $this->zone_id,
            'shared_with_ride_id' => $this->shared_with_ride_id,
            'created_at' => $this->created_at,
            'assigned_at' => $this->assigned_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
        ];
    }
}
