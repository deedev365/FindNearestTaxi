<?php

namespace Taxi\Controllers;

use Taxi\Services\RatingService;
use Taxi\Services\DriverService;
use Taxi\Services\PassengerService;
use Taxi\Utils\ApiResponse;

class RatingController
{
    private RatingService $ratingService;
    private DriverService $driverService;
    private PassengerService $passengerService;

    public function __construct(
        RatingService $ratingService,
        DriverService $driverService,
        PassengerService $passengerService
    ) {
        $this->ratingService = $ratingService;
        $this->driverService = $driverService;
        $this->passengerService = $passengerService;
    }

    public function rateRide(int $rideId): void
    {
        try {
            $input = ApiResponse::getJsonInput();

            if (empty($input['from_user_id']) || empty($input['to_user_id']) || empty($input['score']) || empty($input['user_type'])) {
                ApiResponse::error('Missing required fields: from_user_id, to_user_id, score, user_type', 400);
                return;
            }

            $score = (int)$input['score'];
            if ($score < 1 || $score > 5) {
                ApiResponse::error('Score must be between 1 and 5', 400);
                return;
            }

            if ($this->ratingService->hasRated($rideId, (int)$input['from_user_id'])) {
                ApiResponse::error('This user has already rated this ride', 400);
                return;
            }

            $rating = $this->ratingService->createRating(
                $rideId,
                (int)$input['from_user_id'],
                (int)$input['to_user_id'],
                $input['user_type'],
                $score,
                $input['comment'] ?? null
            );

            // Update user rating
            if ($input['user_type'] === 'driver') {
                $this->driverService->updateRating((int)$input['to_user_id'], $score);
            } else {
                $this->passengerService->updateRating((int)$input['to_user_id'], $score);
            }

            ApiResponse::success(['rating' => $rating->toArray()], 201);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getRideRatings(int $rideId): void
    {
        try {
            $ratings = $this->ratingService->getRideRatings($rideId);
            ApiResponse::success(['ratings' => array_map(fn($r) => $r->toArray(), $ratings)]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getDriverRating(int $driverId): void
    {
        try {
            $average = $this->ratingService->getAverageRating($driverId, 'driver');
            $ratings = $this->ratingService->getUserRatings($driverId, 'driver');

            ApiResponse::success([
                'driver_id' => $driverId,
                'average_rating' => $average,
                'total_ratings' => count($ratings),
                'ratings' => array_map(fn($r) => $r->toArray(), $ratings),
            ]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getPassengerRating(int $passengerId): void
    {
        try {
            $average = $this->ratingService->getAverageRating($passengerId, 'passenger');
            $ratings = $this->ratingService->getUserRatings($passengerId, 'passenger');

            ApiResponse::success([
                'passenger_id' => $passengerId,
                'average_rating' => $average,
                'total_ratings' => count($ratings),
                'ratings' => array_map(fn($r) => $r->toArray(), $ratings),
            ]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }
}
