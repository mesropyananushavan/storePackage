<?php

namespace StorePackage\WarehouseCore\Application\Service;

use StorePackage\WarehouseCore\Contracts\InventoryLotRepositoryInterface;
use StorePackage\WarehouseCore\Contracts\StockMovementRepositoryInterface;
use StorePackage\WarehouseCore\Domain\Entity\CostAllocation;
use StorePackage\WarehouseCore\Domain\Entity\GoodsReceipt;
use StorePackage\WarehouseCore\Domain\Entity\InventoryLot;
use StorePackage\WarehouseCore\Domain\Entity\StockMovement;
use StorePackage\WarehouseCore\Domain\ValueObject\MovementType;

class ReceiveStockService extends AbstractApplicationService
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

    public function receive(GoodsReceipt $receipt)
    {
        $this->requirePositiveQuantity($receipt->getQuantity(), 'Receipt quantity must be greater than zero.');

        $self = $this;

        return $this->transactions->transactional(function () use ($receipt, $self) {
            $operationId = $self->resolveOperationId($receipt->getReceiptId(), 'receipt');
            $receivedAt = $self->resolveTimestamp($receipt->getReceivedAt());
            $lotId = $self->ids->generate('lot');
            $movementId = $self->ids->generate('movement');

            $lot = new InventoryLot(
                $lotId,
                $receipt->getSku(),
                $receipt->getWarehouseId(),
                $receipt->getLocationId(),
                $receivedAt,
                $receipt->getQuantity(),
                $receipt->getQuantity(),
                $receipt->getUnitCost(),
                $receipt->getCurrency(),
                $receipt->getSourceDocument(),
                null
            );

            $allocation = new CostAllocation(
                $lotId,
                $receipt->getQuantity(),
                $receipt->getUnitCost(),
                round($receipt->getQuantity() * $receipt->getUnitCost(), 6),
                $receipt->getCurrency()
            );

            $movement = new StockMovement(
                $movementId,
                $operationId,
                MovementType::RECEIPT,
                $receipt->getSku(),
                $receipt->getWarehouseId(),
                $receipt->getLocationId(),
                $lotId,
                $receipt->getQuantity(),
                $receivedAt,
                null,
                array($allocation),
                $allocation->getTotalCost(),
                $receipt->getSourceDocument(),
                $receipt->getCurrency(),
                array('source' => 'receipt')
            );

            $self->lots->saveInventoryLot($lot);
            $self->movements->saveMovement($movement);
            $self->movements->saveCostAllocation($operationId, $allocation);
            $self->events->dispatch('inventory.received', $movement->toArray());
            $self->logger->info('Stock received.', array('operation_id' => $operationId, 'lot_id' => $lotId));

            return $lot;
        });
    }
}
