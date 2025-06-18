<?php
require_once __DIR__ . '/database_connection.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// --- Route Functions ---
function create_route($data) {
    $pdo = get_db_connection();
    $sql = "INSERT INTO routes (route_name, description, region_city, expected_duration_hours, distance_km, is_active)
            VALUES (:route_name, :description, :region_city, :expected_duration_hours, :distance_km, :is_active)";
    $stmt = $pdo->prepare($sql);
    try {
        $params = [
            ':route_name' => $data['route_name'],
            ':description' => empty($data['description']) ? null : $data['description'],
            ':region_city' => empty($data['region_city']) ? null : $data['region_city'],
            ':expected_duration_hours' => empty($data['expected_duration_hours']) ? null : (float)$data['expected_duration_hours'],
            ':distance_km' => empty($data['distance_km']) ? null : (float)$data['distance_km'],
            ':is_active' => $data['is_active'] ?? true,
        ];
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { $_SESSION['error_message'] = 'A route with this name already exists.'; }
        else { $_SESSION['error_message'] = 'DB Error: ' . $e->getMessage(); }
        error_log("Create Route Error: " . $e->getMessage());
        return false;
    }
}

function get_route_by_id($id) {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT * FROM routes WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $route = $stmt->fetch();
    if ($route) {
        $route['points'] = get_route_points($id); // Attach points directly
    }
    return $route;
}

function get_all_routes($filters = [], $sort_by = 'route_name', $sort_order = 'ASC', $limit = 25, $offset = 0) {
    $pdo = get_db_connection();
    $select_sql = "SELECT r.*, (SELECT COUNT(*) FROM route_points rp WHERE rp.route_id = r.id) as points_count
                   FROM routes r";
    $count_sql = "SELECT COUNT(r.id) FROM routes r"; // Alias r for consistency

    $where_clauses = [];
    $params = [];
    if (isset($filters['is_active']) && $filters['is_active'] !== '') {
        $where_clauses[] = "r.is_active = :is_active"; $params[':is_active'] = (bool)$filters['is_active'];
    }
    if (!empty($filters['search_term'])) {
        $where_clauses[] = "(r.route_name LIKE :search OR r.region_city LIKE :search)";
        $params[':search'] = '%' . $filters['search_term'] . '%';
    }
    if (!empty($where_clauses)) {
       $where_sql_part = " WHERE " . implode(" AND ", $where_clauses);
       $select_sql .= $where_sql_part;
       $count_sql .= $where_sql_part;
    }

    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute($params);
    $total_count = $stmt_count->fetchColumn();

    $allowed_sorts = ['route_name', 'region_city', 'is_active', 'points_count'];
    if(!in_array($sort_by, $allowed_sorts)) $sort_by = 'route_name';
    // points_count is an alias, MySQL allows sorting by alias.
    $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';
    $select_sql .= " ORDER BY {$sort_by} {$sort_order} LIMIT :limit OFFSET :offset";

    $stmt_select = $pdo->prepare($select_sql);
    foreach ($params as $key => $value) { $stmt_select->bindValue($key, $value); }
    $stmt_select->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt_select->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt_select->execute();
    $routes = $stmt_select->fetchAll();
    return ['routes' => $routes, 'total_count' => $total_count];
}

function update_route($id, $data) {
    $pdo = get_db_connection();
    $sql = "UPDATE routes SET route_name = :route_name, description = :description, region_city = :region_city,
                expected_duration_hours = :expected_duration_hours, distance_km = :distance_km,
                is_active = :is_active, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    try {
        $params = [
            ':id' => $id,
            ':route_name' => $data['route_name'],
            ':description' => empty($data['description']) ? null : $data['description'],
            ':region_city' => empty($data['region_city']) ? null : $data['region_city'],
            ':expected_duration_hours' => empty($data['expected_duration_hours']) ? null : (float)$data['expected_duration_hours'],
            ':distance_km' => empty($data['distance_km']) ? null : (float)$data['distance_km'],
            ':is_active' => $data['is_active'] ?? true,
        ];
        return $stmt->execute($params);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { $_SESSION['error_message'] = 'A route with this name already exists.'; }
        else { $_SESSION['error_message'] = 'DB Error: ' . $e->getMessage(); }
        error_log("Update Route Error: " . $e->getMessage());
        return false;
    }
}

function get_active_routes_for_select() {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT id, route_name FROM routes WHERE is_active = TRUE ORDER BY route_name ASC");
    return $stmt->fetchAll();
}

