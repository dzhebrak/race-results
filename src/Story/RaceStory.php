<?php

namespace App\Story;

use App\Factory\RaceFactory;
use App\Factory\RaceResultFactory;
use App\Import\RaceResultsWalker;
use App\Model\FinishTime;
use App\Model\RaceDistance;
use Faker\Factory as FakerFactory;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Story;

final class RaceStory extends Story
{
    public const RACES_NUMBER = 40;
    public const MIN_RESULTS_PER_RACE_NUMBER = 10;
    public const MAX_RESULTS_PER_RACE_NUMBER = 20;

    public function build(): void
    {
        $faker = FakerFactory::create();

        $loadKey = $faker->text(5);
        for ($i = 0; $i < self::RACES_NUMBER; $i++) {
            $race = RaceFactory::createOne(['title' => sprintf('Race #%d [%s]', $i+1, $loadKey)]);

            Factory::delayFlush(static function () use ($faker, $race) {
                $raceResultsWalker   = new RaceResultsWalker();
                $finishTimeInSeconds = $faker->numberBetween(5 * 60, 2 * 60 * 60);
                $resultsNumber = $faker->numberBetween(self::MIN_RESULTS_PER_RACE_NUMBER, self::MAX_RESULTS_PER_RACE_NUMBER);

                for ($j = 0; $j < $resultsNumber; $j++) {
                    $raceResult = RaceResultFactory::createOne();
                    $finishTimeInSeconds += $faker->numberBetween(1 * 60, 15 * 60);
                    $raceResult->setFinishTime(new FinishTime($finishTimeInSeconds));

                    $race->addResult($raceResult->object());
                    $raceResultsWalker->addRaceResult($raceResult->object());

                    $raceResult->setOverallPlacement($raceResultsWalker->getOverallPlacement());
                    $raceResult->setAgeCategoryPlacement($raceResultsWalker->getAgeCategoryPlacement($raceResult->getAgeCategory()));
                }

                $race->setAverageFinishTimeForLongDistance($raceResultsWalker->getAverageFinishTime(RaceDistance::Long->value));
                $race->setAverageFinishTimeForMediumDistance($raceResultsWalker->getAverageFinishTime(RaceDistance::Medium->value));
            });
        }
    }
}
