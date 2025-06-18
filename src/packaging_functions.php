<?php
require_once __DIR__ . '/database_connection.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Define package statuses (can be moved to config)
// Ensure 'READY_TO_SHIP' is present (it was already from previous definition)
define('PACKAGE_STATUSES', ['PENDING_ITEMS', 'ITEMS_ADDED', 'WEIGHED_MEASURED', 'LABELED', 'READY_TO_SHIP', 'SHIPPED']);


/**
 * Creates a new shipment package record for an order.
 */
function create_shipment_package($order_id, $box_definition_id = null, $user_id = null, $status = 'PENDING_ITEMS') {
    $pdo = get_db_connection();
    $sql = "INSERT INTO shipment_packages (order_id, box_definition_id, status, created_by_user_id)
            VALUES (:order_id, :box_definition_id, :status, :user_id)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([
            ':order_id' => $order_id,
            ':box_definition_id' => $box_definition_id, // Can be null
            ':status' => $status,
            ':user_id' => $user_id ?? $_SESSION['user_id'] ?? null
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error creating shipment package: " . $e->getMessage();
        error_log("Create Shipment Package Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Adds an item from an order to a specific shipment package.
 */
function add_item_to_package($shipment_package_id, $order_item_id, $item_id, $quantity_packed) {
    $pdo = get_db_connection();

    // Basic validation: Ensure quantity_packed is positive
    if ((int)$quantity_packed <= 0) {
        $_SESSION['error_message'] = "Quantity packed must be a positive number.";
        return false;
    }

    // TODO: More advanced validation:
    // 1. Check if order_item_id belongs to the same order_id as the shipment_package_id.
    // 2. Check if total quantity_packed for an order_item_id across all packages exceeds order_item.quantity.
    //    This requires fetching the original order_item.quantity and sum of already packed quantities.

    $sql = "INSERT INTO shipment_package_items (shipment_package_id, order_item_id, item_id, quantity_packed)
            VALUES (:shipment_package_id, :order_item_id, :item_id, :quantity_packed)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([
            ':shipment_package_id' => $shipment_package_id,
            ':order_item_id' => $order_item_id,
            ':item_id' => $item_id, // This should match the item_id of the order_item_id
            ':quantity_packed' => (int)$quantity_packed
        ]);

        // Optionally, update the package status to 'ITEMS_ADDED' if not already set
        update_shipment_package_status($shipment_package_id, 'ITEMS_ADDED', ['PENDING_ITEMS']);

        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Integrity constraint (e.g., uq_package_order_item)
            $_SESSION['error_message'] = "This order item is already in this package. Update quantity or remove first.";
        } else {
            $_SESSION['error_message'] = "Error adding item to package: " . $e->getMessage();
        }
        error_log("Add Item to Package Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates the status of a shipment package.
 * Can optionally check current status before updating.
 */
function update_shipment_package_status($shipment_package_id, $new_status, $expected_current_statuses = []) {
    $pdo = get_db_connection();
    if (!in_array($new_status, PACKAGE_STATUSES)) {
        $_SESSION['error_message'] = "Invalid new package status: {$new_status}.";
        return false;
    }

    if (!empty($expected_current_statuses)) {
        $current_pkg = get_shipment_package_details($shipment_package_id); // Use existing helper
        if (!$current_pkg) {
            $_SESSION['error_message'] = "Package ID {$shipment_package_id} not found for status check.";
            return false; // Package not found
        }
        if (!in_array($current_pkg['status'], $expected_current_statuses)) {
            $_SESSION['error_message'] = "Package ID {$shipment_package_id} is in status '{$current_pkg['status']}', which is not one of the expected statuses: " . implode(', ', $expected_current_statuses) . ". Cannot update to '{$new_status}'.";
            error_log("Update Package Status Precondition Failed: PkgID {$shipment_package_id}, Current '{$current_pkg['status']}', Expected " . implode(', ', $expected_current_statuses) . ", Target '{$new_status}'");
            return false; // Current status is not as expected
        }
    }

    $sql = "UPDATE shipment_packages SET status = :new_status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
    $params = [':new_status' => $new_status, ':id' => $shipment_package_id];

    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error updating package status: " . $e->getMessage();
        error_log("Update Package Status Error: " . $e->getMessage());
        return false;
    }
}


/**
 * Fetches all packages associated with a given order_id.
 */
function get_packages_for_order($order_id) {
    $pdo = get_db_connection();
    $sql = "SELECT sp.*, bd.name as box_name,
                   bd.length_cm as box_length, bd.width_cm as box_width, bd.height_cm as box_height
            FROM shipment_packages sp
            LEFT JOIN box_definitions bd ON sp.box_definition_id = bd.id
            WHERE sp.order_id = :order_id
            ORDER BY sp.created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':order_id' => $order_id]);
    return $stmt->fetchAll();
}

/**
 * Fetches all items packed into a specific shipment_package_id.
 */
function get_items_in_package($shipment_package_id) {
    $pdo = get_db_connection();
    // Joins with order_items and items to get item details
    $sql = "SELECT spi.id as shipment_package_item_id, spi.quantity_packed,
                   oi.id as order_item_id, oi.unit_price as price_at_order,
                   i.id as item_id, i.sku, i.name as item_name, i.weight as item_weight,
                   i.length as item_length, i.width as item_width, i.height as item_height
            FROM shipment_package_items spi
            JOIN order_items oi ON spi.order_item_id = oi.id
            JOIN items i ON spi.item_id = i.id
            WHERE spi.shipment_package_id = :shipment_package_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':shipment_package_id' => $shipment_package_id]);
    return $stmt->fetchAll();
}

/**
 * Fetches details for a single shipment package.
 */
function get_shipment_package_details($shipment_package_id) {
    $pdo = get_db_connection();
    $sql = "SELECT sp.*, bd.name as box_name
            FROM shipment_packages sp
            LEFT JOIN box_definitions bd ON sp.box_definition_id = bd.id
            WHERE sp.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $shipment_package_id]);
    return $stmt->fetch();
}

/**
 * Removes an item from a package.
 */
function remove_item_from_package($shipment_package_item_id) {
    $pdo = get_db_connection();
    $sql = "DELETE FROM shipment_package_items WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([':id' => $shipment_package_item_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error removing item from package: " . $e->getMessage();
        error_log("Remove Item From Package Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes an entire shipment package. Items are cascade deleted by DB constraint.
 */
function delete_shipment_package($shipment_package_id) {
     $pdo = get_db_connection();
     // No need for transaction if ON DELETE CASCADE is reliable for shipment_package_items.
     // However, if other related actions were needed, a transaction would be good.
     $sql = "DELETE FROM shipment_packages WHERE id = :id";
     $stmt = $pdo->prepare($sql);
     try {
         return $stmt->execute([':id' => $shipment_package_id]);
     } catch (PDOException $e) {
         $_SESSION['error_message'] = "Error deleting package: " . $e->getMessage();
         error_log("Delete Shipment Package Error: " . $e->getMessage());
         return false;
     }
}

?>
