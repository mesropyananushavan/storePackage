<?php

namespace StorePackage\WarehouseCore\Contracts;

interface TransactionManagerInterface
{
    /**
     * @param callable $operation
     * @return mixed
     */
    public function transactional($operation);
}