// --- Route Point Functions ---
function add_route_point($route_id, $data) {
    $pdo = get_db_connection();
    $sql = "INSERT INTO route_points (route_id, sequence_number, location_name_or_code, latitude, longitude, notes, arrival_time_offset_minutes, departure_time_offset_minutes)
            VALUES (:route_id, :sequence_number, :location_name_or_code, :latitude, :longitude, :notes, :arrival_time_offset_minutes, :departure_time_offset_minutes)";
    $stmt = $pdo->prepare($sql);
    try {
        if (!isset($data['sequence_number']) || $data['sequence_number'] === '') {
            $stmt_max_seq = $pdo->prepare("SELECT MAX(sequence_number) FROM route_points WHERE route_id = :route_id");
            $stmt_max_seq->execute([':route_id' => $route_id]);
            $max_seq = $stmt_max_seq->fetchColumn();
            $data['sequence_number'] = ($max_seq === null) ? 1 : $max_seq + 1;
        }
        $params = [
            ':route_id' => $route_id,
            ':sequence_number' => (int)$data['sequence_number'],
            ':location_name_or_code' => $data['location_name_or_code'],
            ':latitude' => empty($data['latitude']) ? null : (float)$data['latitude'],
            ':longitude' => empty($data['longitude']) ? null : (float)$data['longitude'],
            ':notes' => empty($data['notes']) ? null : $data['notes'],
            ':arrival_time_offset_minutes' => empty($data['arrival_time_offset_minutes']) ? null : (int)$data['arrival_time_offset_minutes'],
            ':departure_time_offset_minutes' => empty($data['departure_time_offset_minutes']) ? null : (int)$data['departure_time_offset_minutes'],
        ];
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000 && strpos(strtolower($e->getMessage()), 'uq_route_sequence') !== false) {
             $_SESSION['error_message'] = 'This sequence number already exists for this route.';
        } else { $_SESSION['error_message'] = 'DB Error adding route point: ' . $e->getMessage(); }
        error_log("Add Route Point Error: " . $e->getMessage());
        return false;
    }
}

function get_route_points($route_id) {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT * FROM route_points WHERE route_id = :route_id ORDER BY sequence_number ASC");
    $stmt->execute([':route_id' => $route_id]);
    return $stmt->fetchAll();
}

function get_route_point_by_id($point_id) {
     $pdo = get_db_connection();
     $stmt = $pdo->prepare("SELECT * FROM route_points WHERE id = :id");
     $stmt->execute([':id' => $point_id]);
     return $stmt->fetch();
}

function update_route_point($point_id, $data) {
    $pdo = get_db_connection();
    $sql = "UPDATE route_points SET sequence_number = :sequence_number,
                location_name_or_code = :location_name_or_code, latitude = :latitude, longitude = :longitude,
                notes = :notes, arrival_time_offset_minutes = :arrival_time_offset_minutes,
                departure_time_offset_minutes = :departure_time_offset_minutes
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    try {
        $params = [
            ':id' => $point_id,
            ':sequence_number' => (int)$data['sequence_number'],
            ':location_name_or_code' => $data['location_name_or_code'],
            ':latitude' => empty($data['latitude']) ? null : (float)$data['latitude'],
            ':longitude' => empty($data['longitude']) ? null : (float)$data['longitude'],
            ':notes' => empty($data['notes']) ? null : $data['notes'],
            ':arrival_time_offset_minutes' => empty($data['arrival_time_offset_minutes']) ? null : (int)$data['arrival_time_offset_minutes'],
            ':departure_time_offset_minutes' => empty($data['departure_time_offset_minutes']) ? null : (int)$data['departure_time_offset_minutes'],
        ];
        return $stmt->execute($params);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000 && strpos(strtolower($e->getMessage()), 'uq_route_sequence') !== false) {
             $_SESSION['error_message'] = 'This sequence number already exists for this route.';
        } else { $_SESSION['error_message'] = 'DB Error updating route point: ' . $e->getMessage(); }
        error_log("Update Route Point Error: " . $e->getMessage());
        return false;
    }
}

function remove_route_point($point_id) {
    $pdo = get_db_connection();
    $sql = "DELETE FROM route_points WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    try {
        return $stmt->execute([':id' => $point_id]);
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'DB Error: ' . $e->getMessage();
        error_log("Remove Route Point Error: " . $e->getMessage());
        return false;
    }
}

function reorder_route_points($route_id, $ordered_point_ids_array) {
    $pdo = get_db_connection();
    $pdo->beginTransaction();
    try {
        $sql = "UPDATE route_points SET sequence_number = :sequence_number WHERE id = :id AND route_id = :route_id";
        $stmt = $pdo->prepare($sql);
        foreach ($ordered_point_ids_array as $index => $point_id) {
            $stmt->execute([
                ':sequence_number' => $index + 1, // Sequence is 1-based
                ':id' => $point_id,
                ':route_id' => $route_id
            ]);
        }
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'DB Error reordering route points: ' . $e->getMessage();
        error_log("Reorder Route Points Error: " . $e->getMessage());
        return false;
    }
}
?>
