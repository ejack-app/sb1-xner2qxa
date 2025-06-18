<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/finance_functions.php';

$page_title = "Admin - Service Types";
$service_types = get_all_service_types();

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
        .top-nav { padding: 10px 20px; background-color: #333; color: #fff; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .top-nav a { color: #fff; text-decoration: none; margin-left: 15px; }
    </style>
</head>
<body>
    <div class="top-nav">
        <span>Admin Panel - Finance Management</span>
        <div><a href="admin_dashboard.php">Dashboard</a><a href="logout.php">Logout</a></div>
    </div>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <?php if ($flash_message): ?>
            <div class="message <?php echo htmlspecialchars($flash_message_type); ?>"><?php echo htmlspecialchars($flash_message); ?></div>
        <?php endif; ?>
        <div class="action-bar"><a href="admin_add_service_type.php" class="add-new-btn">Add New Service Type</a></div>
        <?php if (empty($service_types)): ?>
            <p>No service types defined yet.</p>
        <?php else: ?>
            <table><thead><tr><th>Code</th><th>Name</th><th>Unit</th><th>Active</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($service_types as $st): ?>
                <tr>
                    <td><?php echo htmlspecialchars($st['service_code']); ?></td>
                    <td><?php echo htmlspecialchars($st['name']); ?></td>
                    <td><?php echo htmlspecialchars($st['unit'] ?? 'N/A'); ?></td>
                    <td><?php echo $st['is_active'] ? 'Yes' : 'No'; ?></td>
                    <td class="action-links"><a href="admin_edit_service_type.php?id=<?php echo $st['id']; ?>">Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </div>
</body>
</html>
