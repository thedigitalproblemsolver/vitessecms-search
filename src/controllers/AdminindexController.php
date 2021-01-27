<?php declare(strict_types=1);

namespace VitesseCms\Search\Controllers;

use Phalcon\Tag;
use VitesseCms\Core\AbstractController;
use VitesseCms\Content\Models\Item;
use VitesseCms\Search\Models\Elasticsearch;
use VitesseCms\Search\Repositories\AdminRepositoriesInterface;

class AdminindexController extends AbstractController implements AdminRepositoriesInterface
{
    public function indexAction(): void
    {
        $this->view->setVar('content',
            Tag::linkTo([
                'action' => 'admin/search/adminindex/populatefull',
                'class' => 'btn btn-info',
                'text' => 'Re-index all items'
            ]))
        ;
        parent::prepareView();
    }

    public function populateFullAction(): void
    {
        $this->search->deleteIndex();

        //TODO eerst naar filterbare datagroupen zoeken?
        //Item::setFindValue('roles',['$in' => [null,'']]);
        //Item::setFindValue('published',true);
        //Item::setFindValue('isFilterable',true);

        //foreach (Item::findAll() as $item) :
        $items = $this->repositories->item->getPopulateFull();
        while ($items->valid()):
            $this->search->add($items->current());
            $items->next();
        endwhile;

        $this->flash->setSucces('ADMIN_SEARCH_INDEX_REFRESHED');

        parent::redirect();
    }
}
