<?php

namespace App\Repository;

use App\DBAL\Query\InsertManyQuery;
use App\Entity\Race;
use App\Entity\RaceResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Franzose\DoctrineBulkInsert\Query;

/**
 * @extends ServiceEntityRepository<RaceResult>
 *
 * @method RaceResult|null find($id, $lockMode = null, $lockVersion = null)
 * @method RaceResult|null findOneBy(array $criteria, array $orderBy = null)
 * @method RaceResult[]    findAll()
 * @method RaceResult[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RaceResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RaceResult::class);
    }

    public function getAverageFinishTime(Race $race)
    {
        return $this->getEntityManager()->createQuery(
            'SELECT rr.distance as distance, CAST(AVG(rr.finishTime) AS INT) as avgFinishTime FROM App\Entity\RaceResult rr WHERE rr.race = :race GROUP BY rr.distance'
        )->setParameters(['race' => $race])->getResult();
    }

    public function recalculatePlacements(Race $race)
    {
        // TODO: rewrite using DQL OR QB
        // TODO: add tests
        $stmt = $this->getEntityManager()->getConnection()->prepare("
            UPDATE race_result rr
            INNER JOIN (
                SELECT
                    id,
                    RANK() OVER (ORDER BY finish_time) AS overall_placement,
                    RANK() OVER (PARTITION BY age_category ORDER BY finish_time) AS age_category_placement
                FROM race_result
                WHERE race_id = :raceId AND distance = :distance
            ) rrp ON rr.id = rrp.id
            SET rr.overall_placement = rrp.overall_placement, rr.age_category_placement = rrp.age_category_placement
            WHERE race_id = :raceId AND distance = :distance
        ");

        $stmt->executeStatement([
            'raceId'   => $race->getId(),
            'distance' => 'long'
        ]);
    }

    public function importFromCsvFile(string $filename): void
    {
//        $this->getEntityManager()->getConnection()->executeStatement('SET GLOBAL local_infile = true;');

        $this->getEntityManager()->getConnection()->prepare("
            LOAD DATA LOCAL INFILE :filename
            INTO TABLE race_result
            FIELDS TERMINATED BY ',' 
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\n'
            IGNORE 1 ROWS;
        ")->executeStatement([
            'filename' => $filename
        ]);
    }

    public function insertMany(array $results, int $batchSize = 1000): int
    {
        return $this->getEntityManager()->wrapInTransaction(function () use ($results, $batchSize) {
            $total = 0;
            $batch = [];
            $query = new Query($this->getEntityManager()->getConnection());

            foreach ($results as $idx => $result) {
                $batch[] = $result;

                if (($idx % $batchSize) === 0) {
                    $total += $query->execute('race_result', $batch);
                    $batch = [];
                }
            }

            $total += $query->execute('race_result', $batch);

            return $total;
        });
    }
}
