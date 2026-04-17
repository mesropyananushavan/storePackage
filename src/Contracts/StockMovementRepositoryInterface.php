<?php

namespace StorePackage\WarehouseCore\Contracts;

use StorePackage\WarehouseCore\Domain\Entity\CostAllocation;
use StorePackage\WarehouseCore\Domain\Entity\StockMovement;

interface StockMovementRepositoryInterface
{
    /**
     * @param StockMovement $movement
     * @return void
     */
    public function saveMovement(StockMovement $movement);

    /**
     * @param string $operationId
     * @param CostAllocation $allocation
     * @return void
     */
    public function saveCostAllocation($operationId, CostAllocation $allocation);

    /**
     * @param string $operationId
     * @return StockMovement[]
     */
    public function findByOperationId($operationId);

    /**
     * @return StockMovement[]
     */
    public function all();
}
