<?php

namespace StorePackage\WarehouseCore\Tests\Unit;

use StorePackage\WarehouseCore\Tests\Doubles\BaseTestCase;
use StorePackage\WarehouseCore\Tests\Doubles\TestContext;

class FifoValuationStrategyTest extends BaseTestCase
{
    public function testFifoUsesOldestLotsFirst()
    {
        $context = new TestContext();
        $lotA = $context->seedReceipt('SKU-1', 'WH-1', 'BIN-A', 10, 5, '2026-01-01T00:00:00+00:00', 'PO-A');
        $lotB = $context->seedReceipt('SKU-1', 'WH-1', 'BIN-A', 20, 8, '2026-01-02T00:00:00+00:00', 'PO-B');

        $result = $context->valuation->getIssueValuation('SKU-1', 'WH-1', 'BIN-A', 15, 'fifo');

        $this->assertEquals(15, $result->getAllocatedQuantity());
        $this->assertEquals(90, $result->getTotalCost(), '', 0.0001);
        $this->assertCount(2, $result->getAllocations());
        $this->assertSame($lotA->getLotId(), $result->getAllocations()[0]->getLotId());
        $this->assertSame($lotB->getLotId(), $result->getAllocations()[1]->getLotId());
        $this->assertEquals(10, $result->getAllocations()[0]->getQuantity());
        $this->assertEquals(5, $result->getAllocations()[1]->getQuantity());
    }
}
