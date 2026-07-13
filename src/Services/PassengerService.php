<?php

namespace Taxi\Services;

use Taxi\Storage\JsonStorage;
use Taxi\Models\Passenger;

class PassengerService
{
    private JsonStorage $storage;

    public function __construct(JsonStorage $storage)
    {
        $this->storage = $storage;
        $this->initializePassengers();
    }

    private function initializePassengers(): void
    {
        if (!empty($this->storage->read('passengers.json'))) {
            return;
        }

        $passengers = [];
        $passengerNames = ['John', 'Jane', 'Mike', 'Sarah', 'David', 'Emma', 'Alex', 'Lisa'];

        foreach ($passengerNames as $index => $name) {
            $passengers[] = [
                'id' => $index + 1,
                'name' => $name,
                'rating' => 4.0 + (rand(0, 100) / 100),
                'total_rides' => rand(5, 300),
                'payment_method' => ['card', 'cash', 'wallet'][rand(0, 2)],
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        $this->storage->write('passengers.json', $passengers);
    }

    public function getPassenger(int $passengerId): ?Passenger
    {
        $passenger = $this->storage->findById('passengers.json', $passengerId);
        return $passenger ? new Passenger($passenger) : null;
    }

    public function updatePassenger(int $passengerId, array $updates): bool
    {
        return $this->storage->update('passengers.json', $passengerId, $updates);
    }

    public function updateRating(int $passengerId, int $newRatingScore): bool
    {
        $passenger = $this->storage->findById('passengers.json', $passengerId);
        if (!$passenger) {
            return false;
        }

        $oldRating = $passenger['rating'];
        $totalRides = $passenger['total_rides'] + 1;
        $newRating = (($oldRating * $passenger['total_rides']) + $newRatingScore) / $totalRides;

        return $this->updatePassenger($passengerId, [
            'rating' => round($newRating, 2),
            'total_rides' => $totalRides,
        ]);
    }

    public function getAllPassengers(): array
    {
        $passengers = $this->storage->read('passengers.json');
        return array_map(fn($p) => new Passenger($p), $passengers);
    }
}
