<?php
require_once __DIR__ . '/database_connection.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

function create_stock_location($data) {
    $pdo = get_db_connection();
    $sql = "INSERT INTO stock_locations (warehouse_id, location_code, location_type, parent_location_id,
                                      description, is_pickable, is_sellable, max_weight_kg, max_volume_m3,
                                      created_at, updated_at)
            VALUES (:warehouse_id, :location_code, :location_type, :parent_location_id,
                    :description, :is_pickable, :is_sellable, :max_weight_kg, :max_volume_m3,
                    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([
            ':warehouse_id' => $data['warehouse_id'],
            ':location_code' => $data['location_code'],
            ':location_type' => $data['location_type'] ?? null,
            ':parent_location_id' => $data['parent_location_id'] ?? null,
            ':description' => $data['description'] ?? null,
            ':is_pickable' => $data['is_pickable'] ?? true,
            ':is_sellable' => $data['is_sellable'] ?? true,
            ':max_weight_kg' => $data['max_weight_kg'] ?? null,
            ':max_volume_m3' => $data['max_volume_m3'] ?? null,
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Integrity constraint
            $_SESSION['error_message'] = 'A stock location with this code already exists in this warehouse.';
        } else {
            $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
        }
        error_log("Create Stock Location Error: " . $e->getMessage());
        return false;
    }
}

function get_stock_location_by_id($id) {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT sl.*, w.name as warehouse_name
                           FROM stock_locations sl
                           JOIN warehouses w ON sl.warehouse_id = w.id
                           WHERE sl.id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function get_stock_locations_by_warehouse($warehouse_id, $filters = []) {
    $pdo = get_db_connection();
    // Basic query, can be expanded with more filters (type, parent, etc.) and pagination
    $sql = "SELECT sl.*, psl.location_code as parent_location_code
            FROM stock_locations sl
            LEFT JOIN stock_locations psl ON sl.parent_location_id = psl.id
            WHERE sl.warehouse_id = :warehouse_id ";

    $params = [':warehouse_id' => $warehouse_id];

    if (!empty($filters['location_code'])) {
        $sql .= " AND sl.location_code LIKE :location_code";
        $params[':location_code'] = '%' . $filters['location_code'] . '%';
    }
    if (!empty($filters['location_type'])) {
        $sql .= " AND sl.location_type = :location_type";
        $params[':location_type'] = $filters['location_type'];
    }

    $sql .= " ORDER BY sl.location_code ASC";
    // Add LIMIT/OFFSET here if pagination is needed for this function directly

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_all_stock_locations_for_select($warehouse_id = null) {
   $pdo = get_db_connection();
   $sql = "SELECT id, location_code, warehouse_id FROM stock_locations";
   $params = [];
   if ($warehouse_id !== null) {
       $sql .= " WHERE warehouse_id = :warehouse_id";
       $params[':warehouse_id'] = $warehouse_id;
   }
   $sql .= " ORDER BY warehouse_id, location_code ASC";
   $stmt = $pdo->prepare($sql);
   $stmt->execute($params);
   return $stmt->fetchAll();
}


function update_stock_location($id, $data) {
    $pdo = get_db_connection();
    $sql = "UPDATE stock_locations SET
                location_code = :location_code,
                location_type = :location_type,
                parent_location_id = :parent_location_id,
                description = :description,
                is_pickable = :is_pickable,
                is_sellable = :is_sellable,
                max_weight_kg = :max_weight_kg,
                max_volume_m3 = :max_volume_m3,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND warehouse_id = :warehouse_id"; // warehouse_id check for safety

    $stmt = $pdo->prepare($sql);
    try {
        return $stmt->execute([
            ':id' => $id,
            ':warehouse_id' => $data['warehouse_id'], // Must be provided for the WHERE clause
            ':location_code' => $data['location_code'],
            ':location_type' => $data['location_type'] ?? null,
            ':parent_location_id' => $data['parent_location_id'] ?? null,
            ':description' => $data['description'] ?? null,
            ':is_pickable' => $data['is_pickable'] ?? true,
            ':is_sellable' => $data['is_sellable'] ?? true,
            ':max_weight_kg' => $data['max_weight_kg'] ?? null,
            ':max_volume_m3' => $data['max_volume_m3'] ?? null,
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error_message'] = 'A stock location with this code already exists in this warehouse.';
        } else {
            $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
        }
        error_log("Update Stock Location Error: " . $e->getMessage());
        return false;
    }
}
?>
