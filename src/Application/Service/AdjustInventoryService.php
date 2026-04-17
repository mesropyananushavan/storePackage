<?php

namespace StorePackage\WarehouseCore\Application\Service;

use StorePackage\WarehouseCore\Application\Config\ValuationMethodResolver;
use StorePackage\WarehouseCore\Contracts\InventoryLotRepositoryInterface;
use StorePackage\WarehouseCore\Contracts\InventoryValuationSnapshotRepositoryInterface;
use StorePackage\WarehouseCore\Contracts\ReservationRepositoryInterface;
use StorePackage\WarehouseCore\Contracts\StockMovementRepositoryInterface;
use StorePackage\WarehouseCore\Domain\Entity\CostAllocation;
use StorePackage\WarehouseCore\Domain\Entity\InventoryAdjustment;
use StorePackage\WarehouseCore\Domain\Entity\InventoryLot;
use StorePackage\WarehouseCore\Domain\Entity\InventoryValuationSnapshot;
use StorePackage\WarehouseCore\Domain\Entity\StockMovement;
use StorePackage\WarehouseCore\Domain\Exception\InsufficientStockException;
use StorePackage\WarehouseCore\Domain\Exception\InvalidQuantityException;
use StorePackage\WarehouseCore\Domain\Exception\InventoryLotNotFoundException;
use StorePackage\WarehouseCore\Domain\ValueObject\MovementType;
use StorePackage\WarehouseCore\Domain\ValueObject\ValuationMethod;

class AdjustInventoryService extends AbstractApplicationService
{
    private $lots;
    private $movements;
    private $reservations;
    private $snapshots;
    private $resolver;

    public function __construct(
        InventoryLotRepositoryInterface $lots,
        StockMovementRepositoryInterface $movements,
        ReservationRepositoryInterface $reservations,
        InventoryValuationSnapshotRepositoryInterface $snapshots,
        ValuationMethodResolver $resolver,
        $transactions,
        $clock,
        $ids,
        $events,
        $logger
    ) {
        parent::__construct($transactions, $clock, $ids, $events, $logger);
        $this->lots = $lots;
        $this->movements = $movements;
        $this->reservations = $reservations;
        $this->snapshots = $snapshots;
        $this->resolver = $resolver;
    }

    public function adjust(InventoryAdjustment $adjustment)
    {
        if ($adjustment->getQuantityDelta() == 0) {
            throw new InvalidQuantityException('Adjustment quantity delta cannot be zero.');
        }

        if ($adjustment->getQuantityDelta() > 0) {
            return $this->applyPositiveAdjustment($adjustment);
        }

        return $this->applyNegativeAdjustment($adjustment);
    }

    private function applyPositiveAdjustment(InventoryAdjustment $adjustment)
    {
        $self = $this;

        return $this->transactions->transactional(function () use ($adjustment, $self) {
            $quantity = $adjustment->getQuantityDelta();
            $self->requirePositiveQuantity($quantity, 'Positive adjustment quantity must be greater than zero.');
            if ($adjustment->getUnitCost() === null || $adjustment->getCurrency() === null) {
                throw new InvalidQuantityException('Positive adjustment requires unit cost and currency.');
            }
            $timestamp = $self->resolveTimestamp($adjustment->getAdjustedAt());
            $operationId = $self->resolveOperationId($adjustment->getAdjustmentId(), 'adjustment');
            $lotId = $self->ids->generate('lot');
            $movementId = $self->ids->generate('movement');

            $lot = new InventoryLot(
                $lotId,
                $adjustment->getSku(),
                $adjustment->getWarehouseId(),
                $adjustment->getLocationId(),
                $timestamp,
                $quantity,
                $quantity,
                $adjustment->getUnitCost(),
                $adjustment->getCurrency(),
                $adjustment->getReason(),
                null
            );
            $self->lots->saveInventoryLot($lot);

            $allocation = new CostAllocation(
                $lotId,
                $quantity,
                $adjustment->getUnitCost(),
                round($quantity * $adjustment->getUnitCost(), 6),
                $adjustment->getCurrency()
            );

            $movement = new StockMovement(
                $movementId,
                $operationId,
                MovementType::ADJUSTMENT_IN,
                $adjustment->getSku(),
                $adjustment->getWarehouseId(),
                $adjustment->getLocationId(),
                $lotId,
                $quantity,
                $timestamp,
                null,
                array($allocation),
                $allocation->getTotalCost(),
                $adjustment->getReason(),
                $adjustment->getCurrency(),
                array('adjustment_reason' => $adjustment->getReason())
            );

            $self->movements->saveMovement($movement);
            $self->movements->saveCostAllocation($operationId, $allocation);
            $self->events->dispatch('inventory.adjusted_in', $movement->toArray());
            $self->logger->info('Positive inventory adjustment applied.', array('operation_id' => $operationId));

            return $movement;
        });
    }

