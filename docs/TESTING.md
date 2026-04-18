# Testing

## Covered now

- `tests/Unit/FifoValuationStrategyTest.php`
- `tests/Unit/LifoValuationStrategyTest.php`
- `tests/Unit/AverageCostValuationStrategyTest.php`
- `tests/Unit/InventoryWorkflowTest.php`

## Covered scenarios

- FIFO picks oldest lots first
- LIFO picks newest lots first
- Average cost weighted calculation
- Partial depletion across multiple lots
- Insufficient stock handling
- Reservation effect on availability
- Shipment allocation persistence
- Average valuation reproducibility
- Reservation-aware transfer rejection
- Fractional weighted-average rounding consistency across shipments, adjustments and snapshots
- Audit persistence of valuation method
- Move plus adjustment flow smoke coverage in unit tests

## Not yet covered well

- Concurrent update behavior for DB-backed repositories
- Multi-currency validation edge cases
- Decimal precision edge cases beyond the covered 6-decimal weighted-average paths
- Vendor-specific locking behavior in the reference PDO adapter

## Compatibility strategy

- Runtime core stays PHP 5.6-compatible.
- Dev dependency uses `phpunit/phpunit:^5.7 || ^9.6` so Composer resolves PHPUnit 5.7 on PHP 5.6 and PHPUnit 9.6 on PHP 7.4 / 8.1.
- Tests will use a compatibility base class to support both legacy and namespaced PHPUnit APIs.
- `tests/bootstrap.php` can autoload from Composer or directly from the repository tree.
- Local verification now uses `composer verify`, which runs syntax lint and custom runtime smoke-checks without PHPUnit.

## Execution paths

### Legacy test path

- PHP 5.6
- Composer 2.2 LTS
- PHPUnit 5.7
- Required dev extensions: `dom`, `json`, `libxml`, `mbstring`, `tokenizer`, `xmlwriter`
- Preferred execution mode: Docker
- Local command: `bash tools/run-legacy-tests-docker.sh`
- Composer shortcut: `composer test:legacy:docker`
- Faster repeat local command: `WAREHOUSE_LEGACY_SKIP_BUILD=1 bash tools/run-legacy-tests-docker.sh`
- CI job: `test-legacy`
- CI optimization: Docker Buildx layer cache + cached `.cache/composer-legacy`
- Confirmed locally: full Docker run and repeat skip-build run

### Modern test path

- PHP 7.4 and 8.1
- Composer 2.7
- PHPUnit 9.6
- Required dev extensions: `dom`, `json`, `libxml`, `mbstring`, `tokenizer`, `xmlwriter`
- CI job: `test-modern`
- CI optimization: Composer download cache keyed by OS, PHP version and `composer.json`

### Fallback path

- `composer verify`
- `composer test:smoke`
- No PHPUnit dependency
- Intended for local sanity-checking when PHPUnit extensions are unavailable

### Reference DB adapter path

- `composer test:db-smoke`
- `composer test:db:mysql:docker`
- `composer test:db:pgsql:docker`
- Uses `sqlite::memory:` when `ext-pdo_sqlite` is available
- Can target an external PDO database with `WAREHOUSE_DB_DSN`, `WAREHOUSE_DB_USER`, `WAREHOUSE_DB_PASSWORD` and optional `WAREHOUSE_DB_SCHEMA_FILE`
- Auto-selects `database/schema/mysql.sql` when the DSN starts with `mysql:` and no schema override is provided
- Adds `tests/Unit/PdoReferenceAdapterTest.php`, which skips automatically when `ext-pdo_sqlite` is unavailable
- Covers lot persistence, reservation persistence, reservation-aware transfer rejection, average-cost shipment persistence, movement hydration and snapshot persistence
- Covers fractional weighted-average shipment/adjustment rounding consistency and snapshot reproducibility on both in-memory and PDO-backed paths
- Also covers transaction rollback behavior through `PdoTransactionManager`
- CI job: `test-db-reference`
- CI scope: hosted PHP 8.1 with `pdo_sqlite`, `composer test:db-smoke` and `vendor/bin/phpunit --filter PdoReferenceAdapterTest`
- Docker helper path:
  - `bash tools/run-mysql-verification-docker.sh test`
  - Starts MySQL 8 in Docker, waits for readiness, exports MySQL DSN settings for the child process, runs the DB smoke path and `PdoReferenceAdapterTest`, then removes the container by default
