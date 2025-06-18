<?php
require_once __DIR__ . '/database_connection.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

function create_seller($data) {
    $pdo = get_db_connection();
    $sql = "INSERT INTO sellers (seller_name, contact_person, email, phone, address,
                                  commission_rate_percentage, user_id, is_active)
            VALUES (:seller_name, :contact_person, :email, :phone, :address,
                    :commission_rate_percentage, :user_id, :is_active)";
    $stmt = $pdo->prepare($sql);
    try {
        $params = [
            ':seller_name' => $data['seller_name'],
            ':contact_person' => empty($data['contact_person']) ? null : $data['contact_person'],
            ':email' => $data['email'],
            ':phone' => empty($data['phone']) ? null : $data['phone'],
            ':address' => empty($data['address']) ? null : $data['address'],
            ':commission_rate_percentage' => empty($data['commission_rate_percentage']) ? 0.00 : (float)$data['commission_rate_percentage'],
            ':user_id' => empty($data['user_id']) ? null : (int)$data['user_id'],
            ':is_active' => $data['is_active'] ?? true,
        ];
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Integrity constraint (unique name or email)
            if (strpos(strtolower($e->getMessage()), 'seller_name') !== false) {
               $_SESSION['error_message'] = 'A seller with this name already exists.';
            } elseif (strpos(strtolower($e->getMessage()), 'email') !== false) {
               $_SESSION['error_message'] = 'A seller with this email address already exists.';
            } else {
                $_SESSION['error_message'] = 'Seller creation failed: Duplicate entry. ' . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = 'Database error creating seller: ' . $e->getMessage();
        }
        error_log("Create Seller Error: " . $e->getMessage() . " Data: " . json_encode($data));
        return false;
    }
}

function get_seller_by_id($id) {
    $pdo = get_db_connection();
    $sql = "SELECT s.*, u.username as linked_username
            FROM sellers s
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function get_all_sellers($filters = [], $sort_by = 's.seller_name', $sort_order = 'ASC', $limit = 25, $offset = 0) {
    $pdo = get_db_connection();
    $select_sql = "SELECT s.*, u.username as linked_username FROM sellers s LEFT JOIN users u ON s.user_id = u.id";
    $count_sql = "SELECT COUNT(s.id) FROM sellers s LEFT JOIN users u ON s.user_id = u.id"; // Ensure join for count if filters use joined table

    $where_clauses = [];
    $params = [];

    if (isset($filters['is_active']) && $filters['is_active'] !== '') {
        $where_clauses[] = "s.is_active = :is_active";
        $params[':is_active'] = (bool)$filters['is_active'];
    }
    if (!empty($filters['search_term'])) {
        // Searching on seller_name, email, contact_person, and linked_username
        $where_clauses[] = "(s.seller_name LIKE :search_term OR s.email LIKE :search_term OR s.contact_person LIKE :search_term OR u.username LIKE :search_term)";
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

    $allowed_sort_columns = ['s.seller_name', 's.email', 's.is_active', 's.created_at', 'linked_username'];
    if (!in_array($sort_by, $allowed_sort_columns)) {
        $sort_by = 's.seller_name';
    }
    $actual_sort_column = ($sort_by === 'linked_username') ? 'u.username' : $sort_by;
    $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

    $select_sql .= " ORDER BY {$actual_sort_column} {$sort_order} LIMIT :limit OFFSET :offset";

    $stmt_select = $pdo->prepare($select_sql);
    foreach ($params as $key => $value) {
        $stmt_select->bindValue($key, $value);
    }
    $stmt_select->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt_select->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    $stmt_select->execute();
    $sellers = $stmt_select->fetchAll();

    return ['sellers' => $sellers, 'total_count' => $total_count];
}

function update_seller($id, $data) {
    $pdo = get_db_connection();
    $sql = "UPDATE sellers SET
                seller_name = :seller_name, contact_person = :contact_person, email = :email,
                phone = :phone, address = :address,
                commission_rate_percentage = :commission_rate_percentage,
                user_id = :user_id, is_active = :is_active, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    try {
         $params = [
            ':id' => $id,
            ':seller_name' => $data['seller_name'],
            ':contact_person' => empty($data['contact_person']) ? null : $data['contact_person'],
            ':email' => $data['email'],
            ':phone' => empty($data['phone']) ? null : $data['phone'],
            ':address' => empty($data['address']) ? null : $data['address'],
            ':commission_rate_percentage' => empty($data['commission_rate_percentage']) ? 0.00 : (float)$data['commission_rate_percentage'],
            ':user_id' => empty($data['user_id']) ? null : (int)$data['user_id'],
            ':is_active' => $data['is_active'] ?? true,
        ];
        return $stmt->execute($params);
    } catch (PDOException $e) {
         if ($e->getCode() == 23000) {
            if (strpos(strtolower($e->getMessage()), 'seller_name') !== false) {
               $_SESSION['error_message'] = 'Update failed: Seller name already exists.';
            } elseif (strpos(strtolower($e->getMessage()), 'email') !== false) {
               $_SESSION['error_message'] = 'Update failed: Seller email already exists.';
            } else {
                $_SESSION['error_message'] = 'Update failed: Duplicate entry. ' . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = 'Database error updating seller: ' . $e->getMessage();
        }
        error_log("Update Seller Error (ID: {$id}): " . $e->getMessage() . " Data: " . json_encode($data));
        return false;
    }
}

/**
 * Fetches all active sellers for use in dropdowns.
 * @return array List of active sellers (id, seller_name).
 */
function get_active_sellers_for_select() {
    $pdo = get_db_connection();
    $sql = "SELECT id, seller_name FROM sellers WHERE is_active = TRUE ORDER BY seller_name ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}
?>
