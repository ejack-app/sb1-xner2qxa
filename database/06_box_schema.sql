CREATE TABLE box_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'e.g., Small Box S1, Medium Cube M2',
    length_cm DECIMAL(10,2) NOT NULL,
    width_cm DECIMAL(10,2) NOT NULL,
    height_cm DECIMAL(10,2) NOT NULL,
    max_weight_kg DECIMAL(10,2) NULL COMMENT 'Maximum weight the box can hold',
    empty_box_weight_kg DECIMAL(10,3) NULL COMMENT 'Weight of the empty box itself',
    notes TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) COMMENT 'Standard box sizes and definitions for packaging';
