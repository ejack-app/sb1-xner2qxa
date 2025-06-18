<?php
require_once __DIR__ . '/database_connection.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Define vehicle types and statuses for consistency (can be moved to config later)
define('VEHICLE_TYPES', ['Van', 'Truck (Light)', 'Truck (Heavy)', 'Motorcycle', 'Bicycle', 'Car', 'Other']);
define('VEHICLE_STATUSES', ['Available', 'In Use', 'Maintenance', 'Decommissioned']);

function create_vehicle($data) {
    $pdo = get_db_connection();
    $sql = "INSERT INTO vehicles (vehicle_name, license_plate, make, model, year, vin_number,
                                  vehicle_type, status, current_driver_id, max_payload_kg, max_volume_m3,
                                  notes, is_active)
            VALUES (:vehicle_name, :license_plate, :make, :model, :year, :vin_number,
                    :vehicle_type, :status, :current_driver_id, :max_payload_kg, :max_volume_m3,
                    :notes, :is_active)";
    $stmt = $pdo->prepare($sql);
    try {
        $params = [
            ':vehicle_name' => $data['vehicle_name'],
            ':license_plate' => empty($data['license_plate']) ? null : $data['license_plate'],
            ':make' => empty($data['make']) ? null : $data['make'],
            ':model' => empty($data['model']) ? null : $data['model'],
            ':year' => empty($data['year']) ? null : (int)$data['year'],
            ':vin_number' => empty($data['vin_number']) ? null : $data['vin_number'],
            ':vehicle_type' => $data['vehicle_type'],
            ':status' => $data['status'] ?? 'Available',
            ':current_driver_id' => empty($data['current_driver_id']) ? null : (int)$data['current_driver_id'],
            ':max_payload_kg' => empty($data['max_payload_kg']) ? null : (float)$data['max_payload_kg'],
            ':max_volume_m3' => empty($data['max_volume_m3']) ? null : (float)$data['max_volume_m3'],
            ':notes' => empty($data['notes']) ? null : $data['notes'],
            ':is_active' => $data['is_active'] ?? true,
        ];
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Integrity constraint (e.g., unique license_plate or vin)
            if (strpos(strtolower($e->getMessage()), 'license_plate') !== false) {
               $_SESSION['error_message'] = 'A vehicle with this license plate already exists.';
            } elseif (strpos(strtolower($e->getMessage()), 'vin_number') !== false) {
               $_SESSION['error_message'] = 'A vehicle with this VIN number already exists.';
            } else {
               $_SESSION['error_message'] = 'Vehicle creation failed: Duplicate entry. ' . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
        }
        error_log("Create Vehicle Error: " . $e->getMessage() . " Data: " . json_encode($data));
        return false;
    }
}

