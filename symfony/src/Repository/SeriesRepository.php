<?php

namespace App\Repository;

use App\Entity\Series;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use App\Entity\SeriesSearch;

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
        return $queryBuilder;
    }

    public function findByKeyWordInAll($keyWord)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.title LIKE :keyWord')
            ->orWhere('s.plot LIKE :keyWord')
            ->setParameter('keyWord', '%' . $keyWord . '%')
            ->getQuery();
    }
    public function findByKeyWordInSeriesFollowing(User $user, $keyWord)
    {
        $qb = $this->createQueryBuilder('s');

        $query = $qb->select('s')
            ->where('s.user = :user')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->like('s.title', ':keyWord'),
                $qb->expr()->like('s.plot', ':keyWord')
            ))
            ->setParameter('user', $user)
            ->setParameter('keyWord', '%' . $keyWord . '%')
            ->getQuery();

        return $query->getResult();
    }
    public function queryVisionage(int $userId, array $arraySeriesId, int $seed)
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addScalarResult('percentage_seen', 'percentage_seen');
        $sql = "SELECT ROUND( IFNULL(seen_episodes, 0) * 100.0 / IFNULL(total_episodes, 1), 2) AS percentage_seen
                FROM series
                LEFT JOIN (
                    SELECT S.series_id, COUNT(*) AS total_episodes
                    FROM season S
                    INNER JOIN episode E ON E.season_id = S.id
                    GROUP BY S.series_id
                ) total ON series.id = total.series_id
                LEFT JOIN (
                    SELECT S.series_id, COUNT(*) AS seen_episodes
                    FROM user_episode UE
                    INNER JOIN episode E ON UE.episode_id = E.id
                    INNER JOIN season S ON E.season_id = S.id
                    GROUP BY S.series_id
                ) seen ON series.id = seen.series_id
                WHERE series.id IN (:arraySeriesId)
                ORDER BY RAND(:seed)";
        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('userId', $userId);
        $query->setParameter('arraySeriesId', $arraySeriesId);
        $query->setParameter('seed', $seed);

        $ormQuery = $query;

        return $ormQuery->getResult() ;
    }

    public function querySeriesSuiviesTrieParVisionnage(int $userId,array $arraySeriesId = [])
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata('App\Entity\Series', 's');
        $rsm->addScalarResult('percentage_seen', 'percentage_seen');
    
        $sql = "
            SELECT series.*, 
                   ROUND(IFNULL(seen_episodes, 0) * 100.0 / IFNULL(total_episodes, 1), 2) AS percentage_seen
            FROM series
            LEFT JOIN (
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
            WHERE US.user_id = :userId"
        ;

        if ($arraySeriesId){
           $sql =  $sql." AND series.id IN (:arraySeriesId)";
        }
        $sql = $sql." ORDER BY percentage_seen DESC";
    
        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('userId', $userId);
        if ($arraySeriesId){
            $query->setParameter('arraySeriesId', $arraySeriesId);
        }

        $ormQuery = $query;
    
        return $ormQuery->getResult() ;
    }
    public function findByCriteria(array $criteria, $search)
    {
        $qb = $this->buildQuerryfindByCriteria($criteria, $search);
        return $qb;
    }

    public function buildQuerryfindByCriteria(array $criteria, $search)
    {
        $qb = $this->createQueryBuilder('s');

        if (!empty($criteria['genre'])) {
            $qb->leftJoin('s.genre', 'g')
                ->andWhere('g.name IN (:genres)')
                ->groupBy('s.id')
                ->having('COUNT(s.id) = :count')
                ->setParameters([
                    'genres' => $criteria['genre'],
                    'count' => count($criteria['genre'])
                ]);
        }

        if (!empty($criteria['startDate'])) {
            $qb->andWhere('s.yearStart = :startDate')
               ->setParameter('startDate', $criteria['startDate']);
        }

        if (!empty($criteria['endDate'])) {
            $qb->andWhere('s.yearEnd = :endDate')
               ->setParameter('endDate', $criteria['endDate']);
        }
        if (!empty($search)) {
            $qb->andWhere('s.title LIKE :search OR s.plot LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if (!empty($criteria['ratings'])) {
            $sub = $this->createQueryBuilder('s2')
                ->select('AVG(r.value)')
                ->leftJoin('s2.ratings', 'r')
                ->where('s2.id = s.id');
            $qb->addSelect('(' . $sub->getDQL() . ') as HIDDEN avgRating')
                ->orderBy('avgRating', $criteria['ratings']);
        }
        return $qb;
    }

    public function findByCriteriaFollow(User $user, array $criteria, $search)
    {

        $qb = $this->buildQuerryfindByCriteria($criteria, $search);
        $qb->leftJoin('s.user', 'u')
            ->andWhere('u.id = :userId')
            ->setParameter('userId', $user->getId());
        return $qb;
    }

    public function findUniqueGenres()
    {
        $qb = $this->createQueryBuilder('s')
                ->select('g.name')
                ->distinct()
                ->leftJoin('s.genre', 'g');

        return $qb;
    }
}
