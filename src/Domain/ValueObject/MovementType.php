<?php

namespace StorePackage\WarehouseCore\Domain\ValueObject;

class MovementType
{
    const RECEIPT = 'receipt';
    const SHIPMENT = 'shipment';
    const RESERVATION = 'reservation';
    const RELEASE = 'release';
    const TRANSFER_OUT = 'transfer_out';
    const TRANSFER_IN = 'transfer_in';
    const ADJUSTMENT_IN = 'adjustment_in';
    const ADJUSTMENT_OUT = 'adjustment_out';

    /**
     * @return string[]
     */
    public static function all()
    {
        return array(
            self::RECEIPT,
            self::SHIPMENT,
            self::RESERVATION,
            self::RELEASE,
            self::TRANSFER_OUT,
            self::TRANSFER_IN,
            self::ADJUSTMENT_IN,
            self::ADJUSTMENT_OUT,
        );
    }
}
