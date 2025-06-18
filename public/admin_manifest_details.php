<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/manifest_functions.php';
require_once __DIR__ . '/../src/order_functions.php'; // For get_assignable_orders_for_manifest
require_once __DIR__ . '/../src/company_details_functions.php';
require_once __DIR__ . '/../src/vehicle_functions.php';
require_once __DIR__ . '/../src/user_functions.php';
require_once __DIR__ . '/../src/warehouse_functions.php';

$page_title = "Admin - Manifest Details";
$message = '';
$message_type = '';

$manifest_id = $_GET['manifest_id'] ?? null;

if (!$manifest_id || !filter_var($manifest_id, FILTER_VALIDATE_INT)) {
    $_SESSION['flash_message'] = "Invalid Manifest ID.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: admin_manifests_list.php');
    exit;
}
$manifest_id = (int)$manifest_id;

// Action Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_manifest_details'] ?? '')) {
        die('CSRF token validation failed.');
    }
    $action_taken_on_details_page = true; // Flag to refresh data at the end

    // Update Manifest Header
    if (isset($_POST['update_manifest_header'])) {
        $data_header = [
            'courier_company_id'   => $_POST['courier_company_id'] ?? null,
            'assigned_vehicle_id'  => $_POST['assigned_vehicle_id'] ?? null,
            'assigned_driver_id'   => $_POST['assigned_driver_id'] ?? null,
            'departure_warehouse_id'=> $_POST['departure_warehouse_id'] ?? null,
            'notes'                => $_POST['notes'] ?? null,
            // manifest_date and status are updated via specific actions/buttons
        ];
        // A new function might be needed in manifest_functions.php to update only these specific fields
        // For now, let's assume a generic update function or extend update_manifest_status if suitable.
        // Placeholder for update_manifest_header_details($manifest_id, $data_header);
        // For now, we'll use a direct DB update as a simplified example, NOT recommended for production
        $pdo_update = get_db_connection();
        $sql_hdr = "UPDATE manifests SET courier_company_id = :cc, assigned_vehicle_id = :av, assigned_driver_id = :ad, departure_warehouse_id = :dw, notes = :notes, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND status = 'OPEN'";
        $stmt_hdr = $pdo_update->prepare($sql_hdr);
        if ($stmt_hdr->execute([
            ':cc' => empty($data_header['courier_company_id']) ? null : (int)$data_header['courier_company_id'],
            ':av'  => empty($data_header['assigned_vehicle_id']) ? null : (int)$data_header['assigned_vehicle_id'],
            ':ad'  => empty($data_header['assigned_driver_id']) ? null : (int)$data_header['assigned_driver_id'],
            ':dw' => empty($data_header['departure_warehouse_id']) ? null : (int)$data_header['departure_warehouse_id'],
            ':notes'=> empty($data_header['notes']) ? null : $data_header['notes'],
            ':id'   => $manifest_id
        ])) {
            $message = "Manifest header updated successfully."; $message_type = 'success';
        } else {
            $message = "Failed to update manifest header (may not be in OPEN status or DB error)."; $message_type = 'error';
        }

    } elseif (isset($_POST['change_manifest_status'])) {
        $new_status = $_POST['new_status'] ?? '';
        $notes_for_history = "Status changed via manifest details page by user ID: " . ($_SESSION['user_id'] ?? 'N/A');
        if (update_manifest_status($manifest_id, $new_status, $notes_for_history)) {
            $message = "Manifest status updated to {$new_status}."; $message_type = 'success';
        } else {
            $message = $_SESSION['error_message'] ?? "Failed to update manifest status."; $message_type = 'error';
            unset($_SESSION['error_message']);
        }
    } elseif (isset($_POST['add_selected_orders'])) {
        $order_ids_to_add = $_POST['order_ids_to_add'] ?? [];
        if (empty($order_ids_to_add)) {
            $message = "No orders selected to add."; $message_type = 'error';
        } else {
            $added_count = 0; $failed_count = 0;
            foreach ($order_ids_to_add as $order_id) {
                if (add_order_to_manifest($manifest_id, (int)$order_id)) {
                    $added_count++;
                } else {
                    $failed_count++;
                    // Collect individual error messages if needed, or just a general failure message
                }
            }
            $message = "Added {$added_count} order(s) to manifest. Failed attempts: {$failed_count}.";
            $message_type = ($failed_count > 0) ? 'error' : 'success';
            if ($failed_count > 0 && isset($_SESSION['error_message'])) { // Show last specific error
                 $message .= " Last error: " . $_SESSION['error_message'];
                 unset($_SESSION['error_message']);
            }
        }
    } elseif (isset($_POST['remove_order_from_manifest'])) {
        $order_id_to_remove = $_POST['order_id_to_remove'] ?? null;
        if ($order_id_to_remove && remove_order_from_manifest($manifest_id, (int)$order_id_to_remove)) {
            $message = "Order removed from manifest."; $message_type = 'success';
        } else {
            $message = $_SESSION['error_message'] ?? "Failed to remove order from manifest."; $message_type = 'error';
            unset($_SESSION['error_message']);
        }
    }
    // No redirect for actions on this page, allow message to be shown and data to be re-fetched below.
}


