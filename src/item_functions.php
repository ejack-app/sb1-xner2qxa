<?php
require_once __DIR__ . '/database_connection.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Modified create_item to support initial stock in specific locations
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

        // Handle initial stock entries if provided
        if (!empty($data['stock_entries']) && is_array($data['stock_entries'])) {
            $sql_stock = "INSERT INTO inventory_stock (item_id, stock_location_id, quantity_on_hand, low_stock_threshold)
                          VALUES (:item_id, :stock_location_id, :quantity_on_hand, :low_stock_threshold)";
            $stmt_stock = $pdo->prepare($sql_stock);
            foreach ($data['stock_entries'] as $entry) {
                if (isset($entry['stock_location_id'], $entry['quantity_on_hand'])) {
                    $stmt_stock->execute([
                        ':item_id' => $item_id,
                        ':stock_location_id' => $entry['stock_location_id'],
                        ':quantity_on_hand' => (int)$entry['quantity_on_hand'],
                        ':low_stock_threshold' => isset($entry['low_stock_threshold']) ? (int)$entry['low_stock_threshold'] : null,
                    ]);
                }
            }
        } elseif (isset($data['initial_quantity_on_hand']) && isset($data['initial_stock_location_id'])) {
            // Fallback for single initial stock location (if form doesn't support multiple yet)
             $sql_stock_single = "INSERT INTO inventory_stock (item_id, stock_location_id, quantity_on_hand, low_stock_threshold)
                                  VALUES (:item_id, :stock_location_id, :quantity_on_hand, :low_stock_threshold)";
            $stmt_stock_single = $pdo->prepare($sql_stock_single);
            $stmt_stock_single->execute([
                ':item_id' => $item_id,
                ':stock_location_id' => $data['initial_stock_location_id'],
                ':quantity_on_hand' => (int)$data['initial_quantity_on_hand'],
                ':low_stock_threshold' => isset($data['low_stock_threshold']) ? (int)$data['low_stock_threshold'] : null,
            ]);
        }


        $pdo->commit();
        return $item_id;
    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) {
            if (strpos(strtolower($e->getMessage()), 'sku') !== false) {
                $_SESSION['error_message'] = 'Item with this SKU already exists.';
            } elseif (strpos(strtolower($e->getMessage()), 'barcode') !== false) {
                $_SESSION['error_message'] = 'Item with this Barcode already exists.';
            } elseif (strpos(strtolower($e->getMessage()), 'uq_item_stock_location') !== false) {
                $_SESSION['error_message'] = 'Duplicate stock entry for item at the same location.';
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

// Fetches item master data and AGGREGATE stock levels across all locations
function get_item_by_sku($sku) {
    $pdo = get_db_connection();
    $sql = "SELECT i.*,
                   COALESCE(SUM(s.quantity_on_hand), 0) as total_quantity_on_hand,
                   COALESCE(SUM(s.quantity_allocated), 0) as total_quantity_allocated,
                   (COALESCE(SUM(s.quantity_on_hand), 0) - COALESCE(SUM(s.quantity_allocated), 0)) as total_quantity_available
            FROM items i
            LEFT JOIN inventory_stock s ON i.id = s.item_id
            WHERE i.sku = :sku
            GROUP BY i.id"; // Group by all columns of items table to get one row per item
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':sku' => $sku]);
    return $stmt->fetch();
}

