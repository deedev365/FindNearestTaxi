<?php

namespace Taxi\Services;

use Taxi\Storage\JsonStorage;
use Taxi\Models\Ride;

class RideService
{
    private JsonStorage $storage;
    private ZoneService $zoneService;
    private DriverService $driverService;
    private PassengerService $passengerService;
    private PricingService $pricingService;
    private PaymentService $paymentService;
    private AnalyticsService $analyticsService;

    public function __construct(
        JsonStorage $storage,
        ZoneService $zoneService,
        DriverService $driverService,
        PassengerService $passengerService,
        PricingService $pricingService,
        PaymentService $paymentService,
        AnalyticsService $analyticsService
    ) {
        $this->storage = $storage;
        $this->zoneService = $zoneService;
        $this->driverService = $driverService;
        $this->passengerService = $passengerService;
        $this->pricingService = $pricingService;
        $this->paymentService = $paymentService;
        $this->analyticsService = $analyticsService;
    }

    public function requestRide(int $passengerId, int $pickupPosition, int $dropoffPosition): Ride
    {
        $passenger = $this->passengerService->getPassenger($passengerId);
        if (!$passenger) {
            throw new \Exception("Passenger not found: $passengerId");
        }

        $zone = $this->zoneService->getZoneByPosition($pickupPosition);
        if (!$zone) {
            throw new \Exception("Zone not found for position: $pickupPosition");
        }

        $activeRides = $this->analyticsService->getActiveRidesCount();
        $surgeCoefficient = $this->pricingService->calculateSurgeCoefficient($activeRides);
        $pricing = $this->pricingService->calculatePrice($pickupPosition, $dropoffPosition, $zone->id, $surgeCoefficient);

        $id = $this->storage->getNextId('rides.json');

        $rideData = [
            'id' => $id,
            'passenger_id' => $passengerId,
            'driver_id' => null,
            'status' => 'pending',
            'pickup_position' => $pickupPosition,
            'dropoff_position' => $dropoffPosition,
            'distance' => $pricing['distance'],
            'price' => $pricing['total_price'],
            'base_fare' => $pricing['base_fare'],
            'price_per_km' => $pricing['price_per_km'],
            'surge_coefficient' => $surgeCoefficient,
            'zone_id' => $zone->id,
            'shared_with_ride_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'assigned_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ];

        $this->storage->append('rides.json', $rideData);

        // Create payment
        $this->paymentService->createPayment($id, $pricing['total_price'], $passenger->payment_method);

        return new Ride($rideData);
    }

