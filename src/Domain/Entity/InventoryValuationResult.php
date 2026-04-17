<?php

namespace StorePackage\WarehouseCore\Domain\Entity;

class InventoryValuationResult
{
    private $requestedQuantity;
    private $allocatedQuantity;
    private $allocations;
    private $totalCost;
    private $averageUnitCost;
    private $valuationMethod;
    private $currency;
    private $metadata;

    /**
     * @param CostAllocation[] $allocations
     * @param array $metadata
     */
    public function __construct(
        $requestedQuantity,
        $allocatedQuantity,
        array $allocations,
        $totalCost,
        $averageUnitCost,
        $valuationMethod,
        $currency,
        array $metadata
    ) {
        $this->requestedQuantity = (float) $requestedQuantity;
        $this->allocatedQuantity = (float) $allocatedQuantity;
        $this->allocations = $allocations;
        $this->totalCost = (float) $totalCost;
        $this->averageUnitCost = (float) $averageUnitCost;
        $this->valuationMethod = $valuationMethod;
        $this->currency = $currency;
        $this->metadata = $metadata;
    }

    public function getRequestedQuantity()
    {
        return $this->requestedQuantity;
    }

    public function getAllocatedQuantity()
    {
        return $this->allocatedQuantity;
    }

    public function getAllocations()
    {
        return $this->allocations;
    }

    public function getTotalCost()
    {
        return $this->totalCost;
    }

    public function getAverageUnitCost()
    {
        return $this->averageUnitCost;
    }

    public function getValuationMethod()
    {
        return $this->valuationMethod;
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
        foreach ($this->allocations as $allocation) {
            $allocations[] = $allocation->toArray();
        }

        return array(
            'requested_quantity' => $this->requestedQuantity,
            'allocated_quantity' => $this->allocatedQuantity,
            'allocations' => $allocations,
            'total_cost' => $this->totalCost,
            'average_unit_cost' => $this->averageUnitCost,
            'valuation_method' => $this->valuationMethod,
            'currency' => $this->currency,
            'metadata' => $this->metadata,
        );
    }
}
