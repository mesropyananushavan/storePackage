<?php

namespace StorePackage\WarehouseCore\Tests\Unit;

use StorePackage\WarehouseCore\Tests\Doubles\BaseTestCase;
use StorePackage\WarehouseCore\Tests\Doubles\TestContext;

class AverageCostValuationStrategyTest extends BaseTestCase
{
    public function testAverageCostUsesWeightedAverage()
    {
        $context = new TestContext();
        $context->seedReceipt('SKU-1', 'WH-1', 'BIN-A', 10, 5, '2026-01-01T00:00:00+00:00', 'PO-A');
        $context->seedReceipt('SKU-1', 'WH-1', 'BIN-A', 20, 8, '2026-01-02T00:00:00+00:00', 'PO-B');

        $result = $context->valuation->getIssueValuation('SKU-1', 'WH-1', 'BIN-A', 15, 'average');

        $this->assertEquals(15, $result->getAllocatedQuantity());
        $this->assertEquals(7, $result->getAverageUnitCost(), '', 0.0001);
        $this->assertEquals(105, $result->getTotalCost(), '', 0.0001);
        $this->assertCount(2, $result->getAllocations());
        $this->assertEquals(7, $result->getAllocations()[0]->getUnitCost(), '', 0.0001);
        $this->assertEquals(30, $result->getMetadata()['basis_quantity']);
        $this->assertEquals(210, $result->getMetadata()['basis_cost'], '', 0.0001);
    }
}