function get_vehicle_by_id($id) {
    $pdo = get_db_connection();
    $sql = "SELECT v.*, u.username as driver_username
            FROM vehicles v
            LEFT JOIN users u ON v.current_driver_id = u.id
            WHERE v.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function get_all_vehicles($filters = [], $sort_by = 'v.vehicle_name', $sort_order = 'ASC', $limit = 25, $offset = 0) {
    $pdo = get_db_connection();
    $select_sql = "SELECT v.*, u.username as driver_username
                   FROM vehicles v
                   LEFT JOIN users u ON v.current_driver_id = u.id";
    $count_sql = "SELECT COUNT(v.id) FROM vehicles v LEFT JOIN users u ON v.current_driver_id = u.id"; // Ensure join for count if filters use joined table

    $where_clauses = [];
    $params = [];

    if (!empty($filters['vehicle_type'])) {
        $where_clauses[] = "v.vehicle_type = :vehicle_type";
        $params[':vehicle_type'] = $filters['vehicle_type'];
    }
    if (!empty($filters['status'])) {
        $where_clauses[] = "v.status = :status";
        $params[':status'] = $filters['status'];
    }
    if (isset($filters['is_active']) && $filters['is_active'] !== '') {
        $where_clauses[] = "v.is_active = :is_active";
        $params[':is_active'] = (bool)$filters['is_active'];
    }
    if (!empty($filters['search_term'])) {
        $where_clauses[] = "(v.vehicle_name LIKE :search_term OR v.license_plate LIKE :search_term OR v.vin_number LIKE :search_term OR u.username LIKE :search_term)";
        $params[':search_term'] = '%' . $filters['search_term'] . '%';
    }


    if (!empty($where_clauses)) {
        $where_sql_part = " WHERE " . implode(" AND ", $where_clauses);
        $select_sql .= $where_sql_part;
        $count_sql .= $where_sql_part;
    }

    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute($params);
    $total_count = $stmt_count->fetchColumn();

    $allowed_sort_columns = ['v.vehicle_name', 'v.license_plate', 'v.vehicle_type', 'v.status', 'v.is_active', 'driver_username'];
    if (!in_array($sort_by, $allowed_sort_columns)) {
        $sort_by = 'v.vehicle_name';
    }
    $actual_sort_column = ($sort_by === 'driver_username') ? 'u.username' : $sort_by; // Use table alias for joined column
    $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

    $select_sql .= " ORDER BY {$actual_sort_column} {$sort_order} LIMIT :limit OFFSET :offset";

    $stmt_select = $pdo->prepare($select_sql);
    foreach ($params as $key => $value) {
        $stmt_select->bindValue($key, $value);
    }
    $stmt_select->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt_select->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    $stmt_select->execute();
    $vehicles = $stmt_select->fetchAll();

    return ['vehicles' => $vehicles, 'total_count' => $total_count];
}

function update_vehicle($id, $data) {
    $pdo = get_db_connection();
    $sql = "UPDATE vehicles SET
                vehicle_name = :vehicle_name, license_plate = :license_plate, make = :make,
                model = :model, year = :year, vin_number = :vin_number, vehicle_type = :vehicle_type,
                status = :status, current_driver_id = :current_driver_id, max_payload_kg = :max_payload_kg,
                max_volume_m3 = :max_volume_m3, notes = :notes, is_active = :is_active,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    try {
         $params = [
            ':id' => $id,
            ':vehicle_name' => $data['vehicle_name'],
            ':license_plate' => empty($data['license_plate']) ? null : $data['license_plate'],
            ':make' => empty($data['make']) ? null : $data['make'],
            ':model' => empty($data['model']) ? null : $data['model'],
            ':year' => empty($data['year']) ? null : (int)$data['year'],
            ':vin_number' => empty($data['vin_number']) ? null : $data['vin_number'],
            ':vehicle_type' => $data['vehicle_type'],
            ':status' => $data['status'] ?? 'Available',
            ':current_driver_id' => empty($data['current_driver_id']) ? null : (int)$data['current_driver_id'],
            ':max_payload_kg' => empty($data['max_payload_kg']) ? null : (float)$data['max_payload_kg'],
            ':max_volume_m3' => empty($data['max_volume_m3']) ? null : (float)$data['max_volume_m3'],
            ':notes' => empty($data['notes']) ? null : $data['notes'],
            ':is_active' => $data['is_active'] ?? true,
        ];
        return $stmt->execute($params);
    } catch (PDOException $e) {
         if ($e->getCode() == 23000) {
            if (strpos(strtolower($e->getMessage()), 'license_plate') !== false) {
               $_SESSION['error_message'] = 'Update failed: License plate already exists for another vehicle.';
            } elseif (strpos(strtolower($e->getMessage()), 'vin_number') !== false) {
               $_SESSION['error_message'] = 'Update failed: VIN number already exists for another vehicle.';
            } else {
               $_SESSION['error_message'] = 'Update failed: Duplicate entry. ' . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = 'Database error during vehicle update: ' . $e->getMessage();
        }
        error_log("Update Vehicle Error (ID: {$id}): " . $e->getMessage() . " Data: " . json_encode($data));
        return false;
    }
}

// Fetches users who could be drivers (e.g., specific role or all active users)
function get_available_drivers() {
    $pdo = get_db_connection();
    // This is simplified. Ideally, filter by a 'driver' role.
    // For now, fetching all active users that could be drivers
    $stmt = $pdo->query("SELECT id, username FROM users WHERE is_active = TRUE AND role IN ('driver', 'picker', 'admin', 'user') ORDER BY username ASC");
    // Added 'user' to broaden selection for testing, refine roles as needed.
    return $stmt->fetchAll();
}
?>
