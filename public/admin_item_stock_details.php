<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/item_functions.php'; // Includes get_item_by_id, get_item_stock_levels_by_location, add_stock_quantity
require_once __DIR__ . '/../src/stock_location_functions.php'; // For get_all_stock_locations_for_select (if adding to new location)
require_once __DIR__ . '/../src/warehouse_functions.php'; // For getting warehouse names

$page_title = "Admin - Item Stock Details & Adjustments";
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

// Fetch item master data (uses aggregated stock, which is fine for display here)
$item = get_item_by_id($item_id);
if (!$item) {
    $_SESSION['flash_message'] = "Item not found.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: admin_items_list.php');
    exit;
}

// Handle Stock Adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_stock'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_adjust_stock'] ?? '')) {
        die('CSRF token validation failed for stock adjustment.');
    }
    $stock_location_id = $_POST['stock_location_id'] ?? null;
    $adjustment_quantity = $_POST['adjustment_quantity'] ?? 0;
    $adjustment_notes = $_POST['adjustment_notes'] ?? ''; // For future transaction log

    if (empty($stock_location_id) || !is_numeric($adjustment_quantity)) {
        $message = 'Invalid data for stock adjustment.';
        $message_type = 'error';
    } else {
        $adjustment_quantity = (int)$adjustment_quantity;
        if ($adjustment_quantity == 0) {
            $message = 'Adjustment quantity cannot be zero.';
            $message_type = 'info';
        } else {
            unset($_SESSION['error_message']);
            // add_stock_quantity handles both positive and negative adjustments to quantity_on_hand
            if (add_stock_quantity($item_id, (int)$stock_location_id, $adjustment_quantity)) {
                $message = 'Stock quantity adjusted successfully for location ID ' . htmlspecialchars($stock_location_id) . '.';
                $message_type = 'success';
                // Consider adding to a transaction log here in the future
                // Refresh item data to show updated totals
                $item = get_item_by_id($item_id);
            } else {
                $message = $_SESSION['error_message'] ?? 'Failed to adjust stock quantity.';
                $message_type = 'error';
                unset($_SESSION['error_message']);
            }
        }
    }
}

 // Handle Adding Stock to a New Location for this Item
 if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock_to_new_location'])) {
    if (!isset($_POST['csrf_token_add_new_loc']) || $_POST['csrf_token_add_new_loc'] !== ($_SESSION['csrf_token_add_new_loc'] ?? '')) {
        die('CSRF token validation failed for adding stock to new location.');
    }
    $new_stock_location_id = $_POST['new_stock_location_id'] ?? null;
    $new_quantity_on_hand = $_POST['new_quantity_on_hand'] ?? 0;

    if (empty($new_stock_location_id) || !is_numeric($new_quantity_on_hand) || (int)$new_quantity_on_hand < 0) {
        $message = 'Invalid data for adding stock to a new location.';
        $message_type = 'error';
    } else {
        unset($_SESSION['error_message']);
        // add_stock_quantity with positive value will create the inventory_stock record if it doesn't exist
        if (add_stock_quantity($item_id, (int)$new_stock_location_id, (int)$new_quantity_on_hand)) {
            $message = 'Stock added to new location successfully.';
            $message_type = 'success';
            // Refresh item data
            $item = get_item_by_id($item_id);
        } else {
            $message = $_SESSION['error_message'] ?? 'Failed to add stock to new location.';
            $message_type = 'error';
            unset($_SESSION['error_message']);
        }
    }
}


// Fetch detailed stock levels by location for this item AFTER any adjustments
$stock_levels = get_item_stock_levels_by_location($item_id);

// Get all stock locations to populate dropdown for adding stock to a new location
// Filter out locations where the item already has stock.
$existing_location_ids = array_map(function($sl){ return $sl['stock_location_id']; }, $stock_levels);
$all_available_locations = get_all_stock_locations_for_select(); // Fetches id, location_code, warehouse_id
$locations_for_new_stock = array_filter($all_available_locations, function($loc) use ($existing_location_ids) {
    return !in_array($loc['id'], $existing_location_ids);
});
// For better display in dropdown, group by warehouse
 $locations_for_new_stock_grouped = [];
 if ($locations_for_new_stock) {
     $wh_names = [];
     foreach(get_all_warehouses(true) as $wh) $wh_names[$wh['id']] = $wh['name'];

     foreach($locations_for_new_stock as $loc){
         $wh_name = $wh_names[$loc['warehouse_id']] ?? 'Unknown Warehouse';
         $locations_for_new_stock_grouped[$wh_name][] = $loc;
     }
 }


