<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/item_functions.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_add_item'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $data = [
        'sku' => $_POST['sku'] ?? '',
        'name' => $_POST['name'] ?? '',
        'description' => $_POST['description'] ?? null,
        'barcode' => $_POST['barcode'] ?? null,
        'unit_of_measure' => $_POST['unit_of_measure'] ?? null,
        'default_purchase_price' => !empty($_POST['default_purchase_price']) ? (float)$_POST['default_purchase_price'] : null,
        'default_selling_price' => !empty($_POST['default_selling_price']) ? (float)$_POST['default_selling_price'] : null,
        'weight' => !empty($_POST['weight']) ? (float)$_POST['weight'] : null,
        'length' => !empty($_POST['length']) ? (float)$_POST['length'] : null,
        'width' => !empty($_POST['width']) ? (float)$_POST['width'] : null,
        'height' => !empty($_POST['height']) ? (float)$_POST['height'] : null,
        'image_url' => $_POST['image_url'] ?? null,
        'is_active' => isset($_POST['is_active']),
        'initial_quantity_on_hand' => !empty($_POST['initial_quantity_on_hand']) ? (int)$_POST['initial_quantity_on_hand'] : 0,
        'low_stock_threshold' => !empty($_POST['low_stock_threshold']) ? (int)$_POST['low_stock_threshold'] : null,
        'location_in_warehouse' => $_POST['location_in_warehouse'] ?? null,
    ];

    if (empty($data['sku']) || empty($data['name'])) {
        $message = 'SKU and Name are required fields.';
        $message_type = 'error';
    } else {
        unset($_SESSION['error_message']); // Clear previous specific error
        $item_id = create_item($data);
        if ($item_id) {
            $message = 'Item "' . htmlspecialchars($data['name']) . '" added successfully with ID: ' . $item_id;
            $message_type = 'success';
            // Clear form fields after success by redirecting or resetting POST
            $_POST = [];
        } else {
            $message = $_SESSION['error_message'] ?? 'Failed to add item. Please check the details.';
            $message_type = 'error';
            unset($_SESSION['error_message']);
        }
    }
}

if (empty($_SESSION['csrf_token_add_item'])) {
    $_SESSION['csrf_token_add_item'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Add New Item</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 800px; margin: auto;}
        h1 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        label { display: block; margin-top: 12px; font-weight: bold; }
        input[type="text"], input[type="number"], input[type="url"], textarea, select {
            width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
        }
        input[type="checkbox"] { margin-top: 10px; margin-right: 5px; vertical-align: middle;}
        textarea { resize: vertical; min-height: 80px; }
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .grid-item { /* no specific style needed now */ }
        input[type="submit"] { background-color: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 25px; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align:center; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-section { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px dotted #ccc;}
        .form-section:last-of-type { border-bottom: none; }
        nav { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        nav a { margin-right: 15px; text-decoration: none; color: #007bff; }
        nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <nav>
            <a href="logout.php" style="float: right;">Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>)</a>
            Logged in as: <strong><?php echo htmlspecialchars($_SESSION['user_role'] ?? ''); ?></strong> |
            <a href="admin_items_list.php">Items List</a> |
            <a href="admin_orders_list.php">Orders List</a> |
            <a href="admin_create_order.php">Create Order</a>
        </nav>
        <h1>Add New Inventory Item</h1>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form action="admin_add_item.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_add_item']); ?>">

            <div class="form-section">
                <h2>Basic Information</h2>
                <div class="grid-container">
                    <div class="grid-item">
                        <label for="sku">SKU (Stock Keeping Unit):</label>
                        <input type="text" id="sku" name="sku" value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>" required>
                    </div>
                    <div class="grid-item">
                        <label for="name">Item Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                </div>
                <label for="description">Description:</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                <div class="grid-container">
                   <div class="grid-item">
                        <label for="barcode">Barcode (Optional):</label>
                        <input type="text" id="barcode" name="barcode" value="<?php echo htmlspecialchars($_POST['barcode'] ?? ''); ?>">
                   </div>
                   <div class="grid-item">
                        <label for="unit_of_measure">Unit of Measure (e.g., PCS, KG, BOX):</label>
                        <input type="text" id="unit_of_measure" name="unit_of_measure" value="<?php echo htmlspecialchars($_POST['unit_of_measure'] ?? 'PCS'); ?>">
                   </div>
                </div>
            </div>

            <div class="form-section">
                <h2>Pricing & Cost</h2>
                <div class="grid-container">
                    <div class="grid-item">
                        <label for="default_purchase_price">Default Purchase Price (Optional):</label>
                        <input type="number" id="default_purchase_price" name="default_purchase_price" step="0.01" value="<?php echo htmlspecialchars($_POST['default_purchase_price'] ?? ''); ?>">
                    </div>
                    <div class="grid-item">
                        <label for="default_selling_price">Default Selling Price (Optional):</label>
                        <input type="number" id="default_selling_price" name="default_selling_price" step="0.01" value="<?php echo htmlspecialchars($_POST['default_selling_price'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2>Physical Attributes</h2>
                <div class="grid-container">
                    <div class="grid-item">
                        <label for="weight">Weight (KG) (Optional):</label>
                        <input type="number" id="weight" name="weight" step="0.001" value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>">
                    </div>
                    <div class="grid-item">
                        <label for="length">Length (CM) (Optional):</label>
                        <input type="number" id="length" name="length" step="0.01" value="<?php echo htmlspecialchars($_POST['length'] ?? ''); ?>">
                    </div>
                    <div class="grid-item">
                        <label for="width">Width (CM) (Optional):</label>
                        <input type="number" id="width" name="width" step="0.01" value="<?php echo htmlspecialchars($_POST['width'] ?? ''); ?>">
                    </div>
                    <div class="grid-item">
                        <label for="height">Height (CM) (Optional):</label>
                        <input type="number" id="height" name="height" step="0.01" value="<?php echo htmlspecialchars($_POST['height'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2>Initial Stock & Location</h2>
                <div class="grid-container">
                    <div class="grid-item">
                        <label for="initial_quantity_on_hand">Initial Quantity on Hand:</label>
                        <input type="number" id="initial_quantity_on_hand" name="initial_quantity_on_hand" value="<?php echo htmlspecialchars($_POST['initial_quantity_on_hand'] ?? '0'); ?>" min="0" required>
                    </div>
                    <div class="grid-item">
                        <label for="low_stock_threshold">Low Stock Threshold (Optional):</label>
                        <input type="number" id="low_stock_threshold" name="low_stock_threshold" min="0" value="<?php echo htmlspecialchars($_POST['low_stock_threshold'] ?? ''); ?>">
                    </div>
                </div>
                <label for="location_in_warehouse">Initial Location (Optional):</label>
                <input type="text" id="location_in_warehouse" name="location_in_warehouse" value="<?php echo htmlspecialchars($_POST['location_in_warehouse'] ?? ''); ?>">
            </div>

            <div class="form-section">
               <h2>Other</h2>
                <label for="image_url">Image URL (Optional):</label>
                <input type="url" id="image_url" name="image_url" value="<?php echo htmlspecialchars($_POST['image_url'] ?? ''); ?>">

                <label for="is_active">
                    <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo (isset($_POST['is_active']) || !$_POST) ? 'checked' : ''; ?>>
                    Item is Active
                </label>
            </div>

            <input type="submit" value="Add Item">
        </form>
    </div>
</body>
</html>
