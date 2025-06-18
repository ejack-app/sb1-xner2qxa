<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/finance_functions.php';

$page_title = "Admin - Add Service Type";
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_add_service_type'] ?? '')) {
        die('CSRF token validation failed.');
    }
    $data = [
        'service_code' => $_POST['service_code'] ?? '',
        'name' => $_POST['name'] ?? '',
        'description' => $_POST['description'] ?? null,
        'unit' => $_POST['unit'] ?? null,
        'is_active' => isset($_POST['is_active']),
    ];

    if (empty($data['service_code']) || empty($data['name'])) {
        $message = 'Service Code and Name are required.';
        $message_type = 'error';
    } else {
        unset($_SESSION['error_message']);
        $service_type_id = create_service_type($data);
        if ($service_type_id) {
            $_SESSION['flash_message'] = 'Service Type "' . htmlspecialchars($data['name']) . '" added successfully!';
            $_SESSION['flash_message_type'] = 'success';
            header('Location: admin_service_types_list.php');
            exit;
        } else {
            $message = $_SESSION['error_message'] ?? 'Failed to add service type.';
            $message_type = 'error';
            unset($_SESSION['error_message']);
        }
    }
}

if (empty($_SESSION['csrf_token_add_service_type'])) {
    $_SESSION['csrf_token_add_service_type'] = bin2hex(random_bytes(32));
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
        input[type="text"], textarea, select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-size: 1rem;}
        input[type="checkbox"] { margin-top: 10px; margin-right: 8px; vertical-align: middle; width:auto;}
        textarea { min-height: 80px; resize: vertical;}
        input[type="submit"] { background-color: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; margin-top: 25px; }
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size:0.95rem;}
        .message.error { background-color: #f8d7da; color: #721c24;}
        .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align: center;}
        .nav-links a { margin: 0 10px; text-decoration: none; color: #007bff; }
        .top-nav { padding: 10px 20px; background-color: #333; color: #fff; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .top-nav a { color: #fff; text-decoration: none; margin-left: 15px; }
    </style>
</head>
<body>
    <div class="top-nav"><span>Admin Panel - Finance</span><div><a href="admin_service_types_list.php">Service Types</a><a href="logout.php">Logout</a></div></div>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <?php if ($message): ?><div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <form action="admin_add_service_type.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_add_service_type']); ?>">
            <div><label for="service_code">Service Code (Unique, e.g., DEL_STD):</label><input type="text" id="service_code" name="service_code" value="<?php echo htmlspecialchars($_POST['service_code'] ?? ''); ?>" required></div>
            <div><label for="name">Name (e.g., Standard Delivery):</label><input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required></div>
            <div><label for="description">Description:</label><textarea id="description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea></div>
            <div><label for="unit">Unit (e.g., per_shipment, per_kg):</label><input type="text" id="unit" name="unit" value="<?php echo htmlspecialchars($_POST['unit'] ?? ''); ?>"></div>
            <div><label><input type="checkbox" name="is_active" value="1" <?php echo (isset($_POST['is_active']) || !$_POST) ? 'checked' : ''; ?>> Is Active</label></div>
            <input type="submit" value="Add Service Type">
        </form>
        <div class="nav-links"><a href="admin_service_types_list.php">Back to List</a></div>
    </div>
</body>
</html>