if (empty($_SESSION['csrf_token_adjust_stock'])) {
    $_SESSION['csrf_token_adjust_stock'] = bin2hex(random_bytes(32));
}
if (empty($_SESSION['csrf_token_add_new_loc'])) {
    $_SESSION['csrf_token_add_new_loc'] = bin2hex(random_bytes(32));
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
        h1, h2, h3 { color: #333; }
        h1 {border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0;}
        h2 { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 25px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9em; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align:middle; }
        th { background-color: #e9ecef; font-weight:bold;}
        td { background-color:#fff; }
        tr:nth-child(even) td { background-color: #f8f9fa; }
        .item-info { margin-bottom: 20px; background-color: #e7f3fe; padding: 15px; border-radius: 5px; border:1px solid #bdf;}
        .item-info h3 {margin-top:0;}
        .adjustment-form { display: flex; gap: 10px; align-items: center; margin:0; }
        .adjustment-form input[type="number"] { width: 80px; padding: 8px; font-size:0.9em; }
        .adjustment-form input[type="submit"] { padding: 8px 12px; font-size: 0.9em; background-color:#5cb85c; color:white; border:none; border-radius:4px; cursor:pointer;}
        .adjustment-form input[type="submit"]:hover {background-color:#4cae4c;}
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size:0.95rem;}
        .message.success { background-color: #d4edda; color: #155724; border:1px solid #c3e6cb;}
        .message.error { background-color: #f8d7da; color: #721c24; border:1px solid #f5c6cb;}
        .message.info { background-color: #d1ecf1; color: #0c5460; border:1px solid #bee5eb;}
        .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align:center;}
        .nav-links a { margin:0 10px; text-decoration: none; color:#007bff;}
        .nav-links a:hover {text-decoration:underline;}
        .no-stock { text-align:center; padding:15px; color: #777; background-color:#f8f9fa; border:1px solid #ddd; border-radius:5px;}
        .add-new-location-stock-form { margin-top:20px; padding:20px; background-color:#f9f9f9; border:1px solid #eee; border-radius:5px; }
        .add-new-location-stock-form label {display:block; margin-bottom:5px; font-weight:bold;}
        .add-new-location-stock-form select, .add-new-location-stock-form input[type="number"] {width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:5px; box-sizing:border-box;}
        .add-new-location-stock-form input[type="submit"] {background-color:#007bff; color:white; padding:10px 15px; border:none; border-radius:5px; cursor:pointer;}
        .add-new-location-stock-form input[type="submit"]:hover {background-color:#0056b3;}
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

        <div class="item-info">
            <h3>Item: <?php echo htmlspecialchars($item['name']); ?> (SKU: <?php echo htmlspecialchars($item['sku']); ?>)</h3>
            <p>Description: <?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></p>
            <p>Current Total Stock:
                On Hand: <strong><?php echo htmlspecialchars($item['total_quantity_on_hand'] ?? 0); ?></strong> |
                Allocated: <strong><?php echo htmlspecialchars($item['total_quantity_allocated'] ?? 0); ?></strong> |
                Available: <strong><?php echo htmlspecialchars($item['total_quantity_available'] ?? 0); ?></strong>
            </p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <h2>Stock Levels by Location</h2>
        <?php if (empty($stock_levels)): ?>
            <p class="no-stock">This item has no stock recorded at any specific location yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Warehouse</th>
                        <th>Location Code</th>
                        <th>Qty on Hand</th>
                        <th>Qty Allocated</th>
                        <th>Qty Available</th>
                        <th>Adjust Stock (On Hand)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stock_levels as $sl): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sl['warehouse_name']); ?></td>
                            <td><?php echo htmlspecialchars($sl['location_code']); ?></td>
                            <td><?php echo htmlspecialchars($sl['quantity_on_hand']); ?></td>
                            <td><?php echo htmlspecialchars($sl['quantity_allocated']); ?></td>
                            <td><?php echo htmlspecialchars($sl['quantity_on_hand'] - $sl['quantity_allocated']); ?></td>
                            <td>
                                <form action="admin_item_stock_details.php?item_id=<?php echo $item_id; ?>" method="POST" class="adjustment-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_adjust_stock']); ?>">
                                    <input type="hidden" name="stock_location_id" value="<?php echo htmlspecialchars($sl['stock_location_id']); ?>">
                                    <input type="number" name="adjustment_quantity" placeholder="+/- Qty" required title="Enter positive to add, negative to subtract">
                                    <input type="submit" name="adjust_stock" value="Adjust">
                                    <!-- <label for="adj_notes_<?php echo $sl['stock_location_id']; ?>">Notes:</label>
                                    <input type="text" id="adj_notes_<?php echo $sl['stock_location_id']; ?>" name="adjustment_notes" placeholder="Notes (optional)" style="width:100px;"> -->
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="add-new-location-stock-form">
            <h2>Add Stock to a New Location for this Item</h2>
            <?php if (empty($locations_for_new_stock_grouped)): ?>
                <p>This item is either stocked at all available locations, or no stock locations are defined in the system yet.</p>
            <?php else: ?>
                <form action="admin_item_stock_details.php?item_id=<?php echo $item_id; ?>" method="POST">
                    <input type="hidden" name="csrf_token_add_new_loc" value="<?php echo htmlspecialchars($_SESSION['csrf_token_add_new_loc']); ?>">
                    <div>
                        <label for="new_stock_location_id">Select Location:</label>
                        <select id="new_stock_location_id" name="new_stock_location_id" required>
                            <option value="">-- Select a Location --</option>
                            <?php foreach ($locations_for_new_stock_grouped as $warehouseName => $locations): ?>
                                <optgroup label="<?php echo htmlspecialchars($warehouseName); ?>">
                                    <?php foreach ($locations as $loc): ?>
                                        <option value="<?php echo htmlspecialchars($loc['id']); ?>">
                                            <?php echo htmlspecialchars($loc['location_code']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="new_quantity_on_hand">Quantity on Hand:</label>
                        <input type="number" id="new_quantity_on_hand" name="new_quantity_on_hand" min="0" value="0" required>
                    </div>
                    <input type="submit" name="add_stock_to_new_location" value="Add Stock at This Location">
                </form>
            <?php endif; ?>
        </div>


        <div class="nav-links">
            <a href="admin_edit_item.php?item_id=<?php echo $item_id; ?>">Back to Edit Item</a> |
            <a href="admin_items_list.php">Items List</a>
        </div>
    </div>
</body>
</html>
