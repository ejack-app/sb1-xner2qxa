<?php
require_once __DIR__ . '/database_connection.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

function create_item($data) {
    $pdo = get_db_connection();
    $pdo->beginTransaction();
    try {
        $sql_item = "INSERT INTO items (sku, name, description, barcode, unit_of_measure,
                                      default_purchase_price, default_selling_price,
                                      weight, length, width, height, image_url, is_active, created_by_user_id)
                     VALUES (:sku, :name, :description, :barcode, :unit_of_measure,
                             :default_purchase_price, :default_selling_price,
                             :weight, :length, :width, :height, :image_url, :is_active, :created_by_user_id)";
        $stmt_item = $pdo->prepare($sql_item);
        $stmt_item->execute([
            ':sku' => $data['sku'],
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':barcode' => $data['barcode'] ?? null,
            ':unit_of_measure' => $data['unit_of_measure'] ?? null,
            ':default_purchase_price' => $data['default_purchase_price'] ?? null,
            ':default_selling_price' => $data['default_selling_price'] ?? null,
            ':weight' => $data['weight'] ?? null,
            ':length' => $data['length'] ?? null,
            ':width' => $data['width'] ?? null,
            ':height' => $data['height'] ?? null,
            ':image_url' => $data['image_url'] ?? null,
            ':is_active' => $data['is_active'] ?? true,
            ':created_by_user_id' => $_SESSION['user_id'] ?? null,
        ]);
        $item_id = $pdo->lastInsertId();

        // Create initial stock record
        $sql_stock = "INSERT INTO inventory_stock (item_id, quantity_on_hand, low_stock_threshold, location_in_warehouse)
                      VALUES (:item_id, :quantity_on_hand, :low_stock_threshold, :location_in_warehouse)";
        $stmt_stock = $pdo->prepare($sql_stock);
        $stmt_stock->execute([
            ':item_id' => $item_id,
            ':quantity_on_hand' => $data['initial_quantity_on_hand'] ?? 0,
            ':low_stock_threshold' => $data['low_stock_threshold'] ?? null,
            ':location_in_warehouse' => $data['location_in_warehouse'] ?? null,
        ]);

        $pdo->commit();
        return $item_id;
    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) { // Integrity constraint violation
            if (strpos(strtolower($e->getMessage()), 'sku') !== false) {
                $_SESSION['error_message'] = 'Item with this SKU already exists.';
            } elseif (strpos(strtolower($e->getMessage()), 'barcode') !== false) {
                $_SESSION['error_message'] = 'Item with this Barcode already exists.';
            } else {
                $_SESSION['error_message'] = 'Database error: Duplicate entry. ' . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
        }
        error_log("Create Item Error: " . $e->getMessage());
        return false;
    }
}

function get_item_by_sku($sku) {
    $pdo = get_db_connection();
    $sql = "SELECT i.*, s.quantity_on_hand, s.quantity_allocated,
                   (s.quantity_on_hand - s.quantity_allocated) as quantity_available,
                   s.low_stock_threshold, s.location_in_warehouse
            FROM items i
            LEFT JOIN inventory_stock s ON i.id = s.item_id
            WHERE i.sku = :sku";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':sku' => $sku]);
    return $stmt->fetch();
}

