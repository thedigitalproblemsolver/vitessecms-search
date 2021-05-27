<?php declare(strict_types=1);

namespace VitesseCms\Search\Listeners;

use VitesseCms\Core\Interfaces\InitiateListenersInterface;
use VitesseCms\Core\Interfaces\InjectableInterface;
use VitesseCms\Search\Listeners\Admin\AdminMenuListener;

class InitiateListeners implements InitiateListenersInterface
{
    public static function setListeners(InjectableInterface $di): void
    {
        if($di->user->hasAdminAccess()):
            $di->eventsManager->attach('adminMenu', new AdminMenuListener());
        endif;
    }
}
