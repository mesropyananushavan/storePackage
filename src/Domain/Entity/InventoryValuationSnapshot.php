<?php

namespace StorePackage\WarehouseCore\Domain\Entity;

class InventoryValuationSnapshot
{
    private $snapshotId;
    private $operationId;
    private $sku;
    private $warehouseId;
    private $locationId;
    private $valuationMethod;
    private $averageUnitCost;
    private $currency;
    private $quantityBasis;
    private $totalCostBasis;
    private $createdAt;

    public function __construct(
        $snapshotId,
        $operationId,
        $sku,
        $warehouseId,
        $locationId,
        $valuationMethod,
        $averageUnitCost,
        $currency,
        $quantityBasis,
        $totalCostBasis,
        $createdAt
    ) {
        $this->snapshotId = $snapshotId;
        $this->operationId = $operationId;
        $this->sku = $sku;
        $this->warehouseId = $warehouseId;
        $this->locationId = $locationId;
        $this->valuationMethod = $valuationMethod;
        $this->averageUnitCost = (float) $averageUnitCost;
        $this->currency = $currency;
        $this->quantityBasis = (float) $quantityBasis;
        $this->totalCostBasis = (float) $totalCostBasis;
        $this->createdAt = $createdAt;
    }

    public function getSnapshotId()
    {
        return $this->snapshotId;
    }

    public function getOperationId()
    {
        return $this->operationId;
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

    public function getValuationMethod()
    {
        return $this->valuationMethod;
    }

    public function getAverageUnitCost()
    {
        return $this->averageUnitCost;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getQuantityBasis()
    {
        return $this->quantityBasis;
    }

    public function getTotalCostBasis()
    {
        return $this->totalCostBasis;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
