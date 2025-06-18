<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/warehouse_functions.php'; // For warehouse context if needed
require_once __DIR__ . '/../src/stock_location_functions.php';

$location_id = $_GET['id'] ?? null;
if (!$location_id || !filter_var($location_id, FILTER_VALIDATE_INT)) {
    header('Location: admin_warehouses_list.php?error=' . urlencode('No valid location specified.'));
    exit;
}
$location_id = (int)$location_id;

$location = get_stock_location_by_id($location_id);
if (!$location) {
    header('Location: admin_warehouses_list.php?error=' . urlencode('Stock location not found.'));
    exit;
}

$warehouse = get_warehouse_by_id((int)$location['warehouse_id']); // Get warehouse context
if (!$warehouse) {
    // Should not happen if DB integrity is maintained, but good check
    header('Location: admin_warehouses_list.php?error=' . urlencode('Associated warehouse not found.'));
    exit;
}

$page_title = "Admin - Edit Stock Location: " . htmlspecialchars($location['location_code']);
$message = '';
$message_type = '';

// For parent location dropdown - locations in the same warehouse, excluding self
$possible_parent_locations = array_filter(
    get_stock_locations_by_warehouse((int)$location['warehouse_id']),
    function($pl) use ($location_id) { return $pl['id'] != $location_id; }
);
$location_types = ['AISLE', 'SHELF', 'BIN', 'ZONE', 'PALLET', 'RECEIVING', 'SHIPPING', 'STAGING'];
sort($location_types);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_edit_sl'] ?? '')) {
        die('CSRF token validation failed.');
    }

    // warehouse_id from POST must match location's current warehouse_id for this form
    if (($_POST['warehouse_id'] ?? null) != $location['warehouse_id']) {
        die('Warehouse ID mismatch during edit.');
    }

    $data = [
        // 'id' => $location_id, // Not needed for update function's data array, passed as separate param
        'warehouse_id' => (int)$location['warehouse_id'], // Use existing warehouse_id for the WHERE clause in update
        'location_code' => $_POST['location_code'] ?? '',
        'location_type' => $_POST['location_type'] ?? null,
        'parent_location_id' => !empty($_POST['parent_location_id']) ? (int)$_POST['parent_location_id'] : null,
        'description' => $_POST['description'] ?? null,
        'is_pickable' => isset($_POST['is_pickable']),
        'is_sellable' => isset($_POST['is_sellable']),
        'max_weight_kg' => !empty($_POST['max_weight_kg']) ? (float)$_POST['max_weight_kg'] : null,
        'max_volume_m3' => !empty($_POST['max_volume_m3']) ? (float)$_POST['max_volume_m3'] : null,
    ];

    if (empty($data['location_code'])) {
        $message = 'Location Code is required.';
        $message_type = 'error';
    } else {
        unset($_SESSION['error_message']);
        if (update_stock_location($location_id, $data)) {
            $_SESSION['flash_message'] = 'Stock Location "' . htmlspecialchars($data['location_code']) . '" updated successfully!';
            $_SESSION['flash_message_type'] = 'success';
            header('Location: admin_stock_locations_list.php?warehouse_id=' . $location['warehouse_id']);
            exit;
        } else {
            $message = $_SESSION['error_message'] ?? 'Failed to update stock location.';
            $message_type = 'error';
            unset($_SESSION['error_message']);
            // To show changes on the form if update failed but data was posted
            $location = array_merge($location, $data); // Update displayed $location with submitted data
        }
    }
}

