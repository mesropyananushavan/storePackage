<?php

namespace StorePackage\WarehouseCore\Domain\Entity;

class InventoryAdjustment
{
    private $adjustmentId;
    private $sku;
    private $warehouseId;
    private $locationId;
    private $quantityDelta;
    private $unitCost;
    private $currency;
    private $reason;
    private $adjustedAt;
    private $valuationMethod;

    public function __construct(
        $adjustmentId,
        $sku,
        $warehouseId,
        $locationId,
        $quantityDelta,
        $unitCost,
        $currency,
        $reason,
        $adjustedAt,
        $valuationMethod
    ) {
        $this->adjustmentId = $adjustmentId;
        $this->sku = $sku;
        $this->warehouseId = $warehouseId;
        $this->locationId = $locationId;
        $this->quantityDelta = (float) $quantityDelta;
        $this->unitCost = $unitCost === null ? null : (float) $unitCost;
        $this->currency = $currency;
        $this->reason = $reason;
        $this->adjustedAt = $adjustedAt;
        $this->valuationMethod = $valuationMethod;
    }

    public function getAdjustmentId()
    {
        return $this->adjustmentId;
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

    public function getQuantityDelta()
    {
        return $this->quantityDelta;
    }

    public function getUnitCost()
    {
        return $this->unitCost;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getReason()
    {
        return $this->reason;
    }

    public function getAdjustedAt()
    {
        return $this->adjustedAt;
    }

    public function getValuationMethod()
    {
        return $this->valuationMethod;
    }
}
