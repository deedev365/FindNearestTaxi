<?php

namespace Taxi\Services;

use Taxi\Storage\JsonStorage;
use Taxi\Models\Rating;

class RatingService
{
    private JsonStorage $storage;

    public function __construct(JsonStorage $storage)
    {
        $this->storage = $storage;
    }

    public function createRating(int $rideId, int $fromUserId, int $toUserId, string $userType, int $score, ?string $comment = null): Rating
    {
        if ($score < 1 || $score > 5) {
            throw new \Exception("Rating score must be between 1 and 5");
        }

        $id = $this->storage->getNextId('ratings.json');

        $rating = [
            'id' => $id,
            'ride_id' => $rideId,
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'user_type' => $userType,
            'score' => $score,
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->storage->append('ratings.json', $rating);
        return new Rating($rating);
    }

    public function getRideRatings(int $rideId): array
    {
        $ratings = $this->storage->findAll('ratings.json', function ($r) use ($rideId) {
            return $r['ride_id'] === $rideId;
        });

        return array_map(fn($r) => new Rating($r), $ratings);
    }

    public function hasRated(int $rideId, int $fromUserId): bool
    {
        $ratings = $this->storage->findAll('ratings.json', function ($r) use ($rideId, $fromUserId) {
            return $r['ride_id'] === $rideId && $r['from_user_id'] === $fromUserId;
        });

        return !empty($ratings);
    }

    public function getUserRatings(int $userId, string $userType = 'driver'): array
    {
        $ratings = $this->storage->findAll('ratings.json', function ($r) use ($userId, $userType) {
            return $r['to_user_id'] === $userId && $r['user_type'] === $userType;
        });

        return array_map(fn($r) => new Rating($r), $ratings);
    }

    public function getAverageRating(int $userId, string $userType = 'driver'): float
    {
        $ratings = $this->getUserRatings($userId, $userType);
        if (empty($ratings)) {
            return 0.0;
        }

        $total = array_sum(array_map(fn($r) => $r->score, $ratings));
        return round($total / count($ratings), 2);
    }
}
