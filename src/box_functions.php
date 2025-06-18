<?php
require_once __DIR__ . '/database_connection.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

function create_box_definition($data) {
    $pdo = get_db_connection();
    $sql = "INSERT INTO box_definitions (name, length_cm, width_cm, height_cm,
                                          max_weight_kg, empty_box_weight_kg, notes, is_active)
            VALUES (:name, :length_cm, :width_cm, :height_cm,
                    :max_weight_kg, :empty_box_weight_kg, :notes, :is_active)";
    $stmt = $pdo->prepare($sql);
    try {
        $params = [
            ':name' => $data['name'],
            ':length_cm' => (float)$data['length_cm'],
            ':width_cm' => (float)$data['width_cm'],
            ':height_cm' => (float)$data['height_cm'],
            ':max_weight_kg' => empty($data['max_weight_kg']) ? null : (float)$data['max_weight_kg'],
            ':empty_box_weight_kg' => empty($data['empty_box_weight_kg']) ? null : (float)$data['empty_box_weight_kg'],
            ':notes' => empty($data['notes']) ? null : $data['notes'],
            ':is_active' => $data['is_active'] ?? true,
        ];
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Integrity constraint (e.g., unique name)
           $_SESSION['error_message'] = 'A box definition with this name already exists.';
        } else {
            $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
        }
        error_log("Create Box Definition Error: " . $e->getMessage() . " Data: " . json_encode($data));
        return false;
    }
}

function get_box_definition_by_id($id) {
    $pdo = get_db_connection();
    $sql = "SELECT * FROM box_definitions WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function get_all_box_definitions($filters = [], $sort_by = 'name', $sort_order = 'ASC', $limit = 25, $offset = 0) {
    $pdo = get_db_connection();
    $select_sql = "SELECT * FROM box_definitions";
    $count_sql = "SELECT COUNT(id) FROM box_definitions";

    $where_clauses = [];
    $params = [];

    if (isset($filters['is_active']) && $filters['is_active'] !== '') {
        $where_clauses[] = "is_active = :is_active";
        $params[':is_active'] = (bool)$filters['is_active'];
    }
    if (!empty($filters['name_search'])) {
        $where_clauses[] = "name LIKE :name_search";
        $params[':name_search'] = '%' . $filters['name_search'] . '%';
    }

    if (!empty($where_clauses)) {
        $where_sql_part = " WHERE " . implode(" AND ", $where_clauses);
        $select_sql .= $where_sql_part;
        $count_sql .= $where_sql_part;
    }

    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute($params);
    $total_count = $stmt_count->fetchColumn();

    $allowed_sort_columns = ['name', 'length_cm', 'width_cm', 'height_cm', 'is_active'];
    if (!in_array($sort_by, $allowed_sort_columns)) {
        $sort_by = 'name';
    }
    $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

    $select_sql .= " ORDER BY {$sort_by} {$sort_order} LIMIT :limit OFFSET :offset";

    $stmt_select = $pdo->prepare($select_sql);
    foreach ($params as $key => $value) {
        $stmt_select->bindValue($key, $value);
    }
    $stmt_select->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt_select->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    $stmt_select->execute();
    $boxes = $stmt_select->fetchAll();

    return ['boxes' => $boxes, 'total_count' => $total_count];
}

function update_box_definition($id, $data) {
    $pdo = get_db_connection();
    $sql = "UPDATE box_definitions SET
                name = :name, length_cm = :length_cm, width_cm = :width_cm, height_cm = :height_cm,
                max_weight_kg = :max_weight_kg, empty_box_weight_kg = :empty_box_weight_kg,
                notes = :notes, is_active = :is_active, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    try {
         $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':length_cm' => (float)$data['length_cm'],
            ':width_cm' => (float)$data['width_cm'],
            ':height_cm' => (float)$data['height_cm'],
            ':max_weight_kg' => empty($data['max_weight_kg']) ? null : (float)$data['max_weight_kg'],
            ':empty_box_weight_kg' => empty($data['empty_box_weight_kg']) ? null : (float)$data['empty_box_weight_kg'],
            ':notes' => empty($data['notes']) ? null : $data['notes'],
            ':is_active' => $data['is_active'] ?? true,
        ];
        return $stmt->execute($params);
    } catch (PDOException $e) {
         if ($e->getCode() == 23000) {
             $_SESSION['error_message'] = 'Update failed: A box definition with this name already exists.';
         } else {
            $_SESSION['error_message'] = 'Database error during box definition update: ' . $e->getMessage();
        }
        error_log("Update Box Definition Error (ID: {$id}): " . $e->getMessage() . " Data: " . json_encode($data));
        return false;
    }
}

/**
 * Fetches all active box definitions for use in dropdowns.
 * @return array List of active box definitions (id, name, dimensions).
 */
function get_active_box_definitions_for_select() {
    $pdo = get_db_connection();
    $sql = "SELECT id, name, length_cm, width_cm, height_cm FROM box_definitions WHERE is_active = TRUE ORDER BY name ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}
?>
