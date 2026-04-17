<?php

namespace StorePackage\WarehouseCore\Application\Config;

use StorePackage\WarehouseCore\Domain\ValueObject\ValuationMethod;

class ValuationConfig
{
    private $defaultMethod;
    private $warehouseMethods;
    private $skuMethods;

    public function __construct($defaultMethod, array $warehouseMethods = array(), array $skuMethods = array())
    {
        $this->defaultMethod = ValuationMethod::normalize($defaultMethod);
        $this->warehouseMethods = $warehouseMethods;
        $this->skuMethods = $skuMethods;
    }

    public function setWarehouseMethod($warehouseId, $method)
    {
        $this->warehouseMethods[$warehouseId] = ValuationMethod::normalize($method);
    }

    public function setSkuMethod($sku, $method)
    {
        $this->skuMethods[$sku] = ValuationMethod::normalize($method);
    }

    public function resolve($sku, $warehouseId, $explicitMethod)
    {
        if ($explicitMethod !== null) {
            return ValuationMethod::normalize($explicitMethod);
        }

        if (isset($this->skuMethods[$sku])) {
            return ValuationMethod::normalize($this->skuMethods[$sku]);
        }

        if (isset($this->warehouseMethods[$warehouseId])) {
            return ValuationMethod::normalize($this->warehouseMethods[$warehouseId]);
        }

        return $this->defaultMethod;
    }
}
