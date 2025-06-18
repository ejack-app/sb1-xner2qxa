<?php
require_once __DIR__ . '/database_connection.php';
require_once __DIR__ . '/order_functions.php'; // For update_order_status and getting order details
require_once __DIR__ . '/item_functions.php';    // For deduct_shipped_stock
require_once __DIR__ . '/picking_functions.php'; // For get_completed_picklist_for_order and get_picklist_details

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Define manifest statuses (can be moved to config)
define('MANIFEST_STATUSES', ['OPEN', 'READY_FOR_DISPATCH', 'IN_TRANSIT', 'ARRIVED_HUB', 'COMPLETED', 'CANCELLED']);
define('ORDER_STATUS_PRE_MANIFEST', ['PACKED', 'READY_TO_SHIP', ORDER_STATUS_PICKING_COMPLETE]); // Order statuses eligible for adding to manifest
define('ORDER_STATUS_ON_MANIFEST', 'MANIFESTED'); // Status of order when added to an OPEN manifest
define('ORDER_STATUS_DISPATCHED', 'OUT_FOR_DELIVERY'); // Status of order when manifest is IN_TRANSIT
define('ORDER_STATUS_DELIVERED', 'DELIVERED'); // Status of order when manifest is COMPLETED


function generate_manifest_code() {
    $prefix = 'MAN-';
    $date_part = date('Ymd');
    $random_part = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
    return $prefix . $date_part . '-' . $random_part;
}

function create_manifest($data) {
    $pdo = get_db_connection();
    $sql = "INSERT INTO manifests (manifest_code, manifest_date, status, courier_company_id,
                                  assigned_vehicle_id, assigned_driver_id, departure_warehouse_id,
                                  notes, created_by_user_id)
            VALUES (:manifest_code, :manifest_date, :status, :courier_company_id,
                    :assigned_vehicle_id, :assigned_driver_id, :departure_warehouse_id,
                    :notes, :created_by_user_id)";
    $stmt = $pdo->prepare($sql);
    try {
        $manifest_code = generate_manifest_code();
        $params = [
            ':manifest_code' => $manifest_code,
            ':manifest_date' => $data['manifest_date'],
            ':status' => $data['status'] ?? 'OPEN',
            ':courier_company_id' => empty($data['courier_company_id']) ? null : (int)$data['courier_company_id'],
            ':assigned_vehicle_id' => empty($data['assigned_vehicle_id']) ? null : (int)$data['assigned_vehicle_id'],
            ':assigned_driver_id' => empty($data['assigned_driver_id']) ? null : (int)$data['assigned_driver_id'],
            ':departure_warehouse_id' => empty($data['departure_warehouse_id']) ? null : (int)$data['departure_warehouse_id'],
            ':notes' => empty($data['notes']) ? null : $data['notes'],
            ':created_by_user_id' => $_SESSION['user_id'] ?? null,
        ];
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000 && strpos(strtolower($e->getMessage()), 'manifest_code') !== false) {
            $_SESSION['error_message'] = 'Manifest code generation conflict. Please try again.';
        } else {
            $_SESSION['error_message'] = "Error creating manifest: " . $e->getMessage();
        }
        error_log("Create Manifest Error: " . $e->getMessage() . " Data: " . json_encode($data));
        return false;
    }
}

function add_order_to_manifest($manifest_id, $order_id) {
    $pdo = get_db_connection();

    $stmt_m = $pdo->prepare("SELECT status FROM manifests WHERE id = :manifest_id");
    $stmt_m->execute([':manifest_id' => $manifest_id]);
    $manifest = $stmt_m->fetch();
    if (!$manifest || $manifest['status'] !== 'OPEN') {
        $_SESSION['error_message'] = "Manifest is not OPEN or not found. Cannot add orders.";
        return false;
    }

    $stmt_o = $pdo->prepare("SELECT order_status FROM orders WHERE id = :order_id");
    $stmt_o->execute([':order_id' => $order_id]);
    $order = $stmt_o->fetch();
    if (!$order) {
        $_SESSION['error_message'] = "Order ID {$order_id} not found.";
        return false;
    }
    if (!in_array($order['order_status'], ORDER_STATUS_PRE_MANIFEST)) {
        $_SESSION['error_message'] = "Order ID {$order_id} is in status '{$order['order_status']}' and cannot be added. Expected: " . implode(' or ', ORDER_STATUS_PRE_MANIFEST);
        return false;
    }

    $pdo->beginTransaction();
    try {
        $sql_add = "INSERT INTO manifest_orders (manifest_id, order_id) VALUES (:manifest_id, :order_id)";
        $stmt_add = $pdo->prepare($sql_add);
        $stmt_add->execute([':manifest_id' => $manifest_id, ':order_id' => $order_id]);

        if (!update_order_status($order_id, ORDER_STATUS_ON_MANIFEST, "Added to Manifest ID: {$manifest_id}")) {
            $pdo->rollBack();
            return false;
        }

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) {
            $_SESSION['error_message'] = "Order ID {$order_id} is already on this manifest or another active manifest.";
        } else {
            $_SESSION['error_message'] = "Error adding order to manifest: " . $e->getMessage();
        }
        error_log("Add Order to Manifest Error: " . $e->getMessage());
        return false;
    }
}