    public function findNearestDriver(int $rideId): ?Ride
    {
        $ride = $this->getRide($rideId);
        if (!$ride || $ride->driver_id !== null) {
            return null;
        }

        $availableDrivers = $this->driverService->getAvailableDriversInZone($ride->zone_id, $ride->pickup_position);

        if (empty($availableDrivers)) {
            return null;
        }

        $selectedDriver = $availableDrivers[0];

        $this->storage->update('rides.json', $rideId, [
            'driver_id' => $selectedDriver->id,
            'status' => 'assigned',
            'assigned_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->getRide($rideId);
    }

    public function pickupPassenger(int $rideId): Ride
    {
        $ride = $this->getRide($rideId);
        if (!$ride || $ride->status !== 'assigned') {
            throw new \Exception("Ride cannot be picked up in status: {$ride->status}");
        }

        $this->storage->update('rides.json', $rideId, [
            'status' => 'picked_up',
        ]);

        return $this->getRide($rideId);
    }

    public function completeRide(int $rideId): Ride
    {
        $ride = $this->getRide($rideId);
        if (!$ride) {
            throw new \Exception("Ride not found: $rideId");
        }

        if (!in_array($ride->status, ['picked_up', 'in_progress'])) {
            throw new \Exception("Ride cannot be completed in status: {$ride->status}");
        }

        // Process payment
        $payment = $this->paymentService->getRidePayment($rideId);
        if ($payment) {
            $this->paymentService->processPayment($payment->id);
        }

        $this->storage->update('rides.json', $rideId, [
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        // Update driver and passenger ride counts
        if ($ride->driver_id) {
            $driver = $this->driverService->getDriver($ride->driver_id);
            if ($driver) {
                $this->driverService->updateDriver($ride->driver_id, [
                    'total_rides' => $driver->total_rides + 1,
                ]);
            }
        }

        if ($ride->passenger_id) {
            $passenger = $this->passengerService->getPassenger($ride->passenger_id);
            if ($passenger) {
                $this->passengerService->updatePassenger($ride->passenger_id, [
                    'total_rides' => $passenger->total_rides + 1,
                ]);
            }
        }

        return $this->getRide($rideId);
    }

    public function cancelRide(int $rideId, string $reason = 'user_request'): Ride
    {
        $ride = $this->getRide($rideId);
        if (!$ride || $ride->status === 'completed' || $ride->status === 'cancelled') {
            throw new \Exception("Ride cannot be cancelled in status: {$ride->status}");
        }

        // Refund payment
        $payment = $this->paymentService->getRidePayment($rideId);
        if ($payment && $payment->status === 'completed') {
            $this->paymentService->refundPayment($payment->id);
        }

        $this->storage->update('rides.json', $rideId, [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->getRide($rideId);
    }

    public function getRide(int $rideId): ?Ride
    {
        $ride = $this->storage->findById('rides.json', $rideId);
        return $ride ? new Ride($ride) : null;
    }

    public function getRidesByPassenger(int $passengerId): array
    {
        $rides = $this->storage->findAll('rides.json', fn($r) => $r['passenger_id'] === $passengerId);
        return array_map(fn($r) => new Ride($r), $rides);
    }

    public function getRidesByDriver(int $driverId): array
    {
        $rides = $this->storage->findAll('rides.json', fn($r) => $r['driver_id'] === $driverId);
        return array_map(fn($r) => new Ride($r), $rides);
    }

    public function getAllRides(int $limit = 50): array
    {
        $rides = $this->storage->read('rides.json');
        usort($rides, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        if ($limit > 0) {
            $rides = array_slice($rides, 0, $limit);
        }

        return array_map(fn($r) => new Ride($r), $rides);
    }

    public function shareRide(int $rideId, int $otherRideId): bool
    {
        $ride = $this->getRide($rideId);
        $otherRide = $this->getRide($otherRideId);

        if (!$ride || !$otherRide) {
            throw new \Exception("One or both rides not found");
        }

        // Check if rides can be shared (same zone, similar route, both pending)
        if (
            $ride->zone_id !== $otherRide->zone_id ||
            $ride->status !== 'pending' ||
            $otherRide->status !== 'pending'
        ) {
            return false;
        }

        // Check if pickup/dropoff are within reasonable distance
        $pickupDistance = abs($ride->pickup_position - $otherRide->pickup_position);
        $dropoffDistance = abs($ride->dropoff_position - $otherRide->dropoff_position);

        if ($pickupDistance > 100 || $dropoffDistance > 100) {
            return false;
        }

        // Share the rides - reduce price for both
        $sharedDiscount = 0.15; // 15% discount for sharing
        $newPrice1 = $ride->price * (1 - $sharedDiscount);
        $newPrice2 = $otherRide->price * (1 - $sharedDiscount);

        $this->storage->update('rides.json', $rideId, [
            'shared_with_ride_id' => $otherRideId,
            'price' => round($newPrice1, 2),
        ]);

        $this->storage->update('rides.json', $otherRideId, [
            'shared_with_ride_id' => $rideId,
            'price' => round($newPrice2, 2),
        ]);

        // Update payments
        $payment1 = $this->paymentService->getRidePayment($rideId);
        $payment2 = $this->paymentService->getRidePayment($otherRideId);

        if ($payment1) {
            $this->storage->update('payments.json', $payment1->id, [
                'amount' => round($newPrice1, 2),
            ]);
        }

        if ($payment2) {
            $this->storage->update('payments.json', $payment2->id, [
                'amount' => round($newPrice2, 2),
            ]);
        }

        return true;
    }
}
