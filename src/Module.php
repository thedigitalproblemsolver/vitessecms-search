<?php declare(strict_types=1);

namespace VitesseCms\Search;

use VitesseCms\Admin\Utils\AdminUtil;
use VitesseCms\Core\AbstractModule;
use Phalcon\DiInterface;
use VitesseCms\Search\Repositories\AdminRepositoryCollection;
use VitesseCms\Search\Repositories\ItemRepository;

class Module extends AbstractModule
{
    public function registerServices(DiInterface $di, string $string = null)
    {
        parent::registerServices($di, 'Search');

        if (AdminUtil::isAdminPage()) :
            $di->setShared('repositories', new AdminRepositoryCollection(
                new ItemRepository()
            ));
        endif;
    }
}
