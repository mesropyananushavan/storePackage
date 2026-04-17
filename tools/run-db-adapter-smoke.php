<?php

require_once __DIR__ . '/project_autoload.php';

use StorePackage\WarehouseCore\Application\Config\ValuationConfig;
use StorePackage\WarehouseCore\Application\Config\ValuationMethodResolver;
use StorePackage\WarehouseCore\Application\Service\GetAvailableStockService;
use StorePackage\WarehouseCore\Application\Service\GetInventoryValuationService;
use StorePackage\WarehouseCore\Application\Service\ReceiveStockService;
use StorePackage\WarehouseCore\Application\Service\ReserveStockService;
use StorePackage\WarehouseCore\Application\Service\ShipStockService;
use StorePackage\WarehouseCore\Domain\Entity\GoodsReceipt;
use StorePackage\WarehouseCore\Domain\Entity\InventoryLot;
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

function dbSmokeAssertNear($actual, $expected, $message)
{
    if (abs($actual - $expected) > 0.0001) {
        throw new RuntimeException($message . sprintf(' Expected %s, got %s.', $expected, $actual));
    }
}

function dbSmokeAssertTrue($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function dbSmokeResolveConnectionSettings()
{
    $dsn = getenv('WAREHOUSE_DB_DSN');
    if ($dsn !== false && $dsn !== '') {
        $defaultSchema = __DIR__ . '/../database/schema/reference.sql';
        if (strpos($dsn, 'mysql:') === 0) {
            $defaultSchema = __DIR__ . '/../database/schema/mysql.sql';
        } elseif (strpos($dsn, 'pgsql:') === 0) {
            $defaultSchema = __DIR__ . '/../database/schema/postgresql.sql';
        }

        return array(
            'dsn' => $dsn,
            'user' => getenv('WAREHOUSE_DB_USER') !== false ? getenv('WAREHOUSE_DB_USER') : null,
            'password' => getenv('WAREHOUSE_DB_PASSWORD') !== false ? getenv('WAREHOUSE_DB_PASSWORD') : null,
            'schema' => getenv('WAREHOUSE_DB_SCHEMA_FILE') !== false && getenv('WAREHOUSE_DB_SCHEMA_FILE') !== ''
                ? getenv('WAREHOUSE_DB_SCHEMA_FILE')
                : $defaultSchema,
        );
    }

    if (!extension_loaded('pdo_sqlite')) {
        fwrite(STDOUT, "Skipping DB adapter smoke test: set WAREHOUSE_DB_DSN or enable ext-pdo_sqlite.\n");
        exit(0);
    }

    return array(
        'dsn' => 'sqlite::memory:',
        'user' => null,
        'password' => null,
        'schema' => __DIR__ . '/../database/schema/sqlite.sql',
    );
}

function dbSmokeApplySchema($pdo, $path)
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException(sprintf('Unable to read schema file "%s".', $path));
    }

    dbSmokeResetSchema($pdo);

    $chunks = preg_split('/;\s*[\r\n]+/', $sql);
    foreach ($chunks as $chunk) {
        $statement = trim($chunk);
        if ($statement === '' || strpos($statement, '--') === 0 && strpos($statement, "\n") === false) {
            continue;
        }

        $pdo->exec($statement);
    }
}

function dbSmokeResetSchema($pdo)
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

