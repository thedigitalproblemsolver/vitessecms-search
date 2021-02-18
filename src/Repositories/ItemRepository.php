<?php declare(strict_types=1);

namespace VitesseCms\Search\Repositories;

use VitesseCms\Content\Models\ItemIterator;
use VitesseCms\Database\Models\FindValue;
use VitesseCms\Database\Models\FindValueIterator;

class ItemRepository extends \VitesseCms\Content\Repositories\ItemRepository
{
    public function getPopulateFull(): ItemIterator
    {
        return $this->findAll(
            new FindValueIterator([
                new FindValue('roles', ['$in' => [null, '']]),
            ]),
            true,
            29999
        );
    }
}
