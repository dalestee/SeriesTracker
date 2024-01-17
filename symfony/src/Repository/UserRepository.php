<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }


    public function queryFindRatingFromUser(User $user)
    {
        return $this->createQueryBuilder('u')
            ->select('r')
            ->from('App:Rating', 'r')
            ->where('r.user = :user')
            ->orderBy('r.date', 'DESC')
            ->setParameter('user', $user)
            ->getQuery();
    }
}
