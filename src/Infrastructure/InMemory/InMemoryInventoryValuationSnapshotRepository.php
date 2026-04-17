<?php

namespace StorePackage\WarehouseCore\Infrastructure\InMemory;

use StorePackage\WarehouseCore\Contracts\InventoryValuationSnapshotRepositoryInterface;
use StorePackage\WarehouseCore\Domain\Entity\InventoryValuationSnapshot;

class InMemoryInventoryValuationSnapshotRepository implements InventoryValuationSnapshotRepositoryInterface
{
    private $snapshots;

    public function __construct()
    {
        $this->snapshots = array();
    }

    public function saveSnapshot(InventoryValuationSnapshot $snapshot)
    {
        $this->snapshots[$snapshot->getOperationId()] = $snapshot;
    }

    public function findByOperationId($operationId)
    {
        return isset($this->snapshots[$operationId]) ? $this->snapshots[$operationId] : null;
    }

    public function all()
    {
        return array_values($this->snapshots);
    }
}