// Fetch/Re-fetch manifest details AFTER any POST action
$manifest = get_manifest_details($manifest_id);
if (!$manifest) { // If manifest somehow became invalid after an action or was initially invalid
    $_SESSION['flash_message'] = "Manifest not found or became inaccessible.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: admin_manifests_list.php');
    exit;
}

// Data for forms
$assignable_orders = [];
if ($manifest['status'] === 'OPEN') {
    $assignable_orders = get_assignable_orders_for_manifest();
}
$courier_companies = get_all_courier_companies(true);
$active_vehicles = get_all_vehicles(['is_active' => true], 'v.vehicle_name', 'ASC', 1000, 0)['vehicles'];
$available_drivers = get_available_drivers();
$active_warehouses = get_all_warehouses(true);

// For general page load messages via GET (e.g. after create redirect)
if (isset($_GET['msg']) && !$_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = urldecode($_GET['msg']);
    $message_type = $_GET['msg_type'] ?? 'info';
}

if (empty($_SESSION['csrf_token_manifest_details'])) {
    $_SESSION['csrf_token_manifest_details'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_manifest_details'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title . " #" . $manifest['manifest_code']); ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 1000px; margin: 40px auto;}
        h1, h2, h3 { color: #333; }
        h1 {border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0;}
        h2 { border-bottom: 1px solid #eee; padding-bottom: 8px; margin-top: 25px; }
        .manifest-header-details, .manifest-orders, .assignable-orders, .manifest-actions { margin-bottom: 20px; padding:15px; background-color:#f9f9f9; border:1px solid #eee; border-radius:5px;}
        label { display: block; margin-top: 10px; font-weight: bold; margin-bottom:3px; }
        input[type="date"], input[type="text"], textarea, select { width: 100%; padding: 8px; margin-bottom:10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size:0.95rem; }
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9em; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #e9ecef; }
        button, input[type="submit"] { background-color: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; margin-right:10px; margin-top:5px; font-size:0.9rem; }
        button:hover, input[type="submit"]:hover { background-color: #0056b3; }
        .delete-btn, .remove-btn { background-color: #dc3545 !important; }
        .delete-btn:hover, .remove-btn:hover { background-color: #c82333 !important; }
        .ready-btn { background-color: #ffc107; color:#000;} .ready-btn:hover{background-color:#e0a800;}
        .dispatch-btn { background-color: #5cb85c; } .dispatch-btn:hover{background-color:#4cae4c;}
        .complete-btn { background-color: #17a2b8; } .complete-btn:hover{background-color:#138496;}
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size: 0.95rem; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align: center;}
        .nav-links a { margin: 0 10px; text-decoration: none; color: #007bff; }
        .top-nav { padding: 10px 20px; background-color: #333; color: #fff; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .top-nav a { color: #fff; text-decoration: none; margin-left: 15px; }
    </style>
</head>
<body>
    <div class="top-nav">
        <span>Admin Panel - Manifest Management</span>
        <div><a href="admin_dashboard.php">Dashboard</a><a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>)</a></div>
    </div>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title . " #" . $manifest['manifest_code']); ?></h1>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="manifest-header-details">
            <h2>Manifest Details</h2>
            <form action="admin_manifest_details.php?manifest_id=<?php echo $manifest_id; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="grid-container">
                    <div><label>Manifest Code:</label><input type="text" value="<?php echo htmlspecialchars($manifest['manifest_code']); ?>" readonly></div>
                    <div><label>Manifest Date:</label><input type="date" name="manifest_date_display" value="<?php echo htmlspecialchars($manifest['manifest_date']); ?>" <?php echo ($manifest['status'] !== 'OPEN') ? 'readonly' : 'disabled'; /* disabled for non-update, readonly for view */?>> <small>(Date non-editable here)</small></div>
                    <div><label>Status:</label><input type="text" value="<?php echo htmlspecialchars($manifest['status']); ?>" readonly style="font-weight:bold; color:blue;"></div>
                </div>
                <div class="grid-container">
                    <div><label for="courier_company_id">Courier:</label><select id="courier_company_id" name="courier_company_id" <?php echo ($manifest['status'] !== 'OPEN') ? 'disabled' : ''; ?>>
                        <option value="">-- Select --</option><?php foreach($courier_companies as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo ($manifest['courier_company_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?>
                    </select></div>
                    <div><label for="departure_warehouse_id">Departure Warehouse:</label><select id="departure_warehouse_id" name="departure_warehouse_id" <?php echo ($manifest['status'] !== 'OPEN') ? 'disabled' : ''; ?>>
                        <option value="">-- Select --</option><?php foreach($active_warehouses as $wh): ?><option value="<?php echo $wh['id']; ?>" <?php echo ($manifest['departure_warehouse_id'] == $wh['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($wh['name']); ?></option><?php endforeach; ?>
                    </select></div>
                </div>
                <div class="grid-container">
                    <div><label for="assigned_vehicle_id">Vehicle:</label><select id="assigned_vehicle_id" name="assigned_vehicle_id" <?php echo ($manifest['status'] !== 'OPEN') ? 'disabled' : ''; ?>>
                        <option value="">-- Select --</option><?php foreach($active_vehicles as $v): ?><option value="<?php echo $v['id']; ?>" <?php echo ($manifest['assigned_vehicle_id'] == $v['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($v['vehicle_name']); ?></option><?php endforeach; ?>
                    </select></div>
                    <div><label for="assigned_driver_id">Driver:</label><select id="assigned_driver_id" name="assigned_driver_id" <?php echo ($manifest['status'] !== 'OPEN') ? 'disabled' : ''; ?>>
                        <option value="">-- Select --</option><?php foreach($available_drivers as $d): ?><option value="<?php echo $d['id']; ?>" <?php echo ($manifest['assigned_driver_id'] == $d['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['username']); ?></option><?php endforeach; ?>
                    </select></div>
                </div>
                <div><label for="notes">Notes:</label><textarea id="notes" name="notes" <?php echo ($manifest['status'] !== 'OPEN') ? 'readonly' : ''; ?>><?php echo htmlspecialchars($manifest['notes'] ?? ''); ?></textarea></div>
                <?php if ($manifest['status'] === 'OPEN'): ?>
                    <button type="submit" name="update_manifest_header">Update Header Details</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="manifest-actions">
            <h3>Manage Manifest Status</h3>
            <form action="admin_manifest_details.php?manifest_id=<?php echo $manifest_id; ?>" method="POST" style="display:inline-block;">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <?php if ($manifest['status'] === 'OPEN'): ?>
                    <input type="hidden" name="new_status" value="READY_FOR_DISPATCH">
                    <button type="submit" name="change_manifest_status" class="ready-btn">Mark Ready for Dispatch</button>
                <?php elseif ($manifest['status'] === 'READY_FOR_DISPATCH'): ?>
                    <input type="hidden" name="new_status" value="IN_TRANSIT">
                    <button type="submit" name="change_manifest_status" class="dispatch-btn">Dispatch Manifest (Set IN_TRANSIT)</button>
                <?php elseif ($manifest['status'] === 'IN_TRANSIT'): ?>
                    <input type="hidden" name="new_status" value="COMPLETED">
                    <button type="submit" name="change_manifest_status" class="complete-btn">Mark as Completed</button>
                <?php endif; ?>
                <?php if ($manifest['status'] === 'OPEN' || $manifest['status'] === 'READY_FOR_DISPATCH'): ?>
                     <input type="hidden" name="new_status_cancel" value="CANCELLED"> <!-- Separate for safety if needed -->
                     <button type="submit" name="change_manifest_status" value="CANCELLED" class="delete-btn" onclick="return confirm('Are you sure you want to cancel this manifest? This may affect order statuses.');">Cancel Manifest</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="manifest-orders">
            <h2>Orders on this Manifest (<?php echo count($manifest['orders_on_manifest'] ?? []); ?>)</h2>
            <?php if (empty($manifest['orders_on_manifest'])): ?>
                <p>No orders currently added to this manifest.</p>
            <?php else: ?>
                <table><thead><tr><th>Order #</th><th>Recipient</th><th>Status</th><th>Packages</th><th>Action</th></tr></thead>
                <tbody><?php foreach($manifest['orders_on_manifest'] as $order): ?>
                    <tr>
                        <td><a href="admin_order_details.php?order_id=<?php echo $order['order_id']; ?>" target="_blank"><?php echo htmlspecialchars($order['order_number']); ?></a></td>
                        <td><?php echo htmlspecialchars($order['recipient_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['order_status']); ?></td>
                        <td><?php echo htmlspecialchars($order['package_count']); ?></td>
                        <td>
                            <?php if ($manifest['status'] === 'OPEN'): ?>
                            <form action="admin_manifest_details.php?manifest_id=<?php echo $manifest_id; ?>" method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="order_id_to_remove" value="<?php echo $order['order_id']; ?>">
                                <button type="submit" name="remove_order_from_manifest" class="remove-btn" onclick="return confirm('Remove this order from manifest? Its status will revert.');">Remove</button>
                            </form>
                            <?php else: echo "N/A (Locked)"; endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?></tbody></table>
            <?php endif; ?>
        </div>

        <?php if ($manifest['status'] === 'OPEN'): ?>
        <div class="assignable-orders">
            <h2>Add Assignable Orders to Manifest</h2>
            <?php if (empty($assignable_orders)): ?>
                <p>No orders currently available to add (must be PACKED or READY_TO_SHIP and not on another active manifest).</p>
            <?php else: ?>
            <form action="admin_manifest_details.php?manifest_id=<?php echo $manifest_id; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <table><thead><tr><th><input type="checkbox" id="select_all_assignable"></th><th>Order #</th><th>Recipient</th><th>Status</th><th>Packages</th></tr></thead>
                <tbody><?php foreach($assignable_orders as $order): ?>
                    <tr>
                        <td><input type="checkbox" name="order_ids_to_add[]" value="<?php echo $order['id']; ?>"></td>
                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                        <td><?php echo htmlspecialchars($order['recipient_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['order_status']); ?></td>
                        <td><?php echo htmlspecialchars($order['package_count']); ?></td>
                    </tr>
                <?php endforeach; ?></tbody></table>
                <button type="submit" name="add_selected_orders" style="margin-top:10px;">Add Selected Orders to Manifest</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="nav-links"><a href="admin_manifests_list.php">Back to Manifests List</a></div>
    </div>
    <script>
        if(document.getElementById('select_all_assignable')){
            document.getElementById('select_all_assignable').addEventListener('change', function(e){
                document.querySelectorAll('input[name="order_ids_to_add[]"]').forEach(chk => chk.checked = e.target.checked);
            });
        }
    </script>
</body>
</html>
