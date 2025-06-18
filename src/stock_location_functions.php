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

/**
 * Suggests a pick location for a given item and quantity within a warehouse.
 * Prioritizes locations with enough available stock.
 * Basic version: picks the first suitable location.
 * @param int $item_id
 * @param int $quantity_needed
 * @param int $warehouse_id
 * @return array|null An associative array with 'location_id' and 'location_code', or null if no suitable location found.
 */
function get_suggested_pick_location_for_item($item_id, $quantity_needed, $warehouse_id) {
    $pdo = get_db_connection();
    // Find locations in the given warehouse that have the item with enough available quantity
    // is_pickable = TRUE is important.
    $sql = "SELECT sl.id as location_id, sl.location_code,
                   (s.quantity_on_hand - s.quantity_allocated) as quantity_available
            FROM stock_locations sl
            JOIN inventory_stock s ON sl.id = s.stock_location_id
            WHERE sl.warehouse_id = :warehouse_id
              AND s.item_id = :item_id
              AND sl.is_pickable = TRUE
              AND (s.quantity_on_hand - s.quantity_allocated) >= :quantity_needed
            ORDER BY (s.quantity_on_hand - s.quantity_allocated) ASC, sl.location_code ASC -- Pick from smallest available exact match first, or just any
            LIMIT 1";
            // More advanced: FIFO (by last_stock_update date), preferred location types, etc.

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':warehouse_id' => $warehouse_id,
        ':item_id' => $item_id,
        ':quantity_needed' => $quantity_needed
    ]);
    $location = $stmt->fetch();

    if ($location) {
        return ['location_id' => $location['location_id'], 'location_code' => $location['location_code']];
    }

    // Fallback: If no location has enough, find any location with some stock (for partial pick or to indicate issue)
    $sql_any = "SELECT sl.id as location_id, sl.location_code, (s.quantity_on_hand - s.quantity_allocated) as quantity_available
                 FROM stock_locations sl
                 JOIN inventory_stock s ON sl.id = s.stock_location_id
                 WHERE sl.warehouse_id = :warehouse_id AND s.item_id = :item_id AND sl.is_pickable = TRUE AND (s.quantity_on_hand - s.quantity_allocated) > 0
                 ORDER BY quantity_available DESC LIMIT 1";
    $stmt_any = $pdo->prepare($sql_any);
    $stmt_any->execute([ ':warehouse_id' => $warehouse_id, ':item_id' => $item_id ]);
    $any_location = $stmt_any->fetch();

    if ($any_location) {
         return ['location_id' => $any_location['location_id'], 'location_code' => $any_location['location_code']];
    }
    return null; // No location found with any stock
}

/**
 * Gets all stock locations for a given item within a specific warehouse that have available stock.
 * @param int $item_id
 * @param int $warehouse_id
 * @return array List of stock locations with their codes and available quantities.
 */
function get_stock_locations_for_item_in_warehouse($item_id, $warehouse_id) {
    $pdo = get_db_connection();
    $sql = "SELECT sl.id as stock_location_id, sl.location_code,
                   (s.quantity_on_hand - s.quantity_allocated) as quantity_available
            FROM stock_locations sl
            JOIN inventory_stock s ON sl.id = s.stock_location_id
            WHERE sl.warehouse_id = :warehouse_id
              AND s.item_id = :item_id
              AND sl.is_pickable = TRUE
              AND (s.quantity_on_hand - s.quantity_allocated) > 0 -- Only locations with available stock
            ORDER BY sl.location_code ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':item_id' => $item_id, ':warehouse_id' => $warehouse_id]);
    return $stmt->fetchAll();
}
?>
