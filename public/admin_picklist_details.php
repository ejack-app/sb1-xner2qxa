<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/picking_functions.php';
require_once __DIR__ . '/../src/user_functions.php'; // For get_pickers
require_once __DIR__ . '/../src/stock_location_functions.php'; // For get_stock_locations_for_item_in_warehouse

$page_title = "Admin - Picklist Details";
$message = '';
$message_type = '';
$picklist_id = $_GET['picklist_id'] ?? null;

if (!$picklist_id || !filter_var($picklist_id, FILTER_VALIDATE_INT)) {
    $_SESSION['flash_message'] = "Invalid Picklist ID.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: admin_picklists_list.php');
    exit;
}
$picklist_id = (int)$picklist_id;

// Action Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_picklist_details'] ?? '')) {
        die('CSRF token validation failed.');
    }
    $action_taken_on_details_page = true;

    if (isset($_POST['assign_picker'])) {
        $picker_id_to_assign = $_POST['assigned_picker_id'] ?? null;
        if ($picker_id_to_assign && assign_picklist_to_picker($picklist_id, (int)$picker_id_to_assign)) {
            $message = "Picklist assigned successfully."; $message_type = "success";
        } else {
            $message = $_SESSION['error_message'] ?? "Failed to assign picklist."; $message_type = "error";
            unset($_SESSION['error_message']);
        }
    } elseif (isset($_POST['confirm_item_pick'])) {
        $picklist_item_id = $_POST['picklist_item_id'] ?? null;
        $picked_quantity = $_POST['picked_quantity'] ?? 0;
        $picked_from_location_id = $_POST['picked_from_location_id'] ?? null;
        $item_pick_status = $_POST['item_pick_status'] ?? 'PICKED'; // Default to 'PICKED'
        $picker_notes = $_POST['picker_notes'] ?? null;

        if ($picklist_item_id && $picked_from_location_id !== null && $item_pick_status) { // Location can be 0 if not found
             if ($item_pick_status === 'NOT_FOUND' || $item_pick_status === 'DAMAGE' || $item_pick_status === 'SKIPPED') {
                $picked_quantity = 0; // Force quantity to 0 if item is not found/damaged/skipped
            }
            if (confirm_item_pick((int)$picklist_item_id, (int)$picked_quantity, (int)$picked_from_location_id, $picker_notes, $item_pick_status)) {
                $message = "Item pick confirmed."; $message_type = "success";
            } else {
                $message = $_SESSION['error_message'] ?? "Failed to confirm item pick."; $message_type = "error";
                unset($_SESSION['error_message']);
            }
        } else {
            $message = "Missing data for item pick confirmation (item, location, or status)."; $message_type = "error";
        }
    } elseif (isset($_POST['complete_picklist'])) {
        if (complete_picklist($picklist_id)) {
            $message = "Picklist marked as completed."; $message_type = "success";
        } else {
            $message = $_SESSION['error_message'] ?? "Failed to complete picklist."; $message_type = "error";
            unset($_SESSION['error_message']);
        }
    }
    // No redirect, allow page to re-render with message and updated data
}


$picklist_details = get_picklist_details($picklist_id);
if (!$picklist_details) {
    $_SESSION['flash_message'] = "Picklist (ID: {$picklist_id}) not found.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: admin_picklists_list.php');
    exit;
}

$pickers = get_pickers(); // For assign picker dropdown

// For general page load messages via GET (e.g. after create redirect)
if (isset($_GET['msg']) && !$_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = urldecode($_GET['msg']);
    $message_type = $_GET['msg_type'] ?? 'info';
}

