<?php

namespace StorePackage\WarehouseCore\Domain\Entity;

class Shipment
{
    private $shipmentId;
    private $sku;
    private $warehouseId;
    private $locationId;
    private $quantity;
    private $sourceDocument;
    private $shippedAt;
    private $valuationMethod;

    public function __construct(
        $shipmentId,
        $sku,
        $warehouseId,
        $locationId,
        $quantity,
        $sourceDocument,
        $shippedAt,
        $valuationMethod
    ) {
        $this->shipmentId = $shipmentId;
        $this->sku = $sku;
        $this->warehouseId = $warehouseId;
        $this->locationId = $locationId;
        $this->quantity = (float) $quantity;
        $this->sourceDocument = $sourceDocument;
        $this->shippedAt = $shippedAt;
        $this->valuationMethod = $valuationMethod;
    }

    public function getShipmentId()
    {
        return $this->shipmentId;
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

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function getSourceDocument()
    {
        return $this->sourceDocument;
    }

    public function getShippedAt()
    {
        return $this->shippedAt;
    }

    public function getValuationMethod()
    {
        return $this->valuationMethod;
    }
}
