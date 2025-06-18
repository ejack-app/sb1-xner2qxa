<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/order_functions.php';    // For get_order_by_id, get_order_items_with_packing_status
require_once __DIR__ . '/../src/packaging_functions.php';// For package CRUD
require_once __DIR__ . '/../src/box_functions.php';      // For get_active_box_definitions_for_select

$page_title = "Admin - Order Packaging";
$message = '';
$message_type = '';

$order_id_to_package = $_GET['order_id'] ?? null;
$selected_order = null;
$order_items_to_pack = [];
$existing_packages = [];
$active_boxes = get_active_box_definitions_for_select();
$order_had_items_to_pack = false; // Initialize

if ($order_id_to_package) {
    $order_id_to_package = (int)$order_id_to_package;
    $pdo_order_check = get_db_connection();
    $stmt_order = $pdo_order_check->prepare("SELECT id, order_number, customer_id, recipient_name, order_status FROM orders WHERE id = :id");
    $stmt_order->execute([':id' => $order_id_to_package]);
    $selected_order = $stmt_order->fetch();

    if (!$selected_order) {
        $message = "Order not found.";
        $message_type = 'error';
        $order_id_to_package = null;
    } else {
        $order_items_to_pack = get_order_items_with_packing_status($order_id_to_package);
        $existing_packages = get_packages_for_order($order_id_to_package);
        foreach ($existing_packages as &$pkg) {
            $pkg['items_in_package'] = get_items_in_package($pkg['id']);
        }
        unset($pkg);

        if (!empty($order_items_to_pack)) {
            foreach($order_items_to_pack as $oip_check) {
                if ($oip_check['quantity_ordered'] > 0) {
                    $order_had_items_to_pack = true;
                    break;
                }
            }
        }
    }
}

// Calculate $all_items_packed state based on current $order_items_to_pack
$all_items_packed = false;
if ($order_id_to_package && !empty($order_items_to_pack)) {
    $all_items_packed = true;
    foreach ($order_items_to_pack as $item_check_pack_status) {
        if ($item_check_pack_status['quantity_remaining_to_pack'] > 0) {
            $all_items_packed = false;
            break;
        }
    }
} elseif ($order_id_to_package && empty($order_items_to_pack) && $order_had_items_to_pack) {
    // If order_items_to_pack is empty but we determined it HAD items, means all are packed.
    // This state might be hit if all items were packed and then one was removed, making order_items_to_pack empty.
    // However, get_order_items_with_packing_status should still return items even if fully packed.
    // This specific condition for $all_items_packed might be redundant if get_order_items_with_packing_status always returns all items.
} elseif (!$order_had_items_to_pack && $order_id_to_package) {
    // If order had no items that needed packing (e.g. all 0 quantity or no items at all)
    $all_items_packed = true;
}


