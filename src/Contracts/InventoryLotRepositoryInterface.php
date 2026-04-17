<?php

namespace StorePackage\WarehouseCore\Contracts;

use StorePackage\WarehouseCore\Domain\Entity\InventoryLot;

interface InventoryLotRepositoryInterface
{
    /**
     * @param string $sku
     * @param string $warehouseId
     * @param string|null $locationId
     * @return InventoryLot[]
     */
    public function findAvailableLotsBySku($sku, $warehouseId, $locationId);

    /**
     * @param string $sku
     * @param string $warehouseId
     * @param string|null $locationId
     * @return InventoryLot[]
     */
    public function findAvailableLotsOrderedOldestFirst($sku, $warehouseId, $locationId);

    /**
     * @param string $sku
     * @param string $warehouseId
     * @param string|null $locationId
     * @return InventoryLot[]
     */
    public function findAvailableLotsOrderedNewestFirst($sku, $warehouseId, $locationId);

    /**
     * @param string $sku
     * @param string $warehouseId
     * @param string|null $locationId
     * @return float
     */
    public function getWeightedAverageCost($sku, $warehouseId, $locationId);

    /**
     * @param InventoryLot $lot
     * @return void
     */
    public function saveInventoryLot(InventoryLot $lot);

    /**
     * @param InventoryLot[] $lots
     * @return void
     */
    public function saveInventoryLots(array $lots);

    /**
     * @param string[] $lotIds
     * @return InventoryLot[]
     */
    public function lockLotsForUpdate(array $lotIds);

    /**
     * @param string $lotId
     * @return InventoryLot|null
     */
    public function findById($lotId);

    /**
     * @return InventoryLot[]
     */
    public function all();
}