function get_item_by_id($id) {
    $pdo = get_db_connection();
    $sql = "SELECT i.*,
                   COALESCE(SUM(s.quantity_on_hand), 0) as total_quantity_on_hand,
                   COALESCE(SUM(s.quantity_allocated), 0) as total_quantity_allocated,
                   (COALESCE(SUM(s.quantity_on_hand), 0) - COALESCE(SUM(s.quantity_allocated), 0)) as total_quantity_available
            FROM items i
            LEFT JOIN inventory_stock s ON i.id = s.item_id
            WHERE i.id = :id
            GROUP BY i.id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

// Fetches a list of stock levels for a specific item across different locations
function get_item_stock_levels_by_location($item_id) {
    $pdo = get_db_connection();
    $sql = "SELECT s.*, sl.location_code, w.name as warehouse_name
            FROM inventory_stock s
            JOIN stock_locations sl ON s.stock_location_id = sl.id
            JOIN warehouses w ON sl.warehouse_id = w.id
            WHERE s.item_id = :item_id
            ORDER BY w.name, sl.location_code";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':item_id' => $item_id]);
    return $stmt->fetchAll();
}


function get_all_items($filters = [], $sort_by = 'i.name', $sort_order = 'ASC', $limit = 25, $offset = 0) {
    $pdo = get_db_connection();
    // Columns from items table + aggregated stock quantities
    $select_sql = "SELECT i.id, i.sku, i.name, i.description, i.barcode, i.unit_of_measure, i.is_active,
                          COALESCE(SUM(s.quantity_on_hand), 0) as total_quantity_on_hand,
                          COALESCE(SUM(s.quantity_allocated), 0) as total_quantity_allocated,
                          (COALESCE(SUM(s.quantity_on_hand), 0) - COALESCE(SUM(s.quantity_allocated), 0)) as total_quantity_available
                   FROM items i
                   LEFT JOIN inventory_stock s ON i.id = s.item_id";

    $count_sql_base = "SELECT COUNT(DISTINCT i.id) FROM items i"; // Count distinct items

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
    // Note: Filtering by stock levels (e.g., quantity_available > 0) would require a HAVING clause here.

    $where_sql_part = "";
    if (!empty($where_clauses)) {
        $where_sql_part = " WHERE " . implode(" AND ", $where_clauses);
        $select_sql .= $where_sql_part;
        $count_sql_base .= $where_sql_part; // Apply to the base of count query
    }

    // For count, we don't need the LEFT JOIN if filters are only on `items` table.
    // If filters were on `inventory_stock`, the join would be needed for count too.
    // Since current filters are on `items`, $count_sql_base is fine.
    $stmt_count = $pdo->prepare($count_sql_base);
    $stmt_count->execute($params);
    $total_count = $stmt_count->fetchColumn();

    $select_sql .= " GROUP BY i.id, i.sku, i.name, i.description, i.barcode, i.unit_of_measure, i.is_active "; // Group by all selected non-aggregated columns from items

    // Handle sorting
    $allowed_sort_columns = ['i.sku', 'i.name', 'total_quantity_available', 'i.is_active'];
    // Map alias to actual expression for sorting if needed, or ensure DB supports alias in ORDER BY
    $actual_sort_column = $sort_by;
    if ($sort_by === 'total_quantity_available') {
        // MySQL allows alias in ORDER BY. For other DBs, might need the full expression.
        $actual_sort_column = 'total_quantity_available';
    } elseif (!in_array($sort_by, $allowed_sort_columns)) {
        $actual_sort_column = 'i.name'; // Default sort
    }
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

// Stock adjustment functions now require stock_location_id
function add_stock_quantity($item_id, $stock_location_id, $quantity_change) {
    $pdo = get_db_connection();
    $quantity_change = (int)$quantity_change;
    if ($quantity_change == 0) return true;

    // Upsert logic: If stock record doesn't exist for this item_id/stock_location_id, create it.
    // Otherwise, update it.
    $sql = "INSERT INTO inventory_stock (item_id, stock_location_id, quantity_on_hand, quantity_allocated)
            VALUES (:item_id, :stock_location_id, :quantity_on_hand, 0)
            ON DUPLICATE KEY UPDATE
            quantity_on_hand = quantity_on_hand + VALUES(quantity_on_hand)";
            // Note: VALUES(quantity_on_hand) will be the :quantity_on_hand from insert part.
            // So if inserting 10, it becomes current + 10.
            // If quantity_change can be negative, then use GREATEST(0, quantity_on_hand + :quantity_change_val) for update part.
            // For simplicity, assuming positive quantity_change for "add".

    $params = [
        ':item_id' => $item_id,
        ':stock_location_id' => $stock_location_id,
        ':quantity_on_hand' => $quantity_change // For the INSERT part
    ];

    // More robust for positive/negative changes:
    if ($quantity_change < 0) { // If reducing stock
        $sql_update = "UPDATE inventory_stock
                       SET quantity_on_hand = GREATEST(0, quantity_on_hand + :quantity_change)
                       WHERE item_id = :item_id AND stock_location_id = :stock_location_id";
        $stmt = $pdo->prepare($sql_update);
        $params_update = [
            ':quantity_change' => $quantity_change,
            ':item_id' => $item_id,
            ':stock_location_id' => $stock_location_id
        ];
         try {
            $stmt->execute($params_update);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Update Stock Quantity Error: " . $e->getMessage());
            return false;
        }
    } else { // If adding stock or creating new record
        $stmt = $pdo->prepare($sql);
         try {
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Add Stock Quantity (Insert/Update) Error: " . $e->getMessage());
            return false;
        }
    }
    // TODO: Add inventory transaction log entry here
}

function get_stock_at_location($item_id, $stock_location_id) {
    $pdo = get_db_connection();
    $sql = "SELECT *, (quantity_on_hand - quantity_allocated) as quantity_available
            FROM inventory_stock
            WHERE item_id = :item_id AND stock_location_id = :stock_location_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':item_id' => $item_id, ':stock_location_id' => $stock_location_id]);
    return $stmt->fetch();
}


