<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/item_functions.php'; // For get_item_by_id and a new update_item_master_data

$page_title = "Admin - Edit Item";
$message = '';
$message_type = '';
$item_id = $_GET['item_id'] ?? null;

if (!$item_id || !filter_var($item_id, FILTER_VALIDATE_INT)) {
    $_SESSION['flash_message'] = "Invalid Item ID.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: admin_items_list.php');
    exit;
}
$item_id = (int)$item_id;

$item = get_item_by_id($item_id); // This fetches aggregated stock, which is fine for display here.

if (!$item) {
    $_SESSION['flash_message'] = "Item not found.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: admin_items_list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_edit_item'] ?? '')) {
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
    ];

    if (empty($data['sku']) || empty($data['name'])) {
        $message = 'SKU and Name are required fields.';
        $message_type = 'error';
        $item = array_merge($item, $data); // Keep submitted values in form
    } else {
        if (function_exists('update_item_master_data')) {
            if (update_item_master_data($item_id, $data)) {
                $_SESSION['flash_message'] = 'Item "' . htmlspecialchars($data['name']) . '" updated successfully!';
                $_SESSION['flash_message_type'] = 'success';
                header('Location: admin_items_list.php'); // Redirect to list page
                exit;
            } else {
                $message = $_SESSION['error_message'] ?? 'Failed to update item.';
                $message_type = 'error';
                unset($_SESSION['error_message']);
                $item = array_merge($item, $data); // Keep submitted values
            }
        } else {
            $message = "Error: update_item_master_data function not found. Item not updated.";
            $message_type = "error";
            $item = array_merge($item, $data);
        }
    }
}

if (empty($_SESSION['csrf_token_edit_item'])) {
    $_SESSION['csrf_token_edit_item'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title . " - " . $item['name']); ?></title>
    <!-- <link rel="stylesheet" href="css/admin_style.css"> -->
    <style>
        body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 900px; margin: 40px auto;}
        h1, h2 { color: #333; }
        h1 {border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0;}
        h2 {border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:25px;}
        label { display: block; margin-top: 12px; font-weight: bold; margin-bottom:5px;}
        input[type="text"], input[type="number"], input[type="url"], textarea, select {
            width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-size:1rem;
        }
        input[type="checkbox"] { margin-top: 10px; margin-right: 8px; vertical-align: middle; width:auto;}
        textarea { resize: vertical; min-height: 80px; }
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        input[type="submit"] { background-color: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; margin-top: 25px; transition: background-color 0.2s;}
        input[type="submit"]:hover { background-color: #0056b3; }
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size:0.95rem;}
        .message.success { background-color: #d4edda; color: #155724; border:1px solid #c3e6cb;}
        .message.error { background-color: #f8d7da; color: #721c24; border:1px solid #f5c6cb;}
        .message.info { background-color: #cfe2ff; color: #052c65; border:1px solid #b6d4fe;}
        .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align:center;}
        .nav-links a { margin:0 10px; text-decoration: none; color:#007bff; }
        .nav-links a:hover {text-decoration:underline;}
        .stock-info { background-color:#f0f0f0; padding:15px; border-radius:5px; margin-top:20px; margin-bottom:20px;}
        .stock-info h3 {margin-top:0; border-bottom:1px solid #ddd; padding-bottom:8px;}
        .stock-info p {margin:5px 0;}
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
        <h1><?php echo htmlspecialchars($page_title . ": " . $item['name']); ?></h1>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form action="admin_edit_item.php?item_id=<?php echo htmlspecialchars($item_id); ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_edit_item']); ?>">

            <h2>Basic Information</h2>
            <div class="grid-container">
                <div><label for="sku">SKU:</label><input type="text" id="sku" name="sku" value="<?php echo htmlspecialchars($item['sku'] ?? ''); ?>" required></div>
                <div><label for="name">Item Name:</label><input type="text" id="name" name="name" value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>" required></div>
            </div>
            <label for="description">Description:</label><textarea id="description" name="description"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
            <div class="grid-container">
                <div><label for="barcode">Barcode:</label><input type="text" id="barcode" name="barcode" value="<?php echo htmlspecialchars($item['barcode'] ?? ''); ?>"></div>
                <div><label for="unit_of_measure">Unit of Measure:</label><input type="text" id="unit_of_measure" name="unit_of_measure" value="<?php echo htmlspecialchars($item['unit_of_measure'] ?? 'PCS'); ?>"></div>
            </div>

            <h2>Pricing & Cost</h2>
            <div class="grid-container">
                <div><label for="default_purchase_price">Purchase Price:</label><input type="number" id="default_purchase_price" name="default_purchase_price" step="0.01" value="<?php echo htmlspecialchars($item['default_purchase_price'] ?? ''); ?>"></div>
                <div><label for="default_selling_price">Selling Price:</label><input type="number" id="default_selling_price" name="default_selling_price" step="0.01" value="<?php echo htmlspecialchars($item['default_selling_price'] ?? ''); ?>"></div>
            </div>

            <h2>Physical Attributes</h2>
            <div class="grid-container">
                <div><label for="weight">Weight (KG):</label><input type="number" id="weight" name="weight" step="0.001" value="<?php echo htmlspecialchars($item['weight'] ?? ''); ?>"></div>
                <div><label for="length">Length (CM):</label><input type="number" id="length" name="length" step="0.01" value="<?php echo htmlspecialchars($item['length'] ?? ''); ?>"></div>
                <div><label for="width">Width (CM):</label><input type="number" id="width" name="width" step="0.01" value="<?php echo htmlspecialchars($item['width'] ?? ''); ?>"></div>
                <div><label for="height">Height (CM):</label><input type="number" id="height" name="height" step="0.01" value="<?php echo htmlspecialchars($item['height'] ?? ''); ?>"></div>
            </div>

            <h2>Other</h2>
            <label for="image_url">Image URL:</label><input type="url" id="image_url" name="image_url" value="<?php echo htmlspecialchars($item['image_url'] ?? ''); ?>">
            <label style="display:inline-block; margin-top:15px;"><input type="checkbox" name="is_active" value="1" <?php echo ($item['is_active'] ?? false) ? 'checked' : ''; ?>> Item is Active</label>

            <input type="submit" value="Update Item Details (Master Data Only)">
        </form>

         <div class="stock-info">
             <h3>Current Aggregated Stock Levels</h3>
             <p>Total Quantity on Hand: <strong><?php echo htmlspecialchars($item['total_quantity_on_hand'] ?? 0); ?></strong></p>
             <p>Total Quantity Allocated: <strong><?php echo htmlspecialchars($item['total_quantity_allocated'] ?? 0); ?></strong></p>
             <p>Total Quantity Available: <strong><?php echo htmlspecialchars($item['total_quantity_available'] ?? 0); ?></strong></p>
             <p><a href="admin_item_stock_details.php?item_id=<?php echo $item_id; ?>">View/Adjust Stock by Location</a> (Page to be created)</p>
         </div>


        <div class="nav-links">
            <a href="admin_items_list.php">Back to Items List</a>
        </div>
    </div>
</body>
</html>
