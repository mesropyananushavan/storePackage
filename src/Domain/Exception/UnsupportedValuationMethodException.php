<?php

namespace StorePackage\WarehouseCore\Domain\Exception;

class UnsupportedValuationMethodException extends \InvalidArgumentException
{
    public function __construct($method)
    {
        parent::__construct(sprintf('Unsupported valuation method "%s".', $method));
    }
}
