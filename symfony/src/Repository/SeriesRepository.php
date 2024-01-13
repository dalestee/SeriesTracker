<?php

namespace App\Repository;

use App\Entity\Series;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMappingBuilder;


/**
 * @extends ServiceEntityRepository<Series>
 *
 * @method Series|null find($id, $lockMode = null, $lockVersion = null)
 * @method Series|null findOneBy(array $criteria, array $orderBy = null)
 * @method Series[]    findAll()
 * @method Series[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Series::class);
    }

    public function queryRandom(int $seed)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->orderBy('RAND(' . $seed . ')');
        return $queryBuilder->getQuery();
    }
    public function querySeriesSuiviesTrieParVisionnage(int $userId)
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata('App\Entity\Series', 's');
        $rsm->addScalarResult('percentage_seen', 'percentage_seen');
    
        $sql = "
            SELECT series.*, 
                   ROUND((IFNULL(seen_episodes, 0) * 100.0 / total_episodes), 2) AS percentage_seen
            FROM series
            INNER JOIN (
                SELECT S.series_id, COUNT(*) AS total_episodes
                FROM episode E
                INNER JOIN season S ON E.season_id = S.id
                GROUP BY S.series_id
            ) total ON series.id = total.series_id
            LEFT JOIN (
                SELECT S.series_id, COUNT(*) AS seen_episodes
                FROM user_episode UE
                INNER JOIN episode E ON UE.episode_id = E.id
                INNER JOIN season S ON E.season_id = S.id
                WHERE UE.user_id = :userId
                GROUP BY S.series_id
            ) seen ON series.id = seen.series_id
            INNER JOIN user_series US ON series.id = US.series_id
            WHERE US.user_id = :userId
            ORDER BY percentage_seen DESC
        ";
    
        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('userId', $userId);

        $ormQuery = $query->getResult();
    
        return $ormQuery ;
            
    }
}