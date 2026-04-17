<?php

namespace StorePackage\WarehouseCore\Application\Service;

use StorePackage\WarehouseCore\Contracts\ReservationRepositoryInterface;
use StorePackage\WarehouseCore\Contracts\StockMovementRepositoryInterface;
use StorePackage\WarehouseCore\Domain\Entity\StockMovement;
use StorePackage\WarehouseCore\Domain\Exception\ReservationNotFoundException;
use StorePackage\WarehouseCore\Domain\ValueObject\MovementType;

class ReleaseReservationService extends AbstractApplicationService
{
    private $reservations;
    private $movements;

    public function __construct(
        ReservationRepositoryInterface $reservations,
        StockMovementRepositoryInterface $movements,
        $transactions,
        $clock,
        $ids,
        $events,
        $logger
    ) {
        parent::__construct($transactions, $clock, $ids, $events, $logger);
        $this->reservations = $reservations;
        $this->movements = $movements;
    }

    public function release($reservationId, $quantity = null, $releasedAt = null)
    {
        $self = $this;

        return $this->transactions->transactional(function () use ($reservationId, $quantity, $releasedAt, $self) {
            $reservation = $self->reservations->findReservation($reservationId);
            if ($reservation === null) {
                throw new ReservationNotFoundException($reservationId);
            }

            $timestamp = $self->resolveTimestamp($releasedAt);
            $releaseQuantity = $quantity === null ? $reservation->getActiveQuantity() : $quantity;
            $reservation->release($releaseQuantity, $timestamp);
            $self->reservations->saveReservation($reservation);

            $movement = new StockMovement(
                $self->ids->generate('movement'),
                $reservationId,
                MovementType::RELEASE,
                $reservation->getSku(),
                $reservation->getWarehouseId(),
                $reservation->getLocationId(),
                null,
                $releaseQuantity,
                $timestamp,
                null,
                array(),
                0,
                $reservation->getReference(),
                null,
                array('reservation_status' => $reservation->getStatus())
            );

            $self->movements->saveMovement($movement);
            $self->events->dispatch('inventory.reservation_released', $reservation->toArray());
            $self->logger->info('Reservation released.', array('reservation_id' => $reservationId));

            return $reservation;
        });
    }
}
