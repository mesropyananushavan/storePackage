-- PostgreSQL-oriented schema for the reference PDO adapter.

CREATE TABLE IF NOT EXISTS inventory_lots (
    lot_id VARCHAR(64) NOT NULL PRIMARY KEY,
    sku VARCHAR(128) NOT NULL,
    warehouse_id VARCHAR(64) NOT NULL,
    location_id VARCHAR(64) NULL,
    received_at VARCHAR(40) NOT NULL,
    quantity_received NUMERIC(18,6) NOT NULL,
    quantity_remaining NUMERIC(18,6) NOT NULL,
    unit_cost NUMERIC(18,6) NOT NULL,
    currency VARCHAR(16) NOT NULL,
    source_document VARCHAR(255) NULL,
    parent_lot_id VARCHAR(64) NULL
);

CREATE INDEX IF NOT EXISTS idx_inventory_lots_scope ON inventory_lots (sku, warehouse_id, location_id, received_at);
CREATE INDEX IF NOT EXISTS idx_inventory_lots_available ON inventory_lots (sku, warehouse_id, quantity_remaining);

CREATE TABLE IF NOT EXISTS stock_movements (
    movement_id VARCHAR(64) NOT NULL PRIMARY KEY,
    operation_id VARCHAR(64) NOT NULL,
    movement_type VARCHAR(32) NOT NULL,
    sku VARCHAR(128) NOT NULL,
    warehouse_id VARCHAR(64) NOT NULL,
    location_id VARCHAR(64) NULL,
    lot_id VARCHAR(64) NULL,
    quantity NUMERIC(18,6) NOT NULL,
    movement_timestamp VARCHAR(40) NOT NULL,
    valuation_method VARCHAR(16) NULL,
    total_cost NUMERIC(18,6) NOT NULL,
    source_document VARCHAR(255) NULL,
    currency VARCHAR(16) NULL,
    metadata_json TEXT NULL
);

CREATE INDEX IF NOT EXISTS idx_stock_movements_operation ON stock_movements (operation_id, movement_timestamp);
CREATE INDEX IF NOT EXISTS idx_stock_movements_scope ON stock_movements (sku, warehouse_id, location_id);

CREATE TABLE IF NOT EXISTS stock_movement_cost_allocations (
    allocation_id BIGSERIAL PRIMARY KEY,
    operation_id VARCHAR(64) NOT NULL,
    lot_id VARCHAR(64) NOT NULL,
    quantity NUMERIC(18,6) NOT NULL,
    unit_cost NUMERIC(18,6) NOT NULL,
    total_cost NUMERIC(18,6) NOT NULL,
    currency VARCHAR(16) NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_stock_movement_cost_allocations_operation ON stock_movement_cost_allocations (operation_id, lot_id);

CREATE TABLE IF NOT EXISTS reservations (
    reservation_id VARCHAR(64) NOT NULL PRIMARY KEY,
    sku VARCHAR(128) NOT NULL,
    warehouse_id VARCHAR(64) NOT NULL,
    location_id VARCHAR(64) NULL,
    quantity NUMERIC(18,6) NOT NULL,
    released_quantity NUMERIC(18,6) NOT NULL,
    reference VARCHAR(255) NULL,
    status VARCHAR(32) NOT NULL,
    reserved_at VARCHAR(40) NOT NULL,
    released_at VARCHAR(40) NULL
);

CREATE INDEX IF NOT EXISTS idx_reservations_scope ON reservations (sku, warehouse_id, location_id, status);

CREATE TABLE IF NOT EXISTS valuation_snapshots (
    snapshot_id VARCHAR(64) NOT NULL PRIMARY KEY,
    operation_id VARCHAR(64) NOT NULL,
    sku VARCHAR(128) NOT NULL,
    warehouse_id VARCHAR(64) NOT NULL,
    location_id VARCHAR(64) NULL,
    valuation_method VARCHAR(16) NOT NULL,
    average_unit_cost NUMERIC(18,6) NOT NULL,
    currency VARCHAR(16) NOT NULL,
    quantity_basis NUMERIC(18,6) NOT NULL,
    total_cost_basis NUMERIC(18,6) NOT NULL,
    created_at VARCHAR(40) NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS uniq_valuation_snapshots_operation ON valuation_snapshots (operation_id);
