<?php declare(strict_types=1);

namespace App\Import;

use App\Entity\RaceResult;
use App\Model\FinishTime;

class RaceResultsWalker
{
    private RacePlacementCounter $placementCounter;
    private RaceAverageFinishTimeCalculator $averageFinishTimeCalculator;

    public function __construct()
    {
        $this->placementCounter = new RacePlacementCounter();
        $this->averageFinishTimeCalculator = new RaceAverageFinishTimeCalculator();
    }

    public function addRaceResult(RaceResult $raceResult): void
    {
        $this->placementCounter->addRaceResult($raceResult);
        $this->averageFinishTimeCalculator->addRaceResult($raceResult);
    }

    public function getOverallPlacement(): int
    {
        return $this->placementCounter->getOverallPlacement();
    }

    public function getAgeCategoryPlacement(string $ageCategory): int
    {
        return $this->placementCounter->getAgeCategoryPlacement($ageCategory);
    }

    public function getAverageFinishTime(string $distance): ?FinishTime
    {
        return $this->averageFinishTimeCalculator->getAverageFinishTime($distance);
    }
}
