<?php

namespace Taxi\Services;

use Taxi\Storage\JsonStorage;
use Taxi\Models\Zone;

class ZoneService
{
    private JsonStorage $storage;

    public function __construct(JsonStorage $storage)
    {
        $this->storage = $storage;
        $this->initializeZones();
    }

    private function initializeZones(): void
    {
        if (!empty($this->storage->read('zones.json'))) {
            return;
        }

        $zones = [
            [
                'id' => 1,
                'name' => 'Downtown',
                'min_position' => 0,
                'max_position' => 333,
                'base_fare' => 5.0,
                'price_per_km' => 1.5,
            ],
            [
                'id' => 2,
                'name' => 'Midtown',
                'min_position' => 334,
                'max_position' => 666,
                'base_fare' => 4.0,
                'price_per_km' => 1.2,
            ],
            [
                'id' => 3,
                'name' => 'Uptown',
                'min_position' => 667,
                'max_position' => 1000,
                'base_fare' => 3.5,
                'price_per_km' => 1.0,
            ],
        ];

        $this->storage->write('zones.json', $zones);
    }

    public function getZoneByPosition(int $position): ?Zone
    {
        $zones = $this->storage->read('zones.json');
        foreach ($zones as $zone) {
            if ($position >= $zone['min_position'] && $position <= $zone['max_position']) {
                return new Zone($zone);
            }
        }
        return null;
    }

    public function getZoneById(int $zoneId): ?Zone
    {
        $zone = $this->storage->findById('zones.json', $zoneId);
        return $zone ? new Zone($zone) : null;
    }

    public function getAllZones(): array
    {
        $zones = $this->storage->read('zones.json');
        return array_map(fn($z) => new Zone($z), $zones);
    }
}
