<?php

namespace StorePackage\WarehouseCore\Infrastructure\Support;

use StorePackage\WarehouseCore\Contracts\IdGeneratorInterface;

class IncrementalIdGenerator implements IdGeneratorInterface
{
    private $counters;

    public function __construct()
    {
        $this->counters = array();
    }

    public function generate($prefix)
    {
        if (!isset($this->counters[$prefix])) {
            $this->counters[$prefix] = 0;
        }

        $this->counters[$prefix] = $this->counters[$prefix] + 1;

        return sprintf('%s-%d', $prefix, $this->counters[$prefix]);
    }
}
