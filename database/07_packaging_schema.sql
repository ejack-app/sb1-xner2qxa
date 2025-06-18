CREATE TABLE shipment_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    box_definition_id INT NULL,
    package_tracking_number VARCHAR(255) NULL UNIQUE,
    actual_length_cm DECIMAL(10,2) NULL COMMENT 'Overrides box default if custom packaging',
    actual_width_cm DECIMAL(10,2) NULL,
    actual_height_cm DECIMAL(10,2) NULL,
    actual_weight_kg DECIMAL(10,3) NULL COMMENT 'Total weight of the package',
    status VARCHAR(50) NOT NULL DEFAULT 'PENDING_ITEMS' COMMENT 'e.g., PENDING_ITEMS, ITEMS_ADDED, WEIGHED, LABELED, READY_TO_SHIP',
    created_by_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (box_definition_id) REFERENCES box_definitions(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) COMMENT 'Represents individual packages within a shipment/order';

CREATE TABLE shipment_package_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shipment_package_id INT NOT NULL,
    order_item_id INT NOT NULL COMMENT 'Links to the specific item line in the order',
    item_id INT NOT NULL COMMENT 'Denormalized item_id for convenience, matches items.id',
    quantity_packed INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_package_order_item (shipment_package_id, order_item_id),
    FOREIGN KEY (shipment_package_id) REFERENCES shipment_packages(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) COMMENT 'Links items from an order to a specific package they are packed in';
