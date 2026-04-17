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
- Reference PDO adapter with SQL schema for non-framework persistence

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
database/
  schema/
packages/
  warehouse-pdo-adapter/
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

## Reference DB adapter

The package now includes a framework-agnostic reference adapter under `src/Infrastructure/Pdo/`:

- `PdoInventoryLotRepository`
- `PdoStockMovementRepository`
- `PdoReservationRepository`
- `PdoInventoryValuationSnapshotRepository`
- `PdoTransactionManager`

Design choices:

- Uses plain `PDO` without ORM or framework container bindings
- Stores movement metadata as JSON text for portability
- Stores cost allocations in a dedicated `stock_movement_cost_allocations` table
- Uses best-effort row locking through `SELECT ... FOR UPDATE` only on drivers that support it and only inside an active transaction
- Keeps table names overridable through constructor table-name maps
- Preserves the domain contract for Average Cost: multiple lot-based allocations remain attached to the movement, while the averaged unit cost is captured in both allocations and the valuation snapshot

Reference schema files:

- [database/schema/reference.sql](database/schema/reference.sql): portable baseline for MySQL/PostgreSQL-style adapters
- [database/schema/sqlite.sql](database/schema/sqlite.sql): smoke-test-friendly SQLite variant
- [database/schema/mysql.sql](database/schema/mysql.sql): MySQL-oriented schema with `InnoDB`, `utf8mb4`, MySQL-specific indexes and `MEDIUMTEXT` metadata storage
- [database/schema/postgresql.sql](database/schema/postgresql.sql): PostgreSQL-oriented schema with `NUMERIC`, `TEXT`, `BIGSERIAL` allocation rows and PostgreSQL-friendly indexes

This adapter is intentionally a readable reference implementation. Production hardening such as vendor-specific migrations, retry policies, deadlock handling and stricter JSON/decimal handling stays with the consuming application or a dedicated adapter package.

## Production-focused adapter extraction

The next packaging layer now starts inside this repository at `packages/warehouse-pdo-adapter/`.

Boundary decision is now frozen to the safer path:

- `warehouse-core` keeps ownership of:
  - the verified PDO runtime in `src/Infrastructure/Pdo/`
  - reference schema files in `database/schema/`
  - smoke tests and Docker verification helpers
- `warehouse-pdo-adapter` becomes the production-facing packaging layer on top of that runtime:
  - package metadata for a second package
  - `PdoAdapterConfig` and `PdoAdapterFactory`
  - production-facing schema copies for application migration bootstraps
  - migration, operations and boundary documentation

This avoids a risky runtime move while the adapter contract is still being packaged and documented. If runtime ownership ever changes later, that should happen through an explicit deprecation window rather than as part of this extraction step.

Versioning discipline for the two packages is now:

- `warehouse-core` uses semantic versioning for runtime contracts and the supported reference PDO runtime.
- `warehouse-pdo-adapter` uses semantic versioning for packaging/bootstrap/schema-copy concerns.
- While both live in the same repository and boundary path A remains frozen, major/minor lines stay aligned.
- Current compatibility line: `warehouse-pdo-adapter 0.1.x` requires `warehouse-core ^0.1`.

Schema source of truth is also frozen:

- root source of truth:
  - `database/schema/mysql.sql`
  - `database/schema/postgresql.sql`
- package delivery copies:
  - `packages/warehouse-pdo-adapter/resources/schema/mysql.sql`
  - `packages/warehouse-pdo-adapter/resources/schema/postgresql.sql`

Whenever a root vendor-specific schema changes, the package copy must be updated in the same commit.

Publishing strategy is also fixed:

- `storepackage/warehouse-core` publishes directly from the monorepo root
- `storepackage/warehouse-pdo-adapter` publishes from a dedicated split repository built with `git subtree split --prefix=packages/warehouse-pdo-adapter`

Release preconditions are now explicit:

- the package subtree must already exist in committed history
- an `adapter-remote` must be configured for the split package
- remotes must be reachable from the release environment

