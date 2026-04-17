<?php

namespace StorePackage\WarehouseCore\Tests\Unit;

use PDO;
use StorePackage\WarehouseCore\Application\Config\ValuationConfig;
use StorePackage\WarehouseCore\Application\Config\ValuationMethodResolver;
use StorePackage\WarehouseCore\Application\Service\GetAvailableStockService;
use StorePackage\WarehouseCore\Application\Service\GetInventoryValuationService;
use StorePackage\WarehouseCore\Application\Service\ReceiveStockService;
use StorePackage\WarehouseCore\Application\Service\ReserveStockService;
use StorePackage\WarehouseCore\Application\Service\ShipStockService;
use StorePackage\WarehouseCore\Domain\Entity\GoodsReceipt;
use StorePackage\WarehouseCore\Domain\Entity\Shipment;
use StorePackage\WarehouseCore\Domain\Service\AverageCostValuationStrategy;
use StorePackage\WarehouseCore\Domain\Service\FifoValuationStrategy;
use StorePackage\WarehouseCore\Domain\Service\LifoValuationStrategy;
use StorePackage\WarehouseCore\Infrastructure\Pdo\PdoInventoryLotRepository;
use StorePackage\WarehouseCore\Infrastructure\Pdo\PdoInventoryValuationSnapshotRepository;
use StorePackage\WarehouseCore\Infrastructure\Pdo\PdoReservationRepository;
use StorePackage\WarehouseCore\Infrastructure\Pdo\PdoStockMovementRepository;
use StorePackage\WarehouseCore\Infrastructure\Pdo\PdoTransactionManager;
use StorePackage\WarehouseCore\Infrastructure\Support\IncrementalIdGenerator;
use StorePackage\WarehouseCore\Infrastructure\Support\InMemoryEventDispatcher;
use StorePackage\WarehouseCore\Infrastructure\Support\NullLogger;
use StorePackage\WarehouseCore\Infrastructure\Support\SystemClock;
use StorePackage\WarehouseCore\Tests\Doubles\BaseTestCase;

class PdoReferenceAdapterTest extends BaseTestCase
{
    public function testReferencePdoAdapterPersistsWorkflowState()
    {
        $connection = $this->createConnection();
        $pdo = $connection['pdo'];

        $lotRepository = new PdoInventoryLotRepository($pdo);
        $movementRepository = new PdoStockMovementRepository($pdo);
        $reservationRepository = new PdoReservationRepository($pdo);
        $snapshotRepository = new PdoInventoryValuationSnapshotRepository($pdo);
        $transactions = new PdoTransactionManager($pdo);

        $config = new ValuationConfig('fifo');
        $resolver = new ValuationMethodResolver($config);
        $resolver->registerStrategy(new FifoValuationStrategy());
        $resolver->registerStrategy(new LifoValuationStrategy());
        $resolver->registerStrategy(new AverageCostValuationStrategy());

        $clock = new SystemClock();
        $ids = new IncrementalIdGenerator();
        $events = new InMemoryEventDispatcher();
        $logger = new NullLogger();

        $receive = new ReceiveStockService($lotRepository, $movementRepository, $transactions, $clock, $ids, $events, $logger);
        $reserve = new ReserveStockService($lotRepository, $reservationRepository, $movementRepository, $transactions, $clock, $ids, $events, $logger);
        $ship = new ShipStockService($lotRepository, $movementRepository, $reservationRepository, $snapshotRepository, $resolver, $transactions, $clock, $ids, $events, $logger);
        $available = new GetAvailableStockService($lotRepository, $reservationRepository);
        $valuation = new GetInventoryValuationService($lotRepository, $resolver);

        $receive->receive(new GoodsReceipt(null, 'SKU-DB-TEST', 'WH-1', 'BIN-A', '2026-01-01T00:00:00+00:00', 10, 5, 'USD', 'PO-A'));
        $receive->receive(new GoodsReceipt(null, 'SKU-DB-TEST', 'WH-1', 'BIN-A', '2026-01-02T00:00:00+00:00', 20, 8, 'USD', 'PO-B'));

        $fifo = $valuation->getIssueValuation('SKU-DB-TEST', 'WH-1', 'BIN-A', 15, 'fifo');
        $reserve->reserve('SKU-DB-TEST', 'WH-1', 'BIN-A', 3, 'ORDER-DB', 'RES-DB', '2026-01-03T00:00:00+00:00');
        $result = $ship->ship(new Shipment('SHIP-DB-TEST', 'SKU-DB-TEST', 'WH-1', 'BIN-A', 15, 'SO-DB', '2026-01-04T00:00:00+00:00', 'average'));

        $balance = $available->getBalance('SKU-DB-TEST', 'WH-1', 'BIN-A');
        $snapshot = $snapshotRepository->findByOperationId('SHIP-DB-TEST');
        $movements = $movementRepository->findByOperationId('SHIP-DB-TEST');

        $this->assertEquals(90, $fifo->getTotalCost(), '', 0.0001);
        $this->assertEquals(105, $result->getTotalCost(), '', 0.0001);
        $this->assertEquals(15, $balance->getOnHandQuantity(), '', 0.0001);
        $this->assertEquals(3, $balance->getReservedQuantity(), '', 0.0001);
        $this->assertEquals(12, $balance->getAvailableQuantity(), '', 0.0001);
        $this->assertNotNull($snapshot);
        $this->assertEquals(7, $snapshot->getAverageUnitCost(), '', 0.0001);
        $this->assertCount(1, $movements);
        $this->assertSame('average', $movements[0]->getValuationMethod());
        $this->assertCount(2, $movements[0]->getCostAllocations());
        $this->assertEquals(10, $movements[0]->getCostAllocations()[0]->getQuantity(), '', 0.0001);
        $this->assertEquals(7, $movements[0]->getCostAllocations()[0]->getUnitCost(), '', 0.0001);
        $this->assertEquals(5, $movements[0]->getCostAllocations()[1]->getQuantity(), '', 0.0001);
        $this->assertEquals(7, $movements[0]->getCostAllocations()[1]->getUnitCost(), '', 0.0001);
    }

