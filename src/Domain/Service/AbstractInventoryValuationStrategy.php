<?php

namespace StorePackage\WarehouseCore\Domain\Service;

use StorePackage\WarehouseCore\Contracts\InventoryValuationStrategyInterface;
use StorePackage\WarehouseCore\Domain\Entity\CostAllocation;
use StorePackage\WarehouseCore\Domain\Entity\InventoryValuationResult;
use StorePackage\WarehouseCore\Domain\Exception\InsufficientStockException;
use StorePackage\WarehouseCore\Domain\Exception\InvalidCostAllocationException;

abstract class AbstractInventoryValuationStrategy implements InventoryValuationStrategyInterface
{
    protected function sumAvailableQuantity(array $lots)
    {
        $quantity = 0.0;
        foreach ($lots as $lot) {
            $quantity = $this->roundNumber($quantity + $lot->getQuantityRemaining());
        }

        return $quantity;
    }

    protected function sumAvailableCost(array $lots)
    {
        $cost = 0.0;
        foreach ($lots as $lot) {
            $cost = $this->roundNumber($cost + ($lot->getQuantityRemaining() * $lot->getUnitCost()));
        }

        return $cost;
    }

    protected function resolveCurrency(array $lots, array $context)
    {
        if (isset($context['currency']) && $context['currency'] !== null) {
            return $context['currency'];
        }

        if (empty($lots)) {
            return isset($context['fallback_currency']) ? $context['fallback_currency'] : null;
        }

        return $lots[0]->getCurrency();
    }

    protected function buildSequentialResult(array $lots, $requestedQuantity, $method, array $context, $costMode)
    {
        $requestedQuantity = $this->roundNumber($requestedQuantity);
        $availableQuantity = $this->sumAvailableQuantity($lots);
        if ($requestedQuantity > $availableQuantity) {
            $sku = isset($context['sku']) ? $context['sku'] : 'unknown';
            throw new InsufficientStockException($sku, $requestedQuantity, $availableQuantity);
        }

        $currency = $this->resolveCurrency($lots, $context);
        $remaining = (float) $requestedQuantity;
        $allocated = 0.0;
        $totalCost = 0.0;
        $allocations = array();
        $averageUnitCost = 0.0;
        $basisQuantity = $availableQuantity;
        $basisCost = $this->sumAvailableCost($lots);
        $targetTotalCost = null;

        if ($costMode === 'average') {
            $averageUnitCost = $availableQuantity > 0 ? $this->roundNumber($basisCost / $availableQuantity) : 0.0;
            $targetTotalCost = $this->roundNumber($requestedQuantity * $averageUnitCost);
        }

        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }

            $quantity = min($remaining, $lot->getQuantityRemaining());
            $quantity = $this->roundNumber($quantity);
            if ($quantity <= 0) {
                continue;
            }

            $unitCost = $costMode === 'average' ? $averageUnitCost : $lot->getUnitCost();
            $lineCost = $this->roundNumber($quantity * $unitCost);
            if ($costMode === 'average' && $this->roundNumber($remaining - $quantity) <= 0) {
                $lineCost = $this->roundNumber($targetTotalCost - $totalCost);
            }

            $allocations[] = new CostAllocation($lot->getLotId(), $quantity, $unitCost, $lineCost, $currency);
            $allocated = $this->roundNumber($allocated + $quantity);
            $totalCost = $this->roundNumber($totalCost + $lineCost);
            $remaining = $this->roundNumber($remaining - $quantity);
        }

        if ($this->roundNumber($allocated) !== $this->roundNumber($requestedQuantity)) {
            throw new InvalidCostAllocationException('Allocated quantity does not match requested quantity.');
        }

        if ($costMode !== 'average') {
            $averageUnitCost = $allocated > 0 ? $this->roundNumber($totalCost / $allocated) : 0.0;
        }

        return new InventoryValuationResult(
            $requestedQuantity,
            $allocated,
            $allocations,
            $this->roundNumber($totalCost),
            $averageUnitCost,
            $method,
            $currency,
            array(
                'basis_quantity' => $basisQuantity,
                'basis_cost' => $this->roundNumber($basisCost),
            )
        );
    }

    protected function roundNumber($number)
    {
        return round((float) $number, 6);
    }

    public function valuateOnHand(array $lots, array $context)
    {
        return $this->valuate($lots, $this->sumAvailableQuantity($lots), $context);
    }
}
