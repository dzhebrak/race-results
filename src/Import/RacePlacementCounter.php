<?php declare(strict_types=1);

namespace App\Import;

use App\Entity\RaceResult;
use App\Model\FinishTime;

class RacePlacementCounter
{
    private int $overall = 0;
    private array $ageCategories = [];
    private ?FinishTime $prevFinishTime = null;

    public function addRaceResult(RaceResult $result): void
    {
        if ($this->prevFinishTime && $result->getFinishTime()?->toSeconds() < $this->prevFinishTime->toSeconds()) {
            throw new \LogicException("RacePlacementCounter expects race results to be sorted by finish time in ascending order");
        }

        if ($result->isOverallPlacementRequired()) {
            $this->overall++;
        }

        if ($result->isAgeCategoryPlacementRequired()) {
            if (!isset($this->ageCategories[$result->getAgeCategory()])) {
                $this->ageCategories[$result->getAgeCategory()] = 0;
            }

            $this->ageCategories[$result->getAgeCategory()]++;
        }

        $this->prevFinishTime = $result->getFinishTime();
    }

    public function getOverallPlacement(): int
    {
        return $this->overall;
    }

    public function getAgeCategoryPlacement(string $ageCategory): int
    {
        return $this->ageCategories[$ageCategory] ?? 0;
    }
}
