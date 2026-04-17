<?php

namespace StorePackage\WarehouseCore\Domain\Exception;

class ReservationNotFoundException extends \RuntimeException
{
    public function __construct($reservationId)
    {
        parent::__construct(sprintf('Reservation "%s" was not found.', $reservationId));
    }
}