See [docs/RELEASE_EXECUTION.md](docs/RELEASE_EXECUTION.md) for the concrete release choreography and tag policy.

### MySQL notes

- `database/schema/mysql.sql` is the preferred schema when `WAREHOUSE_DB_DSN` starts with `mysql:`
- Tables use `ENGINE=InnoDB` so the existing `SELECT ... FOR UPDATE` path has meaningful row-level locking semantics
- Text metadata stays in `MEDIUMTEXT` rather than MySQL `JSON` to avoid forcing newer server requirements into the reference adapter contract
- Timestamps remain ISO-8601 strings because the core API is string-based; this is acceptable for the reference adapter, but a production adapter may still choose stricter datetime normalization
- Allocation rows use an auto-increment surrogate key in MySQL to keep inserts simple and indexing explicit
- This workspace currently has `ext-pdo_mysql`, but live MySQL verification still requires `WAREHOUSE_DB_DSN`, `WAREHOUSE_DB_USER`, `WAREHOUSE_DB_PASSWORD` and optionally `WAREHOUSE_DB_SCHEMA_FILE`
- The reference adapter is now verified against Docker-backed MySQL `8.0.45` with transaction isolation `REPEATABLE-READ`
- Local one-command verification helper: `bash tools/run-mysql-verification-docker.sh test`
- Composer shortcut: `composer test:db:mysql:docker`

### PostgreSQL notes

- `database/schema/postgresql.sql` is the preferred schema when `WAREHOUSE_DB_DSN` starts with `pgsql:`
- Textual metadata stays in `TEXT` rather than `jsonb` so the reference adapter stays close to the portable baseline
- Numeric quantities and costs use `NUMERIC(18,6)` to match the existing reference contract
- Allocation rows use `BIGSERIAL` as a surrogate primary key for simple insertion and indexing
- The reference adapter is now verified against Docker-backed PostgreSQL `16.13` with default transaction isolation `read committed`
- Local one-command verification helper: `bash tools/run-postgres-verification-docker.sh test`
- Composer shortcut: `composer test:db:pgsql:docker`

### Minimal PDO bootstrap

```php
<?php

use StorePackage\WarehouseCore\Infrastructure\Pdo\PdoInventoryLotRepository;
use StorePackage\WarehouseCore\Infrastructure\Pdo\PdoInventoryValuationSnapshotRepository;
use StorePackage\WarehouseCore\Infrastructure\Pdo\PdoReservationRepository;
use StorePackage\WarehouseCore\Infrastructure\Pdo\PdoStockMovementRepository;
use StorePackage\WarehouseCore\Infrastructure\Pdo\PdoTransactionManager;

$pdo = new PDO('mysql:host=127.0.0.1;dbname=warehouse', 'user', 'password');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$lotRepository = new PdoInventoryLotRepository($pdo);
$movementRepository = new PdoStockMovementRepository($pdo);
$reservationRepository = new PdoReservationRepository($pdo);
$snapshotRepository = new PdoInventoryValuationSnapshotRepository($pdo);
$transactions = new PdoTransactionManager($pdo);
```

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
- Reference DB adapter requires `ext-pdo`; optional smoke testing via SQLite needs `ext-pdo_sqlite`

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

### Reference DB adapter checks

- `composer test:db-smoke` runs the reference DB adapter smoke path
- Default mode uses `sqlite::memory:` when `ext-pdo_sqlite` is available
- Alternative mode uses `WAREHOUSE_DB_DSN`, `WAREHOUSE_DB_USER`, `WAREHOUSE_DB_PASSWORD` and optional `WAREHOUSE_DB_SCHEMA_FILE`
- If `WAREHOUSE_DB_DSN` starts with `mysql:` and no schema override is provided, the smoke script defaults to `database/schema/mysql.sql`
- If neither `ext-pdo_sqlite` nor an explicit DSN is available, the smoke script exits with a clear skip message
- A skip message means the adapter was not verified end-to-end in that environment
- The current reference verification is confirmed against `sqlite::memory:` with two persisted Average Cost allocations for the seeded shipment scenario

