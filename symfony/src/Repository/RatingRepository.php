<?php

namespace App\Repository;

use App\Entity\Rating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    public function queryRatingsBySeries($seriesId)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.series = :seriesId')
            ->orderBy('r.date', 'DESC')
            ->andWhere('r.moderate = true')
            ->setParameter('seriesId', $seriesId)
            ->getQuery()
        ;
    }

    public function queryRatingNoModerate()
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.moderate = false')
            ->orderBy('r.date', 'DESC')
            ->getQuery()
        ;
    }
}
