-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    role VARCHAR(50) NOT NULL COMMENT 'e.g., admin, seller, picker, customer_service',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Company Details Table
-- Assuming only one set of company details for now.
-- If multiple companies can use the platform, this needs a different structure.
CREATE TABLE company_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    -- default_courier_company_id INT, -- We'll add this FK once courier_companies table is defined
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(255),
    website VARCHAR(255),
    logo_url VARCHAR(255),
    registration_number VARCHAR(100),
    vat_number VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Privacy Policy Table
CREATE TABLE privacy_policy (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    version VARCHAR(50) NOT NULL,
    is_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Terms and Conditions Table
CREATE TABLE terms_and_conditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    version VARCHAR(50) NOT NULL,
    is_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Basic placeholder for Courier Companies
CREATE TABLE courier_companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    tracking_url_format VARCHAR(512), -- e.g., https://tracking.example.com/?id={tracking_id}
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add foreign key to company_details now that courier_companies is defined
ALTER TABLE company_details
ADD COLUMN default_courier_company_id INT,
ADD CONSTRAINT fk_default_courier
FOREIGN KEY (default_courier_company_id) REFERENCES courier_companies(id)
ON DELETE SET NULL;
