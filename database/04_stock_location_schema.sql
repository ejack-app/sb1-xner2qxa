-- Warehouses Table
CREATE TABLE warehouses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    address TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) COMMENT 'Represents physical or logical warehouses';

-- Stock Locations Table
CREATE TABLE stock_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    warehouse_id INT NOT NULL,
    location_code VARCHAR(255) NOT NULL COMMENT 'e.g., A1-S2-B3, RCV-01',
    location_type VARCHAR(50) NULL COMMENT 'e.g., AISLE, SHELF, BIN, ZONE, RECEIVING, SHIPPING',
    parent_location_id INT NULL,
    description TEXT NULL,
    is_pickable BOOLEAN DEFAULT TRUE COMMENT 'Can items be picked directly from here?',
    is_sellable BOOLEAN DEFAULT TRUE COMMENT 'Is stock here available for sale calculations?',
    max_weight_kg DECIMAL(10,2) NULL,
    max_volume_m3 DECIMAL(10,3) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_warehouse_location_code (warehouse_id, location_code),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_location_id) REFERENCES stock_locations(id) ON DELETE SET NULL
) COMMENT 'Specific locations within a warehouse';

-- Modify inventory_stock table for multi-location support
-- Step 1: Drop the old UNIQUE constraint on item_id.
-- The previous script for 03_inventory_schema.sql defined:
-- item_id INT NOT NULL UNIQUE in inventory_stock table.
-- MySQL typically names such an index after the column, so 'item_id'.
ALTER TABLE inventory_stock DROP INDEX item_id;

-- Step 2: Add stock_location_id column
ALTER TABLE inventory_stock
    ADD COLUMN stock_location_id INT NULL AFTER item_id;
    -- Making it NULL initially to handle existing data or default locations.

-- Step 3: Add foreign key constraint for stock_location_id
ALTER TABLE inventory_stock
    ADD CONSTRAINT fk_inventory_stock_location
    FOREIGN KEY (stock_location_id) REFERENCES stock_locations(id) ON DELETE CASCADE;

-- Step 4: Add new UNIQUE constraint for item per location
ALTER TABLE inventory_stock
    ADD CONSTRAINT uq_item_stock_location UNIQUE (item_id, stock_location_id);

-- Step 5: (Optional but recommended) Add warehouse_id to inventory_stock for easier querying,
-- though it can be derived via stock_locations. For denormalization/performance:
-- ALTER TABLE inventory_stock ADD COLUMN warehouse_id INT NULL AFTER stock_location_id;
-- ALTER TABLE inventory_stock ADD CONSTRAINT fk_inventory_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE;
-- For now, we will rely on joining with stock_locations to get warehouse_id.

-- Step 6: The old `location_in_warehouse` text field in `inventory_stock` can now be considered deprecated.
-- It will be left for now, but new logic should prioritize stock_location_id.
-- Consider migrating data from location_in_warehouse to a new stock_locations record and then dropping this column.
-- ALTER TABLE inventory_stock DROP COLUMN location_in_warehouse;
