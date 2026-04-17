<?php

namespace StorePackage\WarehouseCore\Application\Config;

use StorePackage\WarehouseCore\Application\Exception\NoValuationStrategyConfiguredException;
use StorePackage\WarehouseCore\Contracts\InventoryValuationStrategyInterface;

class ValuationMethodResolver
{
    private $config;
    private $strategies;

    public function __construct(ValuationConfig $config)
    {
        $this->config = $config;
        $this->strategies = array();
    }

    public function registerStrategy(InventoryValuationStrategyInterface $strategy)
    {
        $this->strategies[$strategy->getMethod()] = $strategy;
    }

    public function resolveMethod($sku, $warehouseId, $explicitMethod)
    {
        return $this->config->resolve($sku, $warehouseId, $explicitMethod);
    }

    public function resolveStrategy($sku, $warehouseId, $explicitMethod)
    {
        $method = $this->resolveMethod($sku, $warehouseId, $explicitMethod);

        if (!isset($this->strategies[$method])) {
            throw new NoValuationStrategyConfiguredException($method);
        }

        return $this->strategies[$method];
    }
}
