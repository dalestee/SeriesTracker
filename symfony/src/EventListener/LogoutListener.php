<?php
// src/EventListener/LogoutListener.php

namespace App\EventListener;

use Symfony\Component\Security\Http\Event\LogoutEvent;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class LogoutListener
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function onLogoutSuccess(LogoutEvent $event)
    {
        $user = $event->getToken()->getUser();
        if ($user instanceof User) {
            // Set the attribute you want to change here
            // For example, to set the lastConnexion attribute to null:
            $this->entityManager->getRepository(User::class)->querySetLastConnexionNull($user);
            $this->entityManager->flush();
        }
    }
}
?>