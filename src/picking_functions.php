<?php
require_once __DIR__ . '/database_connection.php';
require_once __DIR__ . '/order_functions.php';     // For update_order_status, get_order_items_with_packing_status
require_once __DIR__ . '/item_functions.php';       // For allocate_stock
require_once __DIR__ . '/stock_location_functions.php'; // For get_suggested_pick_location_for_item

if (session_status() == PHP_SESSION_NONE) { session_start(); }

define('PICKLIST_STATUSES', ['PENDING', 'ASSIGNED', 'IN_PROGRESS', 'PARTIALLY_COMPLETED', 'COMPLETED', 'CANCELLED']);
define('PICKLIST_ITEM_STATUSES', ['PENDING', 'PICKED', 'PARTIALLY_PICKED', 'NOT_FOUND', 'SKIPPED', 'DAMAGE']);
define('ORDER_STATUS_READY_FOR_PICKING', ['PROCESSING', 'CONFIRMED', 'AWAITING_FULFILLMENT', 'PACKED']);
define('ORDER_STATUS_PICKING_IN_PROGRESS', 'PICKING_IN_PROGRESS');
define('ORDER_STATUS_PICKING_COMPLETE', 'READY_FOR_PACKAGING');


function generate_picklist_code() {
    $prefix = 'PICK-';
    $date_part = date('Ymd');
    $random_part = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
    return $prefix . $date_part . '-' . $random_part;
}

function create_picklist_for_order($order_id, $warehouse_id, $assigned_picker_id = null, $created_by_user_id = null) {
    $pdo = get_db_connection();

    $stmt_o = $pdo->prepare("SELECT order_status FROM orders WHERE id = :order_id");
    $stmt_o->execute([':order_id' => $order_id]);
    $order = $stmt_o->fetch();
    if (!$order) {
        $_SESSION['error_message'] = "Order ID {$order_id} not found."; return false;
    }

    $stmt_check = $pdo->prepare("SELECT id FROM picklists WHERE order_id = :order_id AND status NOT IN ('COMPLETED', 'CANCELLED')");
    $stmt_check->execute([':order_id' => $order_id]);
    if ($stmt_check->fetch()) {
        $_SESSION['error_message'] = "An active picklist already exists for Order ID {$order_id}."; return false;
    }

    $pdo->beginTransaction();
    try {
        $picklist_code = generate_picklist_code();
        $sql_header = "INSERT INTO picklists (picklist_code, order_id, warehouse_id, status, assigned_picker_id, created_by_user_id)
                       VALUES (:picklist_code, :order_id, :warehouse_id, :status, :assigned_picker_id, :created_by_user_id)";
        $stmt_header = $pdo->prepare($sql_header);
        $stmt_header->execute([
            ':picklist_code' => $picklist_code,
            ':order_id' => $order_id,
            ':warehouse_id' => $warehouse_id,
            ':status' => $assigned_picker_id ? 'ASSIGNED' : 'PENDING',
            ':assigned_picker_id' => $assigned_picker_id,
            ':created_by_user_id' => $created_by_user_id ?? $_SESSION['user_id'] ?? null
        ]);
        $picklist_id = $pdo->lastInsertId();

        $order_items_for_picklist = get_order_items_with_packing_status($order_id);

        $sql_item = "INSERT INTO picklist_items (picklist_id, order_item_id, item_id, quantity_to_pick, suggested_location_id, suggested_location_code, status)
                     VALUES (:picklist_id, :order_item_id, :item_id, :quantity_to_pick, :suggested_location_id, :suggested_location_code, 'PENDING')";
        $stmt_item_insert = $pdo->prepare($sql_item);
        $items_added_to_picklist = 0;
        foreach ($order_items_for_picklist as $oi) {
            if ($oi['quantity_remaining_to_pack'] > 0) {
                $suggested_location = get_suggested_pick_location_for_item($oi['item_id'], $oi['quantity_remaining_to_pack'], $warehouse_id);

                $stmt_item_insert->execute([
                    ':picklist_id' => $picklist_id,
                    ':order_item_id' => $oi['order_item_id'],
                    ':item_id' => $oi['item_id'],
                    ':quantity_to_pick' => $oi['quantity_remaining_to_pack'],
                    ':suggested_location_id' => $suggested_location['location_id'] ?? null,
                    ':suggested_location_code' => $suggested_location['location_code'] ?? null,
                ]);
                $items_added_to_picklist++;
            }
        }

        if ($items_added_to_picklist == 0 && count($order_items_for_picklist) > 0) {
            $_SESSION['error_message'] = "No items require picking for Order ID {$order_id}. All items may already be packed or have zero quantity.";
            $pdo->rollBack(); return false;
        }

        if (!update_order_status($order_id, ORDER_STATUS_PICKING_IN_PROGRESS, "Picklist {$picklist_code} created.")) {
            $pdo->rollBack(); return false;
        }

        $pdo->commit();
        return $picklist_id;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error creating picklist: " . $e->getMessage();
        error_log("Create Picklist Error: " . $e->getMessage());
        return false;
    }
}

