<?php declare(strict_types=1);

namespace App\Import;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\ValidatorInterface as ApiPlatformValidatorInterface;
use App\Entity\Race;
use App\Entity\RaceResult;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\SerializerInterface;

class BulkInsertsRaceResultImportStrategy implements RaceResultImportStrategy
{
    public const BATCH_SIZE = 500;

    public function __construct(
        readonly private PersistProcessor $persistProcessor,
        readonly private ApiPlatformValidatorInterface $apiPlatformValidator,
        readonly private ManagerRegistry $managerRegistry,
        readonly private SerializerInterface $serializer,
    )
    {

    }

    public function import(Race $race, array $results, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var EntityManager $manager */
        $manager = $this->managerRegistry->getManagerForClass(Race::class);

        return $manager->wrapInTransaction(function () use ($race, $results, $manager, $operation, $uriVariables, $context) {
            $manager->persist($race);

            foreach ($results as $idx => $row) {
                $result = $this->serializer->denormalize($row, RaceResult::class);
                $result->setRace($race);

                $this->apiPlatformValidator->validate($result);
                $manager->persist($result);

                if (($idx % self::BATCH_SIZE) === 0) {
                    $manager->flush();
                    $this->clearIdentityMap($manager);
                }
            }

            $manager->flush();

            $manager->getRepository(RaceResult::class)->recalculatePlacements($race);
            $manager->getRepository(Race::class)->updateAverageFinishTime($race);
            $this->clearIdentityMap($manager);

            return $this->persistProcessor->process($race, $operation, $uriVariables, $context);
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
