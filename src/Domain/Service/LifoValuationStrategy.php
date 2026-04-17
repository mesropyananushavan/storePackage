<?php

namespace StorePackage\WarehouseCore\Domain\Service;

use StorePackage\WarehouseCore\Contracts\InventoryLotRepositoryInterface;
use StorePackage\WarehouseCore\Domain\ValueObject\ValuationMethod;

class LifoValuationStrategy extends AbstractInventoryValuationStrategy
{
    public function getMethod()
    {
        return ValuationMethod::LIFO;
    }

    public function getLotsForValuation(InventoryLotRepositoryInterface $repository, $sku, $warehouseId, $locationId)
    {
        return $repository->findAvailableLotsOrderedNewestFirst($sku, $warehouseId, $locationId);
    }

    public function valuate(array $lots, $requestedQuantity, array $context)
    {
        return $this->buildSequentialResult($lots, $requestedQuantity, $this->getMethod(), $context, 'actual');
    }
}
