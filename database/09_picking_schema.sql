CREATE TABLE picklists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    picklist_code VARCHAR(255) NOT NULL UNIQUE COMMENT 'e.g., PICK-YYYYMMDD-XXXX',
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'PENDING' COMMENT 'e.g., PENDING, ASSIGNED, IN_PROGRESS, COMPLETED, CANCELLED',
    assigned_picker_id INT NULL COMMENT 'FK to users.id',
    warehouse_id INT NOT NULL COMMENT 'FK to warehouses.id',
    created_by_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_picker_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) COMMENT 'Header for a list of items to be picked for an order';

CREATE TABLE picklist_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    picklist_id INT NOT NULL,
    order_item_id INT NOT NULL COMMENT 'Original item line from the order',
    item_id INT NOT NULL COMMENT 'Denormalized item_id for quick reference',
    quantity_to_pick INT NOT NULL,
    suggested_location_id INT NULL COMMENT 'FK to stock_locations.id, system suggested pick spot',
    suggested_location_code VARCHAR(255) NULL COMMENT 'Denormalized code for display',
    picked_from_location_id INT NULL COMMENT 'FK to stock_locations.id, actual location picked from',
    quantity_picked INT DEFAULT 0,
    picker_notes TEXT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'PENDING' COMMENT 'e.g., PENDING, PICKED, PARTIALLY_PICKED, NOT_FOUND, DAMAGED',
    picked_at TIMESTAMP NULL,
    FOREIGN KEY (picklist_id) REFERENCES picklists(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (suggested_location_id) REFERENCES stock_locations(id) ON DELETE SET NULL,
    FOREIGN KEY (picked_from_location_id) REFERENCES stock_locations(id) ON DELETE SET NULL,
    UNIQUE KEY uq_picklist_order_item (picklist_id, order_item_id)
) COMMENT 'Individual items to be picked as part of a picklist';
