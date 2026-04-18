<?php

namespace StorePackage\WarehouseCore\Tests\Unit;

use StorePackage\WarehouseCore\Domain\Entity\GoodsReceipt;
use StorePackage\WarehouseCore\Domain\Entity\InventoryAdjustment;
use StorePackage\WarehouseCore\Domain\Entity\Shipment;
use StorePackage\WarehouseCore\Tests\Doubles\BaseTestCase;
use StorePackage\WarehouseCore\Tests\Doubles\TestContext;

class DecimalPrecisionWorkflowTest extends BaseTestCase
{
    public function testFractionalAverageWorkflowKeepsTotalsSnapshotsAndBalanceConsistent()
    {
        $context = new TestContext();

        $context->receive->receive(new GoodsReceipt('REC-FRAC-1', 'SKU-FRAC', 'WH-1', 'BIN-A', '2026-01-01T00:00:00+00:00', 1.111111, 2.222222, 'USD', 'PO-FRAC-1'));
        $context->receive->receive(new GoodsReceipt('REC-FRAC-2', 'SKU-FRAC', 'WH-1', 'BIN-A', '2026-01-02T00:00:00+00:00', 2.222222, 3.333333, 'USD', 'PO-FRAC-2'));

        $shipmentResult = $context->ship->ship(new Shipment('SHIP-FRAC-1', 'SKU-FRAC', 'WH-1', 'BIN-A', 1.234567, 'SO-FRAC-1', '2026-01-03T00:00:00+00:00', 'average'));
        $shipmentSnapshot = $context->snapshotRepository->findByOperationId('SHIP-FRAC-1');
        $shipmentMovement = $context->movementRepository->findByOperationId('SHIP-FRAC-1');

        $this->assertEquals(2.962963, $shipmentResult->getAverageUnitCost(), '', 0.000001);
        $this->assertEquals(3.657976, $shipmentResult->getTotalCost(), '', 0.000001);
        $this->assertEquals(3.333333, $shipmentSnapshot->getQuantityBasis(), '', 0.000001);
        $this->assertEquals(9.876541, $shipmentSnapshot->getTotalCostBasis(), '', 0.000001);
        $this->assertCount(2, $shipmentResult->getAllocations());
        $this->assertEquals(1.111111, $shipmentResult->getAllocations()[0]->getQuantity(), '', 0.000001);
        $this->assertEquals(3.292181, $shipmentResult->getAllocations()[0]->getTotalCost(), '', 0.000001);
        $this->assertEquals(0.123456, $shipmentResult->getAllocations()[1]->getQuantity(), '', 0.000001);
        $this->assertEquals(0.365795, $shipmentResult->getAllocations()[1]->getTotalCost(), '', 0.000001);
        $this->assertEquals(
            $shipmentResult->getTotalCost(),
            round($shipmentResult->getAllocations()[0]->getTotalCost() + $shipmentResult->getAllocations()[1]->getTotalCost(), 6),
            '',
            0.000001
        );
        $this->assertSame('average', $shipmentMovement[0]->getValuationMethod());

        $context->receive->receive(new GoodsReceipt('REC-FRAC-3', 'SKU-FRAC', 'WH-1', 'BIN-A', '2026-01-04T00:00:00+00:00', 0.444444, 4.444444, 'USD', 'PO-FRAC-3'));
        $adjustmentResult = $context->adjust->adjust(new InventoryAdjustment('ADJ-FRAC-1', 'SKU-FRAC', 'WH-1', 'BIN-A', -2.222222, null, null, 'cycle-count loss', '2026-01-05T00:00:00+00:00', 'average'));
        $adjustmentSnapshot = $context->snapshotRepository->findByOperationId('ADJ-FRAC-1');
        $balance = $context->available->getBalance('SKU-FRAC', 'WH-1', 'BIN-A');

        $this->assertEquals(3.527507, $adjustmentSnapshot->getAverageUnitCost(), '', 0.000001);
        $this->assertEquals(2.54321, $adjustmentSnapshot->getQuantityBasis(), '', 0.000001);
        $this->assertEquals(8.971192, $adjustmentSnapshot->getTotalCostBasis(), '', 0.000001);
        $this->assertEquals(7.838904, $adjustmentResult->getTotalCost(), '', 0.000001);
        $this->assertCount(2, $adjustmentResult->getCostAllocations());
        $this->assertEquals(2.098766, $adjustmentResult->getCostAllocations()[0]->getQuantity(), '', 0.000001);
        $this->assertEquals(7.403412, $adjustmentResult->getCostAllocations()[0]->getTotalCost(), '', 0.000001);
        $this->assertEquals(0.123456, $adjustmentResult->getCostAllocations()[1]->getQuantity(), '', 0.000001);
        $this->assertEquals(0.435492, $adjustmentResult->getCostAllocations()[1]->getTotalCost(), '', 0.000001);
        $this->assertEquals(0.320988, $balance->getAvailableQuantity(), '', 0.000001);

        $shipmentSnapshotAfterMoreOperations = $context->snapshotRepository->findByOperationId('SHIP-FRAC-1');
        $this->assertEquals(3.333333, $shipmentSnapshotAfterMoreOperations->getQuantityBasis(), '', 0.000001);
        $this->assertEquals(9.876541, $shipmentSnapshotAfterMoreOperations->getTotalCostBasis(), '', 0.000001);
    }
}
