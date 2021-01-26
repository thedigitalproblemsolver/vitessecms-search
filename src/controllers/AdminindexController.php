<?php

namespace VitesseCms\Search\Controllers;

use VitesseCms\Core\AbstractController;
use VitesseCms\Content\Models\Item;
use VitesseCms\Search\Models\Elasticsearch;

/**
 * Class AdminindexController
 */
class AdminindexController extends AbstractController
{

    /**
     * indexAction
     */
    public function indexAction(): void
    {
        $this->view->setVar('content',
            '<a href="admin/search/adminindex/populatefull" class="btn btn-info">Re-index all items</a><br /><br />
            ');
        parent::prepareView();
    }

    /**
     * repopulate elasticsearch indexes
     * @throws \Phalcon\Mvc\Collection\Exception
     */
    public function populateFullAction(): void
    {
        $elasticSearch = new Elasticsearch();
        $elasticSearch->deleteIndex();

        //TODO eerst naar filterbare datagroupen zoeken?
        Item::setFindValue('roles',['$in' => [null,'']]);
        Item::setFindValue('published',true);
        Item::setFindValue('isFilterable',true);

        /** @var Item $item */
        foreach (Item::findAll() as $item) :
            $elasticSearch->add($item);
        endforeach;

        $this->flash->setSucces('ADMIN_SEARCH_INDEX_REFRESHED');

        parent::redirect();
    }
}
