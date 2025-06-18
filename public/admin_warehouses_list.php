<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/warehouse_functions.php';

$page_title = "Admin - Warehouses List";
$warehouses = get_all_warehouses(); // Fetches all, could add filter for active if needed

// Check for flash messages from redirects (e.g., after a failed edit)
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_message_type = $_SESSION['flash_message_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <!-- <link rel="stylesheet" href="css/admin_style.css"> -->
    <style>
         body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
         .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 900px; margin: 40px auto;}
         h1 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0; }
         .action-bar { margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; }
         .action-bar .add-new-btn {
             background-color: #28a745; color: white; padding: 10px 18px; text-decoration: none; border-radius: 5px; font-size: 0.95rem;
             transition: background-color 0.2s;
         }
         .action-bar .add-new-btn:hover { background-color: #218838; }
         table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9rem;}
         th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
         th { background-color: #e9ecef; font-weight: bold; }
         td { background-color: #fff; }
         tr:nth-child(even) td { background-color: #f8f9fa; }
         .action-links a { margin-right: 12px; text-decoration: none; color: #007bff; }
         .action-links a:hover { text-decoration: underline; }
         .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align:center; }
         .nav-links a { margin:0 10px; text-decoration: none; color:#007bff; }
         .nav-links a:hover {text-decoration:underline;}
         .no-records { text-align:center; padding: 20px; color: #777; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 5px;}
         .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size: 0.95rem; }
         .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
         .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
         .message.info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
         .top-nav { padding: 10px 20px; background-color: #333; color: #fff; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
         .top-nav a { color: #fff; text-decoration: none; margin-left: 15px; }
         .top-nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="top-nav">
        <span>Admin Panel</span>
        <div>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>)</a>
        </div>
    </div>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>

        <?php if ($flash_message): ?>
            <div class="message <?php echo htmlspecialchars($flash_message_type); ?>"><?php echo htmlspecialchars($flash_message); ?></div>
        <?php endif; ?>

        <div class="action-bar">
            <a href="admin_add_warehouse.php" class="add-new-btn">Add New Warehouse</a>
            <!-- Placeholder for filters if needed later -->
        </div>

        <?php if (empty($warehouses)): ?>
            <p class="no-records">No warehouses found. <a href="admin_add_warehouse.php">Add one now!</a></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($warehouses as $warehouse): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($warehouse['id']); ?></td>
                            <td><?php echo htmlspecialchars($warehouse['name']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($warehouse['address'] ?? 'N/A')); ?></td>
                            <td><?php echo $warehouse['is_active'] ? 'Active' : 'Inactive'; ?></td>
                            <td class="action-links">
                                <a href="admin_edit_warehouse.php?id=<?php echo $warehouse['id']; ?>">Edit</a>
                                <a href="admin_stock_locations_list.php?warehouse_id=<?php echo $warehouse['id']; ?>">View Locations</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
