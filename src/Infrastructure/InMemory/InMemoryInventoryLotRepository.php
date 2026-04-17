<?php

namespace StorePackage\WarehouseCore\Infrastructure\InMemory;

use StorePackage\WarehouseCore\Contracts\InventoryLotRepositoryInterface;
use StorePackage\WarehouseCore\Domain\Entity\InventoryLot;

class InMemoryInventoryLotRepository implements InventoryLotRepositoryInterface
{
    private $lots;

    public function __construct()
    {
        $this->lots = array();
    }

    public function findAvailableLotsBySku($sku, $warehouseId, $locationId)
    {
        $result = array();

        foreach ($this->lots as $lot) {
            if ($lot->getSku() !== $sku) {
                continue;
            }

            if ($lot->getWarehouseId() !== $warehouseId) {
                continue;
            }

            if ($locationId !== null && $lot->getLocationId() !== $locationId) {
                continue;
            }

            if (!$lot->isAvailable()) {
                continue;
            }

            $result[] = $lot;
        }

        return $result;
    }

    public function findAvailableLotsOrderedOldestFirst($sku, $warehouseId, $locationId)
    {
        $lots = $this->findAvailableLotsBySku($sku, $warehouseId, $locationId);
        usort($lots, function ($left, $right) {
            if ($left->getReceivedAt() === $right->getReceivedAt()) {
                return strcmp($left->getLotId(), $right->getLotId());
            }

            return strcmp($left->getReceivedAt(), $right->getReceivedAt());
        });

        return $lots;
    }

    public function findAvailableLotsOrderedNewestFirst($sku, $warehouseId, $locationId)
    {
        $lots = $this->findAvailableLotsOrderedOldestFirst($sku, $warehouseId, $locationId);
        return array_reverse($lots);
    }

    public function getWeightedAverageCost($sku, $warehouseId, $locationId)
    {
        $lots = $this->findAvailableLotsBySku($sku, $warehouseId, $locationId);
        $quantity = 0.0;
        $cost = 0.0;

        foreach ($lots as $lot) {
            $quantity = $quantity + $lot->getQuantityRemaining();
            $cost = $cost + ($lot->getQuantityRemaining() * $lot->getUnitCost());
        }

        if ($quantity <= 0) {
            return 0.0;
        }

        return round($cost / $quantity, 6);
    }

    public function saveInventoryLot(InventoryLot $lot)
    {
        $this->lots[$lot->getLotId()] = $lot;
    }

    public function saveInventoryLots(array $lots)
    {
        foreach ($lots as $lot) {
            $this->saveInventoryLot($lot);
        }
    }

    public function lockLotsForUpdate(array $lotIds)
    {
        $lots = array();
        foreach ($lotIds as $lotId) {
            if (isset($this->lots[$lotId])) {
                $lots[] = $this->lots[$lotId];
            }
        }

        return $lots;
    }

    public function findById($lotId)
    {
        return isset($this->lots[$lotId]) ? $this->lots[$lotId] : null;
    }

    public function all()
    {
        return array_values($this->lots);
    }
}
