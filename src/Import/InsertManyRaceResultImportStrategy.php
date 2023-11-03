<?php declare(strict_types=1);

namespace App\Import;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Symfony\Validator\Exception\ValidationException;
use App\Entity\Race;
use App\Entity\RaceResult;
use App\Model\FinishTime;
use App\Model\RaceDistance;
use App\Validator\RaceResultAll;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

class InsertManyRaceResultImportStrategy implements RaceResultImportStrategy
{
    public function __construct(
        readonly private PersistProcessor $persistProcessor,
        readonly private ManagerRegistry $managerRegistry,
        readonly private NormalizerInterface $normalizer,
        private int $batchSize = 500,
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

            $results = $this->normalizer->normalize($results, context: ['race_id' => $race->getId()]);

            // Results are not validated again because they were already validated earlier and modified by the normalizer only slightly
            $manager->getRepository(RaceResult::class)->insertMany($results, $this->batchSize);
            $manager->getRepository(RaceResult::class)->recalculatePlacements($race);
            $manager->getRepository(Race::class)->updateAverageFinishTime($race);

            return $this->persistProcessor->process($race, $operation, $uriVariables, $context);
        });
    }
}
