<?php

namespace StorePackage\WarehouseCore\Domain\Entity;

class CostAllocation
{
    private $lotId;
    private $quantity;
    private $unitCost;
    private $totalCost;
    private $currency;

    public function __construct($lotId, $quantity, $unitCost, $totalCost, $currency)
    {
        $this->lotId = $lotId;
        $this->quantity = (float) $quantity;
        $this->unitCost = (float) $unitCost;
        $this->totalCost = (float) $totalCost;
        $this->currency = $currency;
    }

    public function getLotId()
    {
        return $this->lotId;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function getUnitCost()
    {
        return $this->unitCost;
    }

    public function getTotalCost()
    {
        return $this->totalCost;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function toArray()
    {
        return array(
            'lot_id' => $this->lotId,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unitCost,
            'total_cost' => $this->totalCost,
            'currency' => $this->currency,
        );
    }
}
