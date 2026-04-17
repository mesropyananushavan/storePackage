-- Copy of ../../../database/schema/mysql.sql
-- Source of truth stays in the root reference schema.

-- MySQL-oriented schema for the reference PDO adapter.
-- Assumes InnoDB and utf8mb4 for row-level locking support and safe text storage.
-- Timestamps remain ISO-8601 strings to preserve the core contract.

CREATE TABLE inventory_lots (
    lot_id VARCHAR(64) NOT NULL,
    sku VARCHAR(128) NOT NULL,
    warehouse_id VARCHAR(64) NOT NULL,
    location_id VARCHAR(64) NULL,
    received_at VARCHAR(40) NOT NULL,
    quantity_received DECIMAL(18,6) NOT NULL,
    quantity_remaining DECIMAL(18,6) NOT NULL,
    unit_cost DECIMAL(18,6) NOT NULL,
    currency VARCHAR(16) NOT NULL,
    source_document VARCHAR(255) NULL,
    parent_lot_id VARCHAR(64) NULL,
    PRIMARY KEY (lot_id),
    KEY idx_inventory_lots_scope (sku, warehouse_id, location_id, received_at, lot_id),
    KEY idx_inventory_lots_available (sku, warehouse_id, quantity_remaining),
    KEY idx_inventory_lots_parent (parent_lot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_movements (
    movement_id VARCHAR(64) NOT NULL,
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
    metadata_json MEDIUMTEXT NULL,
    PRIMARY KEY (movement_id),
    KEY idx_stock_movements_operation (operation_id, movement_timestamp, movement_id),
    KEY idx_stock_movements_scope (sku, warehouse_id, location_id),
    KEY idx_stock_movements_lot (lot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_movement_cost_allocations (
    allocation_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    operation_id VARCHAR(64) NOT NULL,
    lot_id VARCHAR(64) NOT NULL,
    quantity DECIMAL(18,6) NOT NULL,
    unit_cost DECIMAL(18,6) NOT NULL,
    total_cost DECIMAL(18,6) NOT NULL,
    currency VARCHAR(16) NOT NULL,
    PRIMARY KEY (allocation_id),
    KEY idx_stock_movement_cost_allocations_operation (operation_id, lot_id),
    KEY idx_stock_movement_cost_allocations_lot (lot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reservations (
    reservation_id VARCHAR(64) NOT NULL,
    sku VARCHAR(128) NOT NULL,
    warehouse_id VARCHAR(64) NOT NULL,
    location_id VARCHAR(64) NULL,
    quantity DECIMAL(18,6) NOT NULL,
    released_quantity DECIMAL(18,6) NOT NULL,
    reference VARCHAR(255) NULL,
    status VARCHAR(32) NOT NULL,
    reserved_at VARCHAR(40) NOT NULL,
    released_at VARCHAR(40) NULL,
    PRIMARY KEY (reservation_id),
    KEY idx_reservations_scope (sku, warehouse_id, location_id, status, reserved_at),
    KEY idx_reservations_status (status, warehouse_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE valuation_snapshots (
    snapshot_id VARCHAR(64) NOT NULL,
    operation_id VARCHAR(64) NOT NULL,
    sku VARCHAR(128) NOT NULL,
    warehouse_id VARCHAR(64) NOT NULL,
    location_id VARCHAR(64) NULL,
    valuation_method VARCHAR(16) NOT NULL,
    average_unit_cost DECIMAL(18,6) NOT NULL,
    currency VARCHAR(16) NOT NULL,
    quantity_basis DECIMAL(18,6) NOT NULL,
    total_cost_basis DECIMAL(18,6) NOT NULL,
    created_at VARCHAR(40) NOT NULL,
    PRIMARY KEY (snapshot_id),
    UNIQUE KEY uniq_valuation_snapshots_operation (operation_id),
    KEY idx_valuation_snapshots_scope (sku, warehouse_id, location_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
