-- Sellers Table
CREATE TABLE sellers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_name VARCHAR(255) NOT NULL UNIQUE,
    contact_person VARCHAR(255) NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50) NULL,
    address TEXT NULL,
    commission_rate_percentage DECIMAL(5,2) NULL DEFAULT 0.00,
    user_id INT NULL COMMENT 'Optional: Primary user account linked to this seller',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) COMMENT 'Manages sellers or clients using the platform';

-- Modify 'orders' table to add seller_id
ALTER TABLE orders
    ADD COLUMN seller_id INT NULL AFTER customer_id,
    ADD CONSTRAINT fk_order_seller FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE SET NULL;

-- Modify 'items' table to add seller_id
-- Placing after is_active for this example, can be adjusted
ALTER TABLE items
    ADD COLUMN seller_id INT NULL AFTER is_active,
    ADD CONSTRAINT fk_item_seller FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE SET NULL;
