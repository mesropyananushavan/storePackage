<?php

namespace StorePackage\WarehouseCore\Tests\Doubles;

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

class TestContext
{
    public $lotRepository;
    public $movementRepository;
    public $reservationRepository;
    public $snapshotRepository;
    public $clock;
    public $ids;
    public $events;
    public $logger;
    public $transactions;
    public $config;
    public $resolver;
    public $receive;
    public $reserve;
    public $release;
    public $ship;
    public $move;
    public $adjust;
    public $available;
    public $valuation;

    public function __construct()
    {
        $this->lotRepository = new InMemoryInventoryLotRepository();
        $this->movementRepository = new InMemoryStockMovementRepository();
        $this->reservationRepository = new InMemoryReservationRepository();
        $this->snapshotRepository = new InMemoryInventoryValuationSnapshotRepository();
        $this->clock = new SystemClock();
        $this->ids = new IncrementalIdGenerator();
        $this->events = new InMemoryEventDispatcher();
        $this->logger = new NullLogger();
        $this->transactions = new InMemoryTransactionManager();
        $this->config = new ValuationConfig('fifo');
        $this->resolver = new ValuationMethodResolver($this->config);
        $this->resolver->registerStrategy(new FifoValuationStrategy());
        $this->resolver->registerStrategy(new LifoValuationStrategy());
        $this->resolver->registerStrategy(new AverageCostValuationStrategy());

        $this->receive = new ReceiveStockService(
            $this->lotRepository,
            $this->movementRepository,
            $this->transactions,
            $this->clock,
            $this->ids,
            $this->events,
            $this->logger
        );
        $this->reserve = new ReserveStockService(
            $this->lotRepository,
            $this->reservationRepository,
            $this->movementRepository,
            $this->transactions,
            $this->clock,
            $this->ids,
            $this->events,
            $this->logger
        );
        $this->release = new ReleaseReservationService(
            $this->reservationRepository,
            $this->movementRepository,
            $this->transactions,
            $this->clock,
            $this->ids,
            $this->events,
            $this->logger
        );
        $this->ship = new ShipStockService(
            $this->lotRepository,
            $this->movementRepository,
            $this->reservationRepository,
            $this->snapshotRepository,
            $this->resolver,
            $this->transactions,
            $this->clock,
            $this->ids,
            $this->events,
            $this->logger
        );
        $this->move = new MoveStockService(
            $this->lotRepository,
            $this->movementRepository,
            $this->transactions,
            $this->clock,
            $this->ids,
            $this->events,
            $this->logger
        );
        $this->adjust = new AdjustInventoryService(
            $this->lotRepository,
            $this->movementRepository,
            $this->reservationRepository,
            $this->snapshotRepository,
            $this->resolver,
            $this->transactions,
            $this->clock,
            $this->ids,
            $this->events,
            $this->logger
        );
        $this->available = new GetAvailableStockService($this->lotRepository, $this->reservationRepository);
        $this->valuation = new GetInventoryValuationService($this->lotRepository, $this->resolver);
    }

    public function seedReceipt($sku, $warehouseId, $locationId, $quantity, $unitCost, $receivedAt, $sourceDocument)
    {
        return $this->receive->receive(
            new GoodsReceipt(
                null,
                $sku,
                $warehouseId,
                $locationId,
                $receivedAt,
                $quantity,
                $unitCost,
                'USD',
                $sourceDocument
            )
        );
    }
}