if (empty($_SESSION['csrf_token_picklist_details'])) {
    $_SESSION['csrf_token_picklist_details'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_picklist_details'];

$all_items_actioned_for_completion = true;
if (!empty($picklist_details['items'])) {
    foreach ($picklist_details['items'] as $item) {
        if (!in_array($item['status'], ['PICKED', 'NOT_FOUND', 'DAMAGE', 'SKIPPED'])) {
            $all_items_actioned_for_completion = false;
            break;
        }
    }
} else { // No items on picklist
    $all_items_actioned_for_completion = true; // Can complete an empty picklist
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title . " #" . $picklist_details['picklist_code']); ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 1000px; margin: 40px auto;}
        h1, h2, h3 { color: #333; }
        h1 {border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0;}
        h2 { border-bottom: 1px solid #eee; padding-bottom: 8px; margin-top: 25px; }
        .picklist-header, .picklist-items-section, .picklist-actions { margin-bottom: 20px; padding:15px; background-color:#f9f9f9; border:1px solid #eee; border-radius:5px;}
        .grid-info { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; }
        .grid-info p { margin: 5px 0; } .grid-info strong { font-weight:bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.85em; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; vertical-align:middle;}
        th { background-color: #e9ecef; }
        select, input[type="number"], input[type="text"], button, input[type="submit"] { padding: 6px; margin-top: 3px; border: 1px solid #ddd; border-radius: 4px; font-size:0.9em; }
        .item-actions-form input[type="number"] {width:60px;} .item-actions-form select {width:120px;}
        .item-actions-form button { background-color:#5cb85c; color:white;}
        .complete-picklist-btn { background-color:#28a745; color:white; padding:10px 15px; font-size:1rem;}
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size:0.95rem;}
        .message.success { background-color: #d4edda; color: #155724;}
        .message.error { background-color: #f8d7da; color: #721c24;}
        .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align:center;}
        .nav-links a { margin:0 10px; text-decoration: none; color:#007bff;}
        .top-nav { padding: 10px 20px; background-color: #333; color: #fff; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .top-nav a { color: #fff; text-decoration: none; margin-left: 15px; }
    </style>
</head>
<body>
    <div class="top-nav">
        <span>Admin Panel - Picking Management</span>
        <div><a href="admin_picklists_list.php">Picklists</a><a href="admin_picklist_creation.php">Create Picklist</a><a href="logout.php">Logout</a></div>
    </div>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title . " #" . $picklist_details['picklist_code']); ?></h1>
        <?php if ($message): ?><div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

        <div class="picklist-header">
            <h3>Header Details</h3>
            <div class="grid-info">
                <p><strong>Order #:</strong> <a href="admin_order_details.php?order_id=<?php echo $picklist_details['order_id']; ?>" target="_blank"><?php echo htmlspecialchars($picklist_details['order_number']); ?></a></p>
                <p><strong>Warehouse:</strong> <?php echo htmlspecialchars($picklist_details['warehouse_name']); ?></p>
                <p><strong>Status:</strong> <strong style="color:blue;"><?php echo htmlspecialchars($picklist_details['status']); ?></strong></p>
                <p><strong>Assigned Picker:</strong> <?php echo htmlspecialchars($picklist_details['picker_username'] ?? 'N/A'); ?></p>
                <p><strong>Created By:</strong> <?php echo htmlspecialchars($picklist_details['creator_username'] ?? 'N/A'); ?></p>
                <p><strong>Created At:</strong> <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($picklist_details['created_at']))); ?></p>
                <?php if($picklist_details['completed_at']): ?><p><strong>Completed At:</strong> <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($picklist_details['completed_at']))); ?></p><?php endif; ?>
            </div>
            <?php if ($picklist_details['status'] === 'PENDING'): ?>
            <form action="admin_picklist_details.php?picklist_id=<?php echo $picklist_id; ?>" method="POST" style="margin-top:10px;">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <label for="assigned_picker_id">Assign to Picker:</label>
                <select name="assigned_picker_id" id="assigned_picker_id" required>
                    <option value="">-- Select Picker --</option>
                    <?php foreach($pickers as $picker): ?>
                    <option value="<?php echo $picker['id']; ?>"><?php echo htmlspecialchars($picker['username']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="assign_picker">Assign</button>
            </form>
            <?php endif; ?>
        </div>

        <div class="picklist-items-section">
            <h2>Items to Pick</h2>
            <?php if (empty($picklist_details['items'])): ?>
                <p>No items on this picklist.</p>
            <?php else: ?>
            <table><thead><tr><th>SKU</th><th>Name</th><th>Qty to Pick</th><th>Suggested Loc.</th><th>Qty Picked</th><th>Picked From</th><th>Notes</th><th>Item Status</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach($picklist_details['items'] as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['item_sku']); ?></td>
                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['quantity_to_pick']); ?></td>
                    <td><?php echo htmlspecialchars($item['actual_suggested_code'] ?? ($item['suggested_location_code'] ?: 'N/A')); ?></td>
                    <form action="admin_picklist_details.php?picklist_id=<?php echo $picklist_id; ?>" method="POST" class="item-actions-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="picklist_item_id" value="<?php echo $item['id']; ?>">
                        <td><input type="number" name="picked_quantity" value="<?php echo htmlspecialchars($item['quantity_picked']); ?>" min="0" max="<?php echo htmlspecialchars($item['quantity_to_pick']); ?>" <?php echo ($picklist_details['status'] !== 'ASSIGNED' && $picklist_details['status'] !== 'IN_PROGRESS') ? 'readonly' : ''; ?>></td>
                        <td>
                            <?php if ($picklist_details['status'] === 'ASSIGNED' || $picklist_details['status'] === 'IN_PROGRESS'):
                                $item_locations = get_stock_locations_for_item_in_warehouse($item['item_id'], $picklist_details['warehouse_id']);
                            ?>
                            <select name="picked_from_location_id">
                                <option value="">- Select Actual -</option>
                                <?php if($item['suggested_location_id']): // Ensure suggested is an option ?>
                                    <option value="<?php echo $item['suggested_location_id']; ?>" <?php echo ($item['picked_from_location_id'] == $item['suggested_location_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($item['actual_suggested_code'] ?? $item['suggested_location_code']); ?> (Suggested)
                                    </option>
                                <?php endif; ?>
                                <?php foreach($item_locations as $loc):
                                    // Avoid duplicating suggested if it's already in the list and selected
                                    if($item['suggested_location_id'] == $loc['stock_location_id'] && $item['picked_from_location_id'] == $item['suggested_location_id']) continue;
                                ?>
                                <option value="<?php echo $loc['stock_location_id']; ?>" <?php echo ($item['picked_from_location_id'] == $loc['stock_location_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($loc['location_code'] . " (Avail: ".$loc['quantity_available'].")"); ?>
                                </option>
                                <?php endforeach; ?>
                                 <option value="0">N/A (Not Found/Damaged)</option>
                            </select>
                            <?php else: echo htmlspecialchars($item['actual_picked_code'] ?? 'N/A'); endif; ?>
                        </td>
                        <td><input type="text" name="picker_notes" value="<?php echo htmlspecialchars($item['picker_notes'] ?? ''); ?>" <?php echo ($picklist_details['status'] !== 'ASSIGNED' && $picklist_details['status'] !== 'IN_PROGRESS') ? 'readonly' : ''; ?>></td>
                        <td>
                            <?php if ($picklist_details['status'] === 'ASSIGNED' || $picklist_details['status'] === 'IN_PROGRESS'): ?>
                            <select name="item_pick_status">
                                <?php foreach(PICKLIST_ITEM_STATUSES as $pis): ?>
                                <option value="<?php echo $pis; ?>" <?php echo ($item['status'] == $pis) ? 'selected' : ''; ?>><?php echo $pis; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php else: echo htmlspecialchars($item['status']); endif; ?>
                        </td>
                        <td>
                            <?php if ($picklist_details['status'] === 'ASSIGNED' || $picklist_details['status'] === 'IN_PROGRESS'): ?>
                            <button type="submit" name="confirm_item_pick">Save Pick</button>
                            <?php else: echo "Locked"; endif; ?>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>
        </div>

        <?php if (in_array($picklist_details['status'], ['ASSIGNED', 'IN_PROGRESS', 'PARTIALLY_COMPLETED']) && $all_items_actioned_for_completion ): ?>
        <div class="picklist-actions">
            <h3>Complete Picklist</h3>
            <form action="admin_picklist_details.php?picklist_id=<?php echo $picklist_id; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <button type="submit" name="complete_picklist" class="complete-picklist-btn" onclick="return confirm('Are you sure all items are picked or accounted for? This will finalize the picklist.');">Mark Picklist as Completed</button>
            </form>
        </div>
        <?php elseif($picklist_details['status'] === 'COMPLETED' || $picklist_details['status'] === 'PARTIALLY_COMPLETED'): ?>
             <div class="picklist-actions"><h3 style="color:green;">Picklist processing is <?php echo strtolower($picklist_details['status']); ?>.</h3></div>
        <?php endif; ?>

        <div class="nav-links"><a href="admin_picklists_list.php">Back to Picklists List</a></div>
    </div>
</body>
</html>