function get_item_by_id($id) {
    $pdo = get_db_connection();
    $sql = "SELECT i.*, s.quantity_on_hand, s.quantity_allocated,
                   (s.quantity_on_hand - s.quantity_allocated) as quantity_available,
                   s.low_stock_threshold, s.location_in_warehouse
            FROM items i
            LEFT JOIN inventory_stock s ON i.id = s.item_id
            WHERE i.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function get_all_items($filters = [], $sort_by = 'i.name', $sort_order = 'ASC', $limit = 25, $offset = 0) {
    $pdo = get_db_connection();
    $select_sql = "SELECT i.id, i.sku, i.name, i.description, i.is_active,
                          s.quantity_on_hand, s.quantity_allocated,
                          (s.quantity_on_hand - s.quantity_allocated) as quantity_available,
                          s.location_in_warehouse
                   FROM items i
                   LEFT JOIN inventory_stock s ON i.id = s.item_id";

    $count_sql = "SELECT COUNT(i.id) FROM items i";

    $where_clauses = [];
    $params = [];

    if (!empty($filters['sku'])) {
        $where_clauses[] = "i.sku LIKE :sku";
        $params[':sku'] = '%' . $filters['sku'] . '%';
    }
    if (!empty($filters['name'])) {
        $where_clauses[] = "i.name LIKE :name";
        $params[':name'] = '%' . $filters['name'] . '%';
    }
    if (isset($filters['is_active']) && $filters['is_active'] !== '') {
        $where_clauses[] = "i.is_active = :is_active";
        $params[':is_active'] = (bool)$filters['is_active'];
    }


    if (!empty($where_clauses)) {
        $where_sql = " WHERE " . implode(" AND ", $where_clauses);
        $select_sql .= $where_sql;
        $count_sql .= $where_sql; // Apply filters to count query as well
    }

    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute($params);
    $total_count = $stmt_count->fetchColumn();

    $allowed_sort_columns = ['i.sku', 'i.name', 'quantity_available', 'i.is_active']; // 'quantity_available' is an alias
    if (!in_array($sort_by, $allowed_sort_columns)) {
        $sort_by = 'i.name';
    }
    // For alias, don't prefix with table name for ORDER BY
    $actual_sort_column = ($sort_by === 'quantity_available') ? 'quantity_available' : $sort_by;

    $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

    $select_sql .= " ORDER BY {$actual_sort_column} {$sort_order}";
    $select_sql .= " LIMIT :limit OFFSET :offset";

    $stmt_select = $pdo->prepare($select_sql);
    foreach ($params as $key => $value) {
        $stmt_select->bindValue($key, $value);
    }
    $stmt_select->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt_select->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    $stmt_select->execute();
    $items = $stmt_select->fetchAll();

    return ['items' => $items, 'total_count' => $total_count];
}

// Basic stock adjustment functions (to be expanded with logging)
function add_stock_quantity($item_id, $quantity_change) {
   $pdo = get_db_connection();
   // Ensure quantity_change is integer
   $quantity_change = (int)$quantity_change;
   if ($quantity_change == 0) return true; // No change

   $sql = "UPDATE inventory_stock
           SET quantity_on_hand = quantity_on_hand + :quantity_change
           WHERE item_id = :item_id";
   $stmt = $pdo->prepare($sql);
   try {
       return $stmt->execute([':quantity_change' => $quantity_change, ':item_id' => $item_id]);
       // TODO: Add inventory transaction log entry here
   } catch (PDOException $e) {
       error_log("Add Stock Quantity Error: " . $e->getMessage());
       return false;
   }
}

function allocate_stock($item_id, $quantity_to_allocate) {
   $pdo = get_db_connection();
   $quantity_to_allocate = (int)$quantity_to_allocate;
   if ($quantity_to_allocate <= 0) return false;

   // Check available stock before allocating
   $item_stock = get_item_by_id($item_id); // This fetches item and stock details
   if (!$item_stock || ($item_stock['quantity_on_hand'] - $item_stock['quantity_allocated']) < $quantity_to_allocate) {
       $_SESSION['error_message'] = "Not enough stock available to allocate for item ID {$item_id}.";
       return false;
   }

   $sql = "UPDATE inventory_stock
           SET quantity_allocated = quantity_allocated + :quantity_to_allocate
           WHERE item_id = :item_id";
   $stmt = $pdo->prepare($sql);
   try {
       $success = $stmt->execute([':quantity_to_allocate' => $quantity_to_allocate, ':item_id' => $item_id]);
       // TODO: Add inventory transaction log entry here ('ALLOCATED')
       return $success;
   } catch (PDOException $e) {
       error_log("Allocate Stock Error: " . $e->getMessage());
       return false;
   }
}

function release_stock($item_id, $quantity_to_release) {
   $pdo = get_db_connection();
   $quantity_to_release = (int)$quantity_to_release;
   if ($quantity_to_release <= 0) return false;

   // Ensure we don't release more than allocated
   $item_stock = get_item_by_id($item_id);
   if ($item_stock && $item_stock['quantity_allocated'] < $quantity_to_release) {
        // Adjust to release only what's allocated if trying to release more
       $quantity_to_release = $item_stock['quantity_allocated'];
   }
   if ($quantity_to_release <= 0) return true; // Nothing to release effectively

   $sql = "UPDATE inventory_stock
           SET quantity_allocated = GREATEST(0, quantity_allocated - :quantity_to_release)
           WHERE item_id = :item_id";
   $stmt = $pdo->prepare($sql);
   try {
       $success = $stmt->execute([':quantity_to_release' => $quantity_to_release, ':item_id' => $item_id]);
       // TODO: Add inventory transaction log entry here ('RELEASED_ALLOCATION' or similar)
       return $success;
   } catch (PDOException $e) {
       error_log("Release Stock Error: " . $e->getMessage());
       return false;
   }
}

function deduct_shipped_stock($item_id, $quantity_shipped) {
   $pdo = get_db_connection();
   $quantity_shipped = (int)$quantity_shipped;
   if ($quantity_shipped <= 0) return false;

   // This function assumes stock was allocated. It reduces both on_hand and allocated.
   $sql = "UPDATE inventory_stock
           SET quantity_on_hand = GREATEST(0, quantity_on_hand - :quantity_shipped),
               quantity_allocated = GREATEST(0, quantity_allocated - :quantity_shipped)
           WHERE item_id = :item_id";
   $stmt = $pdo->prepare($sql);
   try {
       $success = $stmt->execute([':quantity_shipped' => $quantity_shipped, ':item_id' => $item_id]);
       // TODO: Add inventory transaction log entry here ('SHIPPED' or 'SALE')
       return $success;
   } catch (PDOException $e) {
       error_log("Deduct Shipped Stock Error: " . $e->getMessage());
       return false;
   }
}

// update_item function to be added later if needed for editing master item data
?>