- Docker helper path:
  - `bash tools/run-postgres-verification-docker.sh test`
  - Builds a disposable PHP runtime with `pdo_pgsql`, starts PostgreSQL 16 in Docker, waits for readiness, runs the DB smoke path and `PdoReferenceAdapterTest`, then removes the container and helper network by default
- Verification order:
  1. `composer test:db-smoke`
  2. `vendor/bin/phpunit --filter PdoReferenceAdapterTest` when PHPUnit and DB prerequisites are available
  3. optional repeat against a non-SQLite DSN using `database/schema/reference.sql`
- Confirmed in this workspace:
  - `composer test:db-smoke`
  - `vendor/bin/phpunit --filter PdoReferenceAdapterTest`
  - `vendor/bin/phpunit --filter AverageCostValuationStrategyTest`
  - `bash tools/run-mysql-verification-docker.sh test`
  - `bash tools/run-postgres-verification-docker.sh test`
  - `composer test:db:pgsql:docker`
- Root cause of the last failure: expectation defect in the smoke script and adapter-level test. The domain contract already produced two lot-based Average Cost allocations with averaged unit cost, and the PDO layer preserved them correctly.
- MySQL-specific status:
  - schema hardened through `database/schema/mysql.sql`
  - smoke/test entrypoints prepared for DSN-driven MySQL runs
  - live MySQL execution confirmed in this workspace via Docker-backed MySQL `8.0.45`
  - transaction isolation captured as `REPEATABLE-READ`
  - one MySQL-specific defect was found in the verification layer, not in the adapter itself: schema re-application across PHPUnit tests reused the same DB and needed explicit table reset before applying schema
- PostgreSQL-specific status:
  - schema hardened through `database/schema/postgresql.sql`
  - smoke/test entrypoints prepared for DSN-driven PostgreSQL runs
  - live PostgreSQL execution confirmed in this workspace via Docker-backed PostgreSQL `16.13`
  - default transaction isolation captured as `read committed`
  - one PostgreSQL-specific defect was found in the helper layer, not in the adapter itself: the helper originally passed a host-only absolute schema path into the container and had to switch to `/app/database/schema/postgresql.sql`

## Known limitations

- Legacy PHPUnit execution is now confirmed in the Docker path with PHP 5.6.40 and PHPUnit 5.7.27.
- GitHub Actions matrix still needs live confirmation for the Docker legacy job, the hosted modern jobs and the hosted SQLite-backed reference DB job.
- `composer install --no-dev` from a source checkout without `composer.lock` is not a reliable workaround, because Composer still resolves dev constraints before skipping package installation.
- Legacy Docker builds still rely on archived Debian package mirrors, so image build stability depends on archive availability.
- GitHub-side effectiveness of Buildx cache and Composer caches still needs a real remote run to confirm.
- This workspace cannot trigger or inspect GitHub Actions because GitHub tooling/access is unavailable here.
- Remote validation was re-checked in the current iteration and is still blocked by workspace limitations, not by any newly observed workflow issue.
- The verified MySQL path still reflects a disposable Docker baseline rather than a managed production MySQL deployment.
- The verified PostgreSQL path still reflects a disposable Docker baseline rather than a managed production PostgreSQL deployment.
- `packages/warehouse-pdo-adapter/` does not have a separate test matrix and is not expected to own one while boundary path A remains frozen; it reuses the verified runtime from `warehouse-core` and adds packaging/config documentation rather than a second runtime copy.
