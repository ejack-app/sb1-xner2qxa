<?php
require_once __DIR__ . '/database_connection.php';
require_once __DIR__ . '/customer_functions.php'; // For customer handling

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function generate_order_number() {
    $prefix = 'ORD-';
    $date_part = date('Ymd');
    // Generate a random part or use a sequence from DB for uniqueness
    // For simplicity, using a random number here. Ensure it's unique in practice.
    $random_part = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

    $pdo = get_db_connection();
    $order_number = $prefix . $date_part . '-' . $random_part;

    // Check for uniqueness (highly unlikely to collide with this format, but good practice)
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE order_number = :order_number");
    $stmt->execute([':order_number' => $order_number]);
    if ($stmt->fetch()) {
        // If somehow it collides, try once more or implement a more robust sequence
        $random_part = strtoupper(substr(bin2hex(random_bytes(5)), 0, 7));
        $order_number = $prefix . $date_part . '-' . $random_part;
    }
    return $order_number;
}

function create_order(
    $customer_id, // Can be null if customer details are directly embedded or for guest checkouts
    $recipient_details, // Array: name, phone, email, address_line1, address_line2, city, state, postal_code, country_code
    $order_details,     // Array: payment_method, payment_status, shipping_cost, discount_amount, total_order_value, shipping_method, notes, total_cod_amount
    $order_items_data,  // Array of arrays: item_sku, item_name, quantity, unit_price, weight, dimensions
    $created_by_user_id
) {
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    try {
        $order_number = generate_order_number();

        $sql_order = "INSERT INTO orders (
                            order_number, customer_id,
                            recipient_name, recipient_phone, recipient_email,
                            recipient_address_line1, recipient_address_line2, recipient_city, recipient_state, recipient_postal_code, recipient_country_code,
                            order_status, payment_status, payment_method, total_cod_amount, shipping_cost, discount_amount, total_order_value,
                            shipping_method, notes, created_by_user_id, order_date, created_at, updated_at
                        ) VALUES (
                            :order_number, :customer_id,
                            :recipient_name, :recipient_phone, :recipient_email,
                            :recipient_address_line1, :recipient_address_line2, :recipient_city, :recipient_state, :recipient_postal_code, :recipient_country_code,
                            :order_status, :payment_status, :payment_method, :total_cod_amount, :shipping_cost, :discount_amount, :total_order_value,
                            :shipping_method, :notes, :created_by_user_id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                        )";

        $stmt_order = $pdo->prepare($sql_order);
        $stmt_order->execute([
            ':order_number' => $order_number,
            ':customer_id' => $customer_id, // This is the ID of the sender/client from `customers` table
            ':recipient_name' => $recipient_details['name'],
            ':recipient_phone' => $recipient_details['phone'],
            ':recipient_email' => $recipient_details['email'] ?? null,
            ':recipient_address_line1' => $recipient_details['address_line1'],
            ':recipient_address_line2' => $recipient_details['address_line2'] ?? null,
            ':recipient_city' => $recipient_details['city'],
            ':recipient_state' => $recipient_details['state'] ?? null,
            ':recipient_postal_code' => $recipient_details['postal_code'],
            ':recipient_country_code' => $recipient_details['country_code'],
            ':order_status' => $order_details['order_status'] ?? 'PENDING',
            ':payment_status' => $order_details['payment_status'] ?? 'UNPAID',
            ':payment_method' => $order_details['payment_method'] ?? null,
            ':total_cod_amount' => $order_details['total_cod_amount'] ?? 0.00,
            ':shipping_cost' => $order_details['shipping_cost'] ?? 0.00,
            ':discount_amount' => $order_details['discount_amount'] ?? 0.00,
            ':total_order_value' => $order_details['total_order_value'], // Should be calculated sum of items + shipping - discount
            ':shipping_method' => $order_details['shipping_method'] ?? null,
            ':notes' => $order_details['notes'] ?? null,
            ':created_by_user_id' => $created_by_user_id
        ]);

        $order_id = $pdo->lastInsertId();

        // Insert order items
        $sql_item = "INSERT INTO order_items (order_id, item_sku, item_name, quantity, unit_price, total_price, weight, total_weight, dimensions, created_at, updated_at)
                     VALUES (:order_id, :item_sku, :item_name, :quantity, :unit_price, :total_price, :weight, :total_weight, :dimensions, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $stmt_item = $pdo->prepare($sql_item);

        foreach ($order_items_data as $item) {
            $total_price = (float)$item['quantity'] * (float)$item['unit_price'];
            $total_weight = isset($item['weight']) ? (float)$item['quantity'] * (float)$item['weight'] : null;
            $stmt_item->execute([
                ':order_id' => $order_id,
                ':item_sku' => $item['sku'],
                ':item_name' => $item['name'],
                ':quantity' => (int)$item['quantity'],
                ':unit_price' => (float)$item['unit_price'],
                ':total_price' => $total_price,
                ':weight' => isset($item['weight']) && $item['weight'] !== '' ? (float)$item['weight'] : null,
                ':total_weight' => $total_weight,
                ':dimensions' => $item['dimensions'] ?? null,
            ]);
        }

        // Create initial order status history entry
        $sql_status_history = "INSERT INTO order_status_history (order_id, status, notes, changed_by_user_id, changed_at)
                               VALUES (:order_id, :status, :notes, :changed_by_user_id, CURRENT_TIMESTAMP)";
        $stmt_status_history = $pdo->prepare($sql_status_history);
        $stmt_status_history->execute([
           ':order_id' => $order_id,
           ':status' => $order_details['order_status'] ?? 'PENDING',
           ':notes' => 'Order created manually by admin.',
           ':changed_by_user_id' => $created_by_user_id
        ]);


        $pdo->commit();
        return $order_number; // Return the order number on success
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Create Order Error: " . $e->getMessage());
        // Store the error message in session to display it on the form page
        $_SESSION['error_message'] = "Could not create order: " . $e->getMessage();
        return false;
    }
}


