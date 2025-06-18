<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/box_functions.php';

$page_title = "Admin - Add New Box Definition";
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_add_box'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $data = [
        'name' => $_POST['name'] ?? '',
        'length_cm' => $_POST['length_cm'] ?? '',
        'width_cm' => $_POST['width_cm'] ?? '',
        'height_cm' => $_POST['height_cm'] ?? '',
        'max_weight_kg' => $_POST['max_weight_kg'] ?? null,
        'empty_box_weight_kg' => $_POST['empty_box_weight_kg'] ?? null,
        'notes' => $_POST['notes'] ?? null,
        'is_active' => isset($_POST['is_active']),
    ];

    if (empty($data['name']) || !is_numeric($data['length_cm']) || !is_numeric($data['width_cm']) || !is_numeric($data['height_cm']) ||
        (float)$data['length_cm'] <=0 || (float)$data['width_cm'] <=0 || (float)$data['height_cm'] <=0 ) {
        $message = 'Name and valid positive dimensions (Length, Width, Height) are required.';
        $message_type = 'error';
    } else {
        unset($_SESSION['error_message']);
        $box_id = create_box_definition($data);
        if ($box_id) {
            $message = 'Box Definition "' . htmlspecialchars($data['name']) . '" added successfully!';
            $message_type = 'success';
            $_POST = []; // Clear form fields on success
        } else {
            $message = $_SESSION['error_message'] ?? 'Failed to add box definition.';
            $message_type = 'error';
            unset($_SESSION['error_message']);
        }
    }
}

if (empty($_SESSION['csrf_token_add_box'])) {
    $_SESSION['csrf_token_add_box'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 700px; margin: 40px auto;}
        h1 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0; }
        label { display: block; margin-top: 15px; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="number"], textarea {
            width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd;
            border-radius: 5px; box-sizing: border-box; font-size: 1rem;
        }
        input[type="checkbox"] { margin-top: 10px; margin-right: 8px; vertical-align: middle; width:auto;}
        textarea { min-height: 80px; resize: vertical;}
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
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
        <span>Admin Panel - Box Management</span>
        <div>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>)</a>
        </div>
    </div>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form action="admin_add_box.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_add_box']); ?>">

            <div><label for="name">Box Name / Identifier:</label><input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required></div>

            <div class="grid-container">
                <div><label for="length_cm">Length (cm):</label><input type="number" id="length_cm" name="length_cm" step="0.01" min="0.1" value="<?php echo htmlspecialchars($_POST['length_cm'] ?? ''); ?>" required></div>
                <div><label for="width_cm">Width (cm):</label><input type="number" id="width_cm" name="width_cm" step="0.01" min="0.1" value="<?php echo htmlspecialchars($_POST['width_cm'] ?? ''); ?>" required></div>
                <div><label for="height_cm">Height (cm):</label><input type="number" id="height_cm" name="height_cm" step="0.01" min="0.1" value="<?php echo htmlspecialchars($_POST['height_cm'] ?? ''); ?>" required></div>
            </div>

            <div class="grid-container">
                <div><label for="max_weight_kg">Max Weight Capacity (kg, Optional):</label><input type="number" id="max_weight_kg" name="max_weight_kg" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['max_weight_kg'] ?? ''); ?>"></div>
                <div><label for="empty_box_weight_kg">Empty Box Weight (kg, Optional):</label><input type="number" id="empty_box_weight_kg" name="empty_box_weight_kg" step="0.001" min="0" value="<?php echo htmlspecialchars($_POST['empty_box_weight_kg'] ?? ''); ?>"></div>
            </div>

            <div><label for="notes">Notes (Optional):</label><textarea id="notes" name="notes"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea></div>

            <div>
                <label for="is_active" style="display:inline-block;">
                    <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo (isset($_POST['is_active']) || !$_SERVER['REQUEST_METHOD'] === 'POST') ? 'checked' : ''; ?>>
                    Is Active
                </label>
            </div>

            <input type="submit" value="Add Box Definition">
        </form>

        <div class="nav-links">
            <a href="admin_boxes_list.php">Back to Box Definitions List</a>
        </div>
    </div>
</body>
</html>
