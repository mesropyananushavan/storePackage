<?php

namespace StorePackage\WarehouseCore\Domain\Entity;

class Location
{
    private $locationId;
    private $warehouseId;
    private $name;

    public function __construct($locationId, $warehouseId, $name)
    {
        $this->locationId = $locationId;
        $this->warehouseId = $warehouseId;
        $this->name = $name;
    }

    public function getLocationId()
    {
        return $this->locationId;
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