function remove_order_from_manifest($manifest_id, $order_id, $revert_to_status = 'PACKED') {
    $pdo = get_db_connection();

    $stmt_m = $pdo->prepare("SELECT status FROM manifests WHERE id = :manifest_id");
    $stmt_m->execute([':manifest_id' => $manifest_id]);
    $manifest = $stmt_m->fetch();
    if (!$manifest || $manifest['status'] !== 'OPEN') {
        $_SESSION['error_message'] = "Manifest is not OPEN. Cannot remove orders.";
        return false;
    }

    $pdo->beginTransaction();
    try {
        $sql_remove = "DELETE FROM manifest_orders WHERE manifest_id = :manifest_id AND order_id = :order_id";
        $stmt_remove = $pdo->prepare($sql_remove);
        $stmt_remove->execute([':manifest_id' => $manifest_id, ':order_id' => $order_id]);
        $removed_count = $stmt_remove->rowCount();

        if ($removed_count > 0) {
            if (!update_order_status($order_id, $revert_to_status, "Removed from Manifest ID: {$manifest_id}")) {
                $pdo->rollBack();
                return false;
            }
        }
        $pdo->commit();
        return true;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error removing order from manifest: " . $e->getMessage();
        error_log("Remove Order From Manifest Error: " . $e->getMessage());
        return false;
    }
}

function get_manifest_details($manifest_id) {
    $pdo = get_db_connection();
    $sql_manifest = "SELECT m.*, cc.name as courier_name, v.vehicle_name, ud.username as driver_name, w.name as warehouse_name
                     FROM manifests m
                     LEFT JOIN courier_companies cc ON m.courier_company_id = cc.id
                     LEFT JOIN vehicles v ON m.assigned_vehicle_id = v.id
                     LEFT JOIN users ud ON m.assigned_driver_id = ud.id
                     LEFT JOIN warehouses w ON m.departure_warehouse_id = w.id
                     WHERE m.id = :manifest_id";
    $stmt_manifest = $pdo->prepare($sql_manifest);
    $stmt_manifest->execute([':manifest_id' => $manifest_id]);
    $manifest_details = $stmt_manifest->fetch();

    if (!$manifest_details) {
        return null;
    }

    $sql_orders = "SELECT o.id as order_id, o.order_number, o.recipient_name, o.order_status, o.total_order_value,
                          (SELECT COUNT(*) FROM shipment_packages sp WHERE sp.order_id = o.id) as package_count
                   FROM manifest_orders mo
                   JOIN orders o ON mo.order_id = o.id
                   WHERE mo.manifest_id = :manifest_id
                   ORDER BY o.order_number ASC";
    $stmt_orders = $pdo->prepare($sql_orders);
    $stmt_orders->execute([':manifest_id' => $manifest_id]);
    $manifest_details['orders_on_manifest'] = $stmt_orders->fetchAll();

    return $manifest_details;
}

