-- Reference schema for the framework-agnostic PDO adapter.
-- Uses portable column types and string identifiers so the same mapping can
-- be adapted to MySQL, PostgreSQL or another PDO-backed RDBMS.

CREATE TABLE inventory_lots (
    lot_id VARCHAR(64) NOT NULL PRIMARY KEY,
    sku VARCHAR(128) NOT NULL,
    warehouse_id VARCHAR(64) NOT NULL,
    location_id VARCHAR(64) NULL,
    received_at VARCHAR(40) NOT NULL,
    quantity_received DECIMAL(18,6) NOT NULL,
    quantity_remaining DECIMAL(18,6) NOT NULL,
    unit_cost DECIMAL(18,6) NOT NULL,
    currency VARCHAR(16) NOT NULL,
    source_document VARCHAR(255) NULL,
    parent_lot_id VARCHAR(64) NULL
);

CREATE INDEX idx_inventory_lots_scope ON inventory_lots (sku, warehouse_id, location_id, received_at);
CREATE INDEX idx_inventory_lots_available ON inventory_lots (sku, warehouse_id, quantity_remaining);

CREATE TABLE stock_movements (
    movement_id VARCHAR(64) NOT NULL PRIMARY KEY,
    operation_id VARCHAR(64) NOT NULL,
    movement_type VARCHAR(32) NOT NULL,
    sku VARCHAR(128) NOT NULL,
    warehouse_id VARCHAR(64) NOT NULL,
    location_id VARCHAR(64) NULL,
    lot_id VARCHAR(64) NULL,
    quantity DECIMAL(18,6) NOT NULL,
    movement_timestamp VARCHAR(40) NOT NULL,
    valuation_method VARCHAR(16) NULL,
    total_cost DECIMAL(18,6) NOT NULL,
    source_document VARCHAR(255) NULL,
    currency VARCHAR(16) NULL,
    metadata_json TEXT NULL
);

CREATE INDEX idx_stock_movements_operation ON stock_movements (operation_id, movement_timestamp);
CREATE INDEX idx_stock_movements_scope ON stock_movements (sku, warehouse_id, location_id);

CREATE TABLE stock_movement_cost_allocations (
    operation_id VARCHAR(64) NOT NULL,
    lot_id VARCHAR(64) NOT NULL,
    quantity DECIMAL(18,6) NOT NULL,
    unit_cost DECIMAL(18,6) NOT NULL,
    total_cost DECIMAL(18,6) NOT NULL,
    currency VARCHAR(16) NOT NULL
);

CREATE INDEX idx_stock_movement_cost_allocations_operation ON stock_movement_cost_allocations (operation_id, lot_id);

CREATE TABLE reservations (
    reservation_id VARCHAR(64) NOT NULL PRIMARY KEY,
    sku VARCHAR(128) NOT NULL,
    warehouse_id VARCHAR(64) NOT NULL,
    location_id VARCHAR(64) NULL,
    quantity DECIMAL(18,6) NOT NULL,
    released_quantity DECIMAL(18,6) NOT NULL,
    reference VARCHAR(255) NULL,
    status VARCHAR(32) NOT NULL,
    reserved_at VARCHAR(40) NOT NULL,
    released_at VARCHAR(40) NULL
);

CREATE INDEX idx_reservations_scope ON reservations (sku, warehouse_id, location_id, status);

CREATE TABLE valuation_snapshots (
    snapshot_id VARCHAR(64) NOT NULL PRIMARY KEY,
    operation_id VARCHAR(64) NOT NULL,
    sku VARCHAR(128) NOT NULL,
    warehouse_id VARCHAR(64) NOT NULL,
    location_id VARCHAR(64) NULL,
    valuation_method VARCHAR(16) NOT NULL,
    average_unit_cost DECIMAL(18,6) NOT NULL,
    currency VARCHAR(16) NOT NULL,
    quantity_basis DECIMAL(18,6) NOT NULL,
    total_cost_basis DECIMAL(18,6) NOT NULL,
    created_at VARCHAR(40) NOT NULL
);

CREATE UNIQUE INDEX uniq_valuation_snapshots_operation ON valuation_snapshots (operation_id);
