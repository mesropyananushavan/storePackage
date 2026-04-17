# Architecture

## Layers

- `Contracts`: repository and infrastructure abstractions, valuation strategy contract.
- `Domain`: entities, value objects, exceptions and valuation algorithms.
- `Application`: use-case services and configuration-driven strategy resolution.
- `Infrastructure`: in-memory repositories, runtime helpers, reference PDO adapter and extension stubs.

## Planned core entities

- Product / SKU
- Warehouse
- Location
- InventoryLot
- InventoryBalance
- StockMovement
- Reservation
- GoodsReceipt
- Shipment
- InventoryAdjustment
- CostAllocation
- InventoryValuationResult
- InventoryValuationSnapshot

## Key services

- `ReceiveStockService`: creates lots and receipt audit entries.
- `ShipStockService`: resolves valuation strategy, persists cost allocations, updates lots and stores average-cost snapshots.
- `MoveStockService`: moves full lots in place and splits partial transfers into derived lots while preserving provenance.
- `ReserveStockService` / `ReleaseReservationService`: manage availability without changing physical on-hand.
- `AdjustInventoryService`: handles positive adjustments as new lots and negative adjustments through valuation strategies.
- `GetAvailableStockService`: returns on-hand, reserved and available balance.
- `GetInventoryValuationService`: estimates issue or on-hand valuation without mutating stock.

## Valuation

- Strategy interface resolves FIFO, LIFO and weighted average without hardcoding the method into shipment or adjustment services.
- Average cost requires snapshot persistence for reproducible audit.
- Resolver priority is `explicit override > SKU override > warehouse override > global default`.

## Audit

- Shipments and negative adjustments persist cost allocations and valuation metadata.
- Average-cost operations persist `InventoryValuationSnapshot` with basis quantity and basis cost.
- Transfers persist paired `transfer_out` / `transfer_in` movements.
- All movement records keep operation id, SKU, warehouse, location, quantity, timestamp and source document.

## Reference DB adapter

- `Infrastructure\Pdo` contains a plain-PDO reference implementation for the repository contracts and transaction manager.
- `PdoStockMovementRepository` stores movement rows separately from `stock_movement_cost_allocations`, then hydrates the allocations back by `operation_id`.
- `PdoInventoryLotRepository` supports FIFO/LIFO ordering directly in SQL and computes weighted average with aggregate queries.
- `PdoReservationRepository` persists reservations as immutable totals plus released quantity, mirroring the in-memory model.
- `PdoInventoryValuationSnapshotRepository` persists the weighted-average audit snapshot keyed by `operation_id`.
- SQL reference files live in `database/schema/reference.sql`, `database/schema/sqlite.sql`, `database/schema/mysql.sql` and `database/schema/postgresql.sql`.
- For Average Cost, the adapter intentionally persists and hydrates multiple lot-based allocations with the averaged unit cost; the snapshot is the separate audit artifact for the averaging basis.
- Local MySQL verification is exercised through `tools/run-mysql-verification-docker.sh`, which bootstraps a disposable MySQL container and runs the smoke plus adapter-level PHPUnit path.
- Local PostgreSQL verification is exercised through `tools/run-postgres-verification-docker.sh`, which bootstraps a disposable PostgreSQL container plus a disposable PHP runtime with `pdo_pgsql` and runs the same smoke plus adapter-level PHPUnit path.

## Production-focused extraction

- `packages/warehouse-pdo-adapter/` is the first extraction step for a dedicated production-oriented adapter package.
- Boundary decision is frozen to path A:
  - `warehouse-core` owns the verified PDO repository runtime, reference schema files and verification tooling
  - `warehouse-pdo-adapter` owns production-facing package metadata, bootstrap glue, schema copies and operational docs
- Versioning policy is aligned by compatibility line:
  - `warehouse-pdo-adapter 0.1.x` targets `warehouse-core ^0.1`
- Schema policy is aligned by source of truth:
  - root MySQL/PostgreSQL schema files are authoritative
  - package-local schema files are controlled copies for production-facing delivery
- This staged approach keeps the proven reference adapter intact while separating reference verification concerns from deployment-facing adapter concerns.
- Any future runtime move must happen through an explicit deprecation plan rather than as an incidental packaging cleanup.

## Locking and transactions

- `PdoTransactionManager` wraps service execution in a database transaction unless a surrounding transaction already exists.
- `lockLotsForUpdate()` issues `SELECT ... FOR UPDATE` only on drivers that support it and only inside an active transaction.
- SQLite and other drivers without row-level lock support fall back to plain selection; this is acceptable for a reference adapter but not the end of production hardening.
- The MySQL-oriented schema assumes `InnoDB` so the existing `FOR UPDATE` path remains meaningful without changing application services.
- The first verified MySQL baseline is MySQL `8.0.45` under `REPEATABLE-READ`.
- The first verified PostgreSQL baseline is PostgreSQL `16.13` under `read committed`.

## Transfer policy

- Full-lot move: keep the same lot identity and update warehouse/location.
- Partial move: decrement the source lot and create a derived lot with `parentLotId` pointing to the origin lot.
- Original `receivedAt` and unit cost are preserved so FIFO/LIFO aging and provenance remain traceable.

## Legacy support

- Core uses PHP 5.6-compatible syntax and PHPDoc typing.
- Modern framework adapters stay outside this package.
- No scalar type hints, return types, typed properties, enums or attributes.
- Runtime helpers use strings for timestamps to avoid modern date abstractions leaking into the core API.
- Float arithmetic is used for costs with 6-decimal rounding as a pragmatic legacy-compatible compromise.
- The reference DB adapter uses PDO directly and stores flexible metadata as JSON text to avoid DBAL or ORM dependencies that would narrow PHP 5.6 compatibility.
