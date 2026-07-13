<?php

require_once __DIR__ . '/vendor/autoload.php';

use Taxi\Storage\JsonStorage;
use Taxi\Services\{
    ZoneService,
    DriverService,
    PassengerService,
    PricingService,
    PaymentService,
    RatingService,
    AnalyticsService,
    RideService
};
use Taxi\Controllers\{
    RideController,
    RatingController,
    PaymentController,
    AnalyticsController,
    DirectoryController
};
use Taxi\Utils\{Router, ApiResponse};

// Initialize storage
$storage = new JsonStorage(__DIR__ . '/storage');

// Initialize services
$zoneService = new ZoneService($storage);
$driverService = new DriverService($storage);
$passengerService = new PassengerService($storage);
$pricingService = new PricingService($zoneService);
$paymentService = new PaymentService($storage);
$ratingService = new RatingService($storage);
$analyticsService = new AnalyticsService($storage);
$rideService = new RideService(
    $storage,
    $zoneService,
    $driverService,
    $passengerService,
    $pricingService,
    $paymentService,
    $analyticsService
);

// Initialize controllers
$rideController = new RideController($rideService);
$ratingController = new RatingController($ratingService, $driverService, $passengerService);
$paymentController = new PaymentController($paymentService);
$analyticsController = new AnalyticsController($analyticsService);
$directoryController = new DirectoryController($driverService, $passengerService, $zoneService);

// Setup router
$router = new Router();

// Ride endpoints
$router->post('/api/ride/request', fn() => $rideController->requestRide());
$router->get('/api/ride/{id}', fn($id) => $rideController->getRide($id));
$router->post('/api/ride/{id}/accept', fn($id) => $rideController->acceptRide($id));
$router->post('/api/ride/{id}/pickup', fn($id) => $rideController->pickupPassenger($id));
$router->post('/api/ride/{id}/complete', fn($id) => $rideController->completeRide($id));
$router->post('/api/ride/{id}/cancel', fn($id) => $rideController->cancelRide($id));
$router->post('/api/ride/share', fn() => $rideController->shareRide());
$router->get('/api/passenger/{id}/rides', fn($id) => $rideController->getPassengerRides($id));
$router->get('/api/driver/{id}/rides', fn($id) => $rideController->getDriverRides($id));
$router->get('/api/rides', fn() => $rideController->getAllRides());

// Directory endpoints (read-only lists for dashboards)
$router->get('/api/drivers', fn() => $directoryController->getDrivers());
$router->get('/api/passengers', fn() => $directoryController->getPassengers());
$router->get('/api/zones', fn() => $directoryController->getZones());

// Rating endpoints
$router->post('/api/ride/{id}/rate', fn($id) => $ratingController->rateRide($id));
$router->get('/api/ride/{id}/ratings', fn($id) => $ratingController->getRideRatings($id));
$router->get('/api/driver/{id}/rating', fn($id) => $ratingController->getDriverRating($id));
$router->get('/api/passenger/{id}/rating', fn($id) => $ratingController->getPassengerRating($id));

// Payment endpoints
$router->post('/api/payment/{id}/process', fn($id) => $paymentController->processPayment($id));
$router->get('/api/payment/{id}', fn($id) => $paymentController->getPayment($id));
$router->post('/api/payment/{id}/refund', fn($id) => $paymentController->refundPayment($id));

// Analytics endpoints
$router->get('/api/analytics', fn() => $analyticsController->getTotalStats());
$router->get('/api/analytics/driver/{id}', fn($id) => $analyticsController->getDriverStats($id));
$router->get('/api/analytics/passenger/{id}', fn($id) => $analyticsController->getPassengerStats($id));

// Dispatch
$router->dispatch();
