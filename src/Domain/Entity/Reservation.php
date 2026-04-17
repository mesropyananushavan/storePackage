<?php

namespace StorePackage\WarehouseCore\Domain\Entity;

use StorePackage\WarehouseCore\Domain\Exception\InvalidQuantityException;

class Reservation
{
    private $reservationId;
    private $sku;
    private $warehouseId;
    private $locationId;
    private $quantity;
    private $releasedQuantity;
    private $reference;
    private $status;
    private $reservedAt;
    private $releasedAt;

    public function __construct(
        $reservationId,
        $sku,
        $warehouseId,
        $locationId,
        $quantity,
        $releasedQuantity,
        $reference,
        $status,
        $reservedAt,
        $releasedAt
    ) {
        if ($quantity <= 0) {
            throw new InvalidQuantityException('Reservation quantity must be greater than zero.');
        }

        $this->reservationId = $reservationId;
        $this->sku = $sku;
        $this->warehouseId = $warehouseId;
        $this->locationId = $locationId;
        $this->quantity = (float) $quantity;
        $this->releasedQuantity = (float) $releasedQuantity;
        $this->reference = $reference;
        $this->status = $status;
        $this->reservedAt = $reservedAt;
        $this->releasedAt = $releasedAt;
    }

    public function getReservationId()
    {
        return $this->reservationId;
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

    public function getReleasedQuantity()
    {
        return $this->releasedQuantity;
    }

    public function getReference()
    {
        return $this->reference;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getReservedAt()
    {
        return $this->reservedAt;
    }

    public function getReleasedAt()
    {
        return $this->releasedAt;
    }

    public function getActiveQuantity()
    {
        return $this->quantity - $this->releasedQuantity;
    }

    public function isActive()
    {
        return $this->getActiveQuantity() > 0;
    }

    public function release($quantity, $releasedAt)
    {
        if ($quantity === null) {
            $quantity = $this->getActiveQuantity();
        }

        if ($quantity <= 0) {
            throw new InvalidQuantityException('Released quantity must be greater than zero.');
        }

        if ($quantity > $this->getActiveQuantity()) {
            throw new InvalidQuantityException('Cannot release more than the active reservation quantity.');
        }

        $this->releasedQuantity = $this->releasedQuantity + $quantity;
        $this->releasedAt = $releasedAt;

        if ($this->getActiveQuantity() <= 0) {
            $this->status = 'released';
        }
    }

    public function toArray()
    {
        return array(
            'reservation_id' => $this->reservationId,
            'sku' => $this->sku,
            'warehouse_id' => $this->warehouseId,
            'location_id' => $this->locationId,
            'quantity' => $this->quantity,
            'released_quantity' => $this->releasedQuantity,
            'active_quantity' => $this->getActiveQuantity(),
            'reference' => $this->reference,
            'status' => $this->status,
            'reserved_at' => $this->reservedAt,
            'released_at' => $this->releasedAt,
        );
    }
}
