<?php declare(strict_types=1);

namespace App\Import;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use App\Entity\Race;
use App\Entity\RaceResult;
use App\Model\FinishTime;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

class LoadCsvRaceResultImportStrategy implements RaceResultImportStrategy
{
    public function __construct(
        readonly private PersistProcessor $persistProcessor,
        readonly private ManagerRegistry $managerRegistry,
        readonly private SerializerInterface $serializer,
    )
    {

    }

    public function import(Race $race, array $results, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var EntityManager $manager */
        $manager = $this->managerRegistry->getManagerForClass(Race::class);


        return $manager->wrapInTransaction(function () use ($manager, $race, $results, $operation, $uriVariables, $context) {
            $manager->persist($race);
            $manager->flush();

            $filename = sprintf('%s/%s.csv', sys_get_temp_dir(), uniqid('load_csv_race_results-', false));

            try {
                file_put_contents(
                    $filename,
                    $this->serializer->serialize($results, 'csv', ['race_id' => $race->getId()])
                );

                $manager->getRepository(RaceResult::class)->importFromCsvFile($filename);
                $manager->getRepository(RaceResult::class)->recalculatePlacements($race);
                $manager->getRepository(Race::class)->updateAverageFinishTime($race);

                return $this->persistProcessor->process($race, $operation, $uriVariables, $context);
            } finally {
                unlink($filename);
            }
        });
    }
}
