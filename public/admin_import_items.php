<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/item_functions.php'; // For create_item, get_item_by_sku

$page_title = "Admin - Import New Items via CSV";
$message = '';
$message_type = '';
$results = [];

// Define expected CSV headers and their mapping to item data structure for create_item()
$csv_header_mapping_import = [
    'SKU' => 'sku',
    'Name' => 'name',
    'Description' => 'description',
    'Barcode' => 'barcode',
    'UnitOfMeasure' => 'unit_of_measure',
    'DefaultPurchasePrice' => 'default_purchase_price',
    'DefaultSellingPrice' => 'default_selling_price',
    'WeightKG' => 'weight',
    'LengthCM' => 'length',
    'WidthCM' => 'width',
    'HeightCM' => 'height',
    'ImageURL' => 'image_url',
    'IsActive' => 'is_active', // Expects 1 or 0, or true/false/yes/no strings
    // For initial stock for ONE location (create_item fallback)
    'InitialStockLocationID' => 'initial_stock_location_id',
    'InitialStockQuantity' => 'initial_quantity_on_hand',
    'LowStockThreshold' => 'low_stock_threshold' // For the initial stock entry
];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file_import'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_import_items'] ?? '')) {
        die('CSRF token validation failed.');
    }

    if ($_FILES['csv_file_import']['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['csv_file_import']['tmp_name'])) {
        $file_path = $_FILES['csv_file_import']['tmp_name'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);

        if ($mime_type !== 'text/csv' && $mime_type !== 'application/csv' && $mime_type !== 'text/plain' && $mime_type !== 'application/vnd.ms-excel') {
             $message = "Invalid file type: {$mime_type}. Please upload a CSV file.";
             $message_type = 'error';
        } else {
            $file_handle = fopen($file_path, 'r');
            if ($file_handle !== FALSE) {
                $header_row = fgetcsv($file_handle);
                if (empty($header_row) || trim($header_row[0]) === '') {
                     $message = "CSV file is empty or header row is missing.";
                     $message_type = 'error';
                     fclose($file_handle);
                } else {
                    $processed_count = 0;
                    $imported_count = 0;
                    $failed_count = 0;
                    $skipped_count = 0;
                    $row_number = 1;

                    $normalized_header = array_map(function($h){
                        return trim(str_replace("ï»¿", '', $h));
                    }, $header_row);

                    if (!in_array('SKU', $normalized_header) || !in_array('Name', $normalized_header)) {
                        $message = "CSV must contain 'SKU' and 'Name' columns.";
                        $message_type = 'error';
                    } else {
                        while (($row_values = fgetcsv($file_handle)) !== FALSE) {
                            $row_number++;
                            $processed_count++;
                            $item_data_to_create = ['is_active' => true]; // Default is_active to true
                            $current_sku_from_csv = '';

                            foreach ($normalized_header as $index => $csv_col_name) {
                                if (isset($csv_header_mapping_import[$csv_col_name])) {
                                    $item_field_key = $csv_header_mapping_import[$csv_col_name];
                                    $value = trim($row_values[$index] ?? '');

                                    if ($item_field_key === 'sku') {
                                        $current_sku_from_csv = $value;
                                    }

                                    if ($item_field_key === 'is_active') {
                                        // Set to true if value is '1', 'true', 'yes'. Set to false if '0', 'false', 'no'. Otherwise, keep default.
                                        if (in_array(strtolower($value), ['1', 'true', 'yes'])) {
                                            $item_data_to_create[$item_field_key] = true;
                                        } elseif (in_array(strtolower($value), ['0', 'false', 'no'])) {
                                            $item_data_to_create[$item_field_key] = false;
                                        } // else default 'is_active' => true remains
                                    } elseif (in_array($item_field_key, ['default_purchase_price', 'default_selling_price', 'weight', 'length', 'width', 'height', 'initial_quantity_on_hand', 'low_stock_threshold', 'initial_stock_location_id'])) {
                                        $item_data_to_create[$item_field_key] = ($value === '' || $value === null) ? null : (float)$value;
                                    } elseif ($value !== '' || in_array($item_field_key, ['description', 'barcode', 'image_url'])) {
                                        $item_data_to_create[$item_field_key] = $value;
                                    }
                                }
                            }

                            if (empty($current_sku_from_csv) || empty($item_data_to_create['name'])) {
                                $results[] = ['sku' => $current_sku_from_csv ?: '(empty SKU)', 'row' => $row_number, 'status' => 'Failed', 'reason' => 'SKU and Name are mandatory.'];
                                $failed_count++;
                                continue;
                            }

                            $existing_item = get_item_by_sku($current_sku_from_csv);
                            if ($existing_item) {
                                $results[] = ['sku' => $current_sku_from_csv, 'row' => $row_number, 'status' => 'Skipped', 'reason' => 'SKU already exists.'];
                                $skipped_count++;
                                continue;
                            }

                            if (isset($item_data_to_create['initial_stock_location_id']) && $item_data_to_create['initial_stock_location_id'] !== null) {
                                if (!isset($item_data_to_create['initial_quantity_on_hand']) || $item_data_to_create['initial_quantity_on_hand'] === null) {
                                    $item_data_to_create['initial_quantity_on_hand'] = 0;
                                }
                            } else {
                                unset($item_data_to_create['initial_stock_location_id']);
                                unset($item_data_to_create['initial_quantity_on_hand']);
                                unset($item_data_to_create['low_stock_threshold']);
                            }

                            unset($_SESSION['error_message']);
                            $new_item_id = create_item($item_data_to_create);
                            if ($new_item_id) {
                                $results[] = ['sku' => $current_sku_from_csv, 'row' => $row_number, 'status' => 'Success', 'reason' => "Item imported with ID: {$new_item_id}."];
                                $imported_count++;
                            } else {
                                $results[] = ['sku' => $current_sku_from_csv, 'row' => $row_number, 'status' => 'Failed', 'reason' => $_SESSION['error_message'] ?? 'create_item function returned false.'];
                                $failed_count++;
                                unset($_SESSION['error_message']);
                            }
                        }
                        $message = "CSV import complete. Rows Processed: {$processed_count}, Items Imported: {$imported_count}, Skipped (SKU exists): {$skipped_count}, Failed: {$failed_count}.";
                        $message_type = ($failed_count > 0 || $skipped_count > 0 || ($processed_count === 0 && $imported_count === 0)) ? 'error' : 'success';
                         if($processed_count === 0 && $imported_count === 0 && $failed_count === 0 && $skipped_count === 0 && $row_number === 1 && $message_type !== 'error') {
                            $message = "CSV file appears to have only a header row or is empty after the header.";
                            $message_type = 'error';
                        }

                    }
                }
                fclose($file_handle);
            } else {
                $message = "Could not open the uploaded CSV file.";
                $message_type = 'error';
            }
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $message = "No file uploaded or file upload error: " . ($_FILES['csv_file_import']['error'] ?? 'Unknown error');
        $message_type = 'error';
    }
}


if (empty($_SESSION['csrf_token_import_items'])) {
    $_SESSION['csrf_token_import_items'] = bin2hex(random_bytes(32));
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
        h1, h3, h4 { color: #333; }
        h1 {border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0;}
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size:0.95rem;}
        .message.success { background-color: #d4edda; color: #155724; border:1px solid #c3e6cb;}
        .message.error { background-color: #f8d7da; color: #721c24; border:1px solid #f5c6cb;}
        .upload-form { margin-bottom: 20px; padding:15px; background-color:#f0f0f0; border-radius:5px;}
        .upload-form label {font-weight:bold; display:block; margin-bottom:5px;}
        .upload-form input[type="file"] {display:block; margin-bottom:10px; padding:10px; border:1px solid #ccc; border-radius:4px;}
        .upload-form input[type="submit"] {background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .upload-form input[type="submit"]:hover {background-color: #0056b3;}
        .instructions { margin-bottom:15px; font-size:0.9em; padding:10px; background-color:#f9f9f9; border:1px solid #eee; border-radius:4px;}
        .instructions ul {padding-left:20px; margin-top:5px;}
        .instructions pre {background-color:#eee; padding:10px; border-radius:4px; font-size:0.85em; overflow-x:auto;}
        .results-table { width:100%; border-collapse:collapse; margin-top:20px; font-size:0.9em; }
        .results-table th, .results-table td { border:1px solid #ddd; padding:6px; text-align:left; }
        .results-table th { background-color:#e9ecef; font-weight:bold;}
        .status-success { color: green; }
        .status-failed { color: red; }
        .status-skipped { color: orange; }
        .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align:center;}
        .nav-links a { margin:0 10px; text-decoration: none; color:#007bff;}
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

        <div class="upload-form">
            <h3>Upload CSV File to Import New Items</h3>
            <form action="admin_import_items.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_import_items']); ?>">
                <div>
                    <label for="csv_file_import">Select CSV file:</label>
                    <input type="file" id="csv_file_import" name="csv_file_import" accept=".csv,text/csv" required>
                </div>
                <p><input type="submit" value="Upload and Import Items"></p>
            </form>
        </div>

        <div class="instructions">
            <h4>CSV File Format Instructions for Importing New Items:</h4>
            <ul>
                <li>The first row must be a header row.</li>
                <li><strong>Mandatory headers:</strong> `SKU`, `Name`.</li>
                <li><strong>Optional headers:</strong>
                    `<?php
                    $display_headers_import = $csv_header_mapping_import;
                    unset($display_headers_import['SKU'], $display_headers_import['Name']);
                    echo implode('`, `', array_keys($display_headers_import));
                    ?>`
                </li>
                <li>If `InitialStockLocationID` is provided, `InitialStockQuantity` should also be provided (defaults to 0 if quantity is missing or invalid). `LowStockThreshold` is optional for this specific stock entry.</li>
                <li>For `IsActive`, use `1`, `true`, `yes` for active; or `0`, `false`, `no` for inactive. If column is missing or value is unparsable, item defaults to active.</li>
                <li>Items with SKUs that already exist in the system will be skipped.</li>
            </ul>
            <p><strong>Example CSV Content:</strong></p>
            <pre>
SKU,Name,Description,DefaultSellingPrice,IsActive,InitialStockLocationID,InitialStockQuantity,LowStockThreshold
NEWITEM001,"Amazing Product","Desc for amazing",49.99,1,101,50,5
NEWITEM002,"Cool Gadget",,19.50,true,102,25,
NEWITEM003,"Widget Pro","Pro version",99,false,,, <!-- No initial stock -->
</pre>
        </div>

        <?php if (!empty($results)): ?>
            <h2>Import Results:</h2>
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Row #</th>
                        <th>SKU</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($result['row']); ?></td>
                            <td><?php echo htmlspecialchars($result['sku']); ?></td>
                            <td class="status-<?php echo strtolower(htmlspecialchars($result['status'])); ?>">
                                <?php echo htmlspecialchars($result['status']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($result['reason']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="nav-links">
            <a href="admin_items_list.php">Back to Items List</a>
        </div>
    </div>
</body>
</html>
