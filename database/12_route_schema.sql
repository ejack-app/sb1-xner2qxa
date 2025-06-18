CREATE TABLE routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_name VARCHAR(255) NOT NULL UNIQUE COMMENT 'e.g., Riyadh North Route, Dammam Express Loop',
    description TEXT NULL,
    region_city VARCHAR(255) NULL COMMENT 'General region or city this route primarily serves',
    expected_duration_hours DECIMAL(5,2) NULL,
    distance_km DECIMAL(10,2) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) COMMENT 'Defines delivery or collection routes';

CREATE TABLE route_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    sequence_number INT NOT NULL COMMENT 'Order of this point/stop in the route',
    location_name_or_code VARCHAR(255) NOT NULL COMMENT 'e.g., Olaya District, Warehouse B, Postal Code Area',
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    notes TEXT NULL,
    arrival_time_offset_minutes INT NULL COMMENT 'Optional: Estimated minutes from start of route or previous point',
    departure_time_offset_minutes INT NULL,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    UNIQUE KEY uq_route_sequence (route_id, sequence_number) -- A sequence number must be unique per route
) COMMENT 'Defines specific points, stops, or areas within a route';

-- Modify 'manifests' table to add foreign key for route_id
-- Assuming 'route_id INT NULL' column was added as part of the manifests table creation or conceptually planned.
-- If not, it should be added first:
-- ALTER TABLE manifests ADD COLUMN route_id INT NULL DEFAULT NULL AFTER departure_warehouse_id;
-- (The original 08_manifest_schema.sql had it commented out)
-- For this script, we'll assume it might need to be added if it doesn't exist.
-- However, a direct ALTER TABLE ADD COLUMN IF NOT EXISTS is not standard SQL for all engines easily.
-- Best practice is to manage schema changes sequentially.
-- For now, we will just add the FK constraint. If the column doesn't exist, this will fail,
-- indicating a prior step was missed or needs adjustment.
-- The 08_manifest_schema.sql had the column commented: `-- route_id INT NULL,`
-- I will proceed assuming the column needs to be added if it doesn't exist.
-- For the sake of this automated flow, I will add the column explicitly first.
-- This might cause an error if it *was* added manually before.
-- A better approach in a real migration system would be more conditional.

ALTER TABLE manifests
    ADD COLUMN IF NOT EXISTS route_id INT NULL DEFAULT NULL AFTER departure_warehouse_id;

ALTER TABLE manifests
    ADD CONSTRAINT fk_manifest_route FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE SET NULL;
