CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_name VARCHAR(255) NOT NULL COMMENT 'e.g., Truck 1, Van Optimus, or License Plate',
    license_plate VARCHAR(50) NULL UNIQUE,
    make VARCHAR(100) NULL,
    model VARCHAR(100) NULL,
    year INT NULL,
    vin_number VARCHAR(100) NULL UNIQUE,
    vehicle_type VARCHAR(50) NOT NULL COMMENT 'e.g., Van, Truck, Motorcycle, Bicycle',
    status VARCHAR(50) NOT NULL DEFAULT 'Available' COMMENT 'e.g., Available, In Use, Maintenance, Decommissioned',
    current_driver_id INT NULL,
    max_payload_kg DECIMAL(10,2) NULL,
    max_volume_m3 DECIMAL(10,3) NULL,
    notes TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (current_driver_id) REFERENCES users(id) ON DELETE SET NULL
) COMMENT 'Manages fleet vehicles';
