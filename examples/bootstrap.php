<?php

use StorePackage\WarehouseCore\Application\Config\ValuationConfig;
use StorePackage\WarehouseCore\Application\Config\ValuationMethodResolver;
use StorePackage\WarehouseCore\Application\Service\AdjustInventoryService;
use StorePackage\WarehouseCore\Application\Service\GetAvailableStockService;
use StorePackage\WarehouseCore\Application\Service\GetInventoryValuationService;
use StorePackage\WarehouseCore\Application\Service\MoveStockService;
use StorePackage\WarehouseCore\Application\Service\ReceiveStockService;
use StorePackage\WarehouseCore\Application\Service\ReleaseReservationService;
use StorePackage\WarehouseCore\Application\Service\ReserveStockService;
use StorePackage\WarehouseCore\Application\Service\ShipStockService;
use StorePackage\WarehouseCore\Domain\Entity\GoodsReceipt;
use StorePackage\WarehouseCore\Domain\Entity\InventoryAdjustment;
use StorePackage\WarehouseCore\Domain\Entity\Shipment;
use StorePackage\WarehouseCore\Domain\Service\AverageCostValuationStrategy;
use StorePackage\WarehouseCore\Domain\Service\FifoValuationStrategy;
use StorePackage\WarehouseCore\Domain\Service\LifoValuationStrategy;
use StorePackage\WarehouseCore\Infrastructure\InMemory\InMemoryInventoryLotRepository;
use StorePackage\WarehouseCore\Infrastructure\InMemory\InMemoryInventoryValuationSnapshotRepository;
use StorePackage\WarehouseCore\Infrastructure\InMemory\InMemoryReservationRepository;
use StorePackage\WarehouseCore\Infrastructure\InMemory\InMemoryStockMovementRepository;
use StorePackage\WarehouseCore\Infrastructure\Support\IncrementalIdGenerator;
use StorePackage\WarehouseCore\Infrastructure\Support\InMemoryEventDispatcher;
use StorePackage\WarehouseCore\Infrastructure\Support\InMemoryTransactionManager;
use StorePackage\WarehouseCore\Infrastructure\Support\NullLogger;
use StorePackage\WarehouseCore\Infrastructure\Support\SystemClock;

require_once __DIR__ . '/../vendor/autoload.php';

$lotRepository = new InMemoryInventoryLotRepository();
$movementRepository = new InMemoryStockMovementRepository();
$reservationRepository = new InMemoryReservationRepository();
$snapshotRepository = new InMemoryInventoryValuationSnapshotRepository();
$clock = new SystemClock();
$ids = new IncrementalIdGenerator();
$events = new InMemoryEventDispatcher();
$logger = new NullLogger();
$transactions = new InMemoryTransactionManager();

$config = new ValuationConfig('fifo');
$config->setWarehouseMethod('WH-2', 'lifo');
$config->setSkuMethod('SKU-AVG-001', 'average');

$resolver = new ValuationMethodResolver($config);
$resolver->registerStrategy(new FifoValuationStrategy());
$resolver->registerStrategy(new LifoValuationStrategy());
$resolver->registerStrategy(new AverageCostValuationStrategy());

$receive = new ReceiveStockService($lotRepository, $movementRepository, $transactions, $clock, $ids, $events, $logger);
$reserve = new ReserveStockService($lotRepository, $reservationRepository, $movementRepository, $transactions, $clock, $ids, $events, $logger);
$release = new ReleaseReservationService($reservationRepository, $movementRepository, $transactions, $clock, $ids, $events, $logger);
$ship = new ShipStockService($lotRepository, $movementRepository, $reservationRepository, $snapshotRepository, $resolver, $transactions, $clock, $ids, $events, $logger);
$move = new MoveStockService($lotRepository, $movementRepository, $transactions, $clock, $ids, $events, $logger, $reservationRepository);
$adjust = new AdjustInventoryService($lotRepository, $movementRepository, $reservationRepository, $snapshotRepository, $resolver, $transactions, $clock, $ids, $events, $logger);
$available = new GetAvailableStockService($lotRepository, $reservationRepository);
$valuation = new GetInventoryValuationService($lotRepository, $resolver);

$receive->receive(new GoodsReceipt(null, 'SKU-1', 'WH-1', 'BIN-A', null, 10, 5, 'USD', 'PO-001'));
$receive->receive(new GoodsReceipt(null, 'SKU-1', 'WH-1', 'BIN-A', null, 20, 8, 'USD', 'PO-002'));
$reservation = $reserve->reserve('SKU-1', 'WH-1', 'BIN-A', 3, 'ORDER-1');
$release->release($reservation->getReservationId(), 1);
$ship->ship(new Shipment(null, 'SKU-1', 'WH-1', 'BIN-A', 5, 'SHIP-001', null, null));
$move->move('SKU-1', 'WH-1', 'BIN-A', 'WH-2', 'BIN-B', 2, 'MOVE-001');
$adjust->adjust(new InventoryAdjustment(null, 'SKU-1', 'WH-2', 'BIN-B', 4, 6.5, 'USD', 'cycle-count gain', null, null));

$balance = $available->getBalance('SKU-1', 'WH-1', 'BIN-A');
$issueValuation = $valuation->getIssueValuation('SKU-1', 'WH-2', 'BIN-B', 2, 'lifo');
$onHandValuation = $valuation->getOnHandValuation('SKU-1', 'WH-2', 'BIN-B', null);

var_dump($balance->toArray());
var_dump($issueValuation->toArray());
var_dump($onHandValuation->toArray());
