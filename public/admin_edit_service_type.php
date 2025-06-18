<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/finance_functions.php';

$page_title = "Admin - Edit Service Type";
$message = '';
$message_type = '';
$service_type_id = $_GET['id'] ?? null;

if (!$service_type_id || !filter_var($service_type_id, FILTER_VALIDATE_INT)) {
    $_SESSION['flash_message'] = "Invalid Service Type ID."; $_SESSION['flash_message_type'] = "error";
    header('Location: admin_service_types_list.php'); exit;
}
$service_type_id = (int)$service_type_id;
$st = get_service_type_by_id($service_type_id);

if (!$st) {
    $_SESSION['flash_message'] = "Service Type not found."; $_SESSION['flash_message_type'] = "error";
    header('Location: admin_service_types_list.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_edit_service_type'] ?? '')) {
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
        $message = 'Service Code and Name are required.'; $message_type = 'error';
        $st = array_merge($st, $data); // Keep submitted values in form
    } else {
        unset($_SESSION['error_message']);
        if (update_service_type($service_type_id, $data)) {
            $_SESSION['flash_message'] = 'Service Type "' . htmlspecialchars($data['name']) . '" updated successfully!';
            $_SESSION['flash_message_type'] = 'success';
            header('Location: admin_service_types_list.php'); exit;
        } else {
            $message = $_SESSION['error_message'] ?? 'Failed to update service type.'; $message_type = 'error';
            unset($_SESSION['error_message']);
            $st = array_merge($st, $data); // Keep submitted values
        }
    }
}

if (empty($_SESSION['csrf_token_edit_service_type'])) {
    $_SESSION['csrf_token_edit_service_type'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title . " - " . $st['name']); ?></title>
    <style> /* Same styles as add page */
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
        <h1><?php echo htmlspecialchars($page_title . " (ID: ".$st['id'].")"); ?></h1>
        <?php if ($message): ?><div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <form action="admin_edit_service_type.php?id=<?php echo $service_type_id; ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_edit_service_type']); ?>">
            <div><label for="service_code">Service Code:</label><input type="text" id="service_code" name="service_code" value="<?php echo htmlspecialchars($st['service_code'] ?? ''); ?>" required></div>
            <div><label for="name">Name:</label><input type="text" id="name" name="name" value="<?php echo htmlspecialchars($st['name'] ?? ''); ?>" required></div>
            <div><label for="description">Description:</label><textarea id="description" name="description"><?php echo htmlspecialchars($st['description'] ?? ''); ?></textarea></div>
            <div><label for="unit">Unit:</label><input type="text" id="unit" name="unit" value="<?php echo htmlspecialchars($st['unit'] ?? ''); ?>"></div>
            <div><label><input type="checkbox" name="is_active" value="1" <?php echo ($st['is_active'] ?? false) ? 'checked' : ''; ?>> Is Active</label></div>
            <input type="submit" value="Update Service Type">
        </form>
        <div class="nav-links"><a href="admin_service_types_list.php">Back to List</a></div>
    </div>
</body>
</html>
