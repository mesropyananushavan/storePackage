<?php

namespace StorePackage\WarehouseCore\Infrastructure\InMemory;

use StorePackage\WarehouseCore\Contracts\StockMovementRepositoryInterface;
use StorePackage\WarehouseCore\Domain\Entity\CostAllocation;
use StorePackage\WarehouseCore\Domain\Entity\StockMovement;

class InMemoryStockMovementRepository implements StockMovementRepositoryInterface
{
    private $movements;
    private $allocationsByOperation;

    public function __construct()
    {
        $this->movements = array();
        $this->allocationsByOperation = array();
    }

    public function saveMovement(StockMovement $movement)
    {
        $this->movements[] = $movement;
    }

    public function saveCostAllocation($operationId, CostAllocation $allocation)
    {
        if (!isset($this->allocationsByOperation[$operationId])) {
            $this->allocationsByOperation[$operationId] = array();
        }

        $this->allocationsByOperation[$operationId][] = $allocation;
    }

    public function findByOperationId($operationId)
    {
        $result = array();
        foreach ($this->movements as $movement) {
            if ($movement->getOperationId() === $operationId) {
                $result[] = $movement;
            }
        }

        return $result;
    }

    public function all()
    {
        return $this->movements;
    }

    public function getAllocationsByOperation($operationId)
    {
        return isset($this->allocationsByOperation[$operationId]) ? $this->allocationsByOperation[$operationId] : array();
    }
}
