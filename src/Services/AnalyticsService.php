<?php

namespace Taxi\Services;

use Taxi\Storage\JsonStorage;

class AnalyticsService
{
    private JsonStorage $storage;

    public function __construct(JsonStorage $storage)
    {
        $this->storage = $storage;
    }

    public function getTotalStats(): array
    {
        $rides = $this->storage->read('rides.json');
        $payments = $this->storage->read('payments.json');
        $drivers = $this->storage->read('drivers.json');
        $passengers = $this->storage->read('passengers.json');

        $completedRides = array_filter($rides, fn($r) => $r['status'] === 'completed');
        $totalRevenue = array_sum(array_map(function ($ride) use ($payments) {
            foreach ($payments as $p) {
                if ($p['ride_id'] === $ride['id'] && $p['status'] === 'completed') {
                    return $p['amount'];
                }
            }
            return 0;
        }, $completedRides));

        return [
            'total_rides' => count($rides),
            'completed_rides' => count($completedRides),
            'pending_rides' => count(array_filter($rides, fn($r) => $r['status'] === 'pending')),
            'active_rides' => count(array_filter($rides, fn($r) => in_array($r['status'], ['assigned', 'picked_up', 'in_progress']))),
            'cancelled_rides' => count(array_filter($rides, fn($r) => $r['status'] === 'cancelled')),
            'total_revenue' => round($totalRevenue, 2),
            'total_drivers' => count($drivers),
            'online_drivers' => count(array_filter($drivers, fn($d) => $d['status'] === 'online')),
            'total_passengers' => count($passengers),
            'average_ride_price' => count($completedRides) > 0
                ? round($totalRevenue / count($completedRides), 2)
                : 0,
        ];
    }

    public function getDriverStats(int $driverId): array
    {
        $rides = $this->storage->findAll('rides.json', fn($r) => $r['driver_id'] === $driverId);
        $payments = $this->storage->read('payments.json');

        $completedRides = array_filter($rides, fn($r) => $r['status'] === 'completed');
        $totalEarnings = array_sum(array_map(function ($ride) use ($payments) {
            foreach ($payments as $p) {
                if ($p['ride_id'] === $ride['id'] && $p['status'] === 'completed') {
                    return $p['amount'];
                }
            }
            return 0;
        }, $completedRides));

        $totalDistance = array_sum(array_map(fn($r) => $r['distance'], $completedRides));

        return [
            'total_rides' => count($rides),
            'completed_rides' => count($completedRides),
            'cancelled_rides' => count(array_filter($rides, fn($r) => $r['status'] === 'cancelled')),
            'total_earnings' => round($totalEarnings, 2),
            'total_distance' => $totalDistance,
            'average_earnings_per_ride' => count($completedRides) > 0
                ? round($totalEarnings / count($completedRides), 2)
                : 0,
        ];
    }

    public function getPassengerStats(int $passengerId): array
    {
        $rides = $this->storage->findAll('rides.json', fn($r) => $r['passenger_id'] === $passengerId);
        $payments = $this->storage->read('payments.json');

        $completedRides = array_filter($rides, fn($r) => $r['status'] === 'completed');
        $totalSpent = array_sum(array_map(function ($ride) use ($payments) {
            foreach ($payments as $p) {
                if ($p['ride_id'] === $ride['id'] && $p['status'] === 'completed') {
                    return $p['amount'];
                }
            }
            return 0;
        }, $completedRides));

        $totalDistance = array_sum(array_map(fn($r) => $r['distance'], $completedRides));

        return [
            'total_rides' => count($rides),
            'completed_rides' => count($completedRides),
            'cancelled_rides' => count(array_filter($rides, fn($r) => $r['status'] === 'cancelled')),
            'total_spent' => round($totalSpent, 2),
            'total_distance' => $totalDistance,
            'average_spend_per_ride' => count($completedRides) > 0
                ? round($totalSpent / count($completedRides), 2)
                : 0,
        ];
    }

    public function getActiveRidesCount(): int
    {
        $rides = $this->storage->read('rides.json');
        $activeStatuses = ['pending', 'assigned', 'picked_up', 'in_progress'];
        return count(array_filter($rides, fn($r) => in_array($r['status'], $activeStatuses)));
    }
}
