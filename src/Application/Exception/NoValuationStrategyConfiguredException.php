<?php

namespace StorePackage\WarehouseCore\Application\Exception;

class NoValuationStrategyConfiguredException extends \RuntimeException
{
    public function __construct($method)
    {
        parent::__construct(sprintf('No valuation strategy configured for method "%s".', $method));
    }
}
