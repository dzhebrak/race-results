<?php declare(strict_types=1);

namespace App\Import;

use App\Entity\RaceResult;
use App\Model\FinishTime;

class RaceAverageFinishTimeCalculator
{
    private array $data = [];

    public function addRaceResult(RaceResult $result): void
    {
        if (!isset($this->data[$result->getDistance()])) {
            $this->data[$result->getDistance()] = [
                'total_finish_time' => 0,
                'counter'     => 0,
            ];
        }

        $this->data[$result->getDistance()]['total_finish_time'] += $result->getFinishTime()?->toSeconds();
        $this->data[$result->getDistance()]['counter']++;
    }

    public function getAverageFinishTime(string $distance): FinishTime
    {
        if (!isset($this->data[$distance])) {
            return new FinishTime(0);
        }

        return new FinishTime(
            (int)($this->data[$distance]['total_finish_time'] / $this->data[$distance]['counter'])
        );
    }
}
