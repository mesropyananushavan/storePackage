<?php

namespace StorePackage\WarehouseCore\Domain\Entity;

use StorePackage\WarehouseCore\Domain\Exception\InvalidQuantityException;
use StorePackage\WarehouseCore\Domain\Exception\InsufficientStockException;

class InventoryLot
{
    private $lotId;
    private $sku;
    private $warehouseId;
    private $locationId;
    private $receivedAt;
    private $quantityReceived;
    private $quantityRemaining;
    private $unitCost;
    private $currency;
    private $sourceDocument;
    private $parentLotId;

    public function __construct(
        $lotId,
        $sku,
        $warehouseId,
        $locationId,
        $receivedAt,
        $quantityReceived,
        $quantityRemaining,
        $unitCost,
        $currency,
        $sourceDocument,
        $parentLotId
    ) {
        if ($quantityReceived < 0 || $quantityRemaining < 0) {
            throw new InvalidQuantityException('Inventory lot quantities must be non-negative.');
        }

        $this->lotId = $lotId;
        $this->sku = $sku;
        $this->warehouseId = $warehouseId;
        $this->locationId = $locationId;
        $this->receivedAt = $receivedAt;
        $this->quantityReceived = $quantityReceived;
        $this->quantityRemaining = $quantityRemaining;
        $this->unitCost = (float) $unitCost;
        $this->currency = $currency;
        $this->sourceDocument = $sourceDocument;
        $this->parentLotId = $parentLotId;
    }

    public function getLotId()
    {
        return $this->lotId;
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

    public function getReceivedAt()
    {
        return $this->receivedAt;
    }

    public function getQuantityReceived()
    {
        return $this->quantityReceived;
    }

    public function getQuantityRemaining()
    {
        return $this->quantityRemaining;
    }

    public function getUnitCost()
    {
        return $this->unitCost;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getSourceDocument()
    {
        return $this->sourceDocument;
    }

    public function getParentLotId()
    {
        return $this->parentLotId;
    }

    public function isAvailable()
    {
        return $this->quantityRemaining > 0;
    }

    public function decreaseRemaining($quantity)
    {
        if ($quantity <= 0) {
            throw new InvalidQuantityException('Depletion quantity must be greater than zero.');
        }

        if ($quantity > $this->quantityRemaining) {
            throw new InsufficientStockException($this->sku, $quantity, $this->quantityRemaining);
        }

        $this->quantityRemaining = $this->quantityRemaining - $quantity;
    }

    public function increaseRemaining($quantity)
    {
        if ($quantity <= 0) {
            throw new InvalidQuantityException('Increase quantity must be greater than zero.');
        }

        $this->quantityRemaining = $this->quantityRemaining + $quantity;
        $this->quantityReceived = $this->quantityReceived + $quantity;
    }

    public function moveTo($warehouseId, $locationId)
    {
        $this->warehouseId = $warehouseId;
        $this->locationId = $locationId;
    }

    public function createTransferredLot($newLotId, $warehouseId, $locationId, $quantity, $sourceDocument)
    {
        return new self(
            $newLotId,
            $this->sku,
            $warehouseId,
            $locationId,
            $this->receivedAt,
            $quantity,
            $quantity,
            $this->unitCost,
            $this->currency,
            $sourceDocument,
            $this->lotId
        );
    }

    public function toArray()
    {
        return array(
            'lot_id' => $this->lotId,
            'sku' => $this->sku,
            'warehouse_id' => $this->warehouseId,
            'location_id' => $this->locationId,
            'received_at' => $this->receivedAt,
            'quantity_received' => $this->quantityReceived,
            'quantity_remaining' => $this->quantityRemaining,
            'unit_cost' => $this->unitCost,
            'currency' => $this->currency,
            'source_document' => $this->sourceDocument,
            'parent_lot_id' => $this->parentLotId,
        );
    }
}
