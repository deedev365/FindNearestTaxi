<?php

namespace Taxi\Services;

class PricingService
{
    private ZoneService $zoneService;

    public function __construct(ZoneService $zoneService)
    {
        $this->zoneService = $zoneService;
    }

    public function calculatePrice(
        int $pickupPosition,
        int $dropoffPosition,
        int $zoneId,
        float $surgeCoefficient = 1.0
    ): array {
        $zone = $this->zoneService->getZoneById($zoneId);
        if (!$zone) {
            throw new \Exception("Zone not found: $zoneId");
        }

        $distance = abs($dropoffPosition - $pickupPosition);
        $baseFare = $zone->base_fare;
        $pricePerKm = $zone->price_per_km;

        $totalPrice = ($baseFare + ($distance * $pricePerKm)) * $surgeCoefficient;

        return [
            'base_fare' => $baseFare,
            'price_per_km' => $pricePerKm,
            'distance' => $distance,
            'surge_coefficient' => $surgeCoefficient,
            'total_price' => round($totalPrice, 2),
        ];
    }

    public function calculateSurgeCoefficient(int $activeRides): float
    {
        if ($activeRides < 5) {
            return 1.0;
        }
        if ($activeRides < 10) {
            return 1.25;
        }
        if ($activeRides < 20) {
            return 1.5;
        }
        return 2.0;
    }
}
