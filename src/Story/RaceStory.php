<?php

namespace App\Story;

use App\Factory\RaceFactory;
use App\Factory\RaceResultFactory;
use App\Import\RaceAverageFinishTimeCalculator;
use App\Model\FinishTime;
use App\Model\RaceDistance;
use App\Repository\RaceResultRepository;
use Faker\Factory as FakerFactory;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Story;

final class RaceStory extends Story
{
    public const RACES_NUMBER = 40;
    public const MIN_RESULTS_PER_RACE_NUMBER = 10;
    public const MAX_RESULTS_PER_RACE_NUMBER = 20;

    public function __construct(private RaceResultRepository $raceResultRepository)
    {

    }

    public function build(): void
    {
        $faker = FakerFactory::create();

        $loadKey = $faker->text(5);
        for ($i = 0; $i < self::RACES_NUMBER; $i++) {
            $race = RaceFactory::createOne(['title' => sprintf('Race #%d [%s]', $i+1, $loadKey)]);

            Factory::delayFlush(function () use ($faker, $race) {
                $finishTimeCalculator   = new RaceAverageFinishTimeCalculator();
                $finishTimeInSeconds = $faker->numberBetween(5 * 60, 2 * 60 * 60);
                $resultsNumber = $faker->numberBetween(self::MIN_RESULTS_PER_RACE_NUMBER, self::MAX_RESULTS_PER_RACE_NUMBER);

                for ($j = 0; $j < $resultsNumber; $j++) {
                    $raceResult = RaceResultFactory::createOne();
                    $finishTimeInSeconds += $faker->numberBetween(1 * 60, 15 * 60);
                    $raceResult->setFinishTime(new FinishTime($finishTimeInSeconds));
                    $finishTimeCalculator->addRaceResult($raceResult->object());

                    $race->addResult($raceResult->object());
                }

                $race->setAverageFinishTimeForLongDistance($finishTimeCalculator->getAverageFinishTime(RaceDistance::Long->value));
                $race->setAverageFinishTimeForMediumDistance($finishTimeCalculator->getAverageFinishTime(RaceDistance::Medium->value));

                $this->raceResultRepository->recalculatePlacements($race->object());
            });
        }
    }
}
