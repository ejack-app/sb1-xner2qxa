-- Customers Table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(50) NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    postal_code VARCHAR(20) NOT NULL,
    country_code VARCHAR(2) NOT NULL COMMENT 'ISO 3166-1 alpha-2',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Orders Table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(255) UNIQUE NOT NULL,
    customer_id INT,
    recipient_name VARCHAR(255) NOT NULL,
    recipient_phone VARCHAR(50) NOT NULL,
    recipient_email VARCHAR(255),
    recipient_address_line1 VARCHAR(255) NOT NULL,
    recipient_address_line2 VARCHAR(255),
    recipient_city VARCHAR(100) NOT NULL,
    recipient_state VARCHAR(100),
    recipient_postal_code VARCHAR(20) NOT NULL,
    recipient_country_code VARCHAR(2) NOT NULL,
    order_status VARCHAR(50) NOT NULL DEFAULT 'PENDING' COMMENT 'e.g., PENDING, PROCESSING, SHIPPED, DELIVERED, CANCELLED, RETURNED, AWAITING_PAYMENT',
    payment_status VARCHAR(50) NOT NULL DEFAULT 'UNPAID' COMMENT 'e.g., UNPAID, PAID, REFUNDED, PARTIALLY_PAID',
    payment_method VARCHAR(100),
    total_cod_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Cash on Delivery amount',
    shipping_cost DECIMAL(10,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    total_order_value DECIMAL(10,2) NOT NULL COMMENT 'Sum of item prices + shipping - discount',
    shipping_method VARCHAR(100),
    notes TEXT,
    created_by_user_id INT,
    assigned_courier_id INT,
    tracking_number VARCHAR(255),
    external_order_id VARCHAR(255) COMMENT 'ID from external systems like Salla or Zid',
    platform_source VARCHAR(50) COMMENT 'e.g., SALLA, ZID, MANUAL',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estimated_delivery_date TIMESTAMP NULL,
    shipped_date TIMESTAMP NULL,
    delivered_date TIMESTAMP NULL,
    cancelled_date TIMESTAMP NULL,
    cancellation_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_courier_id) REFERENCES courier_companies(id) ON DELETE SET NULL
);

-- Order Items Table
-- This table will link to an 'items' table later for full inventory integration.
-- For now, SKU is a string.
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_sku VARCHAR(255) NOT NULL COMMENT 'Connects to item master later',
    item_name VARCHAR(255) NOT NULL COMMENT 'Can be denormalized or joined',
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL COMMENT 'quantity * unit_price',
    weight DECIMAL(10,2) COMMENT 'Weight of a single unit',
    total_weight DECIMAL(10,2) COMMENT 'quantity * weight',
    dimensions VARCHAR(100) COMMENT 'LxWxH of a single unit',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Order Status History (Optional but highly recommended for tracking)
CREATE TABLE order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT,
    changed_by_user_id INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);
