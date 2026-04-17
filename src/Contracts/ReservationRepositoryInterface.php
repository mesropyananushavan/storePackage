<?php

namespace StorePackage\WarehouseCore\Contracts;

use StorePackage\WarehouseCore\Domain\Entity\Reservation;

interface ReservationRepositoryInterface
{
    /**
     * @param string $reservationId
     * @return Reservation|null
     */
    public function findReservation($reservationId);

    /**
     * @param Reservation $reservation
     * @return void
     */
    public function saveReservation(Reservation $reservation);

    /**
     * @param string $sku
     * @param string $warehouseId
     * @param string|null $locationId
     * @return float
     */
    public function getReservedQuantity($sku, $warehouseId, $locationId);

    /**
     * @param string $sku
     * @param string $warehouseId
     * @param string|null $locationId
     * @return Reservation[]
     */
    public function findActiveReservationsBySku($sku, $warehouseId, $locationId);

    /**
     * @return Reservation[]
     */
    public function all();
}
