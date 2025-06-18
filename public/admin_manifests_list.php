<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/manifest_functions.php';
require_once __DIR__ . '/../src/company_details_functions.php'; // For get_all_courier_companies
require_once __DIR__ . '/../src/vehicle_functions.php'; // For get_all_vehicles (active)
require_once __DIR__ . '/../src/user_functions.php'; // For get_available_drivers (or similar)
require_once __DIR__ . '/../src/warehouse_functions.php'; // For get_all_warehouses (active)


$page_title = "Admin - Manifests List";

$filters = [
    'manifest_code'        => $_GET['manifest_code'] ?? null,
    'status'               => $_GET['status'] ?? null,
    'manifest_date_from'   => $_GET['manifest_date_from'] ?? null,
    'manifest_date_to'     => $_GET['manifest_date_to'] ?? null,
    'courier_company_id'   => $_GET['courier_company_id'] ?? null,
    'assigned_driver_id'   => $_GET['assigned_driver_id'] ?? null,
    'assigned_vehicle_id'  => $_GET['assigned_vehicle_id'] ?? null,
    'departure_warehouse_id'=> $_GET['departure_warehouse_id'] ?? null,
];
$filters = array_filter($filters, function($value) { return $value !== null && $value !== ''; });

$sort_by = $_GET['sort_by'] ?? 'm.manifest_date';
$sort_order = $_GET['sort_order'] ?? 'DESC';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$result = get_all_manifests($filters, $sort_by, $sort_order, $limit, $offset);
$manifests = $result['manifests'];
$total_manifests = $result['total_count'];
$total_pages = ceil($total_manifests / $limit);

// Data for filter dropdowns
$courier_companies = get_all_courier_companies(true); // Active ones
$available_drivers = get_available_drivers();
$active_vehicles = get_all_vehicles(['is_active' => true], 'v.vehicle_name', 'ASC', 1000, 0)['vehicles']; // Get all active
$active_warehouses = get_all_warehouses(true);


