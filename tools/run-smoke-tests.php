<?php

require_once __DIR__ . '/project_autoload.php';

use StorePackage\WarehouseCore\Domain\Entity\InventoryAdjustment;
use StorePackage\WarehouseCore\Domain\Entity\Shipment;
use StorePackage\WarehouseCore\Tests\Doubles\TestContext;

function assertNear($actual, $expected, $message)
{
    if (abs($actual - $expected) > 0.0001) {
        throw new RuntimeException($message . sprintf(' Expected %s, got %s.', $expected, $actual));
    }
}

function assertTrueCondition($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$context = new TestContext();
$context->seedReceipt('SKU-1', 'WH-1', 'BIN-A', 10, 5, '2026-01-01T00:00:00+00:00', 'PO-A');
$context->seedReceipt('SKU-1', 'WH-1', 'BIN-A', 20, 8, '2026-01-02T00:00:00+00:00', 'PO-B');

$fifo = $context->valuation->getIssueValuation('SKU-1', 'WH-1', 'BIN-A', 15, 'fifo');
assertNear($fifo->getTotalCost(), 90, 'FIFO smoke-check failed.');
assertNear($fifo->getAllocations()[0]->getQuantity(), 10, 'FIFO first allocation quantity mismatch.');
assertNear($fifo->getAllocations()[1]->getQuantity(), 5, 'FIFO second allocation quantity mismatch.');

$average = $context->ship->ship(new Shipment('SHIP-AVG-SMOKE', 'SKU-1', 'WH-1', 'BIN-A', 15, 'SO-AVG', '2026-02-01T00:00:00+00:00', 'average'));
$snapshot = $context->snapshotRepository->findByOperationId('SHIP-AVG-SMOKE');
assertNear($average->getTotalCost(), 105, 'Average smoke-check failed.');
assertTrueCondition($snapshot !== null, 'Average snapshot was not created.');
assertNear($snapshot->getAverageUnitCost(), 7, 'Average snapshot unit cost mismatch.');

$workflow = new TestContext();
$workflow->seedReceipt('SKU-2', 'WH-1', 'BIN-A', 10, 5, '2026-01-01T00:00:00+00:00', 'PO-C');
$workflow->move->move('SKU-2', 'WH-1', 'BIN-A', 'WH-2', 'BIN-B', 4, 'MOVE-1', 'MOVE-OP-1', '2026-02-01T00:00:00+00:00');
$workflow->adjust->adjust(new InventoryAdjustment('ADJ-1', 'SKU-2', 'WH-2', 'BIN-B', 2, 6, 'USD', 'gain', '2026-02-02T00:00:00+00:00', null));
$negative = $workflow->adjust->adjust(new InventoryAdjustment('ADJ-2', 'SKU-2', 'WH-2', 'BIN-B', -3, null, null, 'loss', '2026-02-03T00:00:00+00:00', 'fifo'));
$balance = $workflow->available->getBalance('SKU-2', 'WH-2', 'BIN-B');
assertNear($negative->getTotalCost(), 15, 'Move/adjust smoke-check failed.');
assertNear($balance->getAvailableQuantity(), 3, 'Move/adjust balance smoke-check failed.');

fwrite(STDOUT, "Smoke checks passed.\n");
