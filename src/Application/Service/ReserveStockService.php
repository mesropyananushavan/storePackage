<?php

namespace StorePackage\WarehouseCore\Application\Service;

use StorePackage\WarehouseCore\Contracts\InventoryLotRepositoryInterface;
use StorePackage\WarehouseCore\Contracts\ReservationRepositoryInterface;
use StorePackage\WarehouseCore\Contracts\StockMovementRepositoryInterface;
use StorePackage\WarehouseCore\Domain\Entity\Reservation;
use StorePackage\WarehouseCore\Domain\Entity\StockMovement;
use StorePackage\WarehouseCore\Domain\Exception\InsufficientStockException;
use StorePackage\WarehouseCore\Domain\ValueObject\MovementType;

class ReserveStockService extends AbstractApplicationService
{
    private $lots;
    private $reservations;
    private $movements;

    public function __construct(
        InventoryLotRepositoryInterface $lots,
        ReservationRepositoryInterface $reservations,
        StockMovementRepositoryInterface $movements,
        $transactions,
        $clock,
        $ids,
        $events,
        $logger
    ) {
        parent::__construct($transactions, $clock, $ids, $events, $logger);
        $this->lots = $lots;
        $this->reservations = $reservations;
        $this->movements = $movements;
    }

    public function reserve($sku, $warehouseId, $locationId, $quantity, $reference, $reservationId = null, $reservedAt = null)
    {
        $this->requirePositiveQuantity($quantity, 'Reservation quantity must be greater than zero.');
        $self = $this;

        return $this->transactions->transactional(function () use ($sku, $warehouseId, $locationId, $quantity, $reference, $reservationId, $reservedAt, $self) {
            $lots = $self->lots->findAvailableLotsBySku($sku, $warehouseId, $locationId);
            $onHand = $self->sumLotQuantity($lots);
            $reserved = $self->reservations->getReservedQuantity($sku, $warehouseId, $locationId);
            $available = $onHand - $reserved;

            if ($quantity > $available) {
                throw new InsufficientStockException($sku, $quantity, $available);
            }

            $timestamp = $self->resolveTimestamp($reservedAt);
            $operationId = $self->resolveOperationId($reservationId, 'reservation');
            $movementId = $self->ids->generate('movement');
            $reservation = new Reservation(
                $operationId,
                $sku,
                $warehouseId,
                $locationId,
                $quantity,
                0,
                $reference,
                'active',
                $timestamp,
                null
            );

            $movement = new StockMovement(
                $movementId,
                $operationId,
                MovementType::RESERVATION,
                $sku,
                $warehouseId,
                $locationId,
                null,
                $quantity,
                $timestamp,
                null,
                array(),
                0,
                $reference,
                $self->detectCurrencyFromLots($lots, null),
                array('available_before' => $available)
            );

            $self->reservations->saveReservation($reservation);
            $self->movements->saveMovement($movement);
            $self->events->dispatch('inventory.reserved', $reservation->toArray());
            $self->logger->info('Stock reserved.', array('reservation_id' => $operationId));

            return $reservation;
        });
    }
}
