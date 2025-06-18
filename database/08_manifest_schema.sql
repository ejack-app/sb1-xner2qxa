CREATE TABLE manifests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    manifest_code VARCHAR(255) NOT NULL UNIQUE COMMENT 'e.g., MAN-YYYYMMDD-XXXX',
    manifest_date DATE NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'OPEN' COMMENT 'e.g., OPEN, IN_TRANSIT, COMPLETED, CANCELLED',
    courier_company_id INT NULL,
    assigned_vehicle_id INT NULL,
    assigned_driver_id INT NULL,
    -- route_id INT NULL, -- For future route management integration
    departure_warehouse_id INT NULL,
    notes TEXT NULL,
    created_by_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (courier_company_id) REFERENCES courier_companies(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_driver_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (departure_warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) COMMENT 'Groups orders for dispatch or handover to courier';

CREATE TABLE manifest_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    manifest_id INT NOT NULL,
    order_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_manifest_order (manifest_id, order_id) COMMENT 'An order can only be on one open/active manifest',
    FOREIGN KEY (manifest_id) REFERENCES manifests(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    -- If an order is removed from a manifest, its status should revert.
    -- If a manifest is deleted, its orders should revert status. Handled by application logic.
) COMMENT 'Links orders to a specific manifest';
