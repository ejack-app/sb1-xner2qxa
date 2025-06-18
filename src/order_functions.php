<?php
require_once __DIR__ . '/database_connection.php';
require_once __DIR__ . '/customer_functions.php'; // For customer handling

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function generate_order_number() {
    $prefix = 'ORD-';
    $date_part = date('Ymd');
    $random_part = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    $pdo = get_db_connection();
    $order_number = $prefix . $date_part . '-' . $random_part;
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE order_number = :order_number");
    $stmt->execute([':order_number' => $order_number]);
    if ($stmt->fetch()) {
        $random_part = strtoupper(substr(bin2hex(random_bytes(5)), 0, 7));
        $order_number = $prefix . $date_part . '-' . $random_part;
    }
    return $order_number;
}

function create_order(
    $customer_id, $recipient_details, $order_details,
    $order_items_data, $created_by_user_id
) {
    $pdo = get_db_connection();
    $pdo->beginTransaction();
    try {
        $order_number = generate_order_number();
        $sql_order = "INSERT INTO orders (
                            order_number, customer_id, recipient_name, recipient_phone, recipient_email,
                            recipient_address_line1, recipient_address_line2, recipient_city, recipient_state,
                            recipient_postal_code, recipient_country_code, order_status, payment_status,
                            payment_method, total_cod_amount, shipping_cost, discount_amount, total_order_value,
                            shipping_method, notes, created_by_user_id, order_date, created_at, updated_at
                        ) VALUES (
                            :order_number, :customer_id, :recipient_name, :recipient_phone, :recipient_email,
                            :recipient_address_line1, :recipient_address_line2, :recipient_city, :recipient_state,
                            :recipient_postal_code, :recipient_country_code, :order_status, :payment_status,
                            :payment_method, :total_cod_amount, :shipping_cost, :discount_amount, :total_order_value,
                            :shipping_method, :notes, :created_by_user_id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                        )";
        $stmt_order = $pdo->prepare($sql_order);
        $stmt_order->execute([
            ':order_number' => $order_number, ':customer_id' => $customer_id,
            ':recipient_name' => $recipient_details['name'], ':recipient_phone' => $recipient_details['phone'],
            ':recipient_email' => $recipient_details['email'] ?? null,
            ':recipient_address_line1' => $recipient_details['address_line1'],
            ':recipient_address_line2' => $recipient_details['address_line2'] ?? null,
            ':recipient_city' => $recipient_details['city'], ':recipient_state' => $recipient_details['state'] ?? null,
            ':recipient_postal_code' => $recipient_details['postal_code'],
            ':recipient_country_code' => $recipient_details['country_code'],
            ':order_status' => $order_details['order_status'] ?? 'PENDING',
            ':payment_status' => $order_details['payment_status'] ?? 'UNPAID',
            ':payment_method' => $order_details['payment_method'] ?? null,
            ':total_cod_amount' => $order_details['total_cod_amount'] ?? 0.00,
            ':shipping_cost' => $order_details['shipping_cost'] ?? 0.00,
            ':discount_amount' => $order_details['discount_amount'] ?? 0.00,
            ':total_order_value' => $order_details['total_order_value'],
            ':shipping_method' => $order_details['shipping_method'] ?? null,
            ':notes' => $order_details['notes'] ?? null, ':created_by_user_id' => $created_by_user_id
        ]);
        $order_id = $pdo->lastInsertId();

        $sql_item = "INSERT INTO order_items (order_id, item_sku, item_name, quantity, unit_price, total_price, weight, total_weight, dimensions, created_at, updated_at)
                     VALUES (:order_id, :item_sku, :item_name, :quantity, :unit_price, :total_price, :weight, :total_weight, :dimensions, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $stmt_item = $pdo->prepare($sql_item);
        foreach ($order_items_data as $item) {
            $total_price = (float)$item['quantity'] * (float)$item['unit_price'];
            $total_weight = isset($item['weight']) ? (float)$item['quantity'] * (float)$item['weight'] : null;
            $stmt_item->execute([
                ':order_id' => $order_id, ':item_sku' => $item['sku'], ':item_name' => $item['name'],
                ':quantity' => (int)$item['quantity'], ':unit_price' => (float)$item['unit_price'],
                ':total_price' => $total_price,
                ':weight' => isset($item['weight']) && $item['weight'] !== '' ? (float)$item['weight'] : null,
                ':total_weight' => $total_weight, ':dimensions' => $item['dimensions'] ?? null,
            ]);
        }

        $sql_status_history = "INSERT INTO order_status_history (order_id, status, notes, changed_by_user_id, changed_at)
                               VALUES (:order_id, :status, :notes, :changed_by_user_id, CURRENT_TIMESTAMP)";
        $stmt_status_history = $pdo->prepare($sql_status_history);
        $stmt_status_history->execute([
           ':order_id' => $order_id, ':status' => $order_details['order_status'] ?? 'PENDING',
           ':notes' => 'Order created manually by admin.', ':changed_by_user_id' => $created_by_user_id
        ]);
        $pdo->commit();
        return $order_number;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Create Order Error: " . $e->getMessage());
        $_SESSION['error_message'] = "Could not create order: " . $e->getMessage();
        return false;
    }
}

