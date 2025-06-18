<?php
require_once __DIR__ . '/../src/auth_check.php'; // Admin access
require_once __DIR__ . '/../src/warehouse_functions.php';

$page_title = "Admin - Add New Warehouse";
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_add_warehouse'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? null;
    $is_active = isset($_POST['is_active']);

    if (empty($name)) {
        $message = 'Warehouse name is required.';
        $message_type = 'error';
    } else {
        unset($_SESSION['error_message']); // Clear previous specific error
        $warehouse_id = create_warehouse($name, $address, $is_active);
        if ($warehouse_id) {
            $message = 'Warehouse "' . htmlspecialchars($name) . '" added successfully!';
            $message_type = 'success';
            $_POST = []; // Clear form fields on success
        } else {
            $message = $_SESSION['error_message'] ?? 'Failed to add warehouse.';
            $message_type = 'error';
            unset($_SESSION['error_message']);
        }
    }
}

if (empty($_SESSION['csrf_token_add_warehouse'])) {
    $_SESSION['csrf_token_add_warehouse'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <!-- <link rel="stylesheet" href="css/admin_style.css"> --> <!-- Assuming a shared admin CSS -->
     <style>
         /* Basic styles if admin_style.css is not comprehensive */
         body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
         .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 700px; margin: 40px auto;}
         h1 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0; }
         label { display: block; margin-top: 15px; font-weight: bold; margin-bottom: 5px; }
         input[type="text"], textarea {
             width: 100%;
             padding: 10px;
             margin-top: 5px;
             border: 1px solid #ddd;
             border-radius: 5px;
             box-sizing: border-box;
             font-size: 1rem;
         }
         input[type="checkbox"] { margin-top: 10px; margin-right: 8px; vertical-align: middle; width:auto;}
         textarea { min-height: 100px; resize: vertical;}
         input[type="submit"] {
             background-color: #007bff;
             color: white;
             padding: 12px 20px;
             border: none;
             border-radius: 5px;
             cursor: pointer;
             font-size: 1rem;
             margin-top: 25px;
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
        <span>Admin Panel</span>
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

        <form action="admin_add_warehouse.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_add_warehouse']); ?>">

            <div>
                <label for="name">Warehouse Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="address">Address (Optional):</label>
                <textarea id="address" name="address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>
            <div>
                <label for="is_active" style="display:inline-block;">
                    <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo (isset($_POST['is_active']) || !$_SERVER['REQUEST_METHOD'] === 'POST') ? 'checked' : ''; ?>>
                    Is Active
                </label>
            </div>
            <input type="submit" value="Add Warehouse">
        </form>

        <div class="nav-links">
            <a href="admin_warehouses_list.php">Back to Warehouses List</a>
        </div>
    </div>
</body>
</html>
