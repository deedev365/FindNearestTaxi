<?php

namespace Taxi\Models;

class Zone
{
    public int $id;
    public string $name;
    public int $min_position;
    public int $max_position;
    public float $base_fare;
    public float $price_per_km;

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
            'min_position' => $this->min_position,
            'max_position' => $this->max_position,
            'base_fare' => $this->base_fare,
            'price_per_km' => $this->price_per_km,
        ];
    }
}