function get_all_orders($filters = [], $sort_by = 'o.order_date', $sort_order = 'DESC', $limit = 25, $offset = 0) {
    $pdo = get_db_connection();
    $select_sql = "SELECT o.id as order_id, o.order_number, o.order_date, o.order_status,
                       o.recipient_name, o.recipient_phone, o.total_order_value, o.payment_status,
                       c.name as customer_name, c.email as customer_email, u.username as created_by_username,
                       cc.name as courier_company_name
                   FROM orders o
                   LEFT JOIN customers c ON o.customer_id = c.id
                   LEFT JOIN users u ON o.created_by_user_id = u.id
                   LEFT JOIN courier_companies cc ON o.assigned_courier_id = cc.id";
    $count_sql = "SELECT COUNT(o.id) FROM orders o LEFT JOIN customers c ON o.customer_id = c.id LEFT JOIN users u ON o.created_by_user_id = u.id";
    $where_clauses = []; $params = [];
    // ... (filtering logic as before) ...
    if (!empty($filters['order_number'])) { $where_clauses[] = "o.order_number LIKE :order_number"; $params[':order_number'] = '%' . $filters['order_number'] . '%'; }
    if (!empty($filters['customer_name'])) { $where_clauses[] = "c.name LIKE :customer_name"; $params[':customer_name'] = '%' . $filters['customer_name'] . '%'; }
    if (!empty($filters['recipient_name'])) { $where_clauses[] = "o.recipient_name LIKE :recipient_name"; $params[':recipient_name'] = '%' . $filters['recipient_name'] . '%'; }
    if (!empty($filters['order_status'])) { $where_clauses[] = "o.order_status = :order_status"; $params[':order_status'] = $filters['order_status']; }
    if (!empty($filters['date_from'])) { $where_clauses[] = "o.order_date >= :date_from"; $params[':date_from'] = $filters['date_from'] . ' 00:00:00'; }
    if (!empty($filters['date_to'])) { $where_clauses[] = "o.order_date <= :date_to"; $params[':date_to'] = $filters['date_to'] . ' 23:59:59'; }

    if (!empty($where_clauses)) {
        $where_sql = " WHERE " . implode(" AND ", $where_clauses);
        $select_sql .= $where_sql; $count_sql .= $where_sql;
    }
    $stmt_count = $pdo->prepare($count_sql); $stmt_count->execute($params);
    $total_count = $stmt_count->fetchColumn();
    $allowed_sort_columns = ['o.order_date', 'o.order_number', 'c.name', 'o.recipient_name', 'o.order_status', 'o.total_order_value'];
    if (!in_array($sort_by, $allowed_sort_columns)) $sort_by = 'o.order_date';
    $sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';
    $select_sql .= " ORDER BY {$sort_by} {$sort_order} LIMIT :limit OFFSET :offset";
    $stmt_select = $pdo->prepare($select_sql);
    foreach ($params as $key => $value) $stmt_select->bindValue($key, $value);
    $stmt_select->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt_select->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt_select->execute(); $orders = $stmt_select->fetchAll();
    return ['orders' => $orders, 'total_count' => $total_count];
}

function get_order_items_with_packing_status($order_id) {
    $pdo = get_db_connection();
    $sql = "SELECT oi.id as order_item_id, oi.item_sku, oi.item_name, oi.quantity as quantity_ordered,
                   i.id as item_id, COALESCE(SUM(spi.quantity_packed), 0) as quantity_packed_total
            FROM order_items oi
            JOIN items i ON oi.item_sku = i.sku
            LEFT JOIN shipment_package_items spi ON oi.id = spi.order_item_id
            WHERE oi.order_id = :order_id
            GROUP BY oi.id, oi.item_sku, oi.item_name, oi.quantity, i.id
            ORDER BY oi.id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':order_id' => $order_id]);
    $items = $stmt->fetchAll();
    foreach ($items as &$item) {
        $item['quantity_remaining_to_pack'] = $item['quantity_ordered'] - $item['quantity_packed_total'];
    }
    return $items;
}

function update_order_status($order_id, $new_status, $notes = '') {
    $pdo = get_db_connection();
    $pdo->beginTransaction();
    try {
        $sql_update = "UPDATE orders SET order_status = :new_status, updated_at = CURRENT_TIMESTAMP WHERE id = :order_id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([':new_status' => $new_status, ':order_id' => $order_id]);
        $sql_history = "INSERT INTO order_status_history (order_id, status, notes, changed_by_user_id)
                        VALUES (:order_id, :status, :notes, :changed_by_user_id)";
        $stmt_history = $pdo->prepare($sql_history);
        $stmt_history->execute([
            ':order_id' => $order_id, ':status' => $new_status,
            ':notes' => $notes ?: "Order status changed to {$new_status}.",
            ':changed_by_user_id' => $_SESSION['user_id'] ?? null
        ]);
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error updating order status: " . $e->getMessage();
        error_log("Update Order Status Error (Order ID: {$order_id}): " . $e->getMessage());
        return false;
    }
}

