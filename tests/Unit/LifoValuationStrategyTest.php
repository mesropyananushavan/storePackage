<?php

namespace StorePackage\WarehouseCore\Tests\Unit;

use StorePackage\WarehouseCore\Tests\Doubles\BaseTestCase;
use StorePackage\WarehouseCore\Tests\Doubles\TestContext;

class LifoValuationStrategyTest extends BaseTestCase
{
    public function testLifoUsesNewestLotsFirst()
    {
        $context = new TestContext();
        $lotA = $context->seedReceipt('SKU-1', 'WH-1', 'BIN-A', 10, 5, '2026-01-01T00:00:00+00:00', 'PO-A');
        $lotB = $context->seedReceipt('SKU-1', 'WH-1', 'BIN-A', 20, 8, '2026-01-02T00:00:00+00:00', 'PO-B');

        $result = $context->valuation->getIssueValuation('SKU-1', 'WH-1', 'BIN-A', 15, 'lifo');

        $this->assertEquals(15, $result->getAllocatedQuantity());
        $this->assertEquals(120, $result->getTotalCost(), '', 0.0001);
        $this->assertCount(1, $result->getAllocations());
        $this->assertSame($lotB->getLotId(), $result->getAllocations()[0]->getLotId());
        $this->assertEquals(15, $result->getAllocations()[0]->getQuantity());
    }
}
