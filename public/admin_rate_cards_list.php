<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/finance_functions.php';

$page_title = "Admin - Rate Cards";
$rate_cards = get_all_rate_cards();

$flash_message = $_SESSION['flash_message'] ?? null;
$flash_message_type = $_SESSION['flash_message_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <style> /* Basic styles, assuming shared admin_style.css might not exist or be complete */
        body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 900px; margin: 40px auto;}
        h1 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0; }
        .action-bar { margin-bottom: 20px; }
        .action-bar .add-new-btn { background-color: #28a745; color: white; padding: 10px 18px; text-decoration: none; border-radius: 5px; font-size:0.95rem;}
        .action-bar .add-new-btn:hover { background-color: #218838; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em;}
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align:middle; }
        th { background-color: #e9ecef; font-weight:bold; }
        .action-links a { margin-right: 10px; text-decoration: none; color:#007bff; }
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size:0.95rem;}
        .message.success { background-color: #d4edda; color: #155724;}
        .message.info { background-color: #d1ecf1; color: #0c5460; }
        .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align: center;}
        .nav-links a { margin: 0 10px; text-decoration: none; color: #007bff; }
        .top-nav { padding: 10px 20px; background-color: #333; color: #fff; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .top-nav a { color: #fff; text-decoration: none; margin-left: 15px; }
    </style>
</head>
<body>
    <div class="top-nav"><span>Admin Panel - Finance Management</span><div><a href="admin_service_types_list.php">Service Types</a><a href="admin_dashboard.php">Dashboard</a><a href="logout.php">Logout</a></div></div>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <?php if ($flash_message): ?>
            <div class="message <?php echo htmlspecialchars($flash_message_type); ?>"><?php echo htmlspecialchars($flash_message); ?></div>
        <?php endif; ?>
        <div class="action-bar"><a href="admin_add_rate_card.php" class="add-new-btn">Add New Rate Card</a></div>
        <?php if (empty($rate_cards)): ?>
            <p>No rate cards defined yet.</p>
        <?php else: ?>
            <table><thead><tr><th>Name</th><th>Description</th><th>Active</th><th>Valid From</th><th>Valid To</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($rate_cards as $rc): ?>
                <tr>
                    <td><?php echo htmlspecialchars($rc['name']); ?></td>
                    <td><?php echo htmlspecialchars($rc['description'] ?? 'N/A'); ?></td>
                    <td><?php echo $rc['is_active'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo htmlspecialchars($rc['valid_from'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($rc['valid_to'] ?? 'N/A'); ?></td>
                    <td class="action-links">
                        <a href="admin_edit_rate_card.php?id=<?php echo $rc['id']; ?>">Edit Info</a>
                        <a href="admin_rate_card_details.php?rate_card_id=<?php echo $rc['id']; ?>">Manage Rates</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
        <div class="nav-links"><a href="admin_service_types_list.php">Manage Service Types</a></div>
    </div>
</body>
</html>
