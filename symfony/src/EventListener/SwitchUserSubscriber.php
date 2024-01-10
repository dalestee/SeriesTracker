<?php

namespace App\EventListener;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\SecurityEvents;

class SwitchUserSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::SWITCH_USER => 'onSwitchUser',
        ];
    }

    public function onSwitchUser(SwitchUserEvent $event)
    {
        $targetUser = $event->getTargetUser();
        $currentUser = $event->getToken()->getUser();

        // If exiting impersonation, do nothing
        if ($event->getRequest()->get('_switch_user') === '_exit') {
            return;
        }

        // If the target user is a super admin, throw an exception
        if (in_array('ROLE_SUPER_ADMIN', $targetUser->getRoles())) {
            throw new AccessDeniedException();
        }

        // If the current user is not a super admin and the target user is an admin, throw an exception
        if (!in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles()) && in_array('ROLE_ADMIN', $targetUser->getRoles())) {
            throw new AccessDeniedException();
        }
    }
}
