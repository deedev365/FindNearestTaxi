<?php

class Taxi
{
    private int $passenger;
    private array $cars;

    public function __construct(int $passengerLocation, int $countTaxiCarsInPark, bool $isAllCarsBusy = false)
    {
        $this->passenger = $passengerLocation;
        $this->cars = $this->getCars($countTaxiCarsInPark, $isAllCarsBusy);
    }

    private function getCars(int $countTaxiCarsInPark, bool $isAllCarsBusy = false): array
    {
        $cars = [];

        for ($carId = 1; $carId <= $countTaxiCarsInPark; $carId++) {
            $isCarFree = $isAllCarsBusy ? false : (bool) rand(0, 1);
            
            $cars[] = new TaxiCar($carId, rand(0, 1000), $isCarFree);
        }

        return $cars;
    }

    public function findCar(): void
    {
        $carIdForPassenger = $this->getCarIdForPassenger();

        $this->printCarsResult($carIdForPassenger);
    }

    private function getCarIdForPassenger(): ?int
    {
        $carIdForPassenger = null;
        $minDistance = PHP_INT_MAX;

        foreach ($this->cars as $car) {
            $distance = abs($this->passenger - $car->getPosition());
            $car->setDistance($distance);

            if (!$car->isFree()) {
                continue;
            }

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $carIdForPassenger = $car->getId();
            }
        }

        return $carIdForPassenger;
    }

    private function printCarsResult(?int $carIdForPassenger): void
    {
        echo 'The passenger at ' . $this->passenger . 'km' . "\n\n";

        foreach ($this->cars as $car) {
            echo 'Taxi ' . $car->getId() . ' at ' . $car->getPosition() . 'km, ';
            echo 'distance to the passenger ' . $car->getDistance() . 'km ';
            echo $car->isFree() ? '(free)' : '(busy)';
            echo ($car->getId() === $carIdForPassenger) ? ' - this taxi is going' : '';
            echo "\n";
        }

        if ($carIdForPassenger === null) {
            echo "\nSorry, no free taxi available right now.\n";
        }

        echo "\n";
    }
}

class TaxiCar
{
    private int $id;
    private int $position;
    private bool $isFree;
    private ?int $distance = null;

    public function __construct(int $id, int $position, bool $isFree)
    {
        $this->id = $id;
        $this->position = $position;
        $this->isFree = $isFree;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function isFree(): bool
    {
        return $this->isFree;
    }

    public function getDistance(): ?int
    {
        return $this->distance;
    }

    public function setDistance(int $distance): void
    {
        $this->distance = $distance;
    }
}

$taxi = new Taxi(rand(0, 1000), 3, true);
$taxi->findCar();

$taxi = new Taxi(rand(0, 1000), 6);
$taxi->findCar();

$taxi = new Taxi(rand(0, 1000), 9);
$taxi->findCar();
