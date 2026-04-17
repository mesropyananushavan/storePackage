<?php

namespace StorePackage\WarehouseCore\Infrastructure\Support;

use StorePackage\WarehouseCore\Contracts\ClockInterface;

class SystemClock implements ClockInterface
{
    public function now()
    {
        return gmdate('c');
    }
}
