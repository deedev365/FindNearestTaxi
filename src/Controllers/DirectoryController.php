<?php

namespace Taxi\Controllers;

use Taxi\Services\DriverService;
use Taxi\Services\PassengerService;
use Taxi\Services\ZoneService;
use Taxi\Utils\ApiResponse;

class DirectoryController
{
    private DriverService $driverService;
    private PassengerService $passengerService;
    private ZoneService $zoneService;

    public function __construct(
        DriverService $driverService,
        PassengerService $passengerService,
        ZoneService $zoneService
    ) {
        $this->driverService = $driverService;
        $this->passengerService = $passengerService;
        $this->zoneService = $zoneService;
    }

    public function getDrivers(): void
    {
        try {
            $drivers = $this->driverService->getAllDrivers();
            ApiResponse::success(['drivers' => array_map(fn($d) => $d->toArray(), $drivers)]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getPassengers(): void
    {
        try {
            $passengers = $this->passengerService->getAllPassengers();
            ApiResponse::success(['passengers' => array_map(fn($p) => $p->toArray(), $passengers)]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getZones(): void
    {
        try {
            $zones = $this->zoneService->getAllZones();
            ApiResponse::success(['zones' => array_map(fn($z) => $z->toArray(), $zones)]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }
}
