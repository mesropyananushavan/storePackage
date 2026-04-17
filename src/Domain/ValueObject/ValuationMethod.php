<?php

namespace StorePackage\WarehouseCore\Domain\ValueObject;

use StorePackage\WarehouseCore\Domain\Exception\UnsupportedValuationMethodException;

class ValuationMethod
{
    const FIFO = 'fifo';
    const LIFO = 'lifo';
    const AVERAGE = 'average';

    /**
     * @return string[]
     */
    public static function all()
    {
        return array(
            self::FIFO,
            self::LIFO,
            self::AVERAGE,
        );
    }

    /**
     * @param string $method
     * @return string
     */
    public static function normalize($method)
    {
        $normalized = strtolower((string) $method);
        if (!in_array($normalized, self::all(), true)) {
            throw new UnsupportedValuationMethodException($method);
        }

        return $normalized;
    }
}
