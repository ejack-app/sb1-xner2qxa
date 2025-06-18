<?php
require_once __DIR__ . '/database_connection.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

function create_warehouse($name, $address = null, $is_active = true) {
    $pdo = get_db_connection();
    $sql = "INSERT INTO warehouses (name, address, is_active, created_at, updated_at)
            VALUES (:name, :address, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([
            ':name' => $name,
            ':address' => $address,
            ':is_active' => (bool)$is_active
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Integrity constraint (e.g., unique name)
            $_SESSION['error_message'] = 'A warehouse with this name already exists.';
        } else {
            $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
        }
        error_log("Create Warehouse Error: " . $e->getMessage());
        return false;
    }
}

function get_warehouse_by_id($id) {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT * FROM warehouses WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function get_all_warehouses($is_active = null) {
    $pdo = get_db_connection();
    $sql = "SELECT * FROM warehouses";
    $params = [];
    if ($is_active !== null) {
        $sql .= " WHERE is_active = :is_active";
        $params[':is_active'] = (bool)$is_active;
    }
    $sql .= " ORDER BY name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function update_warehouse($id, $name, $address = null, $is_active = true) {
    $pdo = get_db_connection();
    $sql = "UPDATE warehouses SET name = :name, address = :address, is_active = :is_active, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    try {
        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':address' => $address,
            ':is_active' => (bool)$is_active
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error_message'] = 'A warehouse with this name already exists.';
        } else {
            $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
        }
        error_log("Update Warehouse Error: " . $e->getMessage());
        return false;
    }
}
?>
