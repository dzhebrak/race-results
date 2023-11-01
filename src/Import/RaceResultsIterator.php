<?php declare(strict_types=1);

namespace App\Import;

use App\Model\FinishTime;

/**
 * Stores the results of a race in sorted order
 */
class RaceResultsIterator extends \ArrayIterator
{
    /**
     * @param array<array> $elements
     */
    public function __construct(array $elements)
    {
        parent::__construct($elements);

        $this->uasort(function ($a, $b) {


            return FinishTime::fromTime($a['time'] ?? null)->toSeconds() <=> FinishTime::fromTime($b['time'] ?? null)->toSeconds();
        });
    }
}
