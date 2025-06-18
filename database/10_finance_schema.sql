CREATE TABLE service_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_code VARCHAR(100) NOT NULL UNIQUE COMMENT 'e.g., DEL_STD, WH_STORAGE_PALLET, PICK_FEE_ITEM',
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    unit VARCHAR(50) NULL COMMENT 'e.g., per_shipment, per_kg, per_item, per_cbm, per_day, per_pallet_day',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) COMMENT 'Defines billable services offered';

CREATE TABLE rate_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE COMMENT 'e.g., Standard Domestic Rates 2024, Client X Premium Rates',
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    valid_from DATE NULL,
    valid_to DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) COMMENT 'Groups rate definitions, can be versioned or client-specific';

CREATE TABLE rate_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rate_card_id INT NOT NULL,
    service_type_id INT NOT NULL,
    rate_type VARCHAR(50) NOT NULL DEFAULT 'FIXED' COMMENT 'e.g., FIXED, WEIGHT_TIER, ZONE_WEIGHT_TIER',

    fixed_rate DECIMAL(12,2) NULL COMMENT 'Used if rate_type is FIXED',

    -- For tiered/dynamic rates (example fields, can be expanded)
    min_weight_kg DECIMAL(10,3) NULL,
    max_weight_kg DECIMAL(10,3) NULL,
    -- destination_zone_id INT NULL, -- For future zone table
    destination_zone_pattern VARCHAR(255) NULL COMMENT 'Simple text like City Name, Region Code, or * for all',
    rate_per_unit_or_tier DECIMAL(12,2) NULL COMMENT 'Rate for this tier, or per KG/CBM in this tier',
    additional_kg_rate DECIMAL(12,2) NULL COMMENT 'Rate for each additional kg over a base tier',

    currency_code VARCHAR(3) NOT NULL DEFAULT 'SAR',
    priority INT NOT NULL DEFAULT 0 COMMENT 'Lower number = higher priority for matching',
    description_notes TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (rate_card_id) REFERENCES rate_cards(id) ON DELETE CASCADE,
    FOREIGN KEY (service_type_id) REFERENCES service_types(id) ON DELETE CASCADE,
    -- FOREIGN KEY (destination_zone_id) REFERENCES zones(id) ON DELETE SET NULL,
    INDEX idx_rate_lookup (rate_card_id, service_type_id, rate_type, is_active)
) COMMENT 'Specific rate rules within a rate card';
