<?php

namespace StorePackage\WarehouseCore\Application\Service;

use StorePackage\WarehouseCore\Contracts\InventoryLotRepositoryInterface;
use StorePackage\WarehouseCore\Contracts\StockMovementRepositoryInterface;
use StorePackage\WarehouseCore\Domain\Entity\CostAllocation;
use StorePackage\WarehouseCore\Domain\Entity\StockMovement;
use StorePackage\WarehouseCore\Domain\Exception\InsufficientStockException;
use StorePackage\WarehouseCore\Domain\Exception\InventoryLotNotFoundException;
use StorePackage\WarehouseCore\Domain\ValueObject\MovementType;

class MoveStockService extends AbstractApplicationService
{
    private $lots;
    private $movements;

    public function __construct(
        InventoryLotRepositoryInterface $lots,
        StockMovementRepositoryInterface $movements,
        $transactions,
        $clock,
        $ids,
        $events,
        $logger
    ) {
        parent::__construct($transactions, $clock, $ids, $events, $logger);
        $this->lots = $lots;
        $this->movements = $movements;
    }

    public function move($sku, $fromWarehouseId, $fromLocationId, $toWarehouseId, $toLocationId, $quantity, $sourceDocument, $operationId = null, $movedAt = null)
    {
        $this->requirePositiveQuantity($quantity, 'Move quantity must be greater than zero.');
        $self = $this;

        return $this->transactions->transactional(function () use ($sku, $fromWarehouseId, $fromLocationId, $toWarehouseId, $toLocationId, $quantity, $sourceDocument, $operationId, $movedAt, $self) {
            $lots = $self->lots->findAvailableLotsOrderedOldestFirst($sku, $fromWarehouseId, $fromLocationId);
            $available = $self->sumLotQuantity($lots);
            if ($quantity > $available) {
                throw new InsufficientStockException($sku, $quantity, $available);
            }

            $operationId = $self->resolveOperationId($operationId, 'transfer');
            $timestamp = $self->resolveTimestamp($movedAt);
            $remaining = $quantity;
            $allocations = array();

            foreach ($lots as $lot) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($lot->getQuantityRemaining(), $remaining);
                if ($take <= 0) {
                    continue;
                }

                $allocations[] = new CostAllocation(
                    $lot->getLotId(),
                    $take,
                    $lot->getUnitCost(),
                    round($take * $lot->getUnitCost(), 6),
                    $lot->getCurrency()
                );

                if ($take == $lot->getQuantityRemaining()) {
                    $lot->moveTo($toWarehouseId, $toLocationId);
                    $self->lots->saveInventoryLot($lot);
                } else {
                    $lot->decreaseRemaining($take);
                    $self->lots->saveInventoryLot($lot);
                    $transferredLot = $lot->createTransferredLot(
                        $self->ids->generate('lot'),
                        $toWarehouseId,
                        $toLocationId,
                        $take,
                        $sourceDocument
                    );
                    $self->lots->saveInventoryLot($transferredLot);
                }

                $remaining = $remaining - $take;
            }

            if ($remaining > 0) {
                throw new InventoryLotNotFoundException('transfer-allocation');
            }

            $totalCost = 0.0;
            $currency = null;
            foreach ($allocations as $allocation) {
                $totalCost = $totalCost + $allocation->getTotalCost();
                $currency = $allocation->getCurrency();
                $self->movements->saveCostAllocation($operationId, $allocation);
            }

            $outMovement = new StockMovement(
                $self->ids->generate('movement'),
                $operationId,
                MovementType::TRANSFER_OUT,
                $sku,
                $fromWarehouseId,
                $fromLocationId,
                null,
                0 - $quantity,
                $timestamp,
                null,
                $allocations,
                round($totalCost, 6),
                $sourceDocument,
                $currency,
                array('transfer_policy' => 'move-full-lot-or-split-partial')
            );

            $inMovement = new StockMovement(
                $self->ids->generate('movement'),
                $operationId,
                MovementType::TRANSFER_IN,
                $sku,
                $toWarehouseId,
                $toLocationId,
                null,
                $quantity,
                $timestamp,
                null,
                $allocations,
                round($totalCost, 6),
                $sourceDocument,
                $currency,
                array('transfer_policy' => 'move-full-lot-or-split-partial')
            );

            $self->movements->saveMovement($outMovement);
            $self->movements->saveMovement($inMovement);
            $self->events->dispatch('inventory.moved', array('operation_id' => $operationId));
            $self->logger->info('Stock moved.', array('operation_id' => $operationId));

            return array($outMovement, $inMovement);
        });
    }
}