function get_manifest_sort_link($column_name, $display_text, $current_sort_by, $current_sort_order, $current_filters) {
    $new_sort_order = ($current_sort_by === $column_name && $current_sort_order === 'ASC') ? 'DESC' : 'ASC';
    $arrow = '';
    if ($current_sort_by === $column_name) {
        $arrow = $current_sort_order === 'ASC' ? ' &uarr;' : ' &darr;';
    }
    $query_params = array_merge($current_filters, ['sort_by' => $column_name, 'sort_order' => $new_sort_order, 'page' => 1]);
    return '<a href="?' . http_build_query($query_params) . '">' . htmlspecialchars($display_text) . $arrow . '</a>';
}

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
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 1200px; margin: 40px auto;}
        h1 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0; }
        .action-bar { margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; }
        .action-bar .add-new-btn {
            background-color: #28a745; color: white; padding: 10px 18px; text-decoration: none; border-radius: 5px; font-size: 0.95rem;
            transition: background-color 0.2s;
        }
        .action-bar .add-new-btn:hover { background-color: #218838; }
        .filters-form { margin-bottom: 20px; padding: 15px; background-color: #f1f1f1; border-radius: 5px; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; align-items: flex-end; }
        .filters-form div { display: flex; flex-direction: column; }
        .filters-form label { font-weight: bold; margin-bottom: 5px; font-size: 0.9em; }
        .filters-form input[type="text"], .filters-form input[type="date"], .filters-form select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size:0.9rem; width:100%; box-sizing:border-box;}
        .filters-form .buttons-div { grid-column: 1 / -1; display: flex; justify-content: flex-start; gap: 10px; margin-top:10px;}
        .filters-form button { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filters-form button:hover { background-color: #0056b3; }
        .filters-form .reset-button { background-color: #6c757d; text-decoration:none; color:white; }
        .filters-form .reset-button:hover { background-color: #5a6268; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.85em;}
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align:middle; }
        th { background-color: #e9ecef; font-weight:bold; }
        th a { color: #333; text-decoration:none; }
        td { background-color:#fff; }
        tr:nth-child(even) td { background-color: #f8f9fa; }
        .action-links a { margin-right: 10px; text-decoration: none; color:#007bff; }
        .action-links a:hover {text-decoration:underline;}
        .pagination { margin-top: 20px; text-align: center; }
        .pagination a, .pagination strong { padding: 5px 10px; margin: 0 2px; border: 1px solid #ddd; text-decoration: none; color: #007bff; border-radius: 3px;}
        .pagination strong { background-color: #007bff; color: white; border-color: #007bff; }
        .pagination a:hover { background-color: #eee; }
        .no-records { text-align:center; padding: 20px; color: #777; background-color:#f8f9fa; border:1px solid #ddd; border-radius:5px;}
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
        <span>Admin Panel - Manifest Management</span>
        <div><a href="admin_dashboard.php">Dashboard</a><a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>)</a></div>
    </div>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>

        <?php if ($flash_message): ?>
            <div class="message <?php echo htmlspecialchars($flash_message_type); ?>"><?php echo htmlspecialchars($flash_message); ?></div>
        <?php endif; ?>

        <div class="action-bar"><a href="admin_create_manifest.php" class="add-new-btn">Create New Manifest</a></div>

        <form action="admin_manifests_list.php" method="GET" class="filters-form">
            <div><label for="manifest_code">Manifest Code:</label><input type="text" id="manifest_code" name="manifest_code" value="<?php echo htmlspecialchars($filters['manifest_code'] ?? ''); ?>"></div>
            <div><label for="status">Status:</label><select id="status" name="status"><option value="">All</option>
                <?php foreach (MANIFEST_STATUSES as $status_val): ?><option value="<?php echo htmlspecialchars($status_val); ?>" <?php echo (($filters['status'] ?? '') === $status_val) ? 'selected' : ''; ?>><?php echo htmlspecialchars($status_val); ?></option><?php endforeach; ?>
            </select></div>
            <div><label for="manifest_date_from">Date From:</label><input type="date" id="manifest_date_from" name="manifest_date_from" value="<?php echo htmlspecialchars($filters['manifest_date_from'] ?? ''); ?>"></div>
            <div><label for="manifest_date_to">Date To:</label><input type="date" id="manifest_date_to" name="manifest_date_to" value="<?php echo htmlspecialchars($filters['manifest_date_to'] ?? ''); ?>"></div>
            <div><label for="courier_company_id">Courier:</label><select id="courier_company_id" name="courier_company_id"><option value="">All</option>
                <?php foreach ($courier_companies as $courier): ?><option value="<?php echo $courier['id']; ?>" <?php echo (($filters['courier_company_id'] ?? '') == $courier['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($courier['name']); ?></option><?php endforeach; ?>
            </select></div>
            <div><label for="assigned_driver_id">Driver:</label><select id="assigned_driver_id" name="assigned_driver_id"><option value="">All</option>
                <?php foreach ($available_drivers as $driver): ?><option value="<?php echo $driver['id']; ?>" <?php echo (($filters['assigned_driver_id'] ?? '') == $driver['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($driver['username']); ?></option><?php endforeach; ?>
            </select></div>
            <div><label for="assigned_vehicle_id">Vehicle:</label><select id="assigned_vehicle_id" name="assigned_vehicle_id"><option value="">All</option>
                <?php foreach ($active_vehicles as $vehicle): ?><option value="<?php echo $vehicle['id']; ?>" <?php echo (($filters['assigned_vehicle_id'] ?? '') == $vehicle['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($vehicle['vehicle_name'] . ($vehicle['license_plate'] ? ' ('.$vehicle['license_plate'].')':'')); ?></option><?php endforeach; ?>
            </select></div>
            <div><label for="departure_warehouse_id">Warehouse:</label><select id="departure_warehouse_id" name="departure_warehouse_id"><option value="">All</option>
                <?php foreach ($active_warehouses as $wh): ?><option value="<?php echo $wh['id']; ?>" <?php echo (($filters['departure_warehouse_id'] ?? '') == $wh['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($wh['name']); ?></option><?php endforeach; ?>
            </select></div>
            <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sort_by); ?>">
            <input type="hidden" name="sort_order" value="<?php echo htmlspecialchars($sort_order); ?>">
            <div class="buttons-div"><button type="submit">Filter</button><a href="admin_manifests_list.php" class="reset-button button-like">Reset</a></div>
        </form>

        <?php if (empty($manifests)): ?>
            <p class="no-records">No manifests found.</p>
        <?php else: ?>
            <table>
                <thead><tr>
                    <th><?php echo get_manifest_sort_link('m.manifest_code', 'Code', $sort_by, $sort_order, $filters); ?></th>
                    <th><?php echo get_manifest_sort_link('m.manifest_date', 'Date', $sort_by, $sort_order, $filters); ?></th>
                    <th><?php echo get_manifest_sort_link('m.status', 'Status', $sort_by, $sort_order, $filters); ?></th>
                    <th><?php echo get_manifest_sort_link('courier_name', 'Courier', $sort_by, $sort_order, $filters); ?></th>
                    <th><?php echo get_manifest_sort_link('vehicle_name', 'Vehicle', $sort_by, $sort_order, $filters); ?></th>
                    <th><?php echo get_manifest_sort_link('driver_name', 'Driver', $sort_by, $sort_order, $filters); ?></th>
                    <th><?php echo get_manifest_sort_link('warehouse_name', 'Warehouse', $sort_by, $sort_order, $filters); ?></th>
                    <th><?php echo get_manifest_sort_link('order_count', 'Orders', $sort_by, $sort_order, $filters); ?></th>
                    <th>Actions</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($manifests as $manifest): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($manifest['manifest_code']); ?></td>
                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($manifest['manifest_date']))); ?></td>
                            <td><?php echo htmlspecialchars($manifest['status']); ?></td>
                            <td><?php echo htmlspecialchars($manifest['courier_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($manifest['vehicle_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($manifest['driver_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($manifest['warehouse_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($manifest['order_count']); ?></td>
                            <td class="action-links"><a href="admin_manifest_details.php?manifest_id=<?php echo $manifest['id']; ?>">View/Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="pagination">
                <?php if ($page > 1): ?><a href="?<?php echo http_build_query(array_merge($filters, ['sort_by' => $sort_by, 'sort_order' => $sort_order, 'page' => $page - 1])); ?>">Previous</a><?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?><?php if ($i == $page): ?><strong><?php echo $i; ?></strong><?php else: ?><a href="?<?php echo http_build_query(array_merge($filters, ['sort_by' => $sort_by, 'sort_order' => $sort_order, 'page' => $i])); ?>"><?php echo $i; ?></a><?php endif; ?><?php endfor; ?>
                <?php if ($page < $total_pages): ?><a href="?<?php echo http_build_query(array_merge($filters, ['sort_by' => $sort_by, 'sort_order' => $sort_order, 'page' => $page + 1])); ?>">Next</a><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
