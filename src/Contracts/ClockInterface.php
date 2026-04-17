<?php

namespace StorePackage\WarehouseCore\Contracts;

interface ClockInterface
{
    /**
     * @return string ISO-8601 timestamp
     */
    public function now();
}
