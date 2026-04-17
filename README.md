# storepackage/warehouse-core

`storepackage/warehouse-core` is a framework-agnostic Composer package for warehouse automation. It implements reusable inventory business logic for receipts, shipments, transfers, reservations, adjustments, valuation and movement audit.

## Supported PHP versions

- PHP 5.6
- PHP 7.4
- PHP 8.1

The core is intentionally written in a legacy-compatible style so it can run in older environments without sacrificing a clean layered architecture.

## Features

- Stock receipt with lot creation and source document traceability
- Shipment with FIFO, LIFO and weighted average costing
- Transfer between warehouses and locations
- Reservation and release of reservations
- Positive and negative inventory adjustments
- Available stock calculation with reservation impact
- Audit trail for stock movements and valuation allocations
- Average cost snapshot persistence for reproducibility
- In-memory infrastructure for tests and demos

## Package structure

```text
src/
  Contracts/
  Domain/
  Application/
  Infrastructure/
tests/
docs/
examples/
```

## Requirements

- PHP `>=5.6 <8.2`
- No framework runtime dependency in core

## Installation

```bash
composer require storepackage/warehouse-core
```

## Quick start

```php
<?php

use StorePackage\WarehouseCore\Application\Config\ValuationConfig;
use StorePackage\WarehouseCore\Application\Config\ValuationMethodResolver;
use StorePackage\WarehouseCore\Application\Service\GetAvailableStockService;
use StorePackage\WarehouseCore\Application\Service\ReceiveStockService;
use StorePackage\WarehouseCore\Application\Service\ShipStockService;
use StorePackage\WarehouseCore\Domain\Entity\GoodsReceipt;
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

$lotRepository = new InMemoryInventoryLotRepository();
$movementRepository = new InMemoryStockMovementRepository();
$reservationRepository = new InMemoryReservationRepository();
$snapshotRepository = new InMemoryInventoryValuationSnapshotRepository();

$config = new ValuationConfig('fifo');
$resolver = new ValuationMethodResolver($config);
$resolver->registerStrategy(new FifoValuationStrategy());
$resolver->registerStrategy(new LifoValuationStrategy());
$resolver->registerStrategy(new AverageCostValuationStrategy());

$clock = new SystemClock();
$ids = new IncrementalIdGenerator();
$events = new InMemoryEventDispatcher();
$logger = new NullLogger();
$transactions = new InMemoryTransactionManager();

$receive = new ReceiveStockService($lotRepository, $movementRepository, $transactions, $clock, $ids, $events, $logger);
$ship = new ShipStockService($lotRepository, $movementRepository, $reservationRepository, $snapshotRepository, $resolver, $transactions, $clock, $ids, $events, $logger);
$available = new GetAvailableStockService($lotRepository, $reservationRepository);

$receive->receive(new GoodsReceipt(null, 'SKU-1', 'WH-1', 'BIN-A', null, 10, 5, 'USD', 'PO-1001'));
$receive->receive(new GoodsReceipt(null, 'SKU-1', 'WH-1', 'BIN-A', null, 20, 8, 'USD', 'PO-1002'));

$valuation = $ship->ship(new Shipment(null, 'SKU-1', 'WH-1', 'BIN-A', 15, 'SO-2001', null, null));
$balance = $available->getBalance('SKU-1', 'WH-1', 'BIN-A');
```

See [examples/bootstrap.php](examples/bootstrap.php) for a fuller bootstrap example.

## Usage examples

### Receiving stock

```php
$receive->receive(new GoodsReceipt(null, 'SKU-1', 'WH-1', 'BIN-A', null, 50, 3.25, 'USD', 'PO-77'));
```

### Reserving stock

```php
$reservation = $reserve->reserve('SKU-1', 'WH-1', 'BIN-A', 5, 'ORDER-10');
```

### Releasing a reservation

```php
$release->release($reservation->getReservationId(), 2);
```

### Shipping stock

```php
$ship->ship(new Shipment(null, 'SKU-1', 'WH-1', 'BIN-A', 7, 'SHIP-10', null, 'fifo'));
```

### Moving stock

```php
$move->move('SKU-1', 'WH-1', 'BIN-A', 'WH-2', 'BIN-B', 4, 'MOVE-10');
```

### Selecting FIFO / LIFO / Average