if (empty($_SESSION['csrf_token_edit_sl'])) {
    $_SESSION['csrf_token_edit_sl'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <!-- <link rel="stylesheet" href="css/admin_style.css"> -->
    <style>
         body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
         .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 800px; margin: 40px auto;}
         h1 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0; }
         label { display: block; margin-top: 15px; font-weight: bold; margin-bottom:5px; }
         input[type="text"], input[type="number"], textarea, select {
             width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-size:1rem;
         }
         input[type="checkbox"] { margin-right: 8px; vertical-align: middle; width:auto; }
         .checkbox-group label { font-weight: normal; margin-top:0; display:inline-block; margin-right:15px; }
         .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
         textarea {min-height:80px; resize:vertical;}
         input[type="submit"] {
             background-color: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; margin-top:25px; font-size:1rem;
             transition: background-color 0.2s;
         }
         input[type="submit"]:hover { background-color: #0056b3; }
         .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size:0.95rem;}
         .message.success { background-color: #d4edda; color: #155724; border:1px solid #c3e6cb; }
         .message.error { background-color: #f8d7da; color: #721c24; border:1px solid #f5c6cb; }
         .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align:center; }
         .nav-links a { margin:0 10px; text-decoration: none; color:#007bff; }
         .nav-links a:hover {text-decoration:underline;}
         .top-nav { padding: 10px 20px; background-color: #333; color: #fff; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
         .top-nav a { color: #fff; text-decoration: none; margin-left: 15px; }
         .top-nav a:hover { text-decoration: underline; }
         .form-section { margin-bottom:20px; padding-bottom:15px; border-bottom:1px dotted #ccc;}
         .form-section:last-of-type {border-bottom:none;}
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
        <p>Warehouse: <strong><?php echo htmlspecialchars($warehouse['name'] ?? 'N/A'); ?></strong> (ID: <?php echo htmlspecialchars($location['warehouse_id']); ?>)</p>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form action="admin_edit_stock_location.php?id=<?php echo htmlspecialchars($location_id); ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_edit_sl']); ?>">
            <input type="hidden" name="warehouse_id" value="<?php echo htmlspecialchars($location['warehouse_id']); ?>"> <!-- Important for update function check -->

            <div class="form-section">
                <div class="grid-container">
                    <div><label for="location_code">Location Code:</label><input type="text" id="location_code" name="location_code" value="<?php echo htmlspecialchars($location['location_code'] ?? ''); ?>" required></div>
                    <div><label for="location_type">Location Type:</label>
                        <select id="location_type" name="location_type">
                            <option value="">-- Select Type --</option>
                            <?php foreach ($location_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (($location['location_type'] ?? '') == $type) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label for="parent_location_id">Parent Location (Optional):</label>
                        <select id="parent_location_id" name="parent_location_id">
                            <option value="">-- None --</option>
                            <?php foreach ($possible_parent_locations as $pl): ?>
                            <option value="<?php echo htmlspecialchars($pl['id']); ?>" <?php echo (($location['parent_location_id'] ?? null) == $pl['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pl['location_code']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                 <label for="description">Description:</label><textarea id="description" name="description"><?php echo htmlspecialchars($location['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-section">
                <div class="grid-container" style="margin-top:15px;">
                    <div class="checkbox-group"><label><input type="checkbox" name="is_pickable" value="1" <?php echo ($location['is_pickable'] ?? false) ? 'checked' : ''; ?>> Is Pickable</label></div>
                    <div class="checkbox-group"><label><input type="checkbox" name="is_sellable" value="1" <?php echo ($location['is_sellable'] ?? false) ? 'checked' : ''; ?>> Is Sellable</label></div>
                </div>
            </div>

            <div class="form-section">
                <h3>Capacities (Optional)</h3>
                <div class="grid-container">
                    <div><label for="max_weight_kg">Max Weight (kg):</label><input type="number" id="max_weight_kg" name="max_weight_kg" step="0.01" value="<?php echo htmlspecialchars($location['max_weight_kg'] ?? ''); ?>"></div>
                    <div><label for="max_volume_m3">Max Volume (m³):</label><input type="number" id="max_volume_m3" name="max_volume_m3" step="0.001" value="<?php echo htmlspecialchars($location['max_volume_m3'] ?? ''); ?>"></div>
                </div>
            </div>

            <input type="submit" value="Update Stock Location">
        </form>
         <div class="nav-links">
            <a href="admin_stock_locations_list.php?warehouse_id=<?php echo htmlspecialchars($location['warehouse_id']); ?>">Back to Locations List</a>
        </div>
    </div>
</body>
</html>