function allocate_stock($item_id, $stock_location_id, $quantity_to_allocate) {
    $pdo = get_db_connection();
    $quantity_to_allocate = (int)$quantity_to_allocate;
    if ($quantity_to_allocate <= 0) {
        $_SESSION['error_message'] = "Quantity to allocate must be positive.";
        return false;
    }

    $stock_item = get_stock_at_location($item_id, $stock_location_id);
    if (!$stock_item) {
        $_SESSION['error_message'] = "No stock record found for item ID {$item_id} at location ID {$stock_location_id}.";
        return false;
    }
    if ($stock_item['quantity_available'] < $quantity_to_allocate) {
        $_SESSION['error_message'] = "Not enough stock available ({$stock_item['quantity_available']}) to allocate {$quantity_to_allocate} for item ID {$item_id} at location ID {$stock_location_id}.";
        return false;
    }

    $sql = "UPDATE inventory_stock
            SET quantity_allocated = quantity_allocated + :quantity_to_allocate
            WHERE item_id = :item_id AND stock_location_id = :stock_location_id";
    $stmt = $pdo->prepare($sql);
    try {
        $success = $stmt->execute([
            ':quantity_to_allocate' => $quantity_to_allocate,
            ':item_id' => $item_id,
            ':stock_location_id' => $stock_location_id
        ]);
        // TODO: Add inventory transaction log entry here ('ALLOCATED')
        return $success;
    } catch (PDOException $e) {
        error_log("Allocate Stock Error: " . $e->getMessage());
        $_SESSION['error_message'] = "Database error during stock allocation.";
        return false;
    }
}

