<?php

namespace StorePackage\WarehouseCore\Domain\Entity;

class StockMovement
{
    private $movementId;
    private $operationId;
    private $movementType;
    private $sku;
    private $warehouseId;
    private $locationId;
    private $lotId;
    private $quantity;
    private $timestamp;
    private $valuationMethod;
    private $costAllocations;
    private $totalCost;
    private $sourceDocument;
    private $currency;
    private $metadata;

    /**
     * @param CostAllocation[] $costAllocations
     * @param array $metadata
     */
    public function __construct(
        $movementId,
        $operationId,
        $movementType,
        $sku,
        $warehouseId,
        $locationId,
        $lotId,
        $quantity,
        $timestamp,
        $valuationMethod,
        array $costAllocations,
        $totalCost,
        $sourceDocument,
        $currency,
        array $metadata
    ) {
        $this->movementId = $movementId;
        $this->operationId = $operationId;
        $this->movementType = $movementType;
        $this->sku = $sku;
        $this->warehouseId = $warehouseId;
        $this->locationId = $locationId;
        $this->lotId = $lotId;
        $this->quantity = (float) $quantity;
        $this->timestamp = $timestamp;
        $this->valuationMethod = $valuationMethod;
        $this->costAllocations = $costAllocations;
        $this->totalCost = (float) $totalCost;
        $this->sourceDocument = $sourceDocument;
        $this->currency = $currency;
        $this->metadata = $metadata;
    }

    public function getMovementId()
    {
        return $this->movementId;
    }

    public function getOperationId()
    {
        return $this->operationId;
    }

    public function getMovementType()
    {
        return $this->movementType;
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

    public function getLotId()
    {
        return $this->lotId;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getValuationMethod()
    {
        return $this->valuationMethod;
    }

    public function getCostAllocations()
    {
        return $this->costAllocations;
    }

    public function getTotalCost()
    {
        return $this->totalCost;
    }

    public function getSourceDocument()
    {
        return $this->sourceDocument;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function toArray()
    {
        $allocations = array();
        foreach ($this->costAllocations as $allocation) {
            $allocations[] = $allocation->toArray();
        }

        return array(
            'movement_id' => $this->movementId,
            'operation_id' => $this->operationId,
            'movement_type' => $this->movementType,
            'sku' => $this->sku,
            'warehouse_id' => $this->warehouseId,
            'location_id' => $this->locationId,
            'lot_id' => $this->lotId,
            'quantity' => $this->quantity,
            'timestamp' => $this->timestamp,
            'valuation_method' => $this->valuationMethod,
            'cost_allocations' => $allocations,
            'total_cost' => $this->totalCost,
            'source_document' => $this->sourceDocument,
            'currency' => $this->currency,
            'metadata' => $this->metadata,
        );
    }
}
