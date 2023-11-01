<?php declare(strict_types=1);

namespace App\Import\Strategy;

use ApiPlatform\Validator\ValidatorInterface;
use App\Entity\Race;
use App\Entity\RaceResult;
use App\Import\RaceResultsIterator;
use App\Import\RaceResultsWalker;
use App\Model\RaceDistance;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\SerializerInterface;

class BulkInsertsRaceResultImportStrategy implements RaceResultImportStrategy
{
    public const BATCH_SIZE = 3;

    private RaceResultsWalker $raceResultsWalker;

    public function __construct(private readonly ManagerRegistry $managerRegistry, readonly private ValidatorInterface $validator, private readonly SerializerInterface $serializer)
    {
        $this->raceResultsWalker = new RaceResultsWalker();
    }

    public function import(Race $race, RaceResultsIterator $raceResultIterator)
    {
        /** @var EntityManager $manager */
        $manager = $this->managerRegistry->getManagerForClass(Race::class);

        $manager->wrapInTransaction(function () use ($race, $raceResultIterator, $manager) {
            $manager->persist($race);
            $manager->flush();

            $i = 0;

            foreach ($raceResultIterator as $row) {
                $i++;
                $result = $this->serializer->denormalize($row, RaceResult::class);

                $result->setRace($race);
                $result->setOverallPlacement($this->raceResultsWalker->getOverallPlacement() + 1);
                $result->setAgeCategoryPlacement($this->raceResultsWalker->getAgeCategoryPlacement($result->getAgeCategory()) + 1);


                $this->validator->validate($result);
                $this->raceResultsWalker->addRaceResult($result);
                $manager->persist($result);

                if (($i % self::BATCH_SIZE) === 0) {
                    $manager->flush();
                    $this->clearIdentityMap(RaceResult::class, $manager);
                }
            }

            $manager->flush();
            $this->clearIdentityMap(RaceResult::class, $manager);

            $race->setAverageFinishTimeForMediumDistance($this->raceResultsWalker->getAverageFinishTime(RaceDistance::Medium->value));
            $race->setAverageFinishTimeForLongDistance($this->raceResultsWalker->getAverageFinishTime(RaceDistance::Long->value));

            $this->validator->validate($race, ['groups' => ['Default', 'import']]);
            $manager->flush();
        });
    }

    private function clearIdentityMap(string $class, EntityManagerInterface $entityManager): void
    {
        $unitOfWork = $entityManager->getUnitOfWork();
        $entities   = $unitOfWork->getIdentityMap()[$class] ?? [];

        foreach ($entities as $entity) {
            $entityManager->detach($entity);
        }
    }
}
