<?php

namespace StorePackage\WarehouseCore\Contracts;

interface IdGeneratorInterface
{
    /**
     * @param string $prefix
     * @return string
     */
    public function generate($prefix);
}
