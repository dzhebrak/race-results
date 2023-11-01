<?php

namespace App\DataFixtures;

use App\Entity\Race;
use App\Entity\RaceDistance;
use App\Entity\RaceResult;
use App\Model\FinishTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class AppFixtures extends Fixture
{
    public const TOTAL_RACES = 100;
    public const RACE_MIN_RESULTS = 100;
    public const RACE_MAX_RESULTS = 1000;

    private Generator $faker;
    private EntityManager $entityManager;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        /** @var EntityManager $manager $i */
        $this->entityManager = $manager;

        $allMiddlewares = $this->entityManager->getConnection()->getConfiguration()->getMiddlewares();

        $middlewares = array_filter(
            $this->entityManager->getConnection()->getConfiguration()->getMiddlewares(),
            static fn($middleware) => !in_array(get_class($middleware), [
                \Doctrine\Bundle\DoctrineBundle\Middleware\DebugMiddleware::class,
                \Doctrine\DBAL\Logging\Middleware::class,
            ])
        );

        $this->entityManager->getConnection()->getConfiguration()->setMiddlewares($middlewares);

        for ($i = 0; $i < self::TOTAL_RACES; $i++) {
            $race = $this->createRace();

            $this->entityManager->persist($race);
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $this->entityManager->getConnection()->getConfiguration()->setMiddlewares($allMiddlewares);
    }

    private function createRace(): Race
    {
        $race = new Race();
        $race->setTitle($this->faker->text(32));
        $race->setDate(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-1 year')));
        $race->setAverageFinishTimeForMediumDistance(new FinishTime($this->faker->numberBetween(29 * 60 /* 29 minutes */, 24 * 60 * 60 /* 24 hours */)));
        $race->setAverageFinishTimeForLongDistance(new FinishTime($this->faker->numberBetween(59 * 60 /* 59 minutes */, 32 * 60 * 60 /* 32 hours */)));

        $numberOfResults         = random_int(self::RACE_MIN_RESULTS, self::RACE_MAX_RESULTS);
        $prevFinishTimeInSeconds = $this->faker->numberBetween(60 * 60, 3 * 60 * 60);
        $ageCategoryPlacement    = [];
        $overallPlacement = 0;

        for ($j = 0; $j < $numberOfResults; $j++) {
            $race->addResult(
                $this->createRaceResult($race, $prevFinishTimeInSeconds, $ageCategoryPlacement, $j, $overallPlacement)
            );
        }

        return $race;
    }

    private function createRaceResult(Race $race, int &$prevFinishTimeInSeconds, array &$ageCategoryPlacement, int $j, int &$overallPlacement): RaceResult
    {
        $raceResult = new RaceResult();

        $raceResult->setFullName(
            sprintf('%s %s', $this->faker->firstName($j % 2 === 0 ? 'male' : 'female'), $this->faker->lastName())
        );

        $distance = $this->faker->boolean(60) ? RaceDistance::Medium->value : RaceDistance::Long->value;
        $raceResult->setDistance($distance);

        if ($raceResult->isOverallPlacementRequired()) {
            $overallPlacement++;
        }

        $prevFinishTimeInSeconds += $this->faker->numberBetween(1, 15 * 60);
        $raceResult->setFinishTime(new FinishTime($prevFinishTimeInSeconds));

        $raceResult->setOverallPlacement($overallPlacement);

        $ageCategory = $this->faker->randomElement([
            'M18-25', 'F18-25',
            'M26-34', 'F26-34',
            'M35-43', 'F35-43',
        ]);

        $raceResult->setAgeCategory($ageCategory);

        if (!isset($ageCategoryPlacement[$ageCategory])) {
            $ageCategoryPlacement[$ageCategory] = 0;
        }

        if ($raceResult->isAgeCategoryPlacementRequired()) {
            $ageCategoryPlacement[$ageCategory]++;
        }

        $raceResult->setAgeCategoryPlacement($ageCategoryPlacement[$ageCategory]);

        return $raceResult;
    }
}
