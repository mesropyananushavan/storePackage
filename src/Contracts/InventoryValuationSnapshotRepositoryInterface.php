<?php

namespace StorePackage\WarehouseCore\Contracts;

use StorePackage\WarehouseCore\Domain\Entity\InventoryValuationSnapshot;

interface InventoryValuationSnapshotRepositoryInterface
{
    /**
     * @param InventoryValuationSnapshot $snapshot
     * @return void
     */
    public function saveSnapshot(InventoryValuationSnapshot $snapshot);

    /**
     * @param string $operationId
     * @return InventoryValuationSnapshot|null
     */
    public function findByOperationId($operationId);

    /**
     * @return InventoryValuationSnapshot[]
     */
    public function all();
}
