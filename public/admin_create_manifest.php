<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/manifest_functions.php';
require_once __DIR__ . '/../src/company_details_functions.php'; // For get_all_courier_companies
require_once __DIR__ . '/../src/vehicle_functions.php';       // For get_all_vehicles
require_once __DIR__ . '/../src/user_functions.php';          // For get_available_drivers
require_once __DIR__ . '/../src/warehouse_functions.php';     // For get_all_warehouses

$page_title = "Admin - Create New Manifest";
$message = '';
$message_type = '';

// Data for dropdowns
$courier_companies = get_all_courier_companies(true);
$active_vehicles = get_all_vehicles(['is_active' => true], 'v.vehicle_name', 'ASC', 1000, 0)['vehicles'];
$available_drivers = get_available_drivers();
$active_warehouses = get_all_warehouses(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_create_manifest'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $data = [
        'manifest_date' => $_POST['manifest_date'] ?? date('Y-m-d'),
        'status' => $_POST['status'] ?? 'OPEN', // Default to OPEN
        'courier_company_id' => $_POST['courier_company_id'] ?? null,
        'assigned_vehicle_id' => $_POST['assigned_vehicle_id'] ?? null,
        'assigned_driver_id' => $_POST['assigned_driver_id'] ?? null,
        'departure_warehouse_id' => $_POST['departure_warehouse_id'] ?? null,
        'notes' => $_POST['notes'] ?? null,
    ];

    if (empty($data['manifest_date'])) {
        $message = 'Manifest Date is required.';
        $message_type = 'error';
    } else {
        unset($_SESSION['error_message']);
        $manifest_id = create_manifest($data);
        if ($manifest_id) {
            $_SESSION['flash_message'] = 'Manifest created successfully! You can now add orders.';
            $_SESSION['flash_message_type'] = 'success';
            header('Location: admin_manifest_details.php?manifest_id=' . $manifest_id);
            exit;
        } else {
            $message = $_SESSION['error_message'] ?? 'Failed to create manifest.';
            $message_type = 'error';
            unset($_SESSION['error_message']);
        }
    }
}

if (empty($_SESSION['csrf_token_create_manifest'])) {
    $_SESSION['csrf_token_create_manifest'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 800px; margin: 40px auto;}
        h1 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0; }
        label { display: block; margin-top: 15px; font-weight: bold; margin-bottom: 5px; }
        input[type="date"], input[type="text"], textarea, select {
            width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd;
            border-radius: 5px; box-sizing: border-box; font-size: 1rem;
        }
        textarea { min-height: 80px; resize: vertical;}
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        input[type="submit"] {
            background-color: #007bff; color: white; padding: 12px 20px; border: none;
            border-radius: 5px; cursor: pointer; font-size: 1rem; margin-top: 25px;
            transition: background-color 0.2s;
        }
        input[type="submit"]:hover { background-color: #0056b3; }
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size: 0.95rem; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align: center;}
        .nav-links a { margin: 0 10px; text-decoration: none; color: #007bff; }
        .nav-links a:hover { text-decoration: underline; }
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

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form action="admin_create_manifest.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_create_manifest']); ?>">

            <div class="grid-container">
                <div><label for="manifest_date">Manifest Date:</label><input type="date" id="manifest_date" name="manifest_date" value="<?php echo htmlspecialchars($_POST['manifest_date'] ?? date('Y-m-d')); ?>" required></div>
                <div><label for="status">Initial Status:</label>
                    <select id="status" name="status">
                        <option value="OPEN" <?php echo (($_POST['status'] ?? 'OPEN') === 'OPEN') ? 'selected' : ''; ?>>Open</option>
                        <option value="READY_FOR_DISPATCH" <?php echo (($_POST['status'] ?? '') === 'READY_FOR_DISPATCH') ? 'selected' : ''; ?>>Ready for Dispatch</option>
                    </select>
                </div>
            </div>

            <div class="grid-container">
                <div><label for="courier_company_id">Courier Company (Optional):</label>
                    <select id="courier_company_id" name="courier_company_id"><option value="">-- Select Courier --</option>
                        <?php foreach ($courier_companies as $courier): ?><option value="<?php echo $courier['id']; ?>" <?php echo (($_POST['courier_company_id'] ?? null) == $courier['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($courier['name']); ?></option><?php endforeach; ?>
                    </select></div>
                <div><label for="departure_warehouse_id">Departure Warehouse (Optional):</label>
                    <select id="departure_warehouse_id" name="departure_warehouse_id"><option value="">-- Select Warehouse --</option>
                        <?php foreach ($active_warehouses as $wh): ?><option value="<?php echo $wh['id']; ?>" <?php echo (($_POST['departure_warehouse_id'] ?? null) == $wh['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($wh['name']); ?></option><?php endforeach; ?>
                    </select></div>
            </div>

            <div class="grid-container">
                <div><label for="assigned_vehicle_id">Assigned Vehicle (Optional):</label>
                    <select id="assigned_vehicle_id" name="assigned_vehicle_id"><option value="">-- Select Vehicle --</option>
                        <?php foreach ($active_vehicles as $vehicle): ?><option value="<?php echo $vehicle['id']; ?>" <?php echo (($_POST['assigned_vehicle_id'] ?? null) == $vehicle['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($vehicle['vehicle_name'] . ($vehicle['license_plate'] ? ' ('.$vehicle['license_plate'].')':'')); ?></option><?php endforeach; ?>
                    </select></div>
                <div><label for="assigned_driver_id">Assigned Driver (Optional):</label>
                    <select id="assigned_driver_id" name="assigned_driver_id"><option value="">-- Select Driver --</option>
                        <?php foreach ($available_drivers as $driver): ?><option value="<?php echo $driver['id']; ?>" <?php echo (($_POST['assigned_driver_id'] ?? null) == $driver['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($driver['username']); ?></option><?php endforeach; ?>
                    </select></div>
            </div>

            <div><label for="notes">Notes (Optional):</label><textarea id="notes" name="notes"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea></div>

            <input type="submit" value="Create Manifest">
        </form>

        <div class="nav-links"><a href="admin_manifests_list.php">Back to Manifests List</a></div>
    </div>
</body>
</html>
