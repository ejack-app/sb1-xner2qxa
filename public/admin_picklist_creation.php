<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/order_functions.php';
require_once __DIR__ . '/../src/picking_functions.php';
require_once __DIR__ . '/../src/warehouse_functions.php';
require_once __DIR__ . '/../src/user_functions.php'; // For get_pickers

$page_title = "Admin - Create Picklist from Order";
$message = '';
$message_type = '';

// Data for forms
$active_warehouses = get_all_warehouses(true);
$pickers = get_pickers();

// Handle Picklist Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_picklist'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_create_picklist'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $order_id_for_picklist = $_POST['order_id'] ?? null;
    $warehouse_id = $_POST['warehouse_id'] ?? null;
    $assigned_picker_id = $_POST['assigned_picker_id'] ?? null;

    if (!$order_id_for_picklist || !$warehouse_id) {
        $message = "Order ID and Warehouse are required to create a picklist.";
        $message_type = 'error';
    } else {
        unset($_SESSION['error_message']);
        $picklist_id = create_picklist_for_order((int)$order_id_for_picklist, (int)$warehouse_id, empty($assigned_picker_id) ? null : (int)$assigned_picker_id);
        if ($picklist_id) {
            $_SESSION['flash_message'] = "Picklist #{$picklist_id} created successfully for Order ID {$order_id_for_picklist}.";
            $_SESSION['flash_message_type'] = 'success';
            header("Location: admin_picklist_details.php?picklist_id=" . $picklist_id);
            exit;
        } else {
            $message = $_SESSION['error_message'] ?? "Failed to create picklist.";
            $message_type = 'error';
            unset($_SESSION['error_message']);
        }
    }
}

if (empty($_SESSION['csrf_token_create_picklist'])) {
    $_SESSION['csrf_token_create_picklist'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_create_picklist'];


// Fetch orders ready for picking
// TODO: Implement pagination for get_orders_ready_for_picking if list becomes too long
$orders_for_picking = get_orders_ready_for_picking();

$flash_message = $_SESSION['flash_message'] ?? null;
$flash_message_type = $_SESSION['flash_message_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 1000px; margin: 40px auto;}
        h1 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em;}
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align:middle; }
        th { background-color: #e9ecef; font-weight:bold; }
        .action-form select, .action-form button { padding: 6px 10px; margin-right: 5px; font-size:0.9em; border-radius:4px; border:1px solid #ccc;}
        .action-form button { background-color:#28a745; color:white; cursor:pointer;}
        .action-form button:hover { background-color:#218838;}
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size:0.95rem;}
        .message.success { background-color: #d4edda; color: #155724; border:1px solid #c3e6cb;}
        .message.error { background-color: #f8d7da; color: #721c24; border:1px solid #f5c6cb;}
        .message.info { background-color: #d1ecf1; color: #0c5460; border:1px solid #bee5eb;}
        .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align:center;}
        .nav-links a { margin:0 10px; text-decoration: none; color:#007bff;}
        .top-nav { padding: 10px 20px; background-color: #333; color: #fff; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .top-nav a { color: #fff; text-decoration: none; margin-left: 15px; }
    </style>
</head>
<body>
    <div class="top-nav">
        <span>Admin Panel - Picking Management</span>
        <div><a href="admin_picklists_list.php">View All Picklists</a><a href="admin_dashboard.php">Dashboard</a><a href="logout.php">Logout</a></div>
    </div>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($flash_message): ?>
            <div class="message <?php echo htmlspecialchars($flash_message_type); ?>"><?php echo htmlspecialchars($flash_message); ?></div>
        <?php endif; ?>

        <h3>Orders Ready for Picking</h3>
        <?php if (empty($orders_for_picking)): ?>
            <p>No orders currently ready for picklist generation.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Order #</th><th>Recipient</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($orders_for_picking as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                        <td><?php echo htmlspecialchars($order['recipient_name']); ?></td>
                        <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($order['order_date']))); ?></td>
                        <td><?php echo htmlspecialchars($order['order_status']); ?></td>
                        <td>
                            <form action="admin_picklist_creation.php" method="POST" class="action-form">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="warehouse_id" required title="Select warehouse for picking">
                                    <option value="">-- Select Warehouse --</option>
                                    <?php foreach($active_warehouses as $wh): ?>
                                    <option value="<?php echo $wh['id']; ?>"><?php echo htmlspecialchars($wh['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="assigned_picker_id" title="Assign to picker (optional)">
                                    <option value="">-- Unassigned --</option>
                                    <?php foreach($pickers as $picker): ?>
                                    <option value="<?php echo $picker['id']; ?>"><?php echo htmlspecialchars($picker['username']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="create_picklist">Create Picklist</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
