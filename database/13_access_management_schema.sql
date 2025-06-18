-- Roles Table
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    is_system_role BOOLEAN DEFAULT FALSE COMMENT 'System roles might have restricted editing/deletion',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) COMMENT 'Defines user roles/templates';

-- Permissions Table
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(255) NOT NULL UNIQUE COMMENT 'e.g., orders_view, items_edit',
    description TEXT NULL,
    module VARCHAR(100) NULL COMMENT 'Helps group permissions in UI, e.g., Orders, Inventory'
) COMMENT 'Defines individual permissions available in the system';

-- Role-Permissions Link Table
CREATE TABLE role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) COMMENT 'Assigns permissions to roles';

-- Modify 'users' table for new role_id
-- Step 1: Add the new role_id column (nullable for transition)
ALTER TABLE users ADD COLUMN role_id INT NULL DEFAULT NULL AFTER email;

-- Step 2: Drop the old 'role' VARCHAR column
-- This assumes data migration from old 'role' to new 'role_id' is complete or not needed.
-- If the 'role' column does not exist, this statement will cause an error.
-- In a managed migration, you would check for column existence first.
-- For this exercise, we proceed. If it fails, the subsequent FK might also fail or work depending on DB.
-- ALTER TABLE users DROP COLUMN role; -- Commented out for safety in automated run; should be done manually after data migration.

-- Step 3: Add Foreign Key constraint (AFTER roles table is created and populated, and data migrated)
-- This will be added via a separate instruction or after initial roles are seeded.
-- ALTER TABLE users ADD CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;
-- For now, just adding the column. The FK will be handled by a follow-up or manual step by admin.
-- As per worker instructions, the FK should be added. It will work if 'roles' table is created by this point.
-- The DROP COLUMN is the risky part if not handled with care during actual deployment.
-- For the purpose of this automated script, let's assume we are setting up fresh or the old column is fine to be dropped.
-- To make this script runnable, if the `role` column does not exist, `DROP COLUMN` will fail.
-- A common approach is to ensure this script is run on a schema where this is known.
-- Given the plan, I will include the DROP and ADD CONSTRAINT.

-- Attempt to drop the old 'role' column. This will fail if the column does not exist.
-- It's safer to do this manually or with conditional SQL not easily done here.
-- For this exercise, we'll include it but note it's a common point of failure in mixed environments.
-- Let's assume if `role` column doesn't exist, it's fine. We want `role_id`.
-- A better way would be:
-- IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'role')
-- THEN
--    ALTER TABLE users DROP COLUMN role;
-- END IF;
-- But that's not plain SQL for all systems directly in a script.
-- For now, commenting out the DROP. The FK addition is more critical for the new structure.

-- ALTER TABLE users DROP COLUMN role; -- Manual step after data migration

-- Add the Foreign Key constraint
ALTER TABLE users ADD CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;
