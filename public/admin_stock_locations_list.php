<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/warehouse_functions.php';
require_once __DIR__ . '/../src/stock_location_functions.php';

$warehouse_id = $_GET['warehouse_id'] ?? null;
if (!$warehouse_id || !filter_var($warehouse_id, FILTER_VALIDATE_INT)) {
    // Redirect to warehouse list or show an error/selection page
    header('Location: admin_warehouses_list.php?error=' . urlencode('No valid warehouse selected.'));
    exit;
}
$warehouse_id = (int)$warehouse_id;

$warehouse = get_warehouse_by_id($warehouse_id);
if (!$warehouse) {
    header('Location: admin_warehouses_list.php?error=' . urlencode('Warehouse not found.'));
    exit;
}

$page_title = "Admin - Stock Locations for " . htmlspecialchars($warehouse['name']);

$filters = [
    'location_code' => $_GET['location_code'] ?? null,
    'location_type' => $_GET['location_type'] ?? null,
];
$filters = array_filter($filters, function($value) { return $value !== null && $value !== ''; });

$stock_locations = get_stock_locations_by_warehouse($warehouse_id, $filters);

// For filter dropdown
$location_types = ['AISLE', 'SHELF', 'BIN', 'ZONE', 'PALLET', 'RECEIVING', 'SHIPPING', 'STAGING'];
sort($location_types);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <!-- <link rel="stylesheet" href="css/admin_style.css"> --> <!-- Assuming a shared admin CSS -->
    <style>
         body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
         .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 1000px; margin: 40px auto;}
         h1, h2 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0; }
         .action-bar { margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;}
         .action-bar .add-new-btn {
             background-color: #28a745; color: white; padding: 10px 18px; text-decoration: none; border-radius: 5px; font-size:0.95rem;
             transition: background-color 0.2s;
         }
         .action-bar .add-new-btn:hover { background-color: #218838; }
         .filters-form { margin-bottom: 20px; padding: 15px; background-color: #f1f1f1; border-radius: 5px; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
         .filters-form div { display: flex; flex-direction: column; }
         .filters-form label { font-weight: bold; margin-bottom: 5px; font-size: 0.9em; }
         .filters-form input[type="text"], .filters-form select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size:0.9rem; }
         .filters-form button, .filters-form .button-like {
             padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration:none; font-size:0.9rem;
             display:inline-block; line-height:normal;
         }
         .filters-form button:hover, .filters-form .button-like:hover { background-color: #0056b3; }
         .filters-form .reset-button { background-color: #6c757d; }
         .filters-form .reset-button:hover { background-color: #5a6268; }
         table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.85em;}
         th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
         th { background-color: #e9ecef; font-weight:bold; }
         td { background-color:#fff; }
         tr:nth-child(even) td { background-color: #f8f9fa; }
         .action-links a { margin-right: 10px; text-decoration: none; color:#007bff; }
         .action-links a:hover {text-decoration:underline;}
         .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align:center; }
         .nav-links a { margin:0 10px; text-decoration: none; color:#007bff; }
         .nav-links a:hover {text-decoration:underline;}
         .no-records { text-align:center; padding: 20px; color: #777; background-color:#f8f9fa; border:1px solid #ddd; border-radius:5px;}
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
        <p>Warehouse: <strong><?php echo htmlspecialchars($warehouse['name']); ?></strong> (ID: <?php echo htmlspecialchars($warehouse['id']); ?>)</p>

        <div class="action-bar">
            <a href="admin_add_stock_location.php?warehouse_id=<?php echo $warehouse['id']; ?>" class="add-new-btn">Add New Stock Location</a>
        </div>

         <form action="admin_stock_locations_list.php" method="GET" class="filters-form">
             <input type="hidden" name="warehouse_id" value="<?php echo htmlspecialchars($warehouse_id); ?>">
             <div>
                 <label for="location_code">Location Code:</label>
                 <input type="text" id="location_code" name="location_code" value="<?php echo htmlspecialchars($filters['location_code'] ?? ''); ?>">
             </div>
             <div>
                 <label for="location_type">Location Type:</label>
                 <select id="location_type" name="location_type">
                     <option value="">All Types</option>
                     <?php foreach ($location_types as $type): ?>
                         <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($filters['location_type']) && $filters['location_type'] === $type) ? 'selected' : ''; ?>>
                             <?php echo htmlspecialchars($type); ?>
                         </option>
                     <?php endforeach; ?>
                 </select>
             </div>
             <div><label>&nbsp;</label><button type="submit">Filter</button></div>
             <div><label>&nbsp;</label><a href="admin_stock_locations_list.php?warehouse_id=<?php echo htmlspecialchars($warehouse_id); ?>" class="reset-button button-like">Reset</a></div>
         </form>


        <?php if (empty($stock_locations)): ?>
            <p class="no-records">No stock locations found for this warehouse matching your criteria. <a href="admin_add_stock_location.php?warehouse_id=<?php echo $warehouse['id']; ?>">Add one now!</a></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Location Code</th>
                        <th>Type</th>
                        <th>Parent Location</th>
                        <th>Description</th>
                        <th>Pickable</th>
                        <th>Sellable</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stock_locations as $location): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($location['id']); ?></td>
                            <td><?php echo htmlspecialchars($location['location_code']); ?></td>
                            <td><?php echo htmlspecialchars($location['location_type'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($location['parent_location_code'] ?? 'N/A'); ?> (ID: <?php echo htmlspecialchars($location['parent_location_id'] ?? 'None'); ?>)</td>
                            <td><?php echo htmlspecialchars(substr($location['description'] ?? '', 0, 50) . (strlen($location['description'] ?? '') > 50 ? '...' : '')); ?></td>
                            <td><?php echo $location['is_pickable'] ? 'Yes' : 'No'; ?></td>
                            <td><?php echo $location['is_sellable'] ? 'Yes' : 'No'; ?></td>
                            <td class="action-links">
                                <a href="admin_edit_stock_location.php?id=<?php echo $location['id']; ?>">Edit</a>
                                <!-- Delete action would need confirmation -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="nav-links">
            <a href="admin_warehouses_list.php">Back to Warehouses List</a>
        </div>
    </div>
</body>
</html>
