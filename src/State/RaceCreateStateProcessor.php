<?php declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Symfony\Validator\Exception\ValidationException;
use App\Entity\Race;
use App\Entity\RaceDistance;
use App\Entity\RaceResult;
use App\Import\RaceResultsIterator;
use App\Import\Strategy\RaceResultImportStrategy;
use App\Model\FinishTime;
use App\Validator\RaceResultAll;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RaceCreateStateProcessor implements ProcessorInterface
{
    public function __construct(readonly private RequestStack $requestStack, readonly private RaceResultImportStrategy $raceResultImportStrategy, readonly private SerializerInterface $serializer, readonly private ValidatorInterface $validator)
    {

    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof Race || !($operation instanceof HttpOperation && 'POST' === $operation->getMethod())) {
            return $data;
        }

        if (!($uploadedFile = $this->requestStack->getCurrentRequest()->files->get('file'))) {
            return $data;
        }

        $this->doProcess($data, $uploadedFile);
    }

    protected function doProcess(Race $race, UploadedFile $uploadedFile)
    {
        $results = $this->serializer->decode(
            file_get_contents($uploadedFile->getPathname()),
            'csv'
        );

        $this->validateResults($results);

        $this->raceResultImportStrategy->import(
            $race,
            new RaceResultsIterator($results)
        );
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
}
