-- SQLite-oriented schema for the reference PDO adapter smoke path.

CREATE TABLE IF NOT EXISTS inventory_lots (
    lot_id TEXT NOT NULL PRIMARY KEY,
    sku TEXT NOT NULL,
    warehouse_id TEXT NOT NULL,
    location_id TEXT NULL,
    received_at TEXT NOT NULL,
    quantity_received REAL NOT NULL,
    quantity_remaining REAL NOT NULL,
    unit_cost REAL NOT NULL,
    currency TEXT NOT NULL,
    source_document TEXT NULL,
    parent_lot_id TEXT NULL
);

CREATE INDEX IF NOT EXISTS idx_inventory_lots_scope ON inventory_lots (sku, warehouse_id, location_id, received_at);
CREATE INDEX IF NOT EXISTS idx_inventory_lots_available ON inventory_lots (sku, warehouse_id, quantity_remaining);

CREATE TABLE IF NOT EXISTS stock_movements (
    movement_id TEXT NOT NULL PRIMARY KEY,
    operation_id TEXT NOT NULL,
    movement_type TEXT NOT NULL,
    sku TEXT NOT NULL,
    warehouse_id TEXT NOT NULL,
    location_id TEXT NULL,
    lot_id TEXT NULL,
    quantity REAL NOT NULL,
    movement_timestamp TEXT NOT NULL,
    valuation_method TEXT NULL,
    total_cost REAL NOT NULL,
    source_document TEXT NULL,
    currency TEXT NULL,
    metadata_json TEXT NULL
);

CREATE INDEX IF NOT EXISTS idx_stock_movements_operation ON stock_movements (operation_id, movement_timestamp);
CREATE INDEX IF NOT EXISTS idx_stock_movements_scope ON stock_movements (sku, warehouse_id, location_id);

CREATE TABLE IF NOT EXISTS stock_movement_cost_allocations (
    operation_id TEXT NOT NULL,
    lot_id TEXT NOT NULL,
    quantity REAL NOT NULL,
    unit_cost REAL NOT NULL,
    total_cost REAL NOT NULL,
    currency TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_stock_movement_cost_allocations_operation ON stock_movement_cost_allocations (operation_id, lot_id);

CREATE TABLE IF NOT EXISTS reservations (
    reservation_id TEXT NOT NULL PRIMARY KEY,
    sku TEXT NOT NULL,
    warehouse_id TEXT NOT NULL,
    location_id TEXT NULL,
    quantity REAL NOT NULL,
    released_quantity REAL NOT NULL,
    reference TEXT NULL,
    status TEXT NOT NULL,
    reserved_at TEXT NOT NULL,
    released_at TEXT NULL
);

CREATE INDEX IF NOT EXISTS idx_reservations_scope ON reservations (sku, warehouse_id, location_id, status);

CREATE TABLE IF NOT EXISTS valuation_snapshots (
    snapshot_id TEXT NOT NULL PRIMARY KEY,
    operation_id TEXT NOT NULL UNIQUE,
    sku TEXT NOT NULL,
    warehouse_id TEXT NOT NULL,
    location_id TEXT NULL,
    valuation_method TEXT NOT NULL,
    average_unit_cost REAL NOT NULL,
    currency TEXT NOT NULL,
    quantity_basis REAL NOT NULL,
    total_cost_basis REAL NOT NULL,
    created_at TEXT NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS uniq_valuation_snapshots_operation ON valuation_snapshots (operation_id);
