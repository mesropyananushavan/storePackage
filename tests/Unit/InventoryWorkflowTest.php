<?php

namespace StorePackage\WarehouseCore\Tests\Unit;

use StorePackage\WarehouseCore\Domain\Entity\InventoryAdjustment;
use StorePackage\WarehouseCore\Domain\Entity\Shipment;
use StorePackage\WarehouseCore\Domain\Exception\InsufficientStockException;
use StorePackage\WarehouseCore\Tests\Doubles\BaseTestCase;
use StorePackage\WarehouseCore\Tests\Doubles\TestContext;

class InventoryWorkflowTest extends BaseTestCase
{
    public function testReservationReducesAvailabilityAndReleaseRestoresIt()
    {
        $context = new TestContext();
        $context->seedReceipt('SKU-1', 'WH-1', 'BIN-A', 10, 5, '2026-01-01T00:00:00+00:00', 'PO-A');

        $reservation = $context->reserve->reserve('SKU-1', 'WH-1', 'BIN-A', 4, 'ORDER-1');
        $balanceAfterReserve = $context->available->getBalance('SKU-1', 'WH-1', 'BIN-A');
        $context->release->release($reservation->getReservationId(), 2);
        $balanceAfterRelease = $context->available->getBalance('SKU-1', 'WH-1', 'BIN-A');

        $this->assertEquals(10, $balanceAfterReserve->getOnHandQuantity());
        $this->assertEquals(4, $balanceAfterReserve->getReservedQuantity());
        $this->assertEquals(6, $balanceAfterReserve->getAvailableQuantity());
        $this->assertEquals(2, $balanceAfterRelease->getReservedQuantity());
        $this->assertEquals(8, $balanceAfterRelease->getAvailableQuantity());
    }

    public function testShipmentFinalizesCorrectAllocationAndPersistsAudit()
    {
        $context = new TestContext();
        $lotA = $context->seedReceipt('SKU-1', 'WH-1', 'BIN-A', 10, 5, '2026-01-01T00:00:00+00:00', 'PO-A');
        $lotB = $context->seedReceipt('SKU-1', 'WH-1', 'BIN-A', 20, 8, '2026-01-02T00:00:00+00:00', 'PO-B');

        $result = $context->ship->ship(new Shipment('SHIP-1', 'SKU-1', 'WH-1', 'BIN-A', 15, 'SO-1', '2026-02-01T00:00:00+00:00', 'fifo'));
        $movements = $context->movementRepository->findByOperationId('SHIP-1');

        $this->assertEquals(90, $result->getTotalCost(), '', 0.0001);
        $this->assertEquals(0, $context->lotRepository->findById($lotA->getLotId())->getQuantityRemaining());
        $this->assertEquals(15, $context->lotRepository->findById($lotB->getLotId())->getQuantityRemaining());
        $this->assertCount(1, $movements);
        $this->assertSame('fifo', $movements[0]->getValuationMethod());
        $this->assertCount(2, $movements[0]->getCostAllocations());
    }

    public function testInsufficientStockExceptionIsRaisedAgainstAvailableBalance()
    {
        $this->expectException(InsufficientStockException::class);

        $context = new TestContext();
        $context->seedReceipt('SKU-1', 'WH-1', 'BIN-A', 10, 5, '2026-01-01T00:00:00+00:00', 'PO-A');
        $context->reserve->reserve('SKU-1', 'WH-1', 'BIN-A', 8, 'ORDER-1');
        $context->ship->ship(new Shipment('SHIP-2', 'SKU-1', 'WH-1', 'BIN-A', 3, 'SO-2', '2026-02-01T00:00:00+00:00', 'fifo'));
    }

