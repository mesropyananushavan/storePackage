<?php

namespace StorePackage\WarehouseCore\Infrastructure\Stub;

use BadMethodCallException;
use StorePackage\WarehouseCore\Contracts\InventoryLotRepositoryInterface;
use StorePackage\WarehouseCore\Domain\Entity\InventoryLot;

class DatabaseInventoryLotRepositoryStub implements InventoryLotRepositoryInterface
{
    private function notImplemented()
    {
        throw new BadMethodCallException('Replace this stub with a database-backed repository implementation.');
    }

    public function findAvailableLotsBySku($sku, $warehouseId, $locationId)
    {
        $this->notImplemented();
    }

    public function findAvailableLotsOrderedOldestFirst($sku, $warehouseId, $locationId)
    {
        $this->notImplemented();
    }

    public function findAvailableLotsOrderedNewestFirst($sku, $warehouseId, $locationId)
    {
        $this->notImplemented();
    }

    public function getWeightedAverageCost($sku, $warehouseId, $locationId)
    {
        $this->notImplemented();
    }

    public function saveInventoryLot(InventoryLot $lot)
    {
        $this->notImplemented();
    }

    public function saveInventoryLots(array $lots)
    {
        $this->notImplemented();
    }

    public function lockLotsForUpdate(array $lotIds)
    {
        $this->notImplemented();
    }

    public function findById($lotId)
    {
        $this->notImplemented();
    }

    public function all()
    {
        $this->notImplemented();
    }
}
