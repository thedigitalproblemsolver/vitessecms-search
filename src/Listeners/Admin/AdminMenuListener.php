<?php declare(strict_types=1);

namespace VitesseCms\Search\Listeners\Admin;

use VitesseCms\Admin\Models\AdminMenu;
use VitesseCms\Admin\Models\AdminMenuNavBarChildren;
use Phalcon\Events\Event;

class AdminMenuListener
{
    public function AddChildren(Event $event, AdminMenu $adminMenu): void
    {
        $children = new AdminMenuNavBarChildren();
        $children->addChild('Search', 'admin/search/adminindex/index');
        $adminMenu->addDropdown('System', $children);
    }
}