function get_picklist_details($picklist_id) {
    $pdo = get_db_connection();
    $sql_header = "SELECT p.*, o.order_number, w.name as warehouse_name, u.username as picker_username, cu.username as creator_username
                   FROM picklists p
                   JOIN orders o ON p.order_id = o.id
                   JOIN warehouses w ON p.warehouse_id = w.id
                   LEFT JOIN users u ON p.assigned_picker_id = u.id
                   LEFT JOIN users cu ON p.created_by_user_id = cu.id
                   WHERE p.id = :picklist_id";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([':picklist_id' => $picklist_id]);
    $details = $stmt_header->fetch();

    if (!$details) return null;

    $sql_items = "SELECT pli.*, i.sku as item_sku, i.name as item_name,
                        COALESCE(sl_sugg.location_code, pli.suggested_location_code) as actual_suggested_code,
                        sl_picked.location_code as actual_picked_code
                 FROM picklist_items pli
                 JOIN items i ON pli.item_id = i.id
                 LEFT JOIN stock_locations sl_sugg ON pli.suggested_location_id = sl_sugg.id
                 LEFT JOIN stock_locations sl_picked ON pli.picked_from_location_id = sl_picked.id
                 WHERE pli.picklist_id = :picklist_id
                 ORDER BY i.name ASC";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([':picklist_id' => $picklist_id]);
    $details['items'] = $stmt_items->fetchAll();
    return $details;
}

function get_picklists_by_criteria($criteria = [], $limit=50, $offset=0) {
    $pdo = get_db_connection();
    $sql = "SELECT p.*, o.order_number, w.name as warehouse_name, u.username as picker_username
            FROM picklists p
            JOIN orders o ON p.order_id = o.id
            JOIN warehouses w ON p.warehouse_id = w.id
            LEFT JOIN users u ON p.assigned_picker_id = u.id
            WHERE 1=1 ";
    $params = [];
    if(!empty($criteria['status'])){
        $sql .= " AND p.status = :status ";
        $params[':status'] = $criteria['status'];
    }
    if(!empty($criteria['warehouse_id'])){
        $sql .= " AND p.warehouse_id = :warehouse_id ";
        $params[':warehouse_id'] = (int)$criteria['warehouse_id'];
    }
    if(!empty($criteria['assigned_picker_id'])){
        $sql .= " AND p.assigned_picker_id = :assigned_picker_id ";
        $params[':assigned_picker_id'] = (int)$criteria['assigned_picker_id'];
    }

    $sql_count = "SELECT COUNT(p.id) FROM picklists p LEFT JOIN users u ON p.assigned_picker_id = u.id WHERE 1=1 ";
     if(!empty($criteria['status'])){ $sql_count .= " AND p.status = :status "; }
     if(!empty($criteria['warehouse_id'])){ $sql_count .= " AND p.warehouse_id = :warehouse_id "; }
     if(!empty($criteria['assigned_picker_id'])){ $sql_count .= " AND p.assigned_picker_id = :assigned_picker_id "; }
    // For simplicity, count query reuses params from select. Ensure they match.
    // $stmt_count = $pdo->prepare($sql_count);
    // $stmt_count->execute($params);
    // $total_count = $stmt_count->fetchColumn();


    $sql .= " ORDER BY p.created_at DESC LIMIT :offset, :limit";

    $stmt = $pdo->prepare($sql);
    // Bind common params
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    // return $stmt->fetchAll(); // This was original for get_picklists_by_criteria
    // For now, returning just the list. Full pagination would need total count.
    return ['picklists' => $stmt->fetchAll(), 'total_count' => 0]; // Placeholder for total_count
}

