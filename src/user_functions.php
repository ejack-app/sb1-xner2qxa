<?php
require_once __DIR__ . '/database_connection.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Modified to use role_id instead of role string
function create_user($username, $email, $password, $role_id, $is_active = true) {
    $pdo = get_db_connection();
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // SQL now inserts role_id
    $sql = "INSERT INTO users (username, email, password_hash, role_id, is_active, created_at, updated_at)
            VALUES (:username, :email, :password_hash, :role_id, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
    $stmt = $pdo->prepare($sql);

    try {
        return $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $password_hash,
            ':role_id' => empty($role_id) ? null : (int)$role_id, // Ensure role_id is int or null
            ':is_active' => (bool)$is_active
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            if (strpos(strtolower($e->getMessage()), 'users.username') !== false) {
                $_SESSION['error_message'] = 'Username already exists.';
            } elseif (strpos(strtolower($e->getMessage()), 'users.email') !== false) {
                $_SESSION['error_message'] = 'Email already exists.';
            } else {
                $_SESSION['error_message'] = 'A database error occurred (duplicate entry).';
            }
        } else {
            $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
        }
        error_log('Create User Error: ' . $e->getMessage());
        return false;
    }
}

// Modified to fetch role_name via JOIN with roles table
function get_user_by_username($username) {
    $pdo = get_db_connection();
    $sql = "SELECT u.id, u.username, u.email, u.password_hash, u.is_active, u.role_id, r.role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':username' => $username]);
    return $stmt->fetch();
}

// Modified to fetch role_name via JOIN with roles table
function get_user_by_id($user_id) {
    $pdo = get_db_connection();
    $sql = "SELECT u.id, u.username, u.email, u.password_hash, u.is_active, u.role_id, r.role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetch();
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Modified to fetch role_name via JOIN with roles table for display if needed
function get_all_users() {
    $pdo = get_db_connection();
    $sql = "SELECT u.id, u.username, u.email, u.is_active, u.created_at, u.updated_at, u.role_id, r.role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            ORDER BY u.id DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Modified to check for a specific permission instead of role name
function is_admin() {
    return (is_logged_in() && isset($_SESSION['user_permissions']) &&
            in_array('admin_area_access', $_SESSION['user_permissions']));
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit;
    }
}

function require_admin() {
    require_login(); // Ensures user is logged in
    if (!is_admin()) { // Then checks if they have admin privileges
        $_SESSION['error_message'] = "Access Denied. Administrator privileges required.";
        // Redirect to a non-admin page or login. If already logged in but not admin,
        // login page might redirect them again. A dedicated 'access_denied.php' page is better.
        header('Location: login.php');
        exit;
    }
}

/**
 * Fetches users who are designated as pickers (or other relevant roles).
 * This function now joins with 'roles' table to filter by role_name if needed,
 * but for simplicity, if you've migrated users to role_id, you'd filter by role_id.
 * Assuming 'picker' role name exists and is used.
 */
function get_pickers() {
    $pdo = get_db_connection();
    // Example: Fetch users who have a role that's typically a picker
    // This could be by specific role_ids or by role_names if you join roles table.
    // For simplicity, if 'picker' is a role_name:
    $sql = "SELECT u.id, u.username
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.is_active = TRUE AND r.role_name IN ('picker', 'admin', 'warehouse_staff', 'user')
            ORDER BY u.username ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}
?>
