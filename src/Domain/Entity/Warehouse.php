<?php

namespace StorePackage\WarehouseCore\Domain\Entity;

class Warehouse
{
    private $warehouseId;
    private $name;

    public function __construct($warehouseId, $name)
    {
        $this->warehouseId = $warehouseId;
        $this->name = $name;
    }

    public function getWarehouseId()
    {
        return $this->warehouseId;
    }

    public function getName()
    {
        return $this->name;
    }
}
