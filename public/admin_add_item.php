<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/item_functions.php';
require_once __DIR__ . '/../src/warehouse_functions.php'; // For warehouse dropdown
require_once __DIR__ . '/../src/stock_location_functions.php'; // For stock location dropdown

$page_title = "Admin - Add New Item";
$message = '';
$message_type = '';

$warehouses = get_all_warehouses(true); // Get only active warehouses
$all_stock_locations_grouped = [];
foreach ($warehouses as $wh) {
    $all_stock_locations_grouped[$wh['id']] = get_all_stock_locations_for_select($wh['id']);
}
// Convert to JSON for JavaScript
$warehouses_json = json_encode(array_map(function($wh){ return ['id' => $wh['id'], 'name' => $wh['name']]; }, $warehouses));
$locations_json = json_encode($all_stock_locations_grouped);


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
        'stock_entries' => [], // Initialize for new stock entry structure
    ];

    // Process stock entries
    if (isset($_POST['stock_location_ids']) && is_array($_POST['stock_location_ids'])) {
        for ($i = 0; $i < count($_POST['stock_location_ids']); $i++) {
            $loc_id = $_POST['stock_location_ids'][$i] ?? null;
            $qty = $_POST['stock_quantities'][$i] ?? 0;
            $threshold = $_POST['stock_thresholds'][$i] ?? null;

            if (!empty($loc_id) && is_numeric($qty) && $qty >= 0) {
                $data['stock_entries'][] = [
                    'stock_location_id' => (int)$loc_id,
                    'quantity_on_hand' => (int)$qty,
                    'low_stock_threshold' => !empty($threshold) ? (int)$threshold : null,
                ];
            }
        }
    }


    if (empty($data['sku']) || empty($data['name'])) {
        $message = 'SKU and Name are required fields.';
        $message_type = 'error';
    } else {
        unset($_SESSION['error_message']);
        $item_id = create_item($data); // create_item was refactored to handle 'stock_entries'
        if ($item_id) {
            $message = 'Item "' . htmlspecialchars($data['name']) . '" added successfully with ID: ' . $item_id;
            $message_type = 'success';
            $_POST = []; // Clear form fields on success
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
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <!-- <link rel="stylesheet" href="css/admin_style.css"> -->
    <style>
        body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 900px; margin: 40px auto;}
        h1, h2, h3 { color: #333; }
        h1 {border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0;}
        h2 {border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:25px;}
        label { display: block; margin-top: 12px; font-weight: bold; margin-bottom:5px; }
        input[type="text"], input[type="number"], input[type="url"], textarea, select {
            width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-size:1rem;
        }
        input[type="checkbox"] { margin-top: 10px; margin-right: 8px; vertical-align: middle; width:auto;}
        textarea { resize: vertical; min-height: 80px; }
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .stock-entry-row { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 10px; align-items: flex-end; margin-bottom:10px; padding:10px; background-color:#f0f0f0; border-radius:4px;}
        .stock-entry-row label {font-size:0.9em; margin-top:0; margin-bottom:3px;}
        .stock-entry-row select, .stock-entry-row input {margin-top:0; padding:8px; font-size:0.95rem;}
        #add-stock-entry { background-color: #28a745; color:white; border:none; padding: 8px 15px; cursor:pointer; border-radius:5px; margin-top:10px; font-size:0.95em; }
        #add-stock-entry:hover { background-color: #218838;}
        .remove-stock-entry { background-color: #dc3545; color:white; border:none; padding: 8px 15px; cursor:pointer; border-radius:5px; font-size:0.95em; height:38px; line-height:1.3;}
        .remove-stock-entry:hover { background-color: #c82333;}

        input[type="submit"] { background-color: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; margin-top: 25px; transition: background-color 0.2s; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size:0.95rem; }
        .message.success { background-color: #d4edda; color: #155724; border:1px solid #c3e6cb;}
        .message.error { background-color: #f8d7da; color: #721c24; border:1px solid #f5c6cb;}
        .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align:center;}
        .nav-links a { margin:0 10px; text-decoration: none; color:#007bff; }
        .nav-links a:hover {text-decoration:underline;}
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

        <form action="admin_add_item.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_add_item']); ?>">

            <h2>Basic Information</h2>
            <div class="grid-container">
                <div><label for="sku">SKU:</label><input type="text" id="sku" name="sku" value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>" required></div>
                <div><label for="name">Item Name:</label><input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required></div>
            </div>
            <label for="description">Description:</label><textarea id="description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            <div class="grid-container">
                <div><label for="barcode">Barcode:</label><input type="text" id="barcode" name="barcode" value="<?php echo htmlspecialchars($_POST['barcode'] ?? ''); ?>"></div>
                <div><label for="unit_of_measure">Unit of Measure:</label><input type="text" id="unit_of_measure" name="unit_of_measure" value="<?php echo htmlspecialchars($_POST['unit_of_measure'] ?? 'PCS'); ?>"></div>
            </div>

            <h2>Pricing & Cost</h2>
            <div class="grid-container">
                <div><label for="default_purchase_price">Purchase Price:</label><input type="number" id="default_purchase_price" name="default_purchase_price" step="0.01" value="<?php echo htmlspecialchars($_POST['default_purchase_price'] ?? ''); ?>"></div>
                <div><label for="default_selling_price">Selling Price:</label><input type="number" id="default_selling_price" name="default_selling_price" step="0.01" value="<?php echo htmlspecialchars($_POST['default_selling_price'] ?? ''); ?>"></div>
            </div>

            <h2>Physical Attributes</h2>
            <div class="grid-container">
                <div><label for="weight">Weight (KG):</label><input type="number" id="weight" name="weight" step="0.001" value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>"></div>
                <div><label for="length">Length (CM):</label><input type="number" id="length" name="length" step="0.01" value="<?php echo htmlspecialchars($_POST['length'] ?? ''); ?>"></div>
                <div><label for="width">Width (CM):</label><input type="number" id="width" name="width" step="0.01" value="<?php echo htmlspecialchars($_POST['width'] ?? ''); ?>"></div>
                <div><label for="height">Height (CM):</label><input type="number" id="height" name="height" step="0.01" value="<?php echo htmlspecialchars($_POST['height'] ?? ''); ?>"></div>
            </div>

            <h2>Initial Stock Entries</h2>
            <div id="stock-entries-container">
                <!-- Stock entry rows will be added here by JS -->
            </div>
            <button type="button" id="add-stock-entry">Add Stock Entry</button>

            <h2>Other</h2>
            <label for="image_url">Image URL:</label><input type="url" id="image_url" name="image_url" value="<?php echo htmlspecialchars($_POST['image_url'] ?? ''); ?>">
            <label style="display:inline-block; margin-top:15px;"><input type="checkbox" name="is_active" value="1" <?php echo (isset($_POST['is_active']) || !$_SERVER['REQUEST_METHOD'] === 'POST') ? 'checked' : ''; ?>> Item is Active</label>

            <input type="submit" value="Add Item">
        </form>

        <div class="nav-links">
            <a href="admin_items_list.php">Back to Items List</a>
        </div>
    </div>

    <script>
        const warehouses = <?php echo $warehouses_json; ?>;
        const locationsByWarehouse = <?php echo $locations_json; ?>;
        const stockEntriesContainer = document.getElementById('stock-entries-container');
        const addStockEntryButton = document.getElementById('add-stock-entry');

        function createStockEntryRow(entryData = {}) {
            const rowDiv = document.createElement('div');
            rowDiv.className = 'stock-entry-row';

            // Warehouse Select
            const whSelectDiv = document.createElement('div');
            const whLabel = document.createElement('label');
            whLabel.textContent = 'Warehouse:';
            const whSelect = document.createElement('select');
            // Use a unique name for the warehouse select to avoid submission, or make it non-submittable
            // For simplicity, we'll rely on it not being directly used by PHP backend for stock_entries
            whSelect.className = 'stock_warehouse_select';
            whSelect.innerHTML = '<option value="">-- Select Warehouse --</option>';
            warehouses.forEach(wh => {
                const option = document.createElement('option');
                option.value = wh.id;
                option.textContent = wh.name;
                if (entryData.warehouse_id == wh.id) option.selected = true;
                whSelect.appendChild(option);
            });
            whSelectDiv.appendChild(whLabel);
            whSelectDiv.appendChild(whSelect);

            // Stock Location Select
            const locSelectDiv = document.createElement('div');
            const locLabel = document.createElement('label');
            locLabel.textContent = 'Location:';
            const locSelect = document.createElement('select');
            locSelect.name = 'stock_location_ids[]'; // This is what gets submitted
            locSelect.innerHTML = '<option value="">-- Select Location --</option>';
            locSelect.required = true;
            locSelectDiv.appendChild(locLabel);
            locSelectDiv.appendChild(locSelect);

            whSelect.addEventListener('change', function() {
                const selectedWhId = this.value;
                locSelect.innerHTML = '<option value="">-- Select Location --</option>';
                if (selectedWhId && locationsByWarehouse[selectedWhId]) {
                    locationsByWarehouse[selectedWhId].forEach(loc => {
                        const option = document.createElement('option');
                        option.value = loc.id;
                        option.textContent = loc.location_code;
                        locSelect.appendChild(option);
                    });
                }
            });

            if(entryData.warehouse_id) {
                whSelect.value = entryData.warehouse_id; // Set warehouse value
                whSelect.dispatchEvent(new Event('change')); // Trigger change to populate locations

                // Pre-select location if data is available
                if(entryData.stock_location_id){
                     // Needs a slight delay for options to be populated by the event handler
                    setTimeout(() => {
                        locSelect.value = entryData.stock_location_id;
                    }, 0);
                }
            }


            // Quantity Input
            const qtyDiv = document.createElement('div');
            const qtyLabel = document.createElement('label');
            qtyLabel.textContent = 'Quantity:';
            const qtyInput = document.createElement('input');
            qtyInput.type = 'number';
            qtyInput.name = 'stock_quantities[]';
            qtyInput.min = '0';
            qtyInput.value = entryData.quantity_on_hand || '0';
            qtyInput.required = true;
            qtyDiv.appendChild(qtyLabel);
            qtyDiv.appendChild(qtyInput);

            // Threshold Input
            const thresholdDiv = document.createElement('div');
            const thresholdLabel = document.createElement('label');
            thresholdLabel.textContent = 'Low Stock Threshold:';
            const thresholdInput = document.createElement('input');
            thresholdInput.type = 'number';
            thresholdInput.name = 'stock_thresholds[]';
            thresholdInput.min = '0';
            thresholdInput.value = entryData.low_stock_threshold || '';
            thresholdDiv.appendChild(thresholdLabel);
            thresholdDiv.appendChild(thresholdInput);

            // Remove Button
            const removeBtnDiv = document.createElement('div');
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-stock-entry';
            removeBtn.textContent = 'Remove';
            removeBtn.onclick = function() { rowDiv.remove(); };
            removeBtnDiv.appendChild(removeBtn);

            rowDiv.appendChild(whSelectDiv);
            rowDiv.appendChild(locSelectDiv);
            rowDiv.appendChild(qtyDiv);
            rowDiv.appendChild(thresholdDiv);
            rowDiv.appendChild(removeBtnDiv);
            stockEntriesContainer.appendChild(rowDiv);
        }

        addStockEntryButton.addEventListener('click', function() { createStockEntryRow(); });

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stock_location_ids']) && is_array($_POST['stock_location_ids'])) {
            for ($i = 0; $i < count($_POST['stock_location_ids']); $i++) {
                $current_loc_id = $_POST['stock_location_ids'][$i] ?? null;
                $current_wh_id = null;
                if ($current_loc_id) {
                    foreach($all_stock_locations_grouped as $wh_id_key => $loc_array){
                        foreach($loc_array as $loc_obj){
                            if($loc_obj['id'] == $current_loc_id){
                                $current_wh_id = $wh_id_key;
                                break 2;
                            }
                        }
                    }
                }
                echo "createStockEntryRow({ " .
                     "warehouse_id: " . json_encode($current_wh_id) . ", " .
                     "stock_location_id: " . json_encode($current_loc_id) . ", " .
                     "quantity_on_hand: " . json_encode($_POST['stock_quantities'][$i] ?? '0') . ", " .
                     "low_stock_threshold: " . json_encode($_POST['stock_thresholds'][$i] ?? '') .
                     " });\n";
            }
        }
        ?>
    </script>
</body>
</html>