function release_stock($item_id, $stock_location_id, $quantity_to_release) {
    $pdo = get_db_connection();
    $quantity_to_release = (int)$quantity_to_release;
    if ($quantity_to_release <= 0) {
        $_SESSION['error_message'] = "Quantity to release must be positive.";
        return false;
    }

    $stock_item = get_stock_at_location($item_id, $stock_location_id);
    if (!$stock_item) {
        // Or handle as "nothing to release" if that's valid.
        $_SESSION['error_message'] = "No stock record found for item ID {$item_id} at location ID {$stock_location_id}.";
        return false;
    }
    // Ensure we don't release more than allocated
    if ($stock_item['quantity_allocated'] < $quantity_to_release) {
        $quantity_to_release = $stock_item['quantity_allocated']; // Adjust to release only what's actually allocated
    }
    if ($quantity_to_release <= 0) return true; // Effectively nothing to release

    $sql = "UPDATE inventory_stock
            SET quantity_allocated = GREATEST(0, quantity_allocated - :quantity_to_release)
            WHERE item_id = :item_id AND stock_location_id = :stock_location_id";
    $stmt = $pdo->prepare($sql);
    try {
        $success = $stmt->execute([
            ':quantity_to_release' => $quantity_to_release,
            ':item_id' => $item_id,
            ':stock_location_id' => $stock_location_id
        ]);
        // TODO: Add inventory transaction log entry
        return $success;
    } catch (PDOException $e) {
        error_log("Release Stock Error: " . $e->getMessage());
        $_SESSION['error_message'] = "Database error during stock release.";
        return false;
    }
}

function deduct_shipped_stock($item_id, $stock_location_id, $quantity_shipped) {
    $pdo = get_db_connection();
    $quantity_shipped = (int)$quantity_shipped;
    if ($quantity_shipped <= 0) {
        $_SESSION['error_message'] = "Quantity shipped must be positive.";
        return false;
    }

    // It's good practice to check if the stock record exists and has enough allocated/on_hand stock
    // This is a simplified version. A robust system might re-check here or rely on prior allocation.
    $stock_item = get_stock_at_location($item_id, $stock_location_id);
    if (!$stock_item || $stock_item['quantity_on_hand'] < $quantity_shipped || $stock_item['quantity_allocated'] < $quantity_shipped) {
         $_SESSION['error_message'] = "Not enough stock on hand or allocated to deduct {$quantity_shipped} for item ID {$item_id} at location ID {$stock_location_id}. On Hand: {$stock_item['quantity_on_hand']}, Allocated: {$stock_item['quantity_allocated']}";
        return false;
    }


    $sql = "UPDATE inventory_stock
            SET quantity_on_hand = GREATEST(0, quantity_on_hand - :quantity_shipped),
                quantity_allocated = GREATEST(0, quantity_allocated - :quantity_shipped)
            WHERE item_id = :item_id AND stock_location_id = :stock_location_id";
    $stmt = $pdo->prepare($sql);
    try {
        $success = $stmt->execute([
            ':quantity_shipped' => $quantity_shipped,
            ':item_id' => $item_id,
            ':stock_location_id' => $stock_location_id
        ]);
        // TODO: Add inventory transaction log entry
        return $success;
    } catch (PDOException $e) {
        error_log("Deduct Shipped Stock Error: " . $e->getMessage());
        $_SESSION['error_message'] = "Database error during shipped stock deduction.";
        return false;
    }
}

