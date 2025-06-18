<?php
session_start(); // Needed for CSRF check
require_once __DIR__ . '/../src/item_functions.php'; // For get_items_by_ids

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    die("Invalid request method. This page must be accessed via POST.");
}

// CSRF Token Validation
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token_bulk_print']) || !hash_equals($_SESSION['csrf_token_bulk_print'], $_POST['csrf_token'])) {
    http_response_code(403); // Forbidden
    die('CSRF token validation failed. Please try generating the print preview again from the selection page.');
}
// Optional: Unset the token if it's intended for single use, though this might be problematic if user reloads preview
// unset($_SESSION['csrf_token_bulk_print']);


$item_ids_to_print = $_POST['item_ids'] ?? [];
$print_sku = isset($_POST['print_sku']);
$print_name = isset($_POST['print_name']);
$print_barcode_val = isset($_POST['print_barcode_val']);
$print_uom = isset($_POST['print_uom']);
$layout_columns = isset($_POST['layout_columns']) ? (int)$_POST['layout_columns'] : 3;
if ($layout_columns < 1 || $layout_columns > 6) $layout_columns = 3; // Sanitize

if (empty($item_ids_to_print)) {
    die("No items selected for printing. Please go back and select items.");
}

// Sanitize item IDs
$item_ids_to_print = array_map('intval', $item_ids_to_print);
$item_ids_to_print = array_filter($item_ids_to_print, function($id) { return $id > 0; });

if (empty($item_ids_to_print)) {
    die("No valid item IDs selected for printing.");
}

$items = get_items_by_ids($item_ids_to_print);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Preview - SKUs/Barcodes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 10px; background-color: #fff; } /* Default to white for printing */
        .print-container { display: flex; flex-wrap: wrap; gap: 10px; /* Gap between items */ }
        .print-item {
            border: 1px dotted #999; /* Dotted for print view, solid for screen could be an option */
            padding: 8px;
            margin: 0; /* Gap is handled by parent */
            text-align: center;
            flex-grow: 0;
            flex-shrink: 0;
            flex-basis: calc(<?php echo (100 / $layout_columns); ?>% - 10px); /* Adjust 10px for total gap / columns */
            box-sizing: border-box;
            overflow-wrap: break-word;
            page-break-inside: avoid; /* Attempt to keep item content from splitting across pages */
            min-height: 50px; /* Give some minimum height */
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .print-item .sku { font-weight: bold; font-size: 1.1em; margin-bottom: 3px; }
        .print-item .name { font-size: 0.9em; margin-bottom: 3px; color: #333; }
        .print-item .barcode-val { font-family: 'Courier New', Courier, monospace; font-size: 1em; margin-bottom: 3px; }
        .print-item .uom { font-size: 0.8em; color: #555; }
        .print-button-area { text-align: center; margin-top: 20px; margin-bottom: 20px; }
        .print-button-area button { padding: 10px 20px; font-size: 1rem; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;}
        .print-button-area button:hover { background-color: #0056b3;}

        @media print {
            body { margin: 0.5cm; font-family: Arial, sans-serif; } /* Ensure font consistency */
            .print-button-area { display: none; }
            .print-item { border: 1px dotted #999; } /* Use dotted for actual print if desired */
            /* Ensure background colors and images are not printed unless explicitly desired */
            /* Modern browsers often have a "Print background graphics" option for this */
        }
    </style>
    <!-- If using JsBarcode or similar for actual barcode rendering: -->
    <!-- <script src="path/to/JsBarcode.all.min.js"></script> -->
</head>
<body>
    <div class="print-button-area">
        <button onclick="window.print();">Print Labels</button>
        <p><small>Ensure your printer settings (paper size, margins, scale 'Actual Size' or 100%) are appropriate. Test on a single page first.</small></p>
    </div>

    <div class="print-container">
        <?php if (empty($items)): ?>
            <p>No valid item details found for selected IDs.</p>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <div class="print-item">
                    <?php if ($print_name): ?>
                        <div class="name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <?php endif; ?>
                    <?php if ($print_sku): ?>
                        <div class="sku"><?php echo htmlspecialchars($item['sku']); ?></div>
                    <?php endif; ?>
                    <?php if ($print_barcode_val && !empty($item['barcode'])): ?>
                        <div class="barcode-val">BC: <?php echo htmlspecialchars($item['barcode']); ?></div>
                        <!-- For actual barcode image (using a library like JsBarcode):
                             <svg class="barcode-svg"
                                  jsbarcode-format="CODE128"
                                  jsbarcode-value="<?php echo htmlspecialchars($item['barcode']); ?>"
                                  jsbarcode-textmargin="0"
                                  jsbarcode-fontoptions="bold"
                                  jsbarcode-height="30"
                                  jsbarcode-width="1.5"
                                  jsbarcode-displayvalue="false">
                             </svg>
                        -->
                    <?php elseif ($print_barcode_val): ?>
                         <div class="barcode-val">(No Barcode)</div>
                    <?php endif; ?>
                    <?php if ($print_uom && !empty($item['unit_of_measure'])): ?>
                        <div class="uom">UoM: <?php echo htmlspecialchars($item['unit_of_measure']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- <script>
        // If using JsBarcode for SVG barcodes:
        // document.addEventListener('DOMContentLoaded', function () {
        //    JsBarcode(".barcode-svg").init();
        // });
    // </script> -->
</body>
</html>