function get_all_manifests($filters = [], $sort_by = 'm.manifest_date', $sort_order = 'DESC', $limit = 25, $offset = 0) {
    $pdo = get_db_connection();
    $select_sql = "SELECT m.*, cc.name as courier_name, v.vehicle_name, ud.username as driver_name, w.name as warehouse_name,
                          (SELECT COUNT(*) FROM manifest_orders mo WHERE mo.manifest_id = m.id) as order_count
                   FROM manifests m
                   LEFT JOIN courier_companies cc ON m.courier_company_id = cc.id
                   LEFT JOIN vehicles v ON m.assigned_vehicle_id = v.id
                   LEFT JOIN users ud ON m.assigned_driver_id = ud.id
                   LEFT JOIN warehouses w ON m.departure_warehouse_id = w.id";
    $count_sql = "SELECT COUNT(m.id) FROM manifests m
                  LEFT JOIN courier_companies cc ON m.courier_company_id = cc.id
                  LEFT JOIN vehicles v ON m.assigned_vehicle_id = v.id
                  LEFT JOIN users ud ON m.assigned_driver_id = ud.id
                  LEFT JOIN warehouses w ON m.departure_warehouse_id = w.id";

    $where_clauses = [];
    $params = [];

    if (!empty($filters['manifest_code'])) {
        $where_clauses[] = "m.manifest_code LIKE :manifest_code";
        $params[':manifest_code'] = '%' . $filters['manifest_code'] . '%';
    }
    if (!empty($filters['status'])) {
        $where_clauses[] = "m.status = :status";
        $params[':status'] = $filters['status'];
    }
    if (!empty($filters['manifest_date_from'])) {
        $where_clauses[] = "m.manifest_date >= :date_from";
        $params[':date_from'] = $filters['manifest_date_from'];
    }
    if (!empty($filters['manifest_date_to'])) {
        $where_clauses[] = "m.manifest_date <= :date_to";
        $params[':date_to'] = $filters['manifest_date_to'];
    }
    if (!empty($filters['courier_company_id'])) {
        $where_clauses[] = "m.courier_company_id = :courier_company_id";
        $params[':courier_company_id'] = (int)$filters['courier_company_id'];
    }
    if (!empty($filters['assigned_driver_id'])) {
        $where_clauses[] = "m.assigned_driver_id = :driver_id";
        $params[':driver_id'] = (int)$filters['assigned_driver_id'];
    }
    if (!empty($filters['assigned_vehicle_id'])) {
        $where_clauses[] = "m.assigned_vehicle_id = :vehicle_id";
        $params[':vehicle_id'] = (int)$filters['assigned_vehicle_id'];
    }
    if (!empty($filters['departure_warehouse_id'])) {
        $where_clauses[] = "m.departure_warehouse_id = :warehouse_id";
        $params[':warehouse_id'] = (int)$filters['departure_warehouse_id'];
    }

    if (!empty($where_clauses)) {
       $where_sql_part = " WHERE " . implode(" AND ", $where_clauses);
       $select_sql .= $where_sql_part;
       $count_sql .= $where_sql_part;
    }

    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute($params);
    $total_count = $stmt_count->fetchColumn();

    $allowed_sort_columns = ['m.manifest_date', 'm.manifest_code', 'm.status', 'courier_name', 'driver_name', 'vehicle_name', 'warehouse_name', 'order_count'];
    if (!in_array($sort_by, $allowed_sort_columns)) $sort_by = 'm.manifest_date';

    $actual_sort_column = $sort_by;
    if ($sort_by === 'courier_name') $actual_sort_column = 'cc.name';
    if ($sort_by === 'driver_name') $actual_sort_column = 'ud.username';
    if ($sort_by === 'vehicle_name') $actual_sort_column = 'v.vehicle_name';
    if ($sort_by === 'warehouse_name') $actual_sort_column = 'w.name';

    $sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

    $select_sql .= " ORDER BY {$actual_sort_column} {$sort_order} LIMIT :limit OFFSET :offset";

    $stmt_select = $pdo->prepare($select_sql);
    foreach ($params as $key => $value) { $stmt_select->bindValue($key, $value); }
    $stmt_select->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt_select->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt_select->execute();
    $manifests = $stmt_select->fetchAll();

   return ['manifests' => $manifests, 'total_count' => $total_count];
}

