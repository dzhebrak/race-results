<?php declare(strict_types=1);

namespace App\Import;

use ApiPlatform\Metadata\Operation;
use App\Entity\Race;

interface RaceResultImportStrategy
{
    public function import(Race $race, array $results, Operation $operation, array $uriVariables = [], array $context = []);
}
