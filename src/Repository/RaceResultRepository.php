<?php

namespace App\Repository;

use App\Entity\Race;
use App\Entity\RaceResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
}
