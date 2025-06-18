<?php
require_once __DIR__ . '/../src/auth_check.php'; // Handles session start and admin check
require_once __DIR__ . '/../src/customer_functions.php';
require_once __DIR__ . '/../src/order_functions.php';
require_once __DIR__ . '/../src/company_details_functions.php'; // For courier companies

$message = '';
$message_type = ''; // 'success' or 'error'
$order_number_created = null;

$all_customers = get_all_customers_for_select(); // For sender dropdown
$courier_companies = get_all_courier_companies(); // For shipping method (courier)

// Define available order statuses, payment statuses, and payment methods
$order_statuses = ['PENDING', 'PROCESSING', 'AWAITING_PAYMENT', 'CONFIRMED', 'SHIPPED', 'DELIVERED', 'CANCELLED', 'RETURNED'];
$payment_statuses = ['UNPAID', 'PAID', 'PARTIALLY_PAID', 'REFUNDED', 'COD'];
$payment_methods = ['Cash', 'Credit Card', 'Bank Transfer', 'COD', 'Online Payment Gateway'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_create_order'] ?? '')) {
        die('CSRF token validation failed.');
    }

    // --- Sender (Customer) Details ---
    $sender_customer_id = $_POST['sender_customer_id'] ?? null;
    if ($sender_customer_id === 'new') {
        $sender_details = [
            'name' => $_POST['sender_name'] ?? '',
            'email' => $_POST['sender_email'] ?? '',
            'phone' => $_POST['sender_phone'] ?? '',
            'address_line1' => $_POST['sender_address_line1'] ?? '',
            'address_line2' => $_POST['sender_address_line2'] ?? '',
            'city' => $_POST['sender_city'] ?? '',
            'state' => $_POST['sender_state'] ?? '',
            'postal_code' => $_POST['sender_postal_code'] ?? '',
            'country_code' => $_POST['sender_country_code'] ?? '',
        ];
        try {
            // Validate required sender fields if 'new' is chosen
            if (empty($sender_details['name']) || empty($sender_details['phone']) || empty($sender_details['address_line1']) || empty($sender_details['city']) || empty($sender_details['postal_code']) || empty($sender_details['country_code'])) {
                throw new Exception("Required sender details are missing for new customer.");
            }
            $sender_customer_id = get_or_create_customer($sender_details);
        } catch (Exception $e) {
            $message = "Error with sender details: " . $e->getMessage();
            $message_type = 'error';
            // Stop further processing if sender creation fails
            goto end_of_post_handling;
        }
    } elseif (empty($sender_customer_id)) {
         $message = "Sender/Customer selection is required.";
         $message_type = 'error';
         goto end_of_post_handling;
    }


    // --- Recipient Details ---
    $recipient_details = [
        'name' => $_POST['recipient_name'] ?? '',
        'phone' => $_POST['recipient_phone'] ?? '',
        'email' => $_POST['recipient_email'] ?? '',
        'address_line1' => $_POST['recipient_address_line1'] ?? '',
        'address_line2' => $_POST['recipient_address_line2'] ?? '',
        'city' => $_POST['recipient_city'] ?? '',
        'state' => $_POST['recipient_state'] ?? '',
        'postal_code' => $_POST['recipient_postal_code'] ?? '',
        'country_code' => $_POST['recipient_country_code'] ?? '',
    ];
    // Basic validation for recipient
    if (empty($recipient_details['name']) || empty($recipient_details['phone']) || empty($recipient_details['address_line1']) || empty($recipient_details['city']) || empty($recipient_details['postal_code']) || empty($recipient_details['country_code'])) {
        $message = "Required recipient details are missing.";
        $message_type = 'error';
        goto end_of_post_handling;
    }


    // --- Order Details ---
    $order_details = [
        'payment_method' => $_POST['payment_method'] ?? null,
        'payment_status' => $_POST['payment_status'] ?? 'UNPAID',
        'order_status' => $_POST['order_status'] ?? 'PENDING',
        'shipping_method' => $_POST['shipping_method'] ?? null, // This might be a courier ID or name
        'shipping_cost' => !empty($_POST['shipping_cost']) ? (float)$_POST['shipping_cost'] : 0.00,
        'discount_amount' => !empty($_POST['discount_amount']) ? (float)$_POST['discount_amount'] : 0.00,
        'total_cod_amount' => !empty($_POST['total_cod_amount']) ? (float)$_POST['total_cod_amount'] : 0.00,
        'notes' => $_POST['order_notes'] ?? '',
        // total_order_value will be calculated below
    ];

    if ($order_details['payment_status'] === 'COD' && empty($order_details['total_cod_amount'])) {
        $order_details['total_cod_amount'] = null; // Placeholder, will be set by items sum if not specified
    }


    // --- Order Items ---
    $order_items_data = [];
    $calculated_total_value = 0;
    if (isset($_POST['items_sku']) && is_array($_POST['items_sku'])) {
        for ($i = 0; $i < count($_POST['items_sku']); $i++) {
            if (empty($_POST['items_sku'][$i]) || empty($_POST['items_name'][$i]) || !isset($_POST['items_quantity'][$i]) || !isset($_POST['items_unit_price'][$i])) {
                // Skip incomplete item rows silently or add validation message
                continue;
            }
            $quantity = (int)$_POST['items_quantity'][$i];
            $unit_price = (float)$_POST['items_unit_price'][$i];
            if ($quantity <= 0 || $unit_price < 0) {
                $message = "Invalid quantity or price for item: " . htmlspecialchars($_POST['items_name'][$i]);
                $message_type = 'error';
                goto end_of_post_handling;
            }
            $order_items_data[] = [
                'sku' => $_POST['items_sku'][$i],
                'name' => $_POST['items_name'][$i],
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'weight' => $_POST['items_weight'][$i] ?? null,
                'dimensions' => $_POST['items_dimensions'][$i] ?? null,
            ];
            $calculated_total_value += $quantity * $unit_price;
        }
    }

    if (empty($order_items_data)) {
        $message = "At least one item is required to create an order.";
        $message_type = 'error';
        goto end_of_post_handling;
    }

    // Calculate final total_order_value
    $order_details['total_order_value'] = ($calculated_total_value + $order_details['shipping_cost']) - $order_details['discount_amount'];

    // If payment status is COD and total_cod_amount was not explicitly set, set it to total_order_value
    if ($order_details['payment_status'] === 'COD' && ($_POST['total_cod_amount'] === '' || is_null($_POST['total_cod_amount']))) {
       $order_details['total_cod_amount'] = $order_details['total_order_value'];
    }


    // --- Create Order ---
    $created_by_user_id = $_SESSION['user_id']; // From auth_check
    unset($_SESSION['error_message']); // Clear any previous specific error from function

    $new_order_number = create_order(
        $sender_customer_id,
        $recipient_details,
        $order_details,
        $order_items_data,
        $created_by_user_id
    );

    if ($new_order_number) {
        $message = "Order created successfully! Order Number: " . htmlspecialchars($new_order_number);
        $message_type = 'success';
        $order_number_created = $new_order_number; // For display or redirection
        // Optionally, clear form fields here or redirect
    } else {
        $message = $_SESSION['error_message'] ?? "Failed to create order. Please check the details and try again.";
        $message_type = 'error';
        unset($_SESSION['error_message']);
    }

    end_of_post_handling: // Label for goto
}

