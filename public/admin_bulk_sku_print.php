<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/item_functions.php';

$page_title = "Admin - Bulk SKU/Barcode Print";

// Fetch all items for selection (consider pagination for very large lists in a real app)
$items_result = get_all_items([], 'i.name', 'ASC', 500, 0); // Max 500 items for selection page
$selectable_items = $items_result['items'];

if (empty($_SESSION['csrf_token_bulk_print'])) {
    $_SESSION['csrf_token_bulk_print'] = bin2hex(random_bytes(32));
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
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 900px; margin: 40px auto;}
        h1, h3 { color: #333; }
        h1 {border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0;}
        h3 {margin-top:25px;}
        .item-selection-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9em; }
        .item-selection-table th, .item-selection-table td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align:middle;}
        .item-selection-table th { background-color: #e9ecef; font-weight:bold;}
        .item-selection-table td { background-color:#fff; }
        tr:nth-child(even) td { background-color: #f8f9fa; }
        .print-options { margin-top:20px; margin-bottom:20px; padding:15px; background-color:#f0f0f0; border-radius:5px; }
        .print-options h3 { margin-top:0; padding-bottom:10px; border-bottom:1px solid #ddd;}
        .print-options label { margin-right: 15px; font-weight:normal;}
        .print-options input[type="checkbox"] { margin-right: 5px; vertical-align:middle;}
        .print-options select {padding:5px; border-radius:4px; border:1px solid #ccc;}
        .submit-btn { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .submit-btn:hover { background-color: #0056b3;}
        .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align:center;}
        .nav-links a { margin:0 10px; text-decoration: none; color:#007bff;}
        .nav-links a:hover {text-decoration:underline;}
        #select-all-items, #deselect-all-items { font-size:0.8em; padding:4px 8px; margin-left:5px; background-color:#6c757d; color:white; border:none; border-radius:3px; cursor:pointer;}
        #select-all-items:hover, #deselect-all-items:hover { background-color:#5a6268;}
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

        <form action="admin_print_preview_skus.php" method="POST" target="_blank"> <!-- Open in new tab -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_bulk_print']); ?>">

            <div class="print-options">
                <h3>Print Options:</h3>
                <label><input type="checkbox" name="print_sku" value="1" checked> Print SKU</label>
                <label><input type="checkbox" name="print_name" value="1" checked> Print Name</label>
                <label><input type="checkbox" name="print_barcode_val" value="1"> Print Barcode Value</label>
                <label><input type="checkbox" name="print_uom" value="1"> Print Unit of Measure</label>
                <br><br>
                <label for="layout_columns">Items per row (approximate):</label>
                <select name="layout_columns" id="layout_columns">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3" selected>3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                </select>
            </div>

            <h3>Select Items to Print:
                <button type="button" id="select-all-items">Select All</button>
                <button type="button" id="deselect-all-items">Deselect All</button>
            </h3>
            <?php if (empty($selectable_items)): ?>
                <p>No items found.</p>
            <?php else: ?>
                <table class="item-selection-table">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Barcode Value</th>
                            <th>Unit of Measure</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($selectable_items as $item): ?>
                            <tr>
                                <td><input type="checkbox" name="item_ids[]" value="<?php echo htmlspecialchars($item['id']); ?>"></td>
                                <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['barcode'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($item['unit_of_measure'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><small>Showing up to 500 items. For more items, use filters on the main items list or implement advanced search here.</small></p>
            <?php endif; ?>

            <p><input type="submit" value="Generate Print Preview" class="submit-btn" <?php echo empty($selectable_items) ? 'disabled' : ''; ?>></p>
        </form>

        <div class="nav-links">
            <a href="admin_items_list.php">Back to Items List</a>
        </div>
    </div>
    <script>
        document.getElementById('select-all-items').addEventListener('click', function() {
            document.querySelectorAll('input[name="item_ids[]"]').forEach(checkbox => checkbox.checked = true);
        });
        document.getElementById('deselect-all-items').addEventListener('click', function() {
            document.querySelectorAll('input[name="item_ids[]"]').forEach(checkbox => checkbox.checked = false);
        });
    </script>
</body>
</html>
