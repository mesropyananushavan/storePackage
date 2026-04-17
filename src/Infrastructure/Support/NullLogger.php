<?php

namespace StorePackage\WarehouseCore\Infrastructure\Support;

use StorePackage\WarehouseCore\Contracts\LoggerInterface;

class NullLogger implements LoggerInterface
{
    public function info($message, array $context)
    {
    }

    public function error($message, array $context)
    {
    }
}
