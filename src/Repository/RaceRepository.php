<?php

namespace App\Repository;

use App\Entity\Race;
use App\Model\RaceDistance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Race>
 *
 * @method Race|null find($id, $lockMode = null, $lockVersion = null)
 * @method Race|null findOneBy(array $criteria, array $orderBy = null)
 * @method Race[]    findAll()
 * @method Race[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Race::class);
    }

    public function updateAverageFinishTime(Race $race)
    {
        $this->getEntityManager()->createQuery("
            UPDATE App\Entity\Race r 
            SET 
                r.averageFinishTimeForMediumDistance = (SELECT COALESCE(CAST(AVG(rr1.finishTime) AS INT), 0) FROM App\Entity\RaceResult rr1 WHERE rr1.race = :race AND rr1.distance = :mediumDistance),
                r.averageFinishTimeForLongDistance = (SELECT COALESCE(CAST(AVG(rr2.finishTime) AS INT), 0) FROM App\Entity\RaceResult rr2 WHERE rr2.race = :race AND rr2.distance = :longDistance)
            WHERE r = :race
        ")->execute(['race' => $race, 'mediumDistance' => RaceDistance::Medium->value, 'longDistance' => RaceDistance::Long->value]);
    }
}
