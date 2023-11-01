<?php declare(strict_types=1);

namespace App\Import;

use App\Entity\Race;
use App\Entity\RaceResult;
use App\Model\FinishTime;

class RaceResultsWalker
{
    private RacePlacementCounter $placementCounter;
    private RaceAverageFinishTimeCalculator $averageFinishTimeCalculator;
    private ?Race $race = null;

    public function __construct()
    {
        $this->placementCounter = new RacePlacementCounter();
        $this->averageFinishTimeCalculator = new RaceAverageFinishTimeCalculator();
    }

    public function addRaceResult(RaceResult $raceResult): void
    {
        if ($this->race !== null && $raceResult->getRace() !== $this->race) {
            throw new \LogicException('RaceResultsWalker can only process the results of a single race at a time');
        }

        $this->placementCounter->addRaceResult($raceResult);
        $this->averageFinishTimeCalculator->addRaceResult($raceResult);

        $this->race = $raceResult->getRace();
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
