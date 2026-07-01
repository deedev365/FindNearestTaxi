<?php

class Taxi
{
    private int $passengerLocation;
    private array $cars;

    public function __construct(int $passengerLocation, int $countCarsInPark = 10)
    {
        $this->passengerLocation = $passengerLocation;
        $this->cars = $this->getCars($countCarsInPark);
    }

    private function getCars(int $countCarsInPark): array
    {
        $cars = [];

        for ($carId = 1; $carId <= $countCarsInPark; $carId++) {
            $isCarFree = (bool) rand(0, 1);
            
            $cars[] = new TaxiCar($carId, rand(0, 1000), $isCarFree);
        }

        return $cars;
    }

    public function findCar(): void
    {
        $this->printCarsResult($this->getCarIdForPassenger());
    }

    private function getCarIdForPassenger(): ?int
    {
        $carForPassenger = null;

        foreach ($this->cars as $car) {
            $distanceToPassenger = abs($this->passengerLocation - $car->getPosition());
            $car->setDistanceToPassenger($distanceToPassenger);

            if (!$car->isFree()) {
                continue;
            }

            if (
                $carForPassenger === null
                || $car->getDistanceToPassenger() < $carForPassenger->getDistanceToPassenger()
            ) {
                $carForPassenger = $car;
            }
        }

        $this->sortCarsByDistanceToPassenger();

        if ($carForPassenger === null) {
            return null;
        }

        return $carForPassenger->getId();
    }

    private function sortCarsByDistanceToPassenger(): void
    {
        usort($this->cars, function (TaxiCar $firstCar, TaxiCar $secondCar): int {
            return ($firstCar->getDistanceToPassenger() <=> $secondCar->getDistanceToPassenger())
                ?: ($firstCar->getId() <=> $secondCar->getId());
        });
    }

    private function printCarsResult(?int $carIdForPassenger): void
    {
        echo 'The passenger at ' . $this->passengerLocation . 'km' . "\n\n";

        foreach ($this->cars as $car) {
            echo 'Taxi ' . $car->getId() . ' at ' . $car->getPosition() . 'km, ';
            echo 'distance to the passenger ' . $car->getDistanceToPassenger() . 'km ';
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
    private ?int $distanceToPassenger = null;

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

    public function getDistanceToPassenger(): ?int
    {
        return $this->distanceToPassenger;
    }

    public function setDistanceToPassenger(int $distanceToPassenger): void
    {
        $this->distanceToPassenger = $distanceToPassenger;
    }
}

$taxi = new Taxi(rand(1, 1000), 3);
$taxi->findCar();

$taxi = new Taxi(rand(1, 1000), 6);
$taxi->findCar();

$taxi = new Taxi(rand(1, 1000));
$taxi->findCar();
