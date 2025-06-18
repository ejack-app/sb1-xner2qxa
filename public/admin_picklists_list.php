<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/picking_functions.php';
require_once __DIR__ . '/../src/warehouse_functions.php';
require_once __DIR__ . '/../src/user_functions.php';

$page_title = "Admin - Picklists";

$filters = [
    'status' => $_GET['status'] ?? null,
    'warehouse_id' => $_GET['warehouse_id'] ?? null,
    'assigned_picker_id' => $_GET['assigned_picker_id'] ?? null,
];
$filters = array_filter($filters, function($value) { return $value !== null && $value !== ''; });

// For now, get_picklists_by_criteria uses direct limit/offset. Pagination needs count.
// Let's simplify for this UI phase and show a limited list without full pagination.
$picklists = get_picklists_by_criteria($filters, 50, 0);

$active_warehouses = get_all_warehouses(true);
$pickers = get_pickers();

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
        .action-bar { margin-bottom: 20px; }
        .action-bar .add-new-btn { background-color: #28a745; color: white; padding: 10px 18px; text-decoration: none; border-radius: 5px; font-size:0.95rem;}
        .filters-form { margin-bottom: 20px; padding: 15px; background-color: #f1f1f1; border-radius: 5px; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .filters-form div { display: flex; flex-direction: column; }
        .filters-form label { font-weight: bold; margin-bottom: 5px; font-size: 0.9em; }
        .filters-form select, .filters-form button { padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size:0.9rem; }
        .filters-form button { background-color: #007bff; color:white; cursor:pointer;}
        .filters-form .reset-button { background-color: #6c757d; text-decoration:none; color:white; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em;}
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align:middle; }
        th { background-color: #e9ecef; font-weight:bold; }
        .action-links a { margin-right: 10px; text-decoration: none; color:#007bff; }
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size:0.95rem;}
        .message.success { background-color: #d4edda; color: #155724;}
        .no-records { text-align:center; padding: 20px; color: #777;}
        .top-nav { padding: 10px 20px; background-color: #333; color: #fff; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .top-nav a { color: #fff; text-decoration: none; margin-left: 15px; }
    </style>
</head>
<body>
    <div class="top-nav">
        <span>Admin Panel - Picking Management</span>
        <div><a href="admin_picklist_creation.php">Create Picklist</a><a href="admin_dashboard.php">Dashboard</a><a href="logout.php">Logout</a></div>
    </div>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <?php if ($flash_message): ?>
            <div class="message <?php echo htmlspecialchars($flash_message_type); ?>"><?php echo htmlspecialchars($flash_message); ?></div>
        <?php endif; ?>

        <form action="admin_picklists_list.php" method="GET" class="filters-form">
            <div><label for="status">Status:</label><select id="status" name="status"><option value="">All</option>
                <?php foreach (PICKLIST_STATUSES as $status_val): ?><option value="<?php echo htmlspecialchars($status_val); ?>" <?php echo (($filters['status'] ?? '') === $status_val) ? 'selected' : ''; ?>><?php echo htmlspecialchars($status_val); ?></option><?php endforeach; ?>
            </select></div>
            <div><label for="warehouse_id">Warehouse:</label><select id="warehouse_id" name="warehouse_id"><option value="">All</option>
                <?php foreach ($active_warehouses as $wh): ?><option value="<?php echo $wh['id']; ?>" <?php echo (($filters['warehouse_id'] ?? '') == $wh['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($wh['name']); ?></option><?php endforeach; ?>
            </select></div>
            <div><label for="assigned_picker_id">Picker:</label><select id="assigned_picker_id" name="assigned_picker_id"><option value="">All</option>
                <?php foreach ($pickers as $picker): ?><option value="<?php echo $picker['id']; ?>" <?php echo (($filters['assigned_picker_id'] ?? '') == $picker['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($picker['username']); ?></option><?php endforeach; ?>
            </select></div>
            <div><label>&nbsp;</label><button type="submit">Filter</button> <a href="admin_picklists_list.php" class="reset-button button-like">Reset</a></div>
        </form>

        <?php if (empty($picklists)): ?>
            <p class="no-records">No picklists found matching criteria.</p>
        <?php else: ?>
            <table><thead><tr><th>Code</th><th>Order #</th><th>Warehouse</th><th>Status</th><th>Picker</th><th>Created</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($picklists as $picklist): ?>
                <tr>
                    <td><?php echo htmlspecialchars($picklist['picklist_code']); ?></td>
                    <td><?php echo htmlspecialchars($picklist['order_number']); ?></td>
                    <td><?php echo htmlspecialchars($picklist['warehouse_name']); ?></td>
                    <td><?php echo htmlspecialchars($picklist['status']); ?></td>
                    <td><?php echo htmlspecialchars($picklist['picker_username'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($picklist['created_at']))); ?></td>
                    <td class="action-links"><a href="admin_picklist_details.php?picklist_id=<?php echo $picklist['id']; ?>">View/Process</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </div>
</body>
</html>
