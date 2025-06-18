<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/vehicle_functions.php';
require_once __DIR__ . '/../src/user_functions.php';

$page_title = "Admin - Edit Vehicle";
$message = '';
$message_type = '';
$vehicle_id = $_GET['id'] ?? null;

if (!$vehicle_id || !filter_var($vehicle_id, FILTER_VALIDATE_INT)) {
    $_SESSION['flash_message'] = "Invalid Vehicle ID.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: admin_vehicles_list.php');
    exit;
}
$vehicle_id = (int)$vehicle_id;

$vehicle = get_vehicle_by_id($vehicle_id);

if (!$vehicle) {
    $_SESSION['flash_message'] = "Vehicle not found.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: admin_vehicles_list.php');
    exit;
}

$available_drivers = get_available_drivers();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_edit_vehicle'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $data = [
        'vehicle_name' => $_POST['vehicle_name'] ?? '',
        'license_plate' => $_POST['license_plate'] ?? null,
        'make' => $_POST['make'] ?? null,
        'model' => $_POST['model'] ?? null,
        'year' => $_POST['year'] ?? null,
        'vin_number' => $_POST['vin_number'] ?? null,
        'vehicle_type' => $_POST['vehicle_type'] ?? '',
        'status' => $_POST['status'] ?? 'Available',
        'current_driver_id' => $_POST['current_driver_id'] ?? null,
        'max_payload_kg' => $_POST['max_payload_kg'] ?? null,
        'max_volume_m3' => $_POST['max_volume_m3'] ?? null,
        'notes' => $_POST['notes'] ?? null,
        'is_active' => isset($_POST['is_active']),
    ];

    if (empty($data['vehicle_name']) || empty($data['vehicle_type'])) {
        $message = 'Vehicle Name and Type are required.';
        $message_type = 'error';
        // To show current (potentially unsaved) values in form:
        foreach($data as $key => $value) $vehicle[$key] = $value;
    } else {
        unset($_SESSION['error_message']);
        if (update_vehicle($vehicle_id, $data)) {
            $_SESSION['flash_message'] = 'Vehicle "' . htmlspecialchars($data['vehicle_name']) . '" updated successfully!';
            $_SESSION['flash_message_type'] = 'success';
            header('Location: admin_vehicles_list.php');
            exit;
        } else {
            $message = $_SESSION['error_message'] ?? 'Failed to update vehicle.';
            $message_type = 'error';
            unset($_SESSION['error_message']);
            foreach($data as $key => $value) $vehicle[$key] = $value;
        }
    }
}

if (empty($_SESSION['csrf_token_edit_vehicle'])) {
    $_SESSION['csrf_token_edit_vehicle'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title . " - " . $vehicle['vehicle_name']); ?></title>
     <style>
        body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 800px; margin: 40px auto;}
        h1 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0; }
        label { display: block; margin-top: 15px; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="number"], textarea, select {
            width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd;
            border-radius: 5px; box-sizing: border-box; font-size: 1rem;
        }
        input[type="checkbox"] { margin-top: 10px; margin-right: 8px; vertical-align: middle; width:auto;}
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
        <span>Admin Panel - Vehicle Management</span>
        <div>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>)</a>
        </div>
    </div>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title . " (ID: " . $vehicle['id'] . ")"); ?></h1>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form action="admin_edit_vehicle.php?id=<?php echo htmlspecialchars($vehicle_id); ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_edit_vehicle']); ?>">

            <div class="grid-container">
                <div><label for="vehicle_name">Vehicle Name/Identifier:</label><input type="text" id="vehicle_name" name="vehicle_name" value="<?php echo htmlspecialchars($vehicle['vehicle_name'] ?? ''); ?>" required></div>
                <div><label for="license_plate">License Plate:</label><input type="text" id="license_plate" name="license_plate" value="<?php echo htmlspecialchars($vehicle['license_plate'] ?? ''); ?>"></div>
            </div>
            <div class="grid-container">
                <div><label for="make">Make:</label><input type="text" id="make" name="make" value="<?php echo htmlspecialchars($vehicle['make'] ?? ''); ?>"></div>
                <div><label for="model">Model:</label><input type="text" id="model" name="model" value="<?php echo htmlspecialchars($vehicle['model'] ?? ''); ?>"></div>
                <div><label for="year">Year:</label><input type="number" id="year" name="year" min="1900" max="<?php echo date('Y') + 1; ?>" value="<?php echo htmlspecialchars($vehicle['year'] ?? ''); ?>"></div>
            </div>
            <div><label for="vin_number">VIN Number:</label><input type="text" id="vin_number" name="vin_number" value="<?php echo htmlspecialchars($vehicle['vin_number'] ?? ''); ?>"></div>

            <div class="grid-container">
                <div><label for="vehicle_type">Vehicle Type:</label>
                    <select id="vehicle_type" name="vehicle_type" required>
                        <option value="">-- Select Type --</option>
                        <?php foreach (VEHICLE_TYPES as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (($vehicle['vehicle_type'] ?? '') === $type) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <?php foreach (VEHICLE_STATUSES as $status_val): ?>
                        <option value="<?php echo htmlspecialchars($status_val); ?>" <?php echo (($vehicle['status'] ?? '') === $status_val) ? 'selected' : ''; ?>><?php echo htmlspecialchars($status_val); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-container">
                 <div><label for="current_driver_id">Current Driver (Optional):</label>
                    <select id="current_driver_id" name="current_driver_id">
                        <option value="">-- None --</option>
                        <?php foreach ($available_drivers as $driver): ?>
                        <option value="<?php echo htmlspecialchars($driver['id']); ?>" <?php echo (($vehicle['current_driver_id'] ?? null) == $driver['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($driver['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-container">
                <div><label for="max_payload_kg">Max Payload (kg):</label><input type="number" id="max_payload_kg" name="max_payload_kg" step="0.01" value="<?php echo htmlspecialchars($vehicle['max_payload_kg'] ?? ''); ?>"></div>
                <div><label for="max_volume_m3">Max Volume (m³):</label><input type="number" id="max_volume_m3" name="max_volume_m3" step="0.001" value="<?php echo htmlspecialchars($vehicle['max_volume_m3'] ?? ''); ?>"></div>
            </div>

            <div><label for="notes">Notes:</label><textarea id="notes" name="notes"><?php echo htmlspecialchars($vehicle['notes'] ?? ''); ?></textarea></div>

            <div>
                <label for="is_active" style="display:inline-block;">
                    <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo ($vehicle['is_active'] ?? false) ? 'checked' : ''; ?>>
                    Is Active
                </label>
            </div>

            <input type="submit" value="Update Vehicle">
        </form>

        <div class="nav-links">
            <a href="admin_vehicles_list.php">Back to Vehicles List</a>
        </div>
    </div>
</body>
</html>
