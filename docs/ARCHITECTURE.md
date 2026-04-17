# Architecture

## Layers

- `Contracts`: repository and infrastructure abstractions, valuation strategy contract.
- `Domain`: entities, value objects, exceptions and valuation algorithms.
- `Application`: use-case services and configuration-driven strategy resolution.
- `Infrastructure`: in-memory repositories, runtime helpers and extension stubs.

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
