<?php

namespace StorePackage\WarehouseCore\Application\Service;

use StorePackage\WarehouseCore\Contracts\InventoryLotRepositoryInterface;
use StorePackage\WarehouseCore\Contracts\ReservationRepositoryInterface;
use StorePackage\WarehouseCore\Domain\Entity\InventoryBalance;

class GetAvailableStockService
{
    private $lots;
    private $reservations;

    public function __construct(
        InventoryLotRepositoryInterface $lots,
        ReservationRepositoryInterface $reservations
    ) {
        $this->lots = $lots;
        $this->reservations = $reservations;
    }

    public function getBalance($sku, $warehouseId, $locationId)
    {
        $lots = $this->lots->findAvailableLotsBySku($sku, $warehouseId, $locationId);
        $onHand = 0.0;
        foreach ($lots as $lot) {
            $onHand = $onHand + $lot->getQuantityRemaining();
        }

        $reserved = $this->reservations->getReservedQuantity($sku, $warehouseId, $locationId);

        return new InventoryBalance($sku, $warehouseId, $locationId, $onHand, $reserved);
    }
}
