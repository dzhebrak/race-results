<?php

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\RaceResult;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;

class RaceResultStateProcessor implements ProcessorInterface
{
    public function __construct(private readonly ManagerRegistry $managerRegistry, private readonly PersistProcessor $persistProcessor)
    {

    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof RaceResult || !($operation instanceof HttpOperation && 'PATCH' === $operation->getMethod())) {
            return $data;
        }

        /** @var EntityManager $manager */
        $manager = $this->managerRegistry->getManagerForClass(RaceResult::class);
        $repository = $manager->getRepository(RaceResult::class);

        return $manager->wrapInTransaction(function () use ($data, $manager, $operation, $uriVariables, $context, $repository) {
            $this->persistProcessor->process($data, $operation, $uriVariables, $context);
            $repository->recalculatePlacements($data->getRace());
            $manager->flush();
            $manager->refresh($data);

            return $data;
        });
    }
}
