<?php

namespace Taxi\Controllers;

use Taxi\Services\RideService;
use Taxi\Utils\ApiResponse;

class RideController
{
    private RideService $rideService;

    public function __construct(RideService $rideService)
    {
        $this->rideService = $rideService;
    }

    public function requestRide(): void
    {
        try {
            $input = ApiResponse::getJsonInput();

            if (empty($input['passenger_id']) || empty($input['pickup_position']) || empty($input['dropoff_position'])) {
                ApiResponse::error('Missing required fields: passenger_id, pickup_position, dropoff_position', 400);
                return;
            }

            $ride = $this->rideService->requestRide(
                (int)$input['passenger_id'],
                (int)$input['pickup_position'],
                (int)$input['dropoff_position']
            );

            ApiResponse::success(['ride' => $ride->toArray()], 201);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getRide(int $rideId): void
    {
        try {
            $ride = $this->rideService->getRide($rideId);

            if (!$ride) {
                ApiResponse::error("Ride not found: $rideId", 404);
                return;
            }

            ApiResponse::success(['ride' => $ride->toArray()]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function acceptRide(int $rideId): void
    {
        try {
            $ride = $this->rideService->findNearestDriver($rideId);

            if (!$ride) {
                ApiResponse::error("No available drivers for ride: $rideId", 400);
                return;
            }

            ApiResponse::success(['ride' => $ride->toArray()]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function pickupPassenger(int $rideId): void
    {
        try {
            $ride = $this->rideService->pickupPassenger($rideId);
            ApiResponse::success(['ride' => $ride->toArray()]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function completeRide(int $rideId): void
    {
        try {
            $ride = $this->rideService->completeRide($rideId);
            ApiResponse::success(['ride' => $ride->toArray()]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function cancelRide(int $rideId): void
    {
        try {
            $input = ApiResponse::getJsonInput();
            $reason = $input['reason'] ?? 'user_request';

            $ride = $this->rideService->cancelRide($rideId, $reason);
            ApiResponse::success(['ride' => $ride->toArray()]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function shareRide(): void
    {
        try {
            $input = ApiResponse::getJsonInput();

            if (empty($input['ride_id']) || empty($input['other_ride_id'])) {
                ApiResponse::error('Missing required fields: ride_id, other_ride_id', 400);
                return;
            }

            $success = $this->rideService->shareRide(
                (int)$input['ride_id'],
                (int)$input['other_ride_id']
            );

            if (!$success) {
                ApiResponse::error('Rides cannot be shared', 400);
                return;
            }

            $ride1 = $this->rideService->getRide((int)$input['ride_id']);
            $ride2 = $this->rideService->getRide((int)$input['other_ride_id']);

            ApiResponse::success([
                'ride1' => $ride1->toArray(),
                'ride2' => $ride2->toArray(),
            ]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getPassengerRides(int $passengerId): void
    {
        try {
            $rides = $this->rideService->getRidesByPassenger($passengerId);
            ApiResponse::success(['rides' => array_map(fn($r) => $r->toArray(), $rides)]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getDriverRides(int $driverId): void
    {
        try {
            $rides = $this->rideService->getRidesByDriver($driverId);
            ApiResponse::success(['rides' => array_map(fn($r) => $r->toArray(), $rides)]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getAllRides(): void
    {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $rides = $this->rideService->getAllRides($limit);
            ApiResponse::success(['rides' => array_map(fn($r) => $r->toArray(), $rides)]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }
}
