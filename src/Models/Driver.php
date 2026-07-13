<?php

namespace Taxi\Models;

class Driver
{
    public int $id;
    public string $name;
    public int $zone_id;
    public string $status; // online, offline, on_break
    public float $rating;
    public int $total_rides;
    public int $position;
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
            'zone_id' => $this->zone_id,
            'status' => $this->status,
            'rating' => $this->rating,
            'total_rides' => $this->total_rides,
            'position' => $this->position,
            'created_at' => $this->created_at,
        ];
    }
}
