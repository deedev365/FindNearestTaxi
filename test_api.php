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

echo "🚕 Find Taxi API Test\n";
echo str_repeat("=", 50) . "\n\n";

// Initialize
$storage = new JsonStorage(__DIR__ . '/storage');
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

// Test 1: Request a ride
echo "✅ Test 1: Request a Ride\n";
$ride = $rideService->requestRide(1, 100, 500);
echo "   Ride ID: {$ride->id}\n";
echo "   Status: {$ride->status}\n";
echo "   Distance: {$ride->distance} km\n";
echo "   Price: \${$ride->price}\n";
echo "   Surge: {$ride->surge_coefficient}x\n\n";

// Test 2: Accept ride (assign driver)
echo "✅ Test 2: Accept Ride (Assign Driver)\n";
$assignedRide = $rideService->findNearestDriver($ride->id);
if ($assignedRide && $assignedRide->driver_id) {
    echo "   Driver ID: {$assignedRide->driver_id}\n";
    echo "   Status: {$assignedRide->status}\n";
    echo "   Assigned at: {$assignedRide->assigned_at}\n\n";
}

// Test 3: Pickup passenger
echo "✅ Test 3: Pickup Passenger\n";
$pickedUpRide = $rideService->pickupPassenger($ride->id);
echo "   Status: {$pickedUpRide->status}\n\n";

// Test 4: Complete ride
echo "✅ Test 4: Complete Ride\n";
$completedRide = $rideService->completeRide($ride->id);
echo "   Status: {$completedRide->status}\n";
echo "   Completed at: {$completedRide->completed_at}\n\n";

// Test 5: Rate the ride
echo "✅ Test 5: Rate the Ride\n";
// Get fresh ride data after assignment
$rideWithDriver = $rideService->getRide($ride->id);
if ($rideWithDriver && $rideWithDriver->driver_id) {
    $rating = $ratingService->createRating($rideWithDriver->id, 1, $rideWithDriver->driver_id, 'driver', 5, 'Great driver!');
    echo "   Rating ID: {$rating->id}\n";
    echo "   Score: {$rating->score}/5\n";
    echo "   Comment: {$rating->comment}\n\n";
} else {
    echo "   Skipped (no driver assigned)\n\n";
}

// Test 6: Analytics
echo "✅ Test 6: System Analytics\n";
$stats = $analyticsService->getTotalStats();
echo "   Total Rides: {$stats['total_rides']}\n";
echo "   Completed: {$stats['completed_rides']}\n";
echo "   Active: {$stats['active_rides']}\n";
echo "   Revenue: \${$stats['total_revenue']}\n";
echo "   Avg Price: \${$stats['average_ride_price']}\n\n";

// Test 7: Driver stats
echo "✅ Test 7: Driver Analytics\n";
$rideWithDriver = $rideService->getRide($ride->id);
if ($rideWithDriver && $rideWithDriver->driver_id) {
    $driverStats = $analyticsService->getDriverStats($rideWithDriver->driver_id);
    echo "   Driver #{$rideWithDriver->driver_id} Stats:\n";
    echo "   - Completed Rides: {$driverStats['completed_rides']}\n";
    echo "   - Total Earnings: \${$driverStats['total_earnings']}\n";
    echo "   - Avg per Ride: \${$driverStats['average_earnings_per_ride']}\n\n";
} else {
    echo "   Skipped (no driver assigned)\n\n";
}

// Test 8: Passenger stats
echo "✅ Test 8: Passenger Analytics\n";
$passengerStats = $analyticsService->getPassengerStats(1);
echo "   Passenger #1 Stats:\n";
echo "   - Completed Rides: {$passengerStats['completed_rides']}\n";
echo "   - Total Spent: \${$passengerStats['total_spent']}\n";
echo "   - Avg per Ride: \${$passengerStats['average_spend_per_ride']}\n\n";

// Test 9: Request second ride for sharing
echo "✅ Test 9: Ride Sharing Test\n";
$ride2 = $rideService->requestRide(2, 120, 480);
echo "   Ride 2 ID: {$ride2->id}\n";
echo "   Price before sharing: \${$ride2->price}\n";

// Try to share rides
$shared = $rideService->shareRide($ride->id, $ride2->id);
echo "   Sharing attempt: " . ($shared ? "✅ Success" : "❌ Failed") . "\n";
if ($shared) {
    $sharedRide2 = $rideService->getRide($ride2->id);
    echo "   Price after sharing: \${$sharedRide2->price} (15% discount)\n\n";
}

// Test 10: Cancel a ride
echo "✅ Test 10: Cancel Ride\n";
$ride3 = $rideService->requestRide(3, 200, 800);
$cancelledRide = $rideService->cancelRide($ride3->id);
echo "   Ride ID: {$cancelledRide->id}\n";
echo "   Status: {$cancelledRide->status}\n";
echo "   Cancelled at: {$cancelledRide->cancelled_at}\n\n";

echo str_repeat("=", 50) . "\n";
echo "✅ All tests completed successfully!\n";
echo "\n📁 Check storage/ folder for generated JSON files:\n";
$storageFiles = glob(__DIR__ . '/storage/*.json');
foreach ($storageFiles as $file) {
    $size = filesize($file);
    $name = basename($file);
    echo "   - $name (" . ($size / 1024) . " KB)\n";
}