function assign_picklist_to_picker($picklist_id, $picker_id) {
    $pdo = get_db_connection();
    $sql = "UPDATE picklists SET assigned_picker_id = :picker_id, status = 'ASSIGNED', updated_at = CURRENT_TIMESTAMP
            WHERE id = :picklist_id AND status = 'PENDING'";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([':picker_id' => $picker_id, ':picklist_id' => $picklist_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error assigning picklist: " . $e->getMessage();
        return false;
    }
}

function confirm_item_pick($picklist_item_id, $picked_quantity, $picked_from_location_id, $picker_notes = null, $item_status = 'PICKED') {
    $pdo = get_db_connection();

    $stmt_pli = $pdo->prepare("SELECT pli.item_id, pli.quantity_to_pick, p.warehouse_id, pli.picklist_id
                               FROM picklist_items pli
                               JOIN picklists p ON pli.picklist_id = p.id
                               WHERE pli.id = :id");
    $stmt_pli->execute([':id' => $picklist_item_id]);
    $pli_details = $stmt_pli->fetch();

    if (!\$pli_details) {
        $_SESSION['error_message'] = "Picklist item ID {$picklist_item_id} not found."; return false;
    }
    $picked_quantity = (int)$picked_quantity;
    if ($picked_quantity > $pli_details['quantity_to_pick']) {
        $_SESSION['error_message'] = "Picked quantity ({$picked_quantity}) cannot exceed quantity to pick ({$pli_details['quantity_to_pick']})."; return false;
    }
    if ($picked_quantity < 0) {
         $_SESSION['error_message'] = "Picked quantity cannot be negative."; return false;
    }
    if (empty($picked_from_location_id) && $picked_quantity > 0 && $item_status === 'PICKED') {
        $_SESSION['error_message'] = "Picked From Location ID is required if quantity picked is greater than zero and status is 'PICKED'."; return false;
    }


    $pdo->beginTransaction();
    try {
        $sql_update = "UPDATE picklist_items SET
                           quantity_picked = :quantity_picked,
                           picked_from_location_id = :picked_from_location_id,
                           picker_notes = :picker_notes,
                           status = :status,
                           picked_at = CURRENT_TIMESTAMP
                       WHERE id = :id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([
            ':quantity_picked' => $picked_quantity,
            ':picked_from_location_id' => empty($picked_from_location_id) ? null : (int)$picked_from_location_id,
            ':picker_notes' => empty($picker_notes) ? null : $picker_notes,
            ':status' => $item_status,
            ':id' => $picklist_item_id
        ]);

        if ($picked_quantity > 0 && $item_status === 'PICKED') {
            if (!allocate_stock($pli_details['item_id'], (int)$picked_from_location_id, $picked_quantity)) {
                $pdo->rollBack();
                return false;
            }
        }
        // Note: If status is NOT_FOUND or DAMAGED, stock is NOT allocated here.
        // This allocated stock will be "consumed" (moved from on_hand and allocated) when the order is dispatched.

        update_picklist_status_based_on_items($pli_details['picklist_id']);

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error confirming item pick: " . $e->getMessage();
        error_log("Confirm Item Pick Error: " . $e->getMessage());
        return false;
    }
}

function update_picklist_status_based_on_items($picklist_id) {
     $pdo = get_db_connection();
     $sql_check = "SELECT COUNT(*) as total_items,
                          SUM(CASE WHEN status IN ('PICKED', 'NOT_FOUND', 'DAMAGE', 'SKIPPED') THEN 1 ELSE 0 END) as actioned_items,
                          SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_items
                   FROM picklist_items WHERE picklist_id = :picklist_id";
     $stmt_check = $pdo->prepare($sql_check);
     $stmt_check->execute([':picklist_id' => $picklist_id]);
     $counts = $stmt_check->fetch();

     $new_status = null;
     if ($counts && $counts['total_items'] > 0) {
         if ($counts['pending_items'] == $counts['total_items']) {
             // Remains PENDING or ASSIGNED
         } elseif ($counts['actioned_items'] == $counts['total_items']) {
             $new_status = 'IN_PROGRESS'; // Or ready for review if all items are actioned
         } elseif ($counts['actioned_items'] > 0) {
             $new_status = 'IN_PROGRESS';
         }
     }

     if ($new_status) {
         $stmt_current_status = $pdo->prepare("SELECT status FROM picklists WHERE id = :id");
         $stmt_current_status->execute([':id' => $picklist_id]);
         $current_picklist_status = $stmt_current_status->fetchColumn();

         if ($current_picklist_status !== 'COMPLETED' && $current_picklist_status !== 'CANCELLED' && $current_picklist_status !== $new_status && $current_picklist_status !== 'PARTIALLY_COMPLETED') {
              $stmt_update_header = $pdo->prepare("UPDATE picklists SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
              $stmt_update_header->execute([':status' => $new_status, ':id' => $picklist_id]);
         }
     }
}

function complete_picklist($picklist_id, $user_id = null) {
    $pdo = get_db_connection();

    $details = get_picklist_details($picklist_id);
    if (!\$details) {
        $_SESSION['error_message'] = "Picklist ID {$picklist_id} not found."; return false;
    }
    if ($details['status'] === 'COMPLETED' || $details['status'] === 'CANCELLED') {
        $_SESSION['error_message'] = "Picklist is already {$details['status']}."; return true;
    }

    $all_items_actioned = true;
    $all_items_fully_picked = true;
    if (empty($details['items']) && count(get_order_items_with_packing_status($details['order_id'])) > 0 ) {
        // If picklist has no items, but order did, it means nothing was added to picklist - this is an issue.
        // However, create_picklist_for_order should prevent this if items were available.
        // This condition might mean an order with 0 quantity items, which is fine.
        // If order items exist but all are 0 qty, it's fine.
        $order_items_check = get_order_items_with_packing_status($details['order_id']);
        $has_items_to_pick = false;
        foreach($order_items_check as $oic) {
            if($oic['quantity_ordered'] > 0) $has_items_to_pick = true; break;
        }
        if($has_items_to_pick) $all_items_actioned = false; // If order had items, but picklist is empty, not all actioned.

    } elseif (!empty($details['items'])) {
        foreach ($details['items'] as $item) {
            if (!in_array($item['status'], ['PICKED', 'NOT_FOUND', 'DAMAGE', 'SKIPPED'])) {
                $all_items_actioned = false;
            }
            if ($item['status'] !== 'PICKED' || $item['quantity_picked'] < $item['quantity_to_pick']) {
                 if ($item['status'] === 'PICKED' && $item['quantity_picked'] < $item['quantity_to_pick']) {
                     $all_items_fully_picked = false;
                 } elseif ($item['status'] !== 'PICKED') {
                     $all_items_fully_picked = false;
                 }
            }
        }
    }


    if (!\$all_items_actioned) {
        $_SESSION['error_message'] = "Not all items in picklist {$details['picklist_code']} have been actioned (picked, not found, etc.).";
        return false;
    }

    $final_picklist_status = $all_items_fully_picked ? 'COMPLETED' : 'PARTIALLY_COMPLETED';

    $pdo->beginTransaction();
    try {
        $sql_update = "UPDATE picklists SET status = :status, completed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                       WHERE id = :id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([':status' => $final_picklist_status, ':id' => $picklist_id]);

        $new_order_status = ($final_picklist_status === 'COMPLETED') ? ORDER_STATUS_PICKING_COMPLETE : ORDER_STATUS_PICKING_IN_PROGRESS;

        if (!update_order_status($details['order_id'], $new_order_status, "Picklist {$details['picklist_code']} status: {$final_picklist_status}.")) {
            $pdo->rollBack(); return false;
        }

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error completing picklist: " . $e->getMessage();
        return false;
    }
}

/**
 * Fetches the ID of the 'COMPLETED' picklist for a given order.
 * @param int $order_id
 * @return int|null Picklist ID or null if not found or not completed.
 */
function get_completed_picklist_for_order($order_id) {
    $pdo = get_db_connection();
    $sql = "SELECT id FROM picklists
            WHERE order_id = :order_id AND status = 'COMPLETED'
            ORDER BY completed_at DESC, id DESC LIMIT 1"; // Get the latest completed if multiple (should ideally be one)
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':order_id' => $order_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? (int)$result['id'] : null;
}
?>
