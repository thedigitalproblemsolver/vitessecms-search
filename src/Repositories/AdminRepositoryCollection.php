<?php declare(strict_types=1);

namespace VitesseCms\Search\Repositories;

use VitesseCms\Database\Interfaces\BaseRepositoriesInterface;

class AdminRepositoryCollection implements BaseRepositoriesInterface
{
    /**
     * @var ItemRepository
     */
    public $item;

    public function __construct(
        ItemRepository $itemRepository
    ) {
        $this->item = $itemRepository;
    }
}