function update_item_master_data($item_id, $data) {
    $pdo = get_db_connection();
    $sql = "UPDATE items SET
                sku = :sku, name = :name, description = :description, barcode = :barcode,
                unit_of_measure = :unit_of_measure, default_purchase_price = :default_purchase_price,
                default_selling_price = :default_selling_price, weight = :weight, length = :length,
                width = :width, height = :height, image_url = :image_url, is_active = :is_active,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :item_id";
    $stmt = $pdo->prepare($sql);
    try {
        return $stmt->execute([
            ':item_id' => $item_id,
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
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error_message'] = 'Update failed: SKU or Barcode already exists for another item.';
        } else {
            $_SESSION['error_message'] = 'Database error during item update: ' . $e->getMessage();
        }
        error_log("Update Item Master Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetches multiple items by their IDs.
 * @param array $item_ids Array of item IDs.
 * @return array List of items or empty array.
 */
function get_items_by_ids(array $item_ids) {
    if (empty($item_ids)) {
        return [];
    }
    $pdo = get_db_connection();
    // Create placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
    // Fetch item master data - for printing, we might not need aggregated stock here.
    $sql = "SELECT id, sku, name, barcode, unit_of_measure FROM items WHERE id IN ({$placeholders}) ORDER BY name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($item_ids);
    return $stmt->fetchAll();
}

/**
 * Updates specific fields of an item identified by SKU.
 * Only fields present in the $data array will be updated.
 * @param string $sku_key The SKU of the item to update.
 * @param array $data Associative array of item data to update (column_name => value).
 * @return bool True on success, false on failure.
 */
function update_item_selective($sku_key, $data) {
    if (empty($data)) {
        $_SESSION['error_message'] = "No data provided for update.";
        return false; // Nothing to update
    }
    $pdo = get_db_connection();

    // Fetch item_id from SKU first
    $stmt_find = $pdo->prepare("SELECT id FROM items WHERE sku = :sku");
    $stmt_find->execute([':sku' => $sku_key]);
    $item_row = $stmt_find->fetch();

    if (!$item_row) {
        $_SESSION['error_message'] = "Item with SKU '{$sku_key}' not found.";
        return false;
    }
    $item_id = $item_row['id'];

    // Whitelist of allowed fields to update in the 'items' table
    $allowed_fields = [
        'name', 'description', 'barcode', 'unit_of_measure',
        'default_purchase_price', 'default_selling_price',
        'weight', 'length', 'width', 'height', 'image_url', 'is_active',
        'sku' // Allow SKU update
    ];

    $update_fields_sql = [];
    $params = [':item_id' => $item_id];

    foreach ($data as $key => $value) {
        if (in_array($key, $allowed_fields)) {
            // Handle boolean conversion for 'is_active'
            if ($key === 'is_active') {
                $actual_value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($actual_value === null) {
                    error_log("Skipping invalid boolean for is_active: {$value} for SKU {$sku_key}");
                    continue;
                }
            } elseif (strpos($key, 'price') !== false || in_array($key, ['weight', 'length', 'width', 'height'])) {
                // For numeric fields, allow empty string to set to NULL, otherwise cast to float.
                $actual_value = ($value === '' || $value === null) ? null : (float)$value;
            } else {
                 $actual_value = ($value === '' || $value === null) ? null : $value;
            }

            if ($key === 'sku' && $actual_value === $sku_key) {
                continue; // Skip updating SKU if it's the same as the lookup key
            }

            $update_fields_sql[] = "`{$key}` = :{$key}";
            $params[":{$key}"] = $actual_value;
        }
    }

    if (empty($update_fields_sql)) {
        $_SESSION['error_message'] = "No valid fields to update for SKU '{$sku_key}'.";
        return false; // No valid fields found in $data
    }

    $sql = "UPDATE items SET " . implode(", ", $update_fields_sql) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :item_id";

    $stmt = $pdo->prepare($sql);
    try {
        $result = $stmt->execute($params);
        if (!$result) {
            $_SESSION['error_message'] = "DB execute failed for SKU '{$sku_key}'.";
            return false;
        }
        return true;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Integrity constraint violation
             if (isset($data['sku']) && strpos(strtolower($e->getMessage()), 'sku') !== false && $data['sku'] !== $sku_key) {
                  $_SESSION['error_message'] = "Update for SKU '{$sku_key}' failed: The new SKU '{$data['sku']}' already exists.";
             } elseif (isset($data['barcode']) && strpos(strtolower($e->getMessage()), 'barcode') !== false) {
                  $_SESSION['error_message'] = "Update for SKU '{$sku_key}' failed: Barcode '{$data['barcode']}' already exists for another item.";
             } else {
                 $_SESSION['error_message'] = "Update for SKU '{$sku_key}' failed: Database integrity error. " . $e->getMessage();
             }
        } else {
            $_SESSION['error_message'] = "Database error during item update for SKU '{$sku_key}': " . $e->getMessage();
        }
        error_log("Update Item Selective Error (SKU: {$sku_key}): " . $e->getMessage() . " Data: " . json_encode($data));
        return false;
    }
}
?>
