<?php

namespace StorePackage\WarehouseCore\Domain\Entity;

class InventoryBalance
{
    private $sku;
    private $warehouseId;
    private $locationId;
    private $onHandQuantity;
    private $reservedQuantity;
    private $availableQuantity;

    public function __construct($sku, $warehouseId, $locationId, $onHandQuantity, $reservedQuantity)
    {
        $this->sku = $sku;
        $this->warehouseId = $warehouseId;
        $this->locationId = $locationId;
        $this->onHandQuantity = (float) $onHandQuantity;
        $this->reservedQuantity = (float) $reservedQuantity;
        $this->availableQuantity = $this->onHandQuantity - $this->reservedQuantity;
    }

    public function getSku()
    {
        return $this->sku;
    }

    public function getWarehouseId()
    {
        return $this->warehouseId;
    }

    public function getLocationId()
    {
        return $this->locationId;
    }

    public function getOnHandQuantity()
    {
        return $this->onHandQuantity;
    }

    public function getReservedQuantity()
    {
        return $this->reservedQuantity;
    }

    public function getAvailableQuantity()
    {
        return $this->availableQuantity;
    }

    public function toArray()
    {
        return array(
            'sku' => $this->sku,
            'warehouse_id' => $this->warehouseId,
            'location_id' => $this->locationId,
            'on_hand_quantity' => $this->onHandQuantity,
            'reserved_quantity' => $this->reservedQuantity,
            'available_quantity' => $this->availableQuantity,
        );
    }
}
