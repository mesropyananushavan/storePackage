<?php

namespace StorePackage\WarehouseCore\Infrastructure\InMemory;

use StorePackage\WarehouseCore\Contracts\ReservationRepositoryInterface;
use StorePackage\WarehouseCore\Domain\Entity\Reservation;

class InMemoryReservationRepository implements ReservationRepositoryInterface
{
    private $reservations;

    public function __construct()
    {
        $this->reservations = array();
    }

    public function findReservation($reservationId)
    {
        return isset($this->reservations[$reservationId]) ? $this->reservations[$reservationId] : null;
    }

    public function saveReservation(Reservation $reservation)
    {
        $this->reservations[$reservation->getReservationId()] = $reservation;
    }

    public function getReservedQuantity($sku, $warehouseId, $locationId)
    {
        $reserved = 0.0;
        foreach ($this->reservations as $reservation) {
            if ($reservation->getSku() !== $sku) {
                continue;
            }

            if ($reservation->getWarehouseId() !== $warehouseId) {
                continue;
            }

            if ($locationId !== null && $reservation->getLocationId() !== $locationId) {
                continue;
            }

            if (!$reservation->isActive()) {
                continue;
            }

            $reserved = $reserved + $reservation->getActiveQuantity();
        }

        return $reserved;
    }

    public function findActiveReservationsBySku($sku, $warehouseId, $locationId)
    {
        $result = array();
        foreach ($this->reservations as $reservation) {
            if ($reservation->getSku() !== $sku) {
                continue;
            }

            if ($reservation->getWarehouseId() !== $warehouseId) {
                continue;
            }

            if ($locationId !== null && $reservation->getLocationId() !== $locationId) {
                continue;
            }

            if ($reservation->isActive()) {
                $result[] = $reservation;
            }
        }

        return $result;
    }

    public function all()
    {
        return array_values($this->reservations);
    }
}
