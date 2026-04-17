<?php

namespace StorePackage\WarehouseCore\Domain\Entity;

class GoodsReceipt
{
    private $receiptId;
    private $sku;
    private $warehouseId;
    private $locationId;
    private $receivedAt;
    private $quantity;
    private $unitCost;
    private $currency;
    private $sourceDocument;

    public function __construct(
        $receiptId,
        $sku,
        $warehouseId,
        $locationId,
        $receivedAt,
        $quantity,
        $unitCost,
        $currency,
        $sourceDocument
    ) {
        $this->receiptId = $receiptId;
        $this->sku = $sku;
        $this->warehouseId = $warehouseId;
        $this->locationId = $locationId;
        $this->receivedAt = $receivedAt;
        $this->quantity = (float) $quantity;
        $this->unitCost = (float) $unitCost;
        $this->currency = $currency;
        $this->sourceDocument = $sourceDocument;
    }

    public function getReceiptId()
    {
        return $this->receiptId;
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

    public function getQuantity()
    {
        return $this->quantity;
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
}