```php
$config = new ValuationConfig('fifo');
$config->setWarehouseMethod('WH-2', 'lifo');
$config->setSkuMethod('SKU-AVG-001', 'average');
```

Resolver priority is `explicit override > SKU override > warehouse override > global default`.

## Extension points

- Replace in-memory repositories with database-backed implementations of the repository interfaces.
- Wrap the application services in a Laravel or Symfony adapter package instead of coupling the core to a framework.
- Integrate barcode scanners or external APIs through adapter classes outside the core.

### Laravel / Symfony integration

- Keep this package as the domain/application core.
- Build a separate adapter package or app-layer bridge that binds repository interfaces into the framework container.
- Map framework config into `ValuationConfig`.
- Expose application services through controllers, console commands or queue jobs without moving business rules into the framework layer.

## Legacy compatibility limitations

- No typed properties, union types, attributes or enums
- No `strict_types`
- Numeric calculations use floats with documented rounding compromise
- Core avoids framework containers and annotations

## Testing

### PHPUnit path

- `composer test` runs PHPUnit through `tools/run-phpunit.php`
- Legacy path: PHP 5.6 with PHPUnit 5.7
- Modern path: PHP 7.4 and 8.1 with PHPUnit 9.6
- Dev setup requires `ext-dom`, `ext-libxml`, `ext-mbstring`, `ext-tokenizer` and `ext-xmlwriter`

### Legacy Docker path

- Preferred legacy command: `bash tools/run-legacy-tests-docker.sh`
- Composer shortcut: `composer test:legacy:docker`
- Docker image definition: [docker/legacy/php56/Dockerfile](docker/legacy/php56/Dockerfile)
- The helper builds a PHP 5.6 image, mounts the repository into `/app`, reuses `.cache/composer-legacy`, installs PHPUnit 5.7 dependencies inside the container and runs the legacy test path
- Use this path for CI and local verification when hosted PHP 5.6 environments are fragile or unavailable
- Faster repeat run after the first image build: `WAREHOUSE_LEGACY_SKIP_BUILD=1 bash tools/run-legacy-tests-docker.sh`

### Fallback path

- `composer verify` runs syntax lint plus dependency-free smoke checks
- `composer test:smoke` runs the smoke checks only
- `tests/bootstrap.php` can autoload from `vendor/autoload.php` or the local source tree, so PHAR-based PHPUnit is also supported through `PHPUNIT_BINARY=/path/to/phpunit.phar composer test`

### Important Composer note

- This repository intentionally does not commit `composer.lock` because it is a library.
- A local source checkout with missing dev extensions can still fail on `composer install --no-dev`, because Composer must resolve the full dependency graph when no lock file exists.
- This does not affect consumers installing the published package, because package `require-dev` is ignored by downstream installs.

### CI summary

- PHP 5.6: Docker-based legacy job
- PHP 7.4 / 8.1: hosted `setup-php` jobs
- Runtime smoke: hosted PHP 8.1 with `composer verify`
- Remote validation still has to be executed from a real git checkout with GitHub Actions access

## How To Run Checks Locally

- Fast repository sanity-check: `composer verify`
- Full legacy PHPUnit path: `composer test:legacy:docker`
- Faster repeated legacy run with existing image: `WAREHOUSE_LEGACY_SKIP_BUILD=1 bash tools/run-legacy-tests-docker.sh`
- Full modern PHPUnit path: `composer test` in a host environment that has `ext-dom` and `ext-xmlwriter`

## Remote CI Checklist

- Run the GitHub Actions workflow from a real git repository checkout
- Confirm `test-legacy` finishes green with the Docker path
- Confirm both `test-modern` matrix jobs finish green for PHP 7.4 and 8.1
- Confirm `runtime-smoke` finishes green
- Review whether Docker Buildx cache and Composer caches are reused on subsequent runs

## Release process

- Update changelog and docs
- Run tests in supported PHP versions
- Create a git tag such as `v0.1.0`
- Push branch and tag
- Submit or sync the repository in Packagist or Private Packagist

Detailed steps: [docs/PUBLISHING.md](docs/PUBLISHING.md)

## Publication

### Public Packagist

1. Push the repository to a public VCS host.
2. Tag a release.
3. Register or sync the repository in Packagist.

### Private Packagist

1. Connect the VCS repository in Private Packagist.
2. Sync tags and repository access.
3. Require the package from the private Composer repository in consuming applications.
