<?php

namespace VitesseCms\Search;

use VitesseCms\Core\AbstractModule;
use Phalcon\DiInterface;

/**
 * Class Module
 */
class Module extends AbstractModule
{
    /**
     * {@inheritdoc}
     */
    public function registerServices(DiInterface $di, string $string = null)
    {
        parent::registerServices($di, 'Search');
    }
}