function update_manifest_status($manifest_id, $new_status, $notes_for_order_history = '') {
    $pdo = get_db_connection();
    if (!in_array($new_status, MANIFEST_STATUSES)) {
        $_SESSION['error_message'] = "Invalid manifest status: {$new_status}.";
        return false;
    }

    $current_manifest_stmt = $pdo->prepare("SELECT status FROM manifests WHERE id = :id");
    $current_manifest_stmt->execute([':id' => $manifest_id]);
    $current_manifest = $current_manifest_stmt->fetch();

    if (!$current_manifest) {
        $_SESSION['error_message'] = "Manifest ID {$manifest_id} not found.";
        return false;
    }
    if ($current_manifest['status'] === $new_status) {
        return true; // No change needed
    }

    $pdo->beginTransaction();
    try {
        $sql_update = "UPDATE manifests SET status = :new_status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([':new_status' => $new_status, ':id' => $manifest_id]);

        $stmt_get_orders = $pdo->prepare("SELECT order_id FROM manifest_orders WHERE manifest_id = :manifest_id");
        $stmt_get_orders->execute([':manifest_id' => $manifest_id]);
        $order_ids_on_manifest = $stmt_get_orders->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($order_ids_on_manifest)) {
            if ($new_status === 'IN_TRANSIT') {
                foreach ($order_ids_on_manifest as $order_id) {
                    if (!update_order_status($order_id, ORDER_STATUS_DISPATCHED, $notes_for_order_history ?: "Manifest {$manifest_id} is IN_TRANSIT.")) {
                        $pdo->rollBack(); return false;
                    }
                    $picklist_id = get_completed_picklist_for_order($order_id);
                    if ($picklist_id) {
                        $picklist_details = get_picklist_details($picklist_id);
                        if ($picklist_details && !empty($picklist_details['items'])) {
                            foreach ($picklist_details['items'] as $picked_item) {
                                if (in_array($picked_item['status'], ['PICKED', 'PARTIALLY_PICKED']) && $picked_item['quantity_picked'] > 0 && $picked_item['picked_from_location_id']) {
                                    if (!deduct_shipped_stock($picked_item['item_id'], $picked_item['picked_from_location_id'], $picked_item['quantity_picked'])) {
                                        $pdo->rollBack();
                                        $_SESSION['error_message'] = "Critical Error: Failed to deduct stock for item ID {$picked_item['item_id']} (Order: {$order_id}). Manifest dispatch aborted.";
                                        error_log($_SESSION['error_message']);
                                        return false;
                                    }
                                }
                            }
                        } else { error_log("No picked items found for completed picklist ID {$picklist_id} for order ID {$order_id}. Stock deduction potentially incomplete.");}
                    } else { error_log("CRITICAL WARNING: Order ID {$order_id} on manifest {$manifest_id} has no COMPLETED picklist. Inventory deduction cannot proceed accurately."); }
                }
            } elseif ($new_status === 'COMPLETED') {
                foreach ($order_ids_on_manifest as $order_id) {
                    $order_stmt = $pdo->prepare("SELECT order_status FROM orders WHERE id = :order_id");
                    $order_stmt->execute([':order_id' => $order_id]);
                    $current_order_status = $order_stmt->fetchColumn();
                    if ($current_order_status === ORDER_STATUS_DISPATCHED || $current_order_status === ORDER_STATUS_ON_MANIFEST) {
                         if (!update_order_status($order_id, ORDER_STATUS_DELIVERED, $notes_for_order_history ?: "Delivered via Manifest {$manifest_id}.")) {
                            $pdo->rollBack(); return false;
                        }
                    } else { error_log("Order ID {$order_id} on completed manifest {$manifest_id} was '{$current_order_status}'. Not auto-set to DELIVERED.");}
                }
            } elseif ($new_status === 'CANCELLED') {
                 foreach ($order_ids_on_manifest as $order_id) {
                    $order_stmt = $pdo->prepare("SELECT order_status FROM orders WHERE id = :order_id");
                    $order_stmt->execute([':order_id' => $order_id]);
                    $current_order_status = $order_stmt->fetchColumn();
                    if ($current_order_status === ORDER_STATUS_ON_MANIFEST || $current_order_status === ORDER_STATUS_DISPATCHED ) {
                         if (!update_order_status($order_id, 'PACKED', "Manifest {$manifest_id} cancelled. Order returned to PACKED status.")) {
                            $pdo->rollBack(); return false;
                        }
                        // TODO: Release allocated stock for items on these orders if manifest is cancelled after picking.
                        // This requires finding picklist items and calling release_stock() for each.
                        error_log("Placeholder: Stock allocation release needed for order ID {$order_id} due to manifest cancellation.");
                    }
                }
            }
        }
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error updating manifest status: " . $e->getMessage();
        error_log("Update Manifest Status Error (Manifest ID: {$manifest_id}): " . $e->getMessage());
        return false;
    }
}
?>