/**
 * Retrieves a paginated and filterable list of orders.
 *
 * @param array $filters Associative array of filters.
 *                       Supported filters: 'order_number', 'customer_name', 'recipient_name',
 *                                        'order_status', 'date_from', 'date_to'
 * @param string $sort_by Column to sort by.
 * @param string $sort_order Sort direction (ASC or DESC).
 * @param int $limit Number of records per page.
 * @param int $offset Offset for pagination.
 * @return array An array containing 'orders' and 'total_count'.
 */
function get_all_orders($filters = [], $sort_by = 'o.order_date', $sort_order = 'DESC', $limit = 25, $offset = 0) {
    $pdo = get_db_connection();
    $select_sql = "SELECT
                       o.id as order_id, o.order_number, o.order_date, o.order_status,
                       o.recipient_name, o.recipient_phone,
                       o.total_order_value, o.payment_status,
                       c.name as customer_name, c.email as customer_email, -- Sender/Customer
                       u.username as created_by_username,
                       cc.name as courier_company_name
                   FROM orders o
                   LEFT JOIN customers c ON o.customer_id = c.id
                   LEFT JOIN users u ON o.created_by_user_id = u.id
                   LEFT JOIN courier_companies cc ON o.assigned_courier_id = cc.id";

    $count_sql = "SELECT COUNT(o.id)
                  FROM orders o
                  LEFT JOIN customers c ON o.customer_id = c.id
                  LEFT JOIN users u ON o.created_by_user_id = u.id";

    $where_clauses = [];
    $params = [];

    if (!empty($filters['order_number'])) {
        $where_clauses[] = "o.order_number LIKE :order_number";
        $params[':order_number'] = '%' . $filters['order_number'] . '%';
    }
    if (!empty($filters['customer_name'])) { // Searches sender/customer name
        $where_clauses[] = "c.name LIKE :customer_name";
        $params[':customer_name'] = '%' . $filters['customer_name'] . '%';
    }
    if (!empty($filters['recipient_name'])) {
        $where_clauses[] = "o.recipient_name LIKE :recipient_name";
        $params[':recipient_name'] = '%' . $filters['recipient_name'] . '%';
    }
    if (!empty($filters['order_status'])) {
        $where_clauses[] = "o.order_status = :order_status";
        $params[':order_status'] = $filters['order_status'];
    }
    if (!empty($filters['date_from'])) {
        $where_clauses[] = "o.order_date >= :date_from";
        $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
    }
    if (!empty($filters['date_to'])) {
        $where_clauses[] = "o.order_date <= :date_to";
        $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
    }

    if (!empty($where_clauses)) {
        $where_sql = " WHERE " . implode(" AND ", $where_clauses);
        $select_sql .= $where_sql;
        $count_sql .= $where_sql;
    }

    // Get total count for pagination
    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute($params);
    $total_count = $stmt_count->fetchColumn();

    // Add sorting and pagination to the select query
    // Validate sort_by to prevent SQL injection if it's dynamic from user input
    $allowed_sort_columns = ['o.order_date', 'o.order_number', 'c.name', 'o.recipient_name', 'o.order_status', 'o.total_order_value'];
    if (!in_array($sort_by, $allowed_sort_columns)) {
        $sort_by = 'o.order_date'; // Default sort
    }
    $sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC'; // Ensure valid sort order

    $select_sql .= " ORDER BY {$sort_by} {$sort_order}";
    $select_sql .= " LIMIT :limit OFFSET :offset";

    $stmt_select = $pdo->prepare($select_sql);
    // Bind common params
    foreach ($params as $key => $value) {
        $stmt_select->bindValue($key, $value);
    }
    // Bind limit and offset separately as they are integers
    $stmt_select->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt_select->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    $stmt_select->execute();
    $orders = $stmt_select->fetchAll();

    return ['orders' => $orders, 'total_count' => $total_count];
}
?>