/**
 * Fetches orders that are assignable to a new manifest.
 * Orders must be in eligible statuses and not on another active manifest or active picklist.
 * @return array List of assignable orders.
 */
function get_assignable_orders_for_manifest() {
    $pdo = get_db_connection();
    // Eligible statuses for manifesting (ensure ORDER_STATUS_PICKING_COMPLETE is defined, e.g. 'READY_FOR_PACKAGING')
    $eligible_statuses_for_manifest = array_merge(ORDER_STATUS_PRE_MANIFEST, [ORDER_STATUS_PICKING_COMPLETE]);
    $status_placeholders = implode(',', array_fill(0, count($eligible_statuses_for_manifest), '?'));

    $active_manifest_statuses = ['OPEN', 'READY_FOR_DISPATCH', 'IN_TRANSIT'];
    $active_manifest_status_placeholders = implode(',', array_fill(0, count($active_manifest_statuses), '?'));

    $active_picklist_statuses = ['PENDING', 'ASSIGNED', 'IN_PROGRESS', 'PARTIALLY_COMPLETED'];
    $active_picklist_status_placeholders = implode(',', array_fill(0, count($active_picklist_statuses), '?'));

    $sql = "SELECT o.id, o.order_number, o.recipient_name, o.order_date, o.order_status,
                   (SELECT COUNT(*) FROM shipment_packages sp WHERE sp.order_id = o.id) as package_count
            FROM orders o
            WHERE o.order_status IN ({$status_placeholders})
            AND NOT EXISTS (
                SELECT 1 FROM manifest_orders mo
                JOIN manifests m ON mo.manifest_id = m.id
                WHERE mo.order_id = o.id AND m.status IN ({$active_manifest_status_placeholders})
            )
            AND NOT EXISTS (
                SELECT 1 FROM picklists p
                WHERE p.order_id = o.id AND p.status IN ({$active_picklist_status_placeholders})
            )
            ORDER BY o.order_date ASC LIMIT 200";

    $params = array_merge($eligible_statuses_for_manifest, $active_manifest_statuses, $active_picklist_statuses);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Fetches orders that are ready for picking.
 * Orders are in statuses like 'PROCESSING' or 'CONFIRMED' and do not have an active picklist.
 * @param array $filters Optional filters (e.g., warehouse_id if orders are assigned to warehouses).
 * @param int $limit
 * @param int $offset
 * @return array List of orders ready for picking.
 */
function get_orders_ready_for_picking($filters = [], $limit = 50, $offset = 0) {
    $pdo = get_db_connection();

    // Define order statuses eligible for picklist creation (e.g. from picking_functions.php or a shared constants file)
    // For now, directly using what was in picking_functions.php for ORDER_STATUS_READY_FOR_PICKING
    $ready_for_picking_statuses = ['PROCESSING', 'CONFIRMED', 'AWAITING_FULFILLMENT', 'PACKED'];
    $status_placeholders = implode(',', array_fill(0, count($ready_for_picking_statuses), '?'));

    $active_picklist_statuses = ['PENDING', 'ASSIGNED', 'IN_PROGRESS', 'PARTIALLY_COMPLETED'];
    $active_picklist_status_placeholders = implode(',', array_fill(0, count($active_picklist_statuses), '?'));

    $sql = "SELECT o.id, o.order_number, o.recipient_name, o.order_date, o.order_status,
                   (SELECT w.name FROM warehouses w JOIN orders ow ON ow.id = o.id LEFT JOIN picklists p_wh ON p_wh.order_id = o.id WHERE w.id = p_wh.warehouse_id LIMIT 1) as assigned_warehouse_name -- Placeholder
            FROM orders o
            WHERE o.order_status IN ({$status_placeholders})
            AND NOT EXISTS (
                SELECT 1 FROM picklists p
                WHERE p.order_id = o.id AND p.status IN ({$active_picklist_status_placeholders})
            )";

    $params = array_merge($ready_for_picking_statuses, $active_picklist_statuses);

    // Example filter: by order number
    if (!empty($filters['order_number_search'])) {
        $sql .= " AND o.order_number LIKE ? ";
        $params[] = '%' . $filters['order_number_search'] . '%';
    }
    // Example filter: by warehouse_id (if orders were directly associated with a warehouse, or items in order)
    // if (!empty($filters['warehouse_id'])) {
    //    $sql .= " AND o.some_warehouse_id_column = ? "; // Requires schema change on 'orders' or complex join
    //    $params[] = (int)$filters['warehouse_id'];
    // }


    $sql .= " ORDER BY o.order_date ASC ";

    // For pagination (COUNT query would be more complex if filters join other tables)
    // For simplicity, not implementing full pagination count here for this helper.
    // $count_sql = "SELECT COUNT(o.id) FROM orders o WHERE o.order_status IN ({$status_placeholders}) AND NOT EXISTS (...)";

    $sql .= "LIMIT ?, ?";
    $params[] = (int)$offset;
    $params[] = (int)$limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
?>
