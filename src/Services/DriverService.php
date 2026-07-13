<?php

namespace Taxi\Services;

use Taxi\Storage\JsonStorage;
use Taxi\Models\Driver;

class DriverService
{
    private JsonStorage $storage;

    public function __construct(JsonStorage $storage)
    {
        $this->storage = $storage;
        $this->initializeDrivers();
    }

    private function initializeDrivers(): void
    {
        if (!empty($this->storage->read('drivers.json'))) {
            return;
        }

        $drivers = [];
        $driverNames = ['Alice', 'Bob', 'Charlie', 'Diana', 'Edward', 'Fiona', 'George', 'Hannah'];

        foreach ($driverNames as $index => $name) {
            $drivers[] = [
                'id' => $index + 1,
                'name' => $name,
                'zone_id' => (($index % 3) + 1),
                'status' => 'online',
                'rating' => 4.5 + (rand(0, 50) / 100),
                'total_rides' => rand(10, 500),
                'position' => rand(0, 1000),
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        $this->storage->write('drivers.json', $drivers);
    }

    public function getDriver(int $driverId): ?Driver
    {
        $driver = $this->storage->findById('drivers.json', $driverId);
        return $driver ? new Driver($driver) : null;
    }

    public function getAvailableDriversInZone(int $zoneId, int $passengerPosition): array
    {
        $drivers = $this->storage->findAll('drivers.json', function ($driver) use ($zoneId) {
            return $driver['zone_id'] === $zoneId && $driver['status'] === 'online';
        });

        $drivers = array_map(fn($d) => new Driver($d), $drivers);

        usort($drivers, function (Driver $a, Driver $b) use ($passengerPosition) {
            $distanceA = abs($a->position - $passengerPosition);
            $distanceB = abs($b->position - $passengerPosition);
            return $distanceA <=> $distanceB;
        });

        return $drivers;
    }

    public function updateDriver(int $driverId, array $updates): bool
    {
        return $this->storage->update('drivers.json', $driverId, $updates);
    }

    public function updateRating(int $driverId, int $newRatingScore): bool
    {
        $driver = $this->storage->findById('drivers.json', $driverId);
        if (!$driver) {
            return false;
        }

        $oldRating = $driver['rating'];
        $totalRides = $driver['total_rides'] + 1;
        $newRating = (($oldRating * $driver['total_rides']) + $newRatingScore) / $totalRides;

        return $this->updateDriver($driverId, [
            'rating' => round($newRating, 2),
            'total_rides' => $totalRides,
        ]);
    }

    public function getAllDrivers(): array
    {
        $drivers = $this->storage->read('drivers.json');
        return array_map(fn($d) => new Driver($d), $drivers);
    }
}
