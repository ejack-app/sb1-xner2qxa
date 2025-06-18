<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/item_functions.php';

$page_title = "Admin - Bulk Item Update via CSV";
$message = '';
$message_type = '';
$results = [];

// Define expected CSV headers and their mapping to item fields
$csv_header_mapping = [
    'SKU' => 'sku_key', // Special key for matching existing item. This SKU value itself won't be updated by this key.
    'NewSKU' => 'sku', // Use 'NewSKU' in CSV to change an item's SKU.
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
];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_bulk_item_update'] ?? '')) {
        die('CSRF token validation failed.');
    }

    if ($_FILES['csv_file']['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
        $file_path = $_FILES['csv_file']['tmp_name'];

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
                } else {
                    $processed_count = 0;
                    $updated_count = 0;
                    $failed_count = 0;
                    $current_row_number = 1; // Header is row 1

                    $normalized_header = array_map(function($h){
                        return trim(str_replace("ï»¿", '', $h)); // Remove BOM
                    }, $header_row);

                    $sku_column_index = array_search('SKU', $normalized_header);
                    if ($sku_column_index === false) {
                        $message = "CSV must contain an 'SKU' column for matching existing items.";
                        $message_type = 'error';
                    } else {
                        while (($row_values = fgetcsv($file_handle)) !== FALSE) {
                            $current_row_number++;
                            $processed_count++;
                            $item_data_to_update = [];
                            $current_sku_key = trim($row_values[$sku_column_index] ?? '');

                            if (empty($current_sku_key)) {
                                $results[] = ['sku' => '(empty)', 'row' => $current_row_number, 'status' => 'Failed', 'reason' => 'SKU (lookup key) is missing in this row.'];
                                $failed_count++;
                                continue;
                            }

                            foreach ($normalized_header as $index => $csv_col_name) {
                                if ($csv_col_name === 'SKU') continue; // SKU is key, not data unless 'NewSKU' is used

                                if (isset($csv_header_mapping[$csv_col_name])) {
                                    $db_field_key = $csv_header_mapping[$csv_col_name];
                                    $value_from_csv = trim($row_values[$index] ?? '');

                                    // Include field for update if it's not empty,
                                    // or if it's one of the fields that can be explicitly set to empty/null,
                                    // or if it's 'IsActive' (as '0' or 'false' are valid non-empty strings).
                                    if ($value_from_csv !== '' ||
                                        in_array($db_field_key, ['description', 'barcode', 'image_url', 'default_purchase_price', 'default_selling_price', 'weight', 'length', 'width', 'height']) ||
                                        $db_field_key === 'is_active'
                                    ) {
                                         $item_data_to_update[$db_field_key] = $value_from_csv;
                                    }
                                }
                            }

                            if (empty($item_data_to_update)) {
                                 $results[] = ['sku' => $current_sku_key, 'row' => $current_row_number, 'status' => 'Skipped', 'reason' => 'No updatable data found in this row for recognized columns.'];
                                 continue;
                            }

                            unset($_SESSION['error_message']);
                            if (update_item_selective($current_sku_key, $item_data_to_update)) {
                                $results[] = ['sku' => $current_sku_key, 'row' => $current_row_number, 'status' => 'Success', 'reason' => 'Item updated.'];
                                $updated_count++;
                            } else {
                                $results[] = ['sku' => $current_sku_key, 'row' => $current_row_number, 'status' => 'Failed', 'reason' => $_SESSION['error_message'] ?? 'Update function returned false.'];
                                $failed_count++;
                                unset($_SESSION['error_message']);
                            }
                        }
                        $message = "CSV processing complete. Rows Processed: {$processed_count}, Items Updated: {$updated_count}, Rows Failed/Skipped: {$failed_count}.";
                        $message_type = ($failed_count > 0 || $processed_count === 0 && $updated_count === 0) ? 'error' : 'success';
                        if($processed_count === 0 && $updated_count === 0 && $failed_count === 0 && $current_row_number === 1) {
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
        $message = "No file uploaded or file upload error: " . ($_FILES['csv_file']['error'] ?? 'Unknown error');
        $message_type = 'error';
    }
}


if (empty($_SESSION['csrf_token_bulk_item_update'])) {
    $_SESSION['csrf_token_bulk_item_update'] = bin2hex(random_bytes(32));
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
        .instructions ul {padding-left:20px;}
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
            <h3>Upload CSV File</h3>
            <form action="admin_bulk_item_update.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_bulk_item_update']); ?>">
                <div>
                    <label for="csv_file">Select CSV file:</label>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv,text/csv" required>
                </div>
                <p><input type="submit" value="Upload and Process CSV"></p>
            </form>
        </div>

        <div class="instructions">
            <h4>CSV File Format Instructions:</h4>
            <ul>
                <li>The first row must be a header row.</li>
                <li><strong>Required header:</strong> `SKU` (This is used to find existing items).</li>
                <li><strong>Optional headers for update:</strong>
                    `<?php
                    $display_headers = $csv_header_mapping;
                    unset($display_headers['SKU']); // Don't list SKU as updatable here, it's the key
                    echo implode('`, `', array_keys($display_headers));
                    ?>`
                    (Use `NewSKU` in CSV if you intend to change an item's SKU value itself).
                </li>
                <li>Empty values for optional fields will generally be ignored (item's existing data for that field will be kept), unless the field is explicitly allowed to be set to empty (e.g. Description, Barcode, ImageURL) or NULL (numeric/price fields).</li>
                <li>For `IsActive`, use `1`, `true`, `yes` for active; or `0`, `false`, `no` for inactive. Other values might be ignored.</li>
                <li>This tool does not update stock levels. Use specific stock adjustment tools for that.</li>
            </ul>
            <p><strong>Example CSV Content:</strong></p>
            <pre>
SKU,Name,DefaultSellingPrice,IsActive,NewSKU
ITEM001,"New Item Name",29.99,1,ITEM001-MODIFIED
ITEM002,,15.50, <!-- Only updates price for ITEM002 -->
ITEM003,"Another Name",,0 <!-- Updates name and sets inactive -->
</pre>
        </div>

        <?php if (!empty($results)): ?>
            <h2>Processing Results:</h2>
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Row #</th>
                        <th>SKU (Lookup)</th>
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
