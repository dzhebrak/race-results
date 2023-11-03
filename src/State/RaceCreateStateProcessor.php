<?php declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Symfony\Validator\Exception\ValidationException;
use App\Entity\Race;
use App\Entity\RaceResult;
use App\Import\RaceResultImportStrategy;
use App\Model\FinishTime;
use App\Model\RaceDistance;
use App\Validator\RaceResultAll;
use Doctrine\ORM\EntityManager;
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

    public function __construct(
        readonly private RaceResultImportStrategy $raceResultImportStrategy,
        readonly private RequestStack $requestStack,
        readonly private ManagerRegistry $managerRegistry,
        readonly private SerializerInterface $serializer,
        readonly private SymfonyValidatorInterface $validator
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
        /** @var EntityManager $manager */
        $manager = $this->managerRegistry->getManagerForClass(Race::class);

        $manager->getConnection()->getConfiguration()->setMiddlewares([]);

        $results = $this->serializer->decode(
            file_get_contents($uploadedFile->getPathname()),
            'csv'
        );

        $this->validateResults($results);

        return $this->raceResultImportStrategy->import($data, $results, $operation, $uriVariables, $context);
    }

    /**
     * Validates the data to catch basic errors, such as missing or unexpected properties.
     * Returns the exact line number of the file with the found error to the client.
     */
    protected function validateResults(array $results)
    {
        $violations = $this->validator->validate(
            $results, new RaceResultAll([
                new Collection([
                    'fullName'    => [new NotBlank()],
                    'distance'    => [new Choice(callback: [RaceDistance::class, 'values'])],
                    'time'        => [new Regex(pattern: FinishTime::TIME_REGEX_PATTERN)],
                    'ageCategory' => [new Regex(pattern: RaceResult::AGE_CATEGORY_REGEX_PATTERN)]
                ])
            ])
        );

        if ($violations->count() !== 0) {
            throw new ValidationException($violations, errorTitle: 'Invalid race result data');
        }
    }
}
