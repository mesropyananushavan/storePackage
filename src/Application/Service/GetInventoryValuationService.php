<?php

namespace StorePackage\WarehouseCore\Application\Service;

use StorePackage\WarehouseCore\Application\Config\ValuationMethodResolver;
use StorePackage\WarehouseCore\Contracts\InventoryLotRepositoryInterface;

class GetInventoryValuationService
{
    private $lots;
    private $resolver;

    public function __construct(
        InventoryLotRepositoryInterface $lots,
        ValuationMethodResolver $resolver
    ) {
        $this->lots = $lots;
        $this->resolver = $resolver;
    }

    public function getIssueValuation($sku, $warehouseId, $locationId, $quantity, $valuationMethod = null)
    {
        $strategy = $this->resolver->resolveStrategy($sku, $warehouseId, $valuationMethod);
        $lots = $strategy->getLotsForValuation($this->lots, $sku, $warehouseId, $locationId);

        return $strategy->valuate(
            $lots,
            $quantity,
            array(
                'sku' => $sku,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
            )
        );
    }

    public function getOnHandValuation($sku, $warehouseId, $locationId, $valuationMethod = null)
    {
        $strategy = $this->resolver->resolveStrategy($sku, $warehouseId, $valuationMethod);
        $lots = $strategy->getLotsForValuation($this->lots, $sku, $warehouseId, $locationId);

        return $strategy->valuateOnHand(
            $lots,
            array(
                'sku' => $sku,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
            )
        );
    }
}