    private function applyNegativeAdjustment(InventoryAdjustment $adjustment)
    {
        $self = $this;

        return $this->transactions->transactional(function () use ($adjustment, $self) {
            $quantity = abs($adjustment->getQuantityDelta());
            $strategy = $self->resolver->resolveStrategy(
                $adjustment->getSku(),
                $adjustment->getWarehouseId(),
                $adjustment->getValuationMethod()
            );
            $lots = $strategy->getLotsForValuation(
                $self->lots,
                $adjustment->getSku(),
                $adjustment->getWarehouseId(),
                $adjustment->getLocationId()
            );

            $onHand = $self->sumLotQuantity($lots);
            $reserved = $self->reservations->getReservedQuantity(
                $adjustment->getSku(),
                $adjustment->getWarehouseId(),
                $adjustment->getLocationId()
            );
            $available = $onHand - $reserved;
            if ($quantity > $available) {
                throw new InsufficientStockException($adjustment->getSku(), $quantity, $available);
            }

            $operationId = $self->resolveOperationId($adjustment->getAdjustmentId(), 'adjustment');
            $timestamp = $self->resolveTimestamp($adjustment->getAdjustedAt());
            $result = $strategy->valuate(
                $lots,
                $quantity,
                array(
                    'sku' => $adjustment->getSku(),
                    'warehouse_id' => $adjustment->getWarehouseId(),
                    'location_id' => $adjustment->getLocationId(),
                )
            );

            foreach ($result->getAllocations() as $allocation) {
                $lot = $self->lots->findById($allocation->getLotId());
                if ($lot === null) {
                    throw new InventoryLotNotFoundException($allocation->getLotId());
                }

                $lot->decreaseRemaining($allocation->getQuantity());
                $self->lots->saveInventoryLot($lot);
                $self->movements->saveCostAllocation($operationId, $allocation);
            }

            $movement = new StockMovement(
                $self->ids->generate('movement'),
                $operationId,
                MovementType::ADJUSTMENT_OUT,
                $adjustment->getSku(),
                $adjustment->getWarehouseId(),
                $adjustment->getLocationId(),
                null,
                0 - $quantity,
                $timestamp,
                $result->getValuationMethod(),
                $result->getAllocations(),
                $result->getTotalCost(),
                $adjustment->getReason(),
                $result->getCurrency(),
                array('adjustment_reason' => $adjustment->getReason())
            );
            $self->movements->saveMovement($movement);

            if ($result->getValuationMethod() === ValuationMethod::AVERAGE) {
                $metadata = $result->getMetadata();
                $snapshot = new InventoryValuationSnapshot(
                    $self->ids->generate('snapshot'),
                    $operationId,
                    $adjustment->getSku(),
                    $adjustment->getWarehouseId(),
                    $adjustment->getLocationId(),
                    $result->getValuationMethod(),
                    $result->getAverageUnitCost(),
                    $result->getCurrency(),
                    isset($metadata['basis_quantity']) ? $metadata['basis_quantity'] : 0,
                    isset($metadata['basis_cost']) ? $metadata['basis_cost'] : 0,
                    $timestamp
                );
                $self->snapshots->saveSnapshot($snapshot);
            }

            $self->events->dispatch('inventory.adjusted_out', $movement->toArray());
            $self->logger->info('Negative inventory adjustment applied.', array('operation_id' => $operationId));

            return $movement;
        });
    }
}
