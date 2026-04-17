<?php

namespace StorePackage\WarehouseCore\Contracts;

use StorePackage\WarehouseCore\Domain\Entity\InventoryValuationResult;

interface InventoryValuationStrategyInterface
{
    /**
     * @return string
     */
    public function getMethod();

    /**
     * @param InventoryLotRepositoryInterface $repository
     * @param string $sku
     * @param string $warehouseId
     * @param string|null $locationId
     * @return array
     */
    public function getLotsForValuation(InventoryLotRepositoryInterface $repository, $sku, $warehouseId, $locationId);

    /**
     * @param array $lots
     * @param float $requestedQuantity
     * @param array $context
     * @return InventoryValuationResult
     */
    public function valuate(array $lots, $requestedQuantity, array $context);

    /**
     * @param array $lots
     * @param array $context
     * @return InventoryValuationResult
     */
    public function valuateOnHand(array $lots, array $context);
}
