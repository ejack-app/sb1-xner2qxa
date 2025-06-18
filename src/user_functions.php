<?php
require_once __DIR__ . '/database_connection.php';

// It's good practice to start the session if it's not already started,
// as these functions might be used in contexts where session is needed.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function create_user($username, $email, $password, $role, $is_active = true) {
    $pdo = get_db_connection();
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password_hash, role, is_active, created_at, updated_at)
            VALUES (:username, :email, :password_hash, :role, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
    $stmt = $pdo->prepare($sql);

    try {
        return $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $password_hash,
            ':role' => $role,
            ':is_active' => (bool)$is_active
        ]);
    } catch (PDOException $e) {
        // Check for duplicate username or email (MySQL error code 1062)
        if ($e->getCode() == 23000) {
            if (strpos($e->getMessage(), 'users.username') !== false) {
                $_SESSION['error_message'] = 'Username already exists.';
            } elseif (strpos($e->getMessage(), 'users.email') !== false) {
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

function get_user_by_username($username) {
    $pdo = get_db_connection();
    $sql = "SELECT id, username, email, password_hash, role, is_active FROM users WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':username' => $username]);
    return $stmt->fetch();
}

function get_user_by_id($user_id) {
    $pdo = get_db_connection();
    $sql = "SELECT id, username, email, role, is_active FROM users WHERE id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetch();
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function get_all_users() {
    $pdo = get_db_connection();
    // Exclude password_hash from general listing
    $stmt = $pdo->query("SELECT id, username, email, role, is_active, created_at, updated_at FROM users ORDER BY id DESC");
    return $stmt->fetchAll();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return (is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        // Optionally, set a flash message
        $_SESSION['error_message'] = "Access Denied. Administrator privileges required.";
        // Redirect to a non-admin page or a specific 'access denied' page
        // For now, redirecting to a hypothetical dashboard or login.
        header('Location: login.php');
        exit;
    }
}
?>
