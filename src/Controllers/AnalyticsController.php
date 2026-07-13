<?php

namespace Taxi\Controllers;

use Taxi\Services\AnalyticsService;
use Taxi\Utils\ApiResponse;

class AnalyticsController
{
    private AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function getTotalStats(): void
    {
        try {
            $stats = $this->analyticsService->getTotalStats();
            ApiResponse::success(['stats' => $stats]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getDriverStats(int $driverId): void
    {
        try {
            $stats = $this->analyticsService->getDriverStats($driverId);
            ApiResponse::success(['stats' => $stats]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getPassengerStats(int $passengerId): void
    {
        try {
            $stats = $this->analyticsService->getPassengerStats($passengerId);
            ApiResponse::success(['stats' => $stats]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }
}
