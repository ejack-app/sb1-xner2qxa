<?php
require_once __DIR__ . '/database_connection.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

function create_role($role_name, $description = null, $is_system_role = false, $permission_ids = []) {
    $pdo = get_db_connection();
    $pdo->beginTransaction();
    try {
        $sql_role = "INSERT INTO roles (role_name, description, is_system_role) VALUES (:name, :desc, :is_system)";
        $stmt_role = $pdo->prepare($sql_role);
        $stmt_role->execute([':name' => $role_name, ':desc' => $description, ':is_system' => (bool)$is_system_role]);
        $role_id = $pdo->lastInsertId();

        if (!empty($permission_ids) && $role_id) {
            assign_permissions_to_role($role_id, $permission_ids, $pdo); // Pass PDO for transaction
        }
        $pdo->commit();
        return $role_id;
    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) { $_SESSION['error_message'] = 'Role name already exists.'; }
        else { $_SESSION['error_message'] = 'DB Error creating role: ' . $e->getMessage(); }
        error_log("Create Role Error: " . $e->getMessage());
        return false;
    }
}

function get_role_by_id($role_id) {
    $pdo = get_db_connection();
    $role_stmt = $pdo->prepare("SELECT * FROM roles WHERE id = :id");
    $role_stmt->execute([':id' => $role_id]);
    $role = $role_stmt->fetch();
    if ($role) {
        $perms_stmt = $pdo->prepare(
            "SELECT p.id, p.permission_key, p.description, p.module
             FROM permissions p
             JOIN role_permissions rp ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id ORDER BY p.module, p.permission_key"
        );
        $perms_stmt->execute([':role_id' => $role_id]);
        $role['permissions'] = $perms_stmt->fetchAll(PDO::FETCH_ASSOC);
        $role['permission_ids'] = array_column($role['permissions'], 'id');
    }
    return $role;
}

function get_all_roles() {
    $pdo = get_db_connection();
    $sql = "SELECT r.*,
                   (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) as permission_count,
                   (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) as user_count
            FROM roles r ORDER BY r.role_name ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function update_role($role_id, $role_name, $description = null, $is_system_role = false, $permission_ids = []) {
    $pdo = get_db_connection();
    $role = get_role_by_id($role_id); // Fetch existing role to check is_system_role flag
    if (!$role) {
         $_SESSION['error_message'] = 'Role not found for update.';
         return false;
    }
    // Potentially prevent editing name of system roles or changing is_system_role flag
    if ($role['is_system_role'] && ($role['role_name'] !== $role_name || $is_system_role === false)) {
        // For now, we allow description and permission changes for system roles but not name or system flag change.
        // If name change is attempted for system role, or is_system_role is attempted to be set to false:
        // $_SESSION['error_message'] = 'Cannot change name or system status of a system role.';
        // return false;
        // For this implementation, let's assume only description and permissions can be changed for system roles.
        // If $role_name is different from $role['role_name'] for a system role, block it.
        if($role['role_name'] !== $role_name){
            $_SESSION['error_message'] = "Cannot change the name of a system role ('{$role['role_name']}').";
            return false;
        }
         if($is_system_role === false){ // Cannot change a system role to non-system
            $_SESSION['error_message'] = "Cannot change 'is_system_role' flag for a system role.";
            return false;
        }
    }


    $pdo->beginTransaction();
    try {
        $sql_role = "UPDATE roles SET role_name = :name, description = :desc, is_system_role = :is_system, updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";
        $stmt_role = $pdo->prepare($sql_role);
        $stmt_role->execute([':id' => $role_id, ':name' => $role_name, ':desc' => $description, ':is_system' => (bool)$is_system_role]);

        $stmt_delete_perms = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
        $stmt_delete_perms->execute([':role_id' => $role_id]);

        if (!empty($permission_ids)) {
            assign_permissions_to_role($role_id, $permission_ids, $pdo);
        }
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) { $_SESSION['error_message'] = 'Role name already exists (for another role).'; }
        else { $_SESSION['error_message'] = 'DB Error updating role: ' . $e->getMessage(); }
        error_log("Update Role Error: " . $e->getMessage());
        return false;
    }
}

function assign_permissions_to_role($role_id, $permission_ids, $pdo_param = null) {
     $pdo = $pdo_param ?? get_db_connection();
     $sql_assign = "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)";
     $stmt_assign = $pdo->prepare($sql_assign);
     foreach ($permission_ids as $p_id) {
         if (!empty($p_id) && is_numeric($p_id)) {
              $stmt_assign->execute([':role_id' => $role_id, ':permission_id' => (int)$p_id]);
         }
     }
}

function delete_role($role_id) {
    $pdo = get_db_connection();
    $role = get_role_by_id($role_id);
    if ($role && $role['is_system_role']) {
        $_SESSION['error_message'] = "System roles cannot be deleted.";
        return false;
    }
    $stmt_check_users = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = :role_id");
    $stmt_check_users->execute([':role_id' => $role_id]);
    if ($stmt_check_users->fetchColumn() > 0) {
        $_SESSION['error_message'] = "Cannot delete role: Users are currently assigned. Reassign them first.";
        return false;
    }

    $pdo->beginTransaction();
    try {
        // Role_permissions are cascade deleted by DB FK.
        $sql = "DELETE FROM roles WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $role_id]);
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'DB Error deleting role: ' . $e->getMessage();
        error_log("Delete Role Error: " . $e->getMessage());
        return false;
    }
}

function get_all_permissions($group_by_module = true) {
    $pdo = get_db_connection();
    $sql = "SELECT * FROM permissions ORDER BY module ASC, permission_key ASC";
    $stmt = $pdo->query($sql);
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$group_by_module) {
        return $permissions;
    }
    $grouped = [];
    foreach ($permissions as $p) {
        $module_name = $p['module'] ?? 'General';
        $grouped[$module_name][] = $p;
    }
    return $grouped;
}

function check_user_permission($permission_key, $user_id = null) {
    if ($user_id === null) {
        if (!is_logged_in() || !isset($_SESSION['user_permissions'])) {
            return false;
        }
        if (in_array('admin_area_access', $_SESSION['user_permissions'])) {
            return true;
        }
        return in_array($permission_key, $_SESSION['user_permissions']);
    } else {
        $pdo = get_db_connection();
        $sql_user_role = "SELECT role_id FROM users WHERE id = :user_id";
        $stmt_user_role = $pdo->prepare($sql_user_role);
        $stmt_user_role->execute([':user_id' => $user_id]);
        $user_role_info = $stmt_user_role->fetch();

        if (!\$user_role_info || !\$user_role_info['role_id']) return false;

        $sql_perm_check = "SELECT COUNT(*)
                           FROM role_permissions rp
                           JOIN permissions p ON rp.permission_id = p.id
                           WHERE rp.role_id = :role_id AND (p.permission_key = :permission_key OR p.permission_key = 'admin_area_access')";
        $stmt_perm_check = $pdo->prepare($sql_perm_check);
        $stmt_perm_check->execute([':role_id' => \$user_role_info['role_id'], ':permission_key' => \$permission_key]);
        return \$stmt_perm_check->fetchColumn() > 0;
    }
}
?>
