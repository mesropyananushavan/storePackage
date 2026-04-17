<?php

namespace StorePackage\WarehouseCore\Infrastructure\Support;

use StorePackage\WarehouseCore\Contracts\TransactionManagerInterface;

class InMemoryTransactionManager implements TransactionManagerInterface
{
    public function transactional($operation)
    {
        return call_user_func($operation);
    }
}