// --- Action Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $order_id_to_package) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_pkg_actions'] ?? '')) {
        error_log("CSRF Token mismatch in admin_packaging.php. Session: ".($_SESSION['csrf_token_pkg_actions'] ?? 'Not Set')." POST: ".($_POST['csrf_token']??'Not Set'));
        die('CSRF token validation failed. Please refresh and try again.');
    }

    $action_taken = false;

    if (isset($_POST['create_package'])) {
        $action_taken = true;
        $box_id = $_POST['box_definition_id'] ?? null;
        $new_pkg_id = create_shipment_package($order_id_to_package, empty($box_id) ? null : (int)$box_id);
        if ($new_pkg_id) {
            $message = "New package (ID: {$new_pkg_id}) created successfully."; $message_type = 'success';
        } else {
            $message = $_SESSION['error_message'] ?? "Failed to create package."; $message_type = 'error';
            unset($_SESSION['error_message']);
        }
    } elseif (isset($_POST['add_item_to_package'])) {
        $action_taken = true;
        $shipment_package_id = $_POST['shipment_package_id'] ?? null;
        $order_item_id_to_add = $_POST['order_item_id_to_add'] ?? null;
        $item_id_hidden = $_POST['item_id_hidden'] ?? null;
        $quantity_to_pack = $_POST['quantity_to_pack'] ?? 0;
        $item_valid_for_packing = false;

        // Fetch fresh item data for validation before adding to package
        $current_order_items_for_check = get_order_items_with_packing_status($order_id_to_package);
        foreach ($current_order_items_for_check as $oip) {
            if ($oip['order_item_id'] == $order_item_id_to_add) {
                if ((int)$quantity_to_pack > 0 && (int)$quantity_to_pack <= $oip['quantity_remaining_to_pack']) {
                    $item_valid_for_packing = true;
                } else if ((int)$quantity_to_pack > $oip['quantity_remaining_to_pack']) {
                    $message = "Cannot pack more than remaining quantity (" . $oip['quantity_remaining_to_pack'] . ") for item " . htmlspecialchars($oip['item_name']);
                    $message_type = 'error';
                }
                break;
            }
        }

        if ($item_valid_for_packing && $shipment_package_id && $order_item_id_to_add && $item_id_hidden) {
            if (add_item_to_package((int)$shipment_package_id, (int)$order_item_id_to_add, (int)$item_id_hidden, (int)$quantity_to_pack)) {
                $message = "Item added to package successfully."; $message_type = 'success';
            } else {
                $message = $_SESSION['error_message'] ?? "Failed to add item to package."; $message_type = 'error';
                unset($_SESSION['error_message']);
            }
        } elseif (!$message && (int)$quantity_to_pack <= 0) {
             $message = "Quantity to pack must be positive."; $message_type = 'error';
        } elseif (!$message) {
             $message = "Missing or invalid data for adding item to package."; $message_type = 'error';
        }
    } elseif (isset($_POST['delete_package_item'])) {
        $action_taken = true;
        $shipment_package_item_id = $_POST['shipment_package_item_id'] ?? null;
        if ($shipment_package_item_id && remove_item_from_package((int)$shipment_package_item_id)) {
            $message = "Item removed from package."; $message_type = 'success';
        } else {
            $message = $_SESSION['error_message'] ?? "Failed to remove item from package."; $message_type = 'error';
            unset($_SESSION['error_message']);
        }
    } elseif (isset($_POST['delete_package'])) {
        $action_taken = true;
        $shipment_package_id_to_delete = $_POST['shipment_package_id_to_delete'] ?? null;
        if ($shipment_package_id_to_delete && delete_shipment_package((int)$shipment_package_id_to_delete)) {
            $message = "Package deleted successfully."; $message_type = 'success';
        } else {
            $message = $_SESSION['error_message'] ?? "Failed to delete package."; $message_type = 'error';
            unset($_SESSION['error_message']);
        }
    } elseif (isset($_POST['finalize_packaging'])) {
        $action_taken = true;
        // Recalculate $all_items_packed and $order_had_items_to_pack with fresh data before finalizing
        $current_order_items_for_finalize = get_order_items_with_packing_status($order_id_to_package);
        $finalize_all_items_packed = true;
        $finalize_order_had_items = false;
        foreach ($current_order_items_for_finalize as $item_finalize) {
            if ($item_finalize['quantity_ordered'] > 0) $finalize_order_had_items = true;
            if ($item_finalize['quantity_remaining_to_pack'] > 0) {
                $finalize_all_items_packed = false;
            }
        }
        if (empty($current_order_items_for_finalize) && !$order_had_items_to_pack) $finalize_all_items_packed = true; // No items means "all packed"

        $current_existing_packages = get_packages_for_order($order_id_to_package);

        if (!$finalize_all_items_packed || (empty($current_existing_packages) && $finalize_order_had_items)) {
            $message = "Cannot finalize: Not all items are packed, or no packages exist for an order that requires them.";
            $message_type = 'error';
        } else {
            $all_packages_updated_successfully = true;
            if (!empty($current_existing_packages)) {
                foreach ($current_existing_packages as $pkg_finalize) {
                    if (!in_array($pkg_finalize['status'], ['READY_TO_SHIP', 'SHIPPED'])) { // Avoid re-updating already finalized packages
                        if (!update_shipment_package_status($pkg_finalize['id'], 'READY_TO_SHIP', ['PENDING_ITEMS', 'ITEMS_ADDED', 'WEIGHED_MEASURED', 'LABELED'])) {
                            $all_packages_updated_successfully = false;
                            $message = $_SESSION['error_message'] ?? "Failed to update status for package ID {$pkg_finalize['id']}.";
                            $message_type = 'error';
                            unset($_SESSION['error_message']);
                            break;
                        }
                    }
                }
            }

            if ($all_packages_updated_successfully) {
                if (update_order_status($order_id_to_package, 'PACKED', 'All items packaged and ready for shipment.')) {
                    $message = "Order packaging finalized. Order status updated to PACKED. Packages marked READY_TO_SHIP (if applicable).";
                    $message_type = 'success';
                } else {
                    $message = $_SESSION['error_message'] ?? "Failed to update order status to PACKED.";
                    $message_type = 'error';
                    unset($_SESSION['error_message']);
                }
            }
        }
    }

    if ($action_taken) { // If any POST action was handled, redirect with message
        header("Location: admin_packaging.php?order_id=" . $order_id_to_package . "&msg=" . urlencode($message) . "&msg_type=".$message_type);
        exit;
    }
}