### DB verification checklist

MySQL-backed verification checklist:

```bash
export WAREHOUSE_DB_DSN='mysql:host=127.0.0.1;port=3306;dbname=warehouse_core_test;charset=utf8mb4'
export WAREHOUSE_DB_USER='warehouse'
export WAREHOUSE_DB_PASSWORD='secret'
# optional override:
# export WAREHOUSE_DB_SCHEMA_FILE='/abs/path/to/database/schema/mysql.sql'

composer test:db-smoke
vendor/bin/phpunit --filter PdoReferenceAdapterTest
```

Or use the local Docker helper, which starts MySQL, waits for readiness, runs both checks and removes the container by default:

```bash
composer test:db:mysql:docker
```

If `mysql` CLI is available, prefer a quick sanity check before the test run:

```bash
mysql -h 127.0.0.1 -P 3306 -u "$WAREHOUSE_DB_USER" -p
```

If you need to inspect the database after the run, keep the container alive explicitly:

```bash
WAREHOUSE_MYSQL_KEEP_CONTAINER=1 bash tools/run-mysql-verification-docker.sh test
```

PostgreSQL-backed verification checklist:

```bash
export WAREHOUSE_DB_DSN='pgsql:host=127.0.0.1;port=54328;dbname=warehouse_core_test'
export WAREHOUSE_DB_USER='warehouse'
export WAREHOUSE_DB_PASSWORD='warehouse'
# optional override:
# export WAREHOUSE_DB_SCHEMA_FILE='/abs/path/to/database/schema/postgresql.sql'

composer test:db-smoke
vendor/bin/phpunit --filter PdoReferenceAdapterTest
```

Or use the local Docker helper, which builds a disposable PHP runtime with `pdo_pgsql`, starts PostgreSQL, runs both checks and removes the container/network by default:

```bash
composer test:db:pgsql:docker
```

If you need to inspect the database after the run, keep the container alive explicitly:

```bash
WAREHOUSE_PGSQL_KEEP_CONTAINER=1 bash tools/run-postgres-verification-docker.sh test
```

Run this only in a DB-capable environment:

1. Confirm one of these is true:
   - `php -r "echo extension_loaded('pdo_sqlite') ? 'yes' : 'no';"`
   - `echo "$WAREHOUSE_DB_DSN"`
2. Run `composer test:db-smoke`
3. If PHPUnit dev dependencies are available, run `vendor/bin/phpunit --filter PdoReferenceAdapterTest`
4. Verify these behaviors explicitly:
   - lot save/load works
   - FIFO/LIFO lot ordering is correct
   - weighted average returns expected value
   - reservations persist and reduce availability
   - movement history hydrates allocations without collapsing Average Cost into a synthetic single line
   - average-cost shipment persists valuation snapshot
   - transaction wrapper commits on success and rolls back on failure
   - MySQL path uses `database/schema/mysql.sql` unless explicitly overridden

Expected smoke result for the seeded scenario:

- FIFO issue valuation for `10@5 + 20@8`, issue `15` => total cost `90`
- Average shipment total cost => `105`
- Snapshot average unit cost => `7`
- Average shipment allocation count => `2` with quantities `10` and `5`, each at unit cost `7`
- Post-shipment balance after reserving `3` => on hand `15`, reserved `3`, available `12`

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
- Reference DB adapter smoke path: `composer test:db-smoke`
- External DB example:
  `WAREHOUSE_DB_DSN="mysql:host=127.0.0.1;dbname=warehouse" WAREHOUSE_DB_USER=user WAREHOUSE_DB_PASSWORD=secret composer test:db-smoke`
- MySQL PHPUnit path:
  `WAREHOUSE_DB_DSN="mysql:host=127.0.0.1;dbname=warehouse" WAREHOUSE_DB_USER=user WAREHOUSE_DB_PASSWORD=secret vendor/bin/phpunit --filter PdoReferenceAdapterTest`

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