    public function testReferencePdoTransactionManagerRollsBackOnException()
    {
        $connection = $this->createConnection();
        $pdo = $connection['pdo'];
        $lotRepository = new PdoInventoryLotRepository($pdo);
        $transactions = new PdoTransactionManager($pdo);

        try {
            $transactions->transactional(function () use ($lotRepository) {
                $lotRepository->saveInventoryLot(
                    new \StorePackage\WarehouseCore\Domain\Entity\InventoryLot(
                        'lot-rollback-test',
                        'SKU-ROLLBACK',
                        'WH-ROLLBACK',
                        'BIN-ROLLBACK',
                        '2026-01-10T00:00:00+00:00',
                        1,
                        1,
                        1,
                        'USD',
                        'ROLLBACK-CHECK',
                        null
                    )
                );

                throw new \RuntimeException('rollback-check');
            });
            $this->fail('Expected rollback-check exception.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('rollback-check', $exception->getMessage());
        }

        $this->assertNull($lotRepository->findById('lot-rollback-test'));
    }

    private function applySchema(PDO $pdo, $path)
    {
        $sql = file_get_contents($path);
        $this->resetSchema($pdo);
        $chunks = preg_split('/;\s*[\r\n]+/', $sql);

        foreach ($chunks as $chunk) {
            $statement = trim($chunk);
            if ($statement === '') {
                continue;
            }

            $pdo->exec($statement);
        }
    }

    private function resetSchema(PDO $pdo)
    {
        $tables = array(
            'stock_movement_cost_allocations',
            'stock_movements',
            'reservations',
            'valuation_snapshots',
            'inventory_lots',
        );

        foreach ($tables as $table) {
            $pdo->exec('DROP TABLE IF EXISTS ' . $table);
        }
    }

    private function createConnection()
    {
        $dsn = getenv('WAREHOUSE_DB_DSN');
        if ($dsn !== false && $dsn !== '') {
            $schema = getenv('WAREHOUSE_DB_SCHEMA_FILE');
            if ($schema === false || $schema === '') {
                if (strpos($dsn, 'mysql:') === 0) {
                    $schema = __DIR__ . '/../../database/schema/mysql.sql';
                } elseif (strpos($dsn, 'pgsql:') === 0) {
                    $schema = __DIR__ . '/../../database/schema/postgresql.sql';
                } else {
                    $schema = __DIR__ . '/../../database/schema/reference.sql';
                }
            }

            $pdo = new PDO(
                $dsn,
                getenv('WAREHOUSE_DB_USER') !== false ? getenv('WAREHOUSE_DB_USER') : null,
                getenv('WAREHOUSE_DB_PASSWORD') !== false ? getenv('WAREHOUSE_DB_PASSWORD') : null
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->applySchema($pdo, $schema);

            return array('pdo' => $pdo, 'schema' => $schema);
        }

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('ext-pdo_sqlite or WAREHOUSE_DB_DSN is required for the reference PDO adapter integration test.');
        }

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $schema = __DIR__ . '/../../database/schema/sqlite.sql';
        $this->applySchema($pdo, $schema);

        return array('pdo' => $pdo, 'schema' => $schema);
    }
}