// For general page load messages via GET (after redirects)
if (isset($_GET['msg']) && !$_SERVER['REQUEST_METHOD'] === 'POST') { // Only show GET messages if not a POST (to avoid overwriting POST error)
    $message = urldecode($_GET['msg']);
    $message_type = $_GET['msg_type'] ?? 'info';
}

if (empty($_SESSION['csrf_token_pkg_actions'])) {
    $_SESSION['csrf_token_pkg_actions'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_pkg_actions'];

// Fetch list of packable orders if no order is selected yet
$packable_orders = [];
if (!$order_id_to_package) {
    $pdo_packable = get_db_connection();
    $stmt_packable = $pdo_packable->query("SELECT id, order_number, recipient_name, order_date, order_status FROM orders
                                  WHERE order_status IN ('PROCESSING', 'CONFIRMED', 'AWAITING_PAYMENT', 'PENDING')
                                  ORDER BY order_date DESC LIMIT 50");
    $packable_orders = $stmt_packable->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 1000px; margin: 40px auto;}
        h1, h2, h3 { color: #333; }
        h1 {border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0;}
        h2 { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 25px; }
        .order-selection, .order-details, .unpackaged-items, .existing-packages, .package-actions { margin-bottom: 20px; padding:15px; background-color:#f0f0f0; border-radius:5px;}
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9em; background-color:#fff; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align:middle; }
        th { background-color: #e9ecef; font-weight:bold;}
        td { background-color:#fff; }
        tr:nth-child(even) td { background-color: #f8f9fa; }
        select, input[type="number"], input[type="text"], button, input[type="submit"] { padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; font-size:0.95rem;}
        button, input[type="submit"] { background-color: #007bff; color: white; cursor: pointer; transition: background-color 0.2s;}
        button:hover, input[type="submit"]:hover { background-color: #0056b3; }
        .delete-btn { background-color: #dc3545; }
        .delete-btn:hover { background-color: #c82333; }
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size:0.95rem;}
        .message.success { background-color: #d4edda; color: #155724; border:1px solid #c3e6cb;}
        .message.error { background-color: #f8d7da; color: #721c24; border:1px solid #f5c6cb;}
        .message.info { background-color: #d1ecf1; color: #0c5460; border:1px solid #bee5eb;}
        .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align:center;}
        .nav-links a { margin:0 10px; text-decoration: none; color:#007bff;}
        .nav-links a:hover {text-decoration:underline;}
        .package-card { border:1px solid #ccc; padding:15px; margin-bottom:15px; border-radius:5px; background-color:#fdfdfd;}
        .package-card h4 {margin-top:0; padding-bottom:8px; border-bottom:1px dotted #ddd;}
        .package-card .items-in-pkg-table td {font-size:0.9em; padding:4px;}
        .top-nav { padding: 10px 20px; background-color: #333; color: #fff; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .top-nav a { color: #fff; text-decoration: none; margin-left: 15px; }
        .top-nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="top-nav">
        <span>Admin Panel - Packaging</span>
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

        <?php if (!$order_id_to_package): ?>
            <div class="order-selection">
                <h2>Select an Order to Package:</h2>
                <?php if (empty($packable_orders)): ?>
                    <p>No orders currently available for packaging (checked for status PROCESSING, CONFIRMED, PENDING, AWAITING_PAYMENT).</p>
                <?php else: ?>
                    <table><thead><tr><th>Order #</th><th>Recipient</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($packable_orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                            <td><?php echo htmlspecialchars($order['recipient_name']); ?></td>
                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($order['order_date']))); ?></td>
                            <td><?php echo htmlspecialchars($order['order_status']); ?></td>
                            <td><a href="admin_packaging.php?order_id=<?php echo $order['id']; ?>" style="text-decoration:none;padding:5px 10px;background-color:#5cb85c;color:white;border-radius:4px;">Package This Order</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody></table>
                <?php endif; ?>
            </div>
        <?php else: // Order is selected ?>
            <div class="order-details">
                <h2>Packaging Order: <?php echo htmlspecialchars($selected_order['order_number']); ?></h2>
                <p>Recipient: <?php echo htmlspecialchars($selected_order['recipient_name']); ?> | Status: <strong style="color:blue;"><?php echo htmlspecialchars($selected_order['order_status']); ?></strong></p>
                <p><a href="admin_packaging.php"> &laquo; Back to Order Selection</a></p>
            </div>

            <div class="unpackaged-items">
                <h3>Items to Pack:</h3>
                <?php
                   $display_fully_packed_message = true;
                   if (!empty($order_items_to_pack)) {
                       foreach ($order_items_to_pack as $item_display_check) {
                           if ($item_display_check['quantity_remaining_to_pack'] > 0) {
                               $display_fully_packed_message = false;
                               break;
                           }
                       }
                   } else { // No items in order means technically all (zero) items are packed
                        if(!$order_had_items_to_pack) $display_fully_packed_message = false; // If order had no items to start with, don't say "all packed"
                   }

                   if (!$order_had_items_to_pack) {
                        echo "<p>This order has no shippable items.</p>";
                   } elseif ($display_fully_packed_message) {
                        echo "<p>All items for this order have been fully packed.</p>";
                   } else {
                   ?>
                       <table><thead><tr><th>SKU</th><th>Name</th><th>Ordered</th><th>Packed</th><th>Remaining</th></tr></thead>
                       <tbody>
                           <?php foreach ($order_items_to_pack as $item): ?>
                               <?php if ($item['quantity_remaining_to_pack'] > 0): ?>
                                   <tr>
                                       <td><?php echo htmlspecialchars($item['item_sku']); ?></td>
                                       <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                       <td><?php echo htmlspecialchars($item['quantity_ordered']); ?></td>
                                       <td><?php echo htmlspecialchars($item['quantity_packed_total']); ?></td>
                                       <td><strong><?php echo htmlspecialchars($item['quantity_remaining_to_pack']); ?></strong></td>
                                   </tr>
                               <?php endif; ?>
                           <?php endforeach; ?>
                       </tbody></table>
                   <?php } ?>
            </div>

            <div class="existing-packages">
                <h3>Current Packages for this Order:</h3>
                <?php if (empty($existing_packages)): ?>
                    <p>No packages created for this order yet.</p>
                <?php else: ?>
                    <?php foreach ($existing_packages as $pkg): ?>
                        <div class="package-card">
                            <h4>Package ID: <?php echo $pkg['id']; ?>
                                (Box: <?php echo htmlspecialchars($pkg['box_name'] ?? 'Custom'); ?>,
                                Status: <?php echo htmlspecialchars($pkg['status']); ?>)
                                <form action="admin_packaging.php?order_id=<?php echo $order_id_to_package; ?>" method="POST" style="display:inline; margin-left:10px;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="shipment_package_id_to_delete" value="<?php echo $pkg['id']; ?>">
                                    <button type="submit" name="delete_package" class="delete-btn" onclick="return confirm('Are you sure you want to delete this entire package and its items?');">Delete Package</button>
                                </form>
                            </h4>
                            <?php if (!empty($pkg['items_in_package'])): ?>
                                <table class="items-in-pkg-table"><thead><tr><th>SKU</th><th>Name</th><th>Qty Packed</th><th>Action</th></tr></thead><tbody>
                                <?php foreach($pkg['items_in_package'] as $pkg_item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pkg_item['sku']);?></td>
                                    <td><?php echo htmlspecialchars($pkg_item['item_name']);?></td>
                                    <td><?php echo htmlspecialchars($pkg_item['quantity_packed']);?></td>
                                    <td>
                                        <form action="admin_packaging.php?order_id=<?php echo $order_id_to_package; ?>" method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="shipment_package_item_id" value="<?php echo $pkg_item['shipment_package_item_id']; ?>">
                                            <button type="submit" name="delete_package_item" class="delete-btn" style="font-size:0.8em;padding:3px 6px;" onclick="return confirm('Remove this item from package?');">Remove Item</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody></table>
                            <?php else: ?><p><small>No items added to this package yet.</small></p><?php endif; ?>

                            <?php if (in_array($pkg['status'], ['PENDING_ITEMS', 'ITEMS_ADDED', 'WEIGHED_MEASURED', 'LABELED'])): // Allow adding if not yet finalized for shipping ?>
                                <form action="admin_packaging.php?order_id=<?php echo $order_id_to_package; ?>" method="POST" style="margin-top:10px; padding-top:10px; border-top:1px dashed #eee;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="shipment_package_id" value="<?php echo $pkg['id']; ?>">
                                    <select name="order_item_id_to_add" required>
                                        <option value="">-- Select Item to Add --</option>
                                        <?php foreach ($order_items_to_pack as $item): if($item['quantity_remaining_to_pack'] > 0): ?>
                                        <option value="<?php echo htmlspecialchars($item['order_item_id']); ?>" data-item-id="<?php echo htmlspecialchars($item['item_id']); ?>">
                                            <?php echo htmlspecialchars($item['item_name'] . " (SKU: " . $item['item_sku'] . ") - Remaining: " . $item['quantity_remaining_to_pack']); ?>
                                        </option>
                                        <?php endif; endforeach; ?>
                                    </select>
                                    <input type="hidden" name="item_id_hidden" value="">
                                    <input type="number" name="quantity_to_pack" placeholder="Qty" min="1" required style="width:70px;">
                                    <button type="submit" name="add_item_to_package">Add to Package <?php echo $pkg['id']; ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="package-actions">
                <h3>Create New Package for this Order:</h3>
                <form action="admin_packaging.php?order_id=<?php echo $order_id_to_package; ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <label for="box_definition_id">Select Box (Optional):</label>
                    <select id="box_definition_id" name="box_definition_id">
                        <option value="">-- Custom Packaging (No Box) --</option>
                        <?php foreach ($active_boxes as $box): ?>
                        <option value="<?php echo htmlspecialchars($box['id']); ?>">
                            <?php echo htmlspecialchars($box['name'] . " (" . $box['length_cm'] . "x" . $box['width_cm'] . "x" . $box['height_cm'] . " cm)"); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="create_package" style="margin-top:10px;">Create Empty Package</button>
                </form>
            </div>

            <?php if ($order_id_to_package && ($order_had_items_to_pack && $all_items_packed && !empty($existing_packages) || !$order_had_items_to_pack ) && !in_array($selected_order['order_status'], ['PACKED', 'SHIPPED', 'DELIVERED', 'CANCELLED'])): ?>
                <div class="package-actions" style="border-top: 2px solid #007bff; margin-top: 30px; padding-top: 20px;">
                    <h3>Finalize Packaging for Order</h3>
                    <p>Ensure all items are packed correctly (if any) and all packages are accounted for.</p>
                    <form action="admin_packaging.php?order_id=<?php echo $order_id_to_package; ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <button type="submit" name="finalize_packaging" style="background-color: #28a745; padding: 10px 20px; font-size: 1rem;">Mark as PACKED & Ready to Ship</button>
                    </form>
                </div>
            <?php elseif ($order_id_to_package && $selected_order['order_status'] === 'PACKED'): ?>
                <div class="package-actions" style="border-top: 2px solid #007bff; margin-top: 30px; padding-top: 20px;">
                   <h3>Order Packed</h3>
                   <p style="color:green; font-weight:bold;">This order is marked as PACKED and all packages are READY_TO_SHIP.</p>
               </div>
            <?php endif; ?>

            <div class="nav-links">
                <a href="admin_orders_list.php">Back to Orders List</a>
            </div>
        <?php endif; // End if order_id_to_package ?>
    </div>
    <script>
     document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('select[name="order_item_id_to_add"]').forEach(selectElement => {
            selectElement.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const itemId = selectedOption.getAttribute('data-item-id');
                let hiddenInput = this.closest('form').querySelector('input[name="item_id_hidden"]');
                if(hiddenInput) {
                    hiddenInput.value = itemId || '';
                }
            });
            if (selectElement.value) {
                 const selectedOption = selectElement.options[selectElement.selectedIndex];
                 const itemId = selectedOption.getAttribute('data-item-id');
                 let hiddenInput = selectElement.closest('form').querySelector('input[name="item_id_hidden"]');
                 if(hiddenInput && !hiddenInput.value) { // Only set if not already set (e.g. by POST repopulation)
                    hiddenInput.value = itemId || '';
                 }
            }
        });
     });
    </script>
</body>
</html>
