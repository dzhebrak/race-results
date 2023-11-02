<?php declare(strict_types=1);

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Validator\ValidatorInterface as ApiPlatformValidatorInterface;
use App\Entity\Race;
use App\Entity\RaceResult;
use App\Model\FinishTime;
use App\Model\RaceDistance;
use App\Validator\RaceResultAll;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

/**
 * Creates a race and imports its results from a CSV file using a bulk inserts strategy.
 * Other possible approaches include generating a new CSV file and loading it directly into the database, or using asynchronous loading with Symfony Messenger.
 */
class RaceCreateStateProcessor implements ProcessorInterface
{
    public const BATCH_SIZE = 500;

    public function __construct(
        readonly private RequestStack $requestStack,
        readonly private PersistProcessor $persistProcessor,
        readonly private ManagerRegistry $managerRegistry,
        readonly private SerializerInterface $serializer,
        readonly private SymfonyValidatorInterface $validator,
        readonly private ApiPlatformValidatorInterface $apiPlatformValidator
    )
    {

    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Race || !($operation instanceof HttpOperation && 'POST' === $operation->getMethod())) {
            return $data;
        }

        if (!($uploadedFile = $this->requestStack->getCurrentRequest()->files->get('file'))) {
            return $data;
        }

        $results = $this->serializer->decode(
            file_get_contents($uploadedFile->getPathname()),
            'csv'
        );

        $this->validateResults($results);

        /** @var EntityManager $manager */
        $manager = $this->managerRegistry->getManagerForClass(Race::class);

        return $manager->wrapInTransaction(function () use ($data, $results, $manager, $operation, $uriVariables, $context) {
            $manager->persist($data);

            foreach ($results as $idx => $row) {
                $result = $this->serializer->denormalize($row, RaceResult::class);
                $result->setRace($data);

                $this->apiPlatformValidator->validate($result);
                $manager->persist($result);

                if (($idx % self::BATCH_SIZE) === 0) {
                    $manager->flush();
                    $this->clearIdentityMap($manager);
                }
            }

            $manager->flush();

            $manager->getRepository(RaceResult::class)->recalculatePlacements($data);
            $manager->getRepository(Race::class)->updateAverageFinishTime($data);
            $this->clearIdentityMap($manager);

            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        });
    }

    /**
     * Validates the data to catch basic errors, such as missing or unexpected properties.
     * Returns the exact line number of the file with the found error to the client.
     */
    protected function validateResults(array $results)
    {
        $violations = $this->validator->validate($results, new RaceResultAll([
            new Collection([
                'fullName' => [new NotBlank()],
                'distance' => [new Choice(callback: [RaceDistance::class, 'values'])],
                'time' => [new Regex(pattern: FinishTime::TIME_REGEX_PATTERN)],
                'ageCategory' => [new NotBlank()]
            ])
        ]));

        if ($violations->count() !== 0) {
            throw new ValidationException($violations, errorTitle: 'Invalid race result data');
        }
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
