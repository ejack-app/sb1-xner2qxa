-- Item Master Data Table
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    barcode VARCHAR(255) NULL UNIQUE,
    -- supplier_id INT NULL, -- Add FK when suppliers table is created
    -- category_id INT NULL, -- Add FK when item_categories table is created
    unit_of_measure VARCHAR(50) NULL COMMENT 'e.g., PCS, KG, BOX',
    default_purchase_price DECIMAL(12,2) NULL,
    default_selling_price DECIMAL(12,2) NULL,
    weight DECIMAL(10,3) NULL COMMENT 'Weight of one unit in KG',
    length DECIMAL(10,2) NULL COMMENT 'Dimension in CM',
    width DECIMAL(10,2) NULL COMMENT 'Dimension in CM',
    height DECIMAL(10,2) NULL COMMENT 'Dimension in CM',
    image_url VARCHAR(512) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
    -- CONSTRAINT fk_item_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    -- CONSTRAINT fk_item_category FOREIGN KEY (category_id) REFERENCES item_categories(id) ON DELETE SET NULL
) COMMENT 'Master data for all sellable/stockable items';

-- Inventory Stock Table
-- For now, assumes a single default warehouse or location per item.
-- warehouse_id can be added later for multi-warehouse support.
CREATE TABLE inventory_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL UNIQUE, -- Each item has one primary stock record here
    -- warehouse_id INT NULL, -- To be used for multi-warehouse inventory
    quantity_on_hand INT NOT NULL DEFAULT 0,
    quantity_allocated INT NOT NULL DEFAULT 0 COMMENT 'Reserved for open orders',
    -- quantity_available can be calculated as (quantity_on_hand - quantity_allocated)
    low_stock_threshold INT NULL,
    location_in_warehouse VARCHAR(255) NULL COMMENT 'e.g., Shelf A, Bin 3 - more detailed later',
    last_stock_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) COMMENT 'Tracks stock levels for items';

-- Optional: Inventory Transaction Log (for full audit trail - can be added in a later step)
/*
CREATE TABLE inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    transaction_type VARCHAR(50) NOT NULL COMMENT 'e.g., INITIAL_STOCK, SALE, PURCHASE_RECEIPT, ADJUSTMENT_IN, ADJUSTMENT_OUT, ALLOCATED, SHIPPED, RETURNED',
    quantity_change INT NOT NULL COMMENT 'Positive for stock in, negative for stock out',
    related_order_id INT NULL,
    related_purchase_order_id INT NULL,
    notes TEXT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT NULL,
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (related_order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
*/
