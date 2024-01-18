<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;

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

    public function queryFindUsersFollowing(int $userId)
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(User::class, 'u');
        $rsm->addFieldResult('u', 'id', 'id');
        $rsm->addFieldResult('u', 'name', 'name');

        $sql = '
            SELECT user.name, user.id
            FROM user
            JOIN user_followers ON user_followers.user_followed = user.id
            WHERE user_followers.user_follower = :userId
        ';

        return $this->getEntityManager()->createNativeQuery($sql, $rsm)
            ->setParameter('userId', $userId);
    }
}
