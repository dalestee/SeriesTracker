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
            ->andWhere('r.moderate = true')
            ->orderBy('r.date', 'DESC')
            ->setParameter('user', $user)
            ->getQuery();
    }

    public function querySetLastConnexionNull(User $user)
    {
        return $this->createQueryBuilder('u')
            ->update(User::class, 'u')
            ->set('u.lastConnexion', ':connexion')
            ->where('u.id = :userId') // Assurez-vous de comparer avec l'identifiant correct (id dans cet exemple)
            ->setParameter('userId', $user->getId())
            ->setParameter('connexion', null)
            ->getQuery()
            ->execute()
        ;
    }
}