function dbSmokeAssertTransactionRollback($transactions, $lotRepository)
{
    try {
        $transactions->transactional(function () use ($lotRepository) {
            $lotRepository->saveInventoryLot(
                new InventoryLot(
                    'lot-rollback-check',
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

            throw new RuntimeException('rollback-check');
        });
    } catch (RuntimeException $exception) {
        if ($exception->getMessage() !== 'rollback-check') {
            throw $exception;
        }
    }

    dbSmokeAssertTrue($lotRepository->findById('lot-rollback-check') === null, 'Reference DB adapter transaction rollback mismatch.');
}

$settings = dbSmokeResolveConnectionSettings();
$pdo = new PDO($settings['dsn'], $settings['user'], $settings['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

dbSmokeApplySchema($pdo, $settings['schema']);

$lotRepository = new PdoInventoryLotRepository($pdo);
$movementRepository = new PdoStockMovementRepository($pdo);
$reservationRepository = new PdoReservationRepository($pdo);
$snapshotRepository = new PdoInventoryValuationSnapshotRepository($pdo);

$config = new ValuationConfig('fifo');
$resolver = new ValuationMethodResolver($config);
$resolver->registerStrategy(new FifoValuationStrategy());
$resolver->registerStrategy(new LifoValuationStrategy());
$resolver->registerStrategy(new AverageCostValuationStrategy());

$clock = new SystemClock();
$ids = new IncrementalIdGenerator();
$events = new InMemoryEventDispatcher();
$logger = new NullLogger();
$transactions = new PdoTransactionManager($pdo);

dbSmokeAssertTransactionRollback($transactions, $lotRepository);

$receive = new ReceiveStockService($lotRepository, $movementRepository, $transactions, $clock, $ids, $events, $logger);
$reserve = new ReserveStockService($lotRepository, $reservationRepository, $movementRepository, $transactions, $clock, $ids, $events, $logger);
$ship = new ShipStockService($lotRepository, $movementRepository, $reservationRepository, $snapshotRepository, $resolver, $transactions, $clock, $ids, $events, $logger);
$available = new GetAvailableStockService($lotRepository, $reservationRepository);
$valuation = new GetInventoryValuationService($lotRepository, $resolver);

$receive->receive(new GoodsReceipt(null, 'SKU-DB-1', 'WH-1', 'BIN-A', '2026-01-01T00:00:00+00:00', 10, 5, 'USD', 'PO-DB-A'));
$receive->receive(new GoodsReceipt(null, 'SKU-DB-1', 'WH-1', 'BIN-A', '2026-01-02T00:00:00+00:00', 20, 8, 'USD', 'PO-DB-B'));

$fifo = $valuation->getIssueValuation('SKU-DB-1', 'WH-1', 'BIN-A', 15, 'fifo');
dbSmokeAssertNear($fifo->getTotalCost(), 90, 'Reference DB adapter FIFO valuation mismatch.');

$reservation = $reserve->reserve('SKU-DB-1', 'WH-1', 'BIN-A', 3, 'ORDER-DB-1', 'RES-DB-1', '2026-01-03T00:00:00+00:00');
dbSmokeAssertNear($reservation->getActiveQuantity(), 3, 'Reference DB adapter reservation mismatch.');

$shipment = $ship->ship(new Shipment('SHIP-DB-AVG', 'SKU-DB-1', 'WH-1', 'BIN-A', 15, 'SO-DB-AVG', '2026-01-04T00:00:00+00:00', 'average'));
dbSmokeAssertNear($shipment->getTotalCost(), 105, 'Reference DB adapter average shipment mismatch.');

$snapshot = $snapshotRepository->findByOperationId('SHIP-DB-AVG');
dbSmokeAssertTrue($snapshot !== null, 'Reference DB adapter did not persist the average valuation snapshot.');
dbSmokeAssertNear($snapshot->getAverageUnitCost(), 7, 'Reference DB adapter average snapshot mismatch.');

$balance = $available->getBalance('SKU-DB-1', 'WH-1', 'BIN-A');
dbSmokeAssertNear($balance->getOnHandQuantity(), 15, 'Reference DB adapter on-hand balance mismatch.');
dbSmokeAssertNear($balance->getReservedQuantity(), 3, 'Reference DB adapter reserved balance mismatch.');
dbSmokeAssertNear($balance->getAvailableQuantity(), 12, 'Reference DB adapter available balance mismatch.');

$movements = $movementRepository->findByOperationId('SHIP-DB-AVG');
dbSmokeAssertTrue(count($movements) === 1, 'Reference DB adapter shipment movement count mismatch.');
dbSmokeAssertTrue($movements[0]->getValuationMethod() === 'average', 'Reference DB adapter valuation method persistence mismatch.');
dbSmokeAssertTrue(count($movements[0]->getCostAllocations()) === 2, 'Reference DB adapter average allocation count mismatch.');
dbSmokeAssertNear($movements[0]->getCostAllocations()[0]->getQuantity(), 10, 'Reference DB adapter first average allocation quantity mismatch.');
dbSmokeAssertNear($movements[0]->getCostAllocations()[0]->getUnitCost(), 7, 'Reference DB adapter first average allocation unit cost mismatch.');
dbSmokeAssertNear($movements[0]->getCostAllocations()[1]->getQuantity(), 5, 'Reference DB adapter second average allocation quantity mismatch.');
dbSmokeAssertNear($movements[0]->getCostAllocations()[1]->getUnitCost(), 7, 'Reference DB adapter second average allocation unit cost mismatch.');

fwrite(STDOUT, sprintf("Reference DB adapter smoke checks passed on %s.\n", $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)));
