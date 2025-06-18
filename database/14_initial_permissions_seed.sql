-- Initial Permissions
INSERT INTO permissions (permission_key, description, module) VALUES
    ('dashboard_view', 'View Admin Dashboard', 'General'),
    ('admin_area_access', 'Full access to all admin functionalities (Super Admin permission)', 'General'),

    ('orders_view_all', 'View all orders', 'Orders'),
    ('orders_view_assigned', 'View orders assigned to self (e.g. for driver/picker)', 'Orders'),
    ('orders_create', 'Create new orders', 'Orders'),
    ('orders_edit_all', 'Edit any order', 'Orders'),
    ('orders_update_status', 'Update order statuses', 'Orders'),

    ('items_view', 'View items and inventory', 'Inventory'),
    ('items_create', 'Create new items', 'Inventory'),
    ('items_edit', 'Edit item master data', 'Inventory'),
    ('items_delete', 'Delete items', 'Inventory'),
    ('items_import_bulk', 'Import or Bulk Update items', 'Inventory'),
    ('items_print_sku', 'Print SKUs/Barcodes', 'Inventory'),
    ('stock_adjust', 'Adjust stock quantities manually', 'Inventory'),

    ('warehouses_manage', 'Manage warehouses (add, edit, delete)', 'Inventory'),
    ('stock_locations_manage', 'Manage stock locations', 'Inventory'),

    ('picklists_view_all', 'View all picklists', 'Picking'),
    ('picklists_create', 'Create new picklists', 'Picking'),
    ('picklists_assign', 'Assign picklists to pickers', 'Picking'),
    ('picklists_process_own', 'Process picklists assigned to self', 'Picking'),
    ('picklists_process_all', 'Process any picklist', 'Picking'),

    ('packaging_manage', 'Manage packaging for orders', 'Packaging'),

    ('manifests_view_all', 'View all manifests', 'Manifests'),
    ('manifests_create', 'Create new manifests', 'Manifests'),
    ('manifests_manage_orders', 'Add/remove orders from manifests', 'Manifests'),
    ('manifests_dispatch', 'Dispatch manifests (set to IN_TRANSIT, triggers stock deduction)', 'Manifests'),
    ('manifests_complete', 'Mark manifests as COMPLETED (updates order statuses)', 'Manifests'),

    ('vehicles_manage', 'Manage vehicles (add, edit, delete)', 'Logistics'),
    ('boxes_manage', 'Manage box definitions', 'Logistics'),
    ('routes_manage', 'Manage routes and route points', 'Logistics'),

    ('sellers_manage', 'Manage sellers/clients', 'Sellers'),

    ('finance_rates_manage', 'Manage service types, rate cards, and rate definitions', 'Finance'),
    ('finance_invoices_view', 'View invoices', 'Finance'),
    ('finance_invoices_manage', 'Manage invoices (create, send)', 'Finance'),

    ('users_view', 'View users list', 'Users & Roles'),
    ('users_create', 'Create new users', 'Users & Roles'),
    ('users_edit', 'Edit user details (excluding roles/permissions)', 'Users & Roles'),
    ('users_manage_roles', 'Assign roles to users / Change user roles', 'Users & Roles'),
    ('users_delete', 'Delete users', 'Users & Roles'),

    ('roles_view', 'View roles and their permissions', 'Users & Roles'),
    ('roles_manage', 'Manage roles (create, edit, delete, assign permissions)', 'Users & Roles'),

    ('company_details_manage', 'Edit company details', 'Settings'),
    ('integrations_config_manage', 'Manage integration configurations', 'Settings'),
    ('legal_content_manage', 'Manage privacy policy and terms & conditions', 'Settings')
ON DUPLICATE KEY UPDATE permission_key=VALUES(permission_key), description=VALUES(description), module=VALUES(module);

-- Initial Roles
INSERT INTO roles (role_name, description, is_system_role) VALUES
    ('Administrator', 'Full system access.', TRUE)
ON DUPLICATE KEY UPDATE role_name=VALUES(role_name);

INSERT INTO roles (role_name, description, is_system_role) VALUES
    ('Logistics Staff', 'Staff member with operational permissions.', FALSE)
ON DUPLICATE KEY UPDATE role_name=VALUES(role_name);

-- Assign all permissions to Administrator role
-- Using a subquery to get the Administrator role ID robustly
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.role_name = 'Administrator'
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id), permission_id=VALUES(permission_id);

-- Assign specific permissions to Logistics Staff role
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.role_name = 'Logistics Staff' AND p.permission_key IN (
    'dashboard_view',
    'orders_view_all', 'orders_create', 'orders_update_status',
    'items_view',
    'picklists_view_all', 'picklists_process_own',
    'packaging_manage',
    'manifests_view_all'
)
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id), permission_id=VALUES(permission_id);

-- Note: The step to update existing users' role_id is intentionally left out of this seed script.
-- It's a data migration step that should be handled carefully based on existing user data and roles.
-- Example: UPDATE users SET role_id = (SELECT id FROM roles WHERE role_name = 'Logistics Staff') WHERE role_id IS NULL AND role = 'user';
-- (Assuming old role column was 'role' and had a value 'user')