    public function testMoveDoesNotTransferReservedStockOutOfSourceLocation()
    {
        $context = new TestContext();
        $context->seedReceipt('SKU-1', 'WH-1', 'BIN-A', 10, 5, '2026-01-01T00:00:00+00:00', 'PO-A');
        $context->reserve->reserve('SKU-1', 'WH-1', 'BIN-A', 8, 'ORDER-1');

        try {
            $context->move->move('SKU-1', 'WH-1', 'BIN-A', 'WH-2', 'BIN-B', 3, 'MOVE-1', 'MOVE-OP-1', '2026-02-01T00:00:00+00:00');
            $this->fail('Expected move to reject transferring reserved stock.');
        } catch (InsufficientStockException $exception) {
            $this->assertSame('Insufficient stock for SKU "SKU-1". Requested 3, available 2.', $exception->getMessage());
        }

        $sourceBalance = $context->available->getBalance('SKU-1', 'WH-1', 'BIN-A');
        $targetBalance = $context->available->getBalance('SKU-1', 'WH-2', 'BIN-B');

        $this->assertEquals(10, $sourceBalance->getOnHandQuantity(), '', 0.0001);
        $this->assertEquals(8, $sourceBalance->getReservedQuantity(), '', 0.0001);
        $this->assertEquals(2, $sourceBalance->getAvailableQuantity(), '', 0.0001);
        $this->assertEquals(0, $targetBalance->getOnHandQuantity(), '', 0.0001);
        $movements = $context->movementRepository->all();

        $this->assertCount(2, $movements);
        $this->assertSame('receipt', $movements[0]->getMovementType());
        $this->assertSame('reservation', $movements[1]->getMovementType());
    }

    public function testAverageValuationCreatesReproducibleSnapshot()
    {
        $context = new TestContext();
        $context->seedReceipt('SKU-1', 'WH-1', 'BIN-A', 10, 5, '2026-01-01T00:00:00+00:00', 'PO-A');
        $context->seedReceipt('SKU-1', 'WH-1', 'BIN-A', 20, 8, '2026-01-02T00:00:00+00:00', 'PO-B');

        $result = $context->ship->ship(new Shipment('SHIP-AVG-1', 'SKU-1', 'WH-1', 'BIN-A', 15, 'SO-AVG', '2026-02-01T00:00:00+00:00', 'average'));
        $snapshot = $context->snapshotRepository->findByOperationId('SHIP-AVG-1');
        $movement = $context->movementRepository->findByOperationId('SHIP-AVG-1');

        $this->assertEquals(105, $result->getTotalCost(), '', 0.0001);
        $this->assertEquals(7, $snapshot->getAverageUnitCost(), '', 0.0001);
        $this->assertEquals(30, $snapshot->getQuantityBasis(), '', 0.0001);
        $this->assertEquals(210, $snapshot->getTotalCostBasis(), '', 0.0001);
        $this->assertSame('average', $movement[0]->getValuationMethod());
    }

    public function testMoveAndAdjustFlowRemainCostTraceable()
    {
        $context = new TestContext();
        $context->seedReceipt('SKU-1', 'WH-1', 'BIN-A', 10, 5, '2026-01-01T00:00:00+00:00', 'PO-A');
        $context->move->move('SKU-1', 'WH-1', 'BIN-A', 'WH-2', 'BIN-B', 4, 'MOVE-1', 'MOVE-OP-1', '2026-02-01T00:00:00+00:00');
        $context->adjust->adjust(new InventoryAdjustment('ADJ-1', 'SKU-1', 'WH-2', 'BIN-B', 2, 6, 'USD', 'gain', '2026-02-02T00:00:00+00:00', null));
        $negative = $context->adjust->adjust(new InventoryAdjustment('ADJ-2', 'SKU-1', 'WH-2', 'BIN-B', -3, null, null, 'loss', '2026-02-03T00:00:00+00:00', 'fifo'));

        $balance = $context->available->getBalance('SKU-1', 'WH-2', 'BIN-B');

        $this->assertEquals(-3, $negative->getQuantity());
        $this->assertEquals(15, $negative->getTotalCost(), '', 0.0001);
        $this->assertEquals(3, $balance->getAvailableQuantity(), '', 0.0001);
    }
}
