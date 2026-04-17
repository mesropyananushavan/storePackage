<?php

namespace StorePackage\WarehouseCore\Domain\Exception;

class InsufficientStockException extends \RuntimeException
{
    public function __construct($sku, $requestedQuantity, $availableQuantity)
    {
        parent::__construct(
            sprintf(
                'Insufficient stock for SKU "%s". Requested %s, available %s.',
                $sku,
                $requestedQuantity,
                $availableQuantity
            )
        );
    }
}
