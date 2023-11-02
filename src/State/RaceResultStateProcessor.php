<?php

namespace App\State;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\RaceResult;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;

class RaceResultStateProcessor implements ProcessorInterface
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {

    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof RaceResult || !($operation instanceof HttpOperation && 'PUT' === $operation->getMethod())) {
            return $data;
        }

        /** @var EntityManager $manager */
        $manager = $this->managerRegistry->getManagerForClass(RaceResult::class);
        $repository = $manager->getRepository(RaceResult::class);

        $manager->wrapInTransaction(function () use ($data, $repository) {
            $repository->recalculatePlacements($data->getRace());
        });

    }
}
