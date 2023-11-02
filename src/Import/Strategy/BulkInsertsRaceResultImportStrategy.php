<?php declare(strict_types=1);

namespace App\Import\Strategy;

use ApiPlatform\Validator\ValidatorInterface;
use App\Entity\Race;
use App\Entity\RaceResult;
use App\Import\RaceAverageFinishTimeCalculator;
use App\Import\RaceResultsIterator;
use App\Model\RaceDistance;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\SerializerInterface;

class BulkInsertsRaceResultImportStrategy implements RaceResultImportStrategy
{
    public const BATCH_SIZE = 500;

    public function __construct(private readonly ManagerRegistry $managerRegistry, readonly private ValidatorInterface $validator, private readonly SerializerInterface $serializer)
    {
    }

    public function import(Race $race, RaceResultsIterator $raceResultIterator)
    {
        /** @var EntityManager $manager */
        $manager = $this->managerRegistry->getManagerForClass(Race::class);

        $manager->wrapInTransaction(function () use ($race, $raceResultIterator, $manager) {
            $averageFinishTimeCalculator = new RaceAverageFinishTimeCalculator();
            $manager->persist($race);

            foreach ($raceResultIterator as $idx => $row) {
                $result = $this->serializer->denormalize($row, RaceResult::class);
                $result->setRace($race);

                $this->validator->validate($result);
                $manager->persist($result);
                $averageFinishTimeCalculator->addRaceResult($result);

                if (($idx % self::BATCH_SIZE) === 0) {
                    $manager->flush();
                    $this->clearIdentityMap($manager);
                }
            }

            $manager->flush();
            $this->clearIdentityMap($manager);

            $race->setAverageFinishTimeForMediumDistance($averageFinishTimeCalculator->getAverageFinishTime(RaceDistance::Medium->value));
            $race->setAverageFinishTimeForLongDistance($averageFinishTimeCalculator->getAverageFinishTime(RaceDistance::Long->value));

            $this->validator->validate($race, ['groups' => ['Default', 'import']]);

            $manager->getRepository(RaceResult::class)->recalculatePlacements($race);
            $manager->flush();
        });
    }

    private function clearIdentityMap(EntityManagerInterface $entityManager): void
    {
        $unitOfWork = $entityManager->getUnitOfWork();
        $entities   = $unitOfWork->getIdentityMap()[RaceResult::class] ?? [];

        foreach ($entities as $entity) {
            $entityManager->detach($entity);
            unset($entity);
        }
    }
}
