<?php

namespace StorePackage\WarehouseCore\Domain\Exception;

class InventoryLotNotFoundException extends \RuntimeException
{
    public function __construct($lotId)
    {
        parent::__construct(sprintf('Inventory lot "%s" was not found.', $lotId));
    }
}
