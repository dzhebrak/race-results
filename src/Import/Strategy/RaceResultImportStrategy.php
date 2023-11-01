<?php declare(strict_types=1);

namespace App\Import\Strategy;

use App\Entity\Race;
use App\Import\RaceResultsIterator;

// TODO: Add MysqlLoadCsvRaceResultsImportStrategy
interface RaceResultImportStrategy
{
    public function import(Race $race, RaceResultsIterator $raceResultIterator);
}