// Generate CSRF token
if (empty($_SESSION['csrf_token_create_order'])) {
    $_SESSION['csrf_token_create_order'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Create New Order</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 900px; margin: auto;}
        h1, h2 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="tel"], input[type="number"], textarea, select {
            width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
        }
        textarea { resize: vertical; min-height: 60px; }
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .grid-item { /* background-color: #f0f0f0; padding: 15px; border-radius: 5px; */ }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .items-table th { background-color: #f0f0f0; }
        .items-table input { width: calc(100% - 16px); } /* Adjust for padding */
        .items-table .action-col { width: 80px; text-align: center; }
        .items-table .action-col button { background-color: #dc3545; color:white; border:none; padding: 5px 10px; cursor:pointer; border-radius:3px; }
        .items-table .action-col button:hover { background-color: #c82333; }
        #add-item-row { background-color: #28a745; color:white; border:none; padding: 8px 15px; cursor:pointer; border-radius:3px; margin-top:10px; }
        #add-item-row:hover { background-color: #218838; }
        input[type="submit"] { background-color: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 20px; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align:center; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .logout-link { float: right; margin-bottom:10px; }
        .form-section { margin-bottom: 20px; padding: 15px; border: 1px solid #eee; border-radius: 5px; }
        #new-sender-fields { display: none; margin-top: 10px; padding:10px; background-color:#efefef; border-radius:4px;}
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
            <a href="admin_users.php">User Management</a> |
            <a href="admin_company_details.php">Company Details</a> |
            <a href="admin_privacy_policy.php">Privacy Policy</a> |
            <a href="admin_terms_conditions.php">Terms & Conditions</a>
        </nav>
        <h1>Create New Order</h1>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
                <?php if ($order_number_created): ?>
                    <br>View Order (link to be implemented): <?php echo htmlspecialchars($order_number_created); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form action="admin_create_order.php" method="POST" id="create-order-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_create_order']); ?>">

            <div class="form-section">
                <h2>Sender Details (Customer)</h2>
                <label for="sender_customer_id">Select Existing Sender/Customer or Add New:</label>
                <select id="sender_customer_id" name="sender_customer_id" onchange="toggleNewSenderFields()">
                    <option value="">-- Select Sender --</option>
                    <?php foreach ($all_customers as $customer): ?>
                        <option value="<?php echo htmlspecialchars($customer['id']); ?>" <?php echo (isset($_POST['sender_customer_id']) && $_POST['sender_customer_id'] == $customer['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($customer['name'] . ' (' . $customer['email'] . ' | ' . $customer['phone'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="new" <?php echo (isset($_POST['sender_customer_id']) && $_POST['sender_customer_id'] == 'new') ? 'selected' : ''; ?>>-- Add New Sender --</option>
                </select>

                <div id="new-sender-fields">
                    <h3>New Sender Details</h3>
                    <div class="grid-container">
                        <div class="grid-item">
                            <label for="sender_name">Full Name:</label>
                            <input type="text" id="sender_name" name="sender_name" value="<?php echo htmlspecialchars($_POST['sender_name'] ?? ''); ?>">
                        </div>
                        <div class="grid-item">
                            <label for="sender_email">Email:</label>
                            <input type="email" id="sender_email" name="sender_email" value="<?php echo htmlspecialchars($_POST['sender_email'] ?? ''); ?>">
                        </div>
                        <div class="grid-item">
                            <label for="sender_phone">Phone:</label>
                            <input type="tel" id="sender_phone" name="sender_phone" value="<?php echo htmlspecialchars($_POST['sender_phone'] ?? ''); ?>">
                        </div>
                        <div class="grid-item">
                            <label for="sender_address_line1">Address Line 1:</label>
                            <input type="text" id="sender_address_line1" name="sender_address_line1" value="<?php echo htmlspecialchars($_POST['sender_address_line1'] ?? ''); ?>">
                        </div>
                        <div class="grid-item">
                            <label for="sender_address_line2">Address Line 2 (Optional):</label>
                            <input type="text" id="sender_address_line2" name="sender_address_line2" value="<?php echo htmlspecialchars($_POST['sender_address_line2'] ?? ''); ?>">
                        </div>
                        <div class="grid-item">
                            <label for="sender_city">City:</label>
                            <input type="text" id="sender_city" name="sender_city" value="<?php echo htmlspecialchars($_POST['sender_city'] ?? ''); ?>">
                        </div>
                        <div class="grid-item">
                            <label for="sender_state">State/Province (Optional):</label>
                            <input type="text" id="sender_state" name="sender_state" value="<?php echo htmlspecialchars($_POST['sender_state'] ?? ''); ?>">
                        </div>
                        <div class="grid-item">
                            <label for="sender_postal_code">Postal Code:</label>
                            <input type="text" id="sender_postal_code" name="sender_postal_code" value="<?php echo htmlspecialchars($_POST['sender_postal_code'] ?? ''); ?>">
                        </div>
                        <div class="grid-item">
                            <label for="sender_country_code">Country Code (e.g., US, CA, SA):</label>
                            <input type="text" id="sender_country_code" name="sender_country_code" value="<?php echo htmlspecialchars($_POST['sender_country_code'] ?? 'SA'); ?>" maxlength="2">
                        </div>
                    </div>
                </div>
            </div>


            <div class="form-section">
                <h2>Recipient Details</h2>
                <div class="grid-container">
                    <div class="grid-item"><label for="recipient_name">Full Name:</label><input type="text" id="recipient_name" name="recipient_name" value="<?php echo htmlspecialchars($_POST['recipient_name'] ?? ''); ?>" required></div>
                    <div class="grid-item"><label for="recipient_phone">Phone:</label><input type="tel" id="recipient_phone" name="recipient_phone" value="<?php echo htmlspecialchars($_POST['recipient_phone'] ?? ''); ?>" required></div>
                    <div class="grid-item"><label for="recipient_email">Email (Optional):</label><input type="email" id="recipient_email" name="recipient_email" value="<?php echo htmlspecialchars($_POST['recipient_email'] ?? ''); ?>"></div>
                    <div class="grid-item"><label for="recipient_address_line1">Address Line 1:</label><input type="text" id="recipient_address_line1" name="recipient_address_line1" value="<?php echo htmlspecialchars($_POST['recipient_address_line1'] ?? ''); ?>" required></div>
                    <div class="grid-item"><label for="recipient_address_line2">Address Line 2 (Optional):</label><input type="text" id="recipient_address_line2" name="recipient_address_line2" value="<?php echo htmlspecialchars($_POST['recipient_address_line2'] ?? ''); ?>"></div>
                    <div class="grid-item"><label for="recipient_city">City:</label><input type="text" id="recipient_city" name="recipient_city" value="<?php echo htmlspecialchars($_POST['recipient_city'] ?? ''); ?>" required></div>
                    <div class="grid-item"><label for="recipient_state">State/Province (Optional):</label><input type="text" id="recipient_state" name="recipient_state" value="<?php echo htmlspecialchars($_POST['recipient_state'] ?? ''); ?>"></div>
                    <div class="grid-item"><label for="recipient_postal_code">Postal Code:</label><input type="text" id="recipient_postal_code" name="recipient_postal_code" value="<?php echo htmlspecialchars($_POST['recipient_postal_code'] ?? ''); ?>" required></div>
                    <div class="grid-item"><label for="recipient_country_code">Country Code (e.g., US, CA, SA):</label><input type="text" id="recipient_country_code" name="recipient_country_code" value="<?php echo htmlspecialchars($_POST['recipient_country_code'] ?? 'SA'); ?>" required maxlength="2"></div>
                </div>
            </div>

            <div class="form-section">
                <h2>Order Items</h2>
                <table id="order-items-table" class="items-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Item Name</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Weight (kg)</th>
                            <th>Dimensions (LxWxH cm)</th>
                            <th class="action-col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Item rows will be added here by JS -->
                    </tbody>
                </table>
                <button type="button" id="add-item-row">Add Item</button>
            </div>

            <div class="form-section">
                <h2>Order Summary & Payment</h2>
                <div class="grid-container">
                   <div class="grid-item">
                        <label for="order_status">Order Status:</label>
                        <select id="order_status" name="order_status">
                            <?php foreach($order_statuses as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo (($_POST['order_status'] ?? 'PENDING') == $status) ? 'selected' : ''; ?>><?php echo ucfirst(strtolower(str_replace('_', ' ', $status))); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid-item">
                        <label for="payment_status">Payment Status:</label>
                        <select id="payment_status" name="payment_status" onchange="toggleCodAmountField()">
                            <?php foreach($payment_statuses as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo (($_POST['payment_status'] ?? 'UNPAID') == $status) ? 'selected' : ''; ?>><?php echo ucfirst(strtolower(str_replace('_', ' ', $status))); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <div class="grid-item" id="cod_amount_field_container" style="display: <?php echo (($_POST['payment_status'] ?? '') == 'COD') ? 'block' : 'none'; ?>;">
                        <label for="total_cod_amount">Total COD Amount (auto-calculated if blank):</label>
                        <input type="number" id="total_cod_amount" name="total_cod_amount" step="0.01" value="<?php echo htmlspecialchars($_POST['total_cod_amount'] ?? ''); ?>">
                    </div>
                    <div class="grid-item">
                        <label for="payment_method">Payment Method:</label>
                        <select id="payment_method" name="payment_method">
                           <option value="">-- Select Method --</option>
                            <?php foreach($payment_methods as $method): ?>
                            <option value="<?php echo $method; ?>" <?php echo (($_POST['payment_method'] ?? '') == $method) ? 'selected' : ''; ?>><?php echo $method; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid-item">
                        <label for="shipping_method">Shipping Method/Courier:</label>
                        <select id="shipping_method" name="shipping_method">
                            <option value="">-- Select Courier --</option>
                            <?php foreach($courier_companies as $courier): ?>
                            <option value="<?php echo htmlspecialchars($courier['id']); ?>" <?php echo (($_POST['shipping_method'] ?? '') == $courier['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($courier['name']); ?>
                            </option>
                            <?php endforeach; ?>
                            <option value="custom">Other/Manual</option>
                        </select>
                    </div>
                    <div class="grid-item"><label for="shipping_cost">Shipping Cost:</label><input type="number" id="shipping_cost" name="shipping_cost" step="0.01" value="<?php echo htmlspecialchars($_POST['shipping_cost'] ?? '0.00'); ?>"></div>
                    <div class="grid-item"><label for="discount_amount">Discount Amount:</label><input type="number" id="discount_amount" name="discount_amount" step="0.01" value="<?php echo htmlspecialchars($_POST['discount_amount'] ?? '0.00'); ?>"></div>
                </div>
                <label for="order_notes">Order Notes (Optional):</label>
                <textarea id="order_notes" name="order_notes"><?php echo htmlspecialchars($_POST['order_notes'] ?? ''); ?></textarea>
            </div>

            <input type="submit" value="Create Order">
        </form>
     </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const itemsTableBody = document.querySelector('#order-items-table tbody');
            const addItemButton = document.getElementById('add-item-row');

            function createItemRow() {
                const row = itemsTableBody.insertRow();
                row.innerHTML = `
                    <td><input type="text" name="items_sku[]" placeholder="SKU001" required></td>
                    <td><input type="text" name="items_name[]" placeholder="Item Name" required></td>
                    <td><input type="number" name="items_quantity[]" placeholder="1" min="1" value="1" required></td>
                    <td><input type="number" name="items_unit_price[]" placeholder="0.00" step="0.01" min="0" required></td>
                    <td><input type="number" name="items_weight[]" placeholder="0.0" step="0.01" min="0"></td>
                    <td><input type="text" name="items_dimensions[]" placeholder="10x10x10"></td>
                    <td class="action-col"><button type="button" class="remove-item-row">Remove</button></td>
                `;
                row.querySelector('.remove-item-row').addEventListener('click', function() {
                    row.remove();
                });
            }

            addItemButton.addEventListener('click', createItemRow);

            // Add one item row by default if no POST data (i.e., not a failed submission)
            <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $message_type === 'success'): ?>
            createItemRow();
            <?php else: ?>
            // Re-populate item rows if form was submitted and there was an error
            <?php
            if (isset($_POST['items_sku']) && is_array($_POST['items_sku'])) {
                for ($i = 0; $i < count($_POST['items_sku']); $i++) {
                    echo "createItemRow();";
                    echo "const lastRow = itemsTableBody.rows[itemsTableBody.rows.length - 1];";
                    echo "lastRow.cells[0].querySelector('input').value = '" . htmlspecialchars(addslashes($_POST['items_sku'][$i] ?? ''), ENT_QUOTES) . "';";
                    echo "lastRow.cells[1].querySelector('input').value = '" . htmlspecialchars(addslashes($_POST['items_name'][$i] ?? ''), ENT_QUOTES) . "';";
                    echo "lastRow.cells[2].querySelector('input').value = '" . htmlspecialchars(addslashes($_POST['items_quantity'][$i] ?? '1'), ENT_QUOTES) . "';";
                    echo "lastRow.cells[3].querySelector('input').value = '" . htmlspecialchars(addslashes($_POST['items_unit_price'][$i] ?? ''), ENT_QUOTES) . "';";
                    echo "lastRow.cells[4].querySelector('input').value = '" . htmlspecialchars(addslashes($_POST['items_weight'][$i] ?? ''), ENT_QUOTES) . "';";
                    echo "lastRow.cells[5].querySelector('input').value = '" . htmlspecialchars(addslashes($_POST['items_dimensions'][$i] ?? ''), ENT_QUOTES) . "';";
                }
            } else {
                echo "createItemRow();"; // Add one empty row if no items were submitted
            }
            ?>
            <?php endif; ?>

            toggleNewSenderFields(); // Call on load to set initial state
            toggleCodAmountField(); // Call on load for COD field
        });

        function toggleNewSenderFields() {
            const senderSelect = document.getElementById('sender_customer_id');
            const newSenderFieldsDiv = document.getElementById('new-sender-fields');
            const requiredNewSenderInputs = newSenderFieldsDiv.querySelectorAll('input[name^="sender_"]');

            if (senderSelect.value === 'new') {
                newSenderFieldsDiv.style.display = 'block';
                requiredNewSenderInputs.forEach(input => {
                    // Only set required for key fields, others can be optional
                    if (input.name === 'sender_name' || input.name === 'sender_phone' || input.name === 'sender_address_line1' || input.name === 'sender_city' || input.name === 'sender_postal_code' || input.name === 'sender_country_code') {
                       // input.required = true; // Handled by PHP for now
                    }
                });
            } else {
                newSenderFieldsDiv.style.display = 'none';
                // requiredNewSenderInputs.forEach(input => input.required = false);
            }
        }

        function toggleCodAmountField() {
           const paymentStatusSelect = document.getElementById('payment_status');
           const codAmountContainer = document.getElementById('cod_amount_field_container');
           if (paymentStatusSelect.value === 'COD') {
               codAmountContainer.style.display = 'block';
           } else {
               codAmountContainer.style.display = 'none';
           }
       }
    </script>
</body>
</html>
