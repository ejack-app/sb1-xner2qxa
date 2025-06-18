<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/item_functions.php'; // To get local items and stock
require_once __DIR__ . '/../src/Integrations/SallaIntegration.php';
require_once __DIR__ . '/../src/Integrations/ZidIntegration.php';

$page_title = "Admin - Manual Inventory Synchronization";
$message = '';
$message_type = '';

$salla_config = (require __DIR__ . '/../config/integrations_config.php')['salla'];
$zid_config = (require __DIR__ . '/../config/integrations_config.php')['zid'];

// Handle Sync Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_action'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_inv_sync'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $item_id_to_sync = $_POST['item_id'] ?? null;
    $platform = $_POST['platform'] ?? null; // 'salla' or 'zid'
    $item_sku = $_POST['item_sku'] ?? null; // SKU is critical for mapping

    if ($item_id_to_sync && $platform && $item_sku) {
        $item_details = get_item_by_id((int)$item_id_to_sync);
        if ($item_details) {
            $local_available_stock = $item_details['total_quantity_available'];

            if ($platform === 'salla' && $salla_config['enabled']) {
                $salla_integration = new SallaIntegration();
                // Salla's bulk update endpoint is preferred.
                // Data format: [['sku' => string, 'quantity' => int]]
                // The SallaIntegration class needs an `updateMultipleProductsStock` method
                // For now, we'll assume it exists or adapt to a single product update method if that's what's available.
                // Let's assume a method like syncInventory which takes [['sku' => ..., 'quantity' => ...]]
                $quantities_data = [['sku' => $item_sku, 'quantity' => (int)$local_available_stock]];

                // Placeholder for actual method call, assuming syncInventory or similar for single/multiple
                // If SallaIntegration only has syncInventory for a single item, it would be:
                // $response_success = $salla_integration->syncInventory($item_sku, (int)$local_available_stock);
                // For now, let's stick to the plan's implication of a bulk-like method or a general one:
                // This is a conceptual call; the SallaIntegration class would need a method that matches this.
                // Based on previous plan, SallaIntegration has `syncInventory(array $inventoryData)`
                // where $inventoryData is [['sku' => 'SKU001', 'quantity' => 10]].

                $response_success = $salla_integration->syncInventory($quantities_data);
                // The placeholder syncInventory returns true/false. A real API might return more.
                // For a more detailed response as described in the plan, the placeholder would need to be updated.
                // For now, we'll just use the boolean.

                if ($response_success) { // Placeholder syncInventory returns bool
                    $message = "Salla: Sync successful for SKU {$item_sku}. Local available stock: {$local_available_stock}. (Placeholder response)";
                    $message_type = 'success';
                } else {
                    $message = "Salla: Sync failed for SKU {$item_sku}. (Placeholder response)";
                    $message_type = 'error';
                }

            } elseif ($platform === 'zid' && $zid_config['enabled']) {
                $zid_integration = new ZidIntegration();
                $zid_product_id_to_use = !empty($_POST['zid_product_id']) ? $_POST['zid_product_id'] : $item_sku;

                // ZidIntegration placeholder has syncInventory(array $inventoryData)
                // Let's adapt to that for consistency in placeholder calls.
                $inventoryData = [['sku' => $zid_product_id_to_use, 'quantity' => (int)$local_available_stock]];
                $response_success = $zid_integration->syncInventory($inventoryData);

                if ($response_success) {
                    $message = "Zid: Sync successful for ID/SKU {$zid_product_id_to_use}. Local available stock: {$local_available_stock}. (Placeholder response)";
                    $message_type = 'success';
                } else {
                    $message = "Zid: Sync failed for ID/SKU {$zid_product_id_to_use}. (Placeholder response)";
                    $message_type = 'error';
                }
            } else {
                $message = "Platform '{$platform}' is not enabled or not supported.";
                $message_type = 'error';
            }
        } else {
            $message = "Item with ID {$item_id_to_sync} not found.";
            $message_type = 'error';
        }
    } else {
        $message = 'Missing item ID, SKU, or platform for sync.';
        $message_type = 'error';
    }
}


// Fetch items for display (simplified list for now, can add pagination/filters later)
$items_result = get_all_items([], 'i.name', 'ASC', 100, 0); // Get up to 100 items
$local_items = $items_result['items'];

if (empty($_SESSION['csrf_token_inv_sync'])) {
    $_SESSION['csrf_token_inv_sync'] = bin2hex(random_bytes(32));
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
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 1000px; margin: 40px auto;}
        h1 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em;}
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: middle;}
        th { background-color: #e9ecef; font-weight:bold; }
        td { background-color:#fff; }
        tr:nth-child(even) td { background-color: #f8f9fa; }
        .sync-actions form { display: inline-block; margin-right: 5px; }
        .sync-actions button { padding: 5px 10px; font-size:0.9em; cursor:pointer; border-radius:4px; }
        .salla-btn { background-color: #5cb85c; color:white; border:1px solid #4cae4c;}
        .salla-btn:hover { background-color: #4cae4c; }
        .zid-btn { background-color: #337ab7; color:white; border:1px solid #2e6da4;}
        .zid-btn:hover { background-color: #286090; }
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size:0.95rem; }
        .message.success { background-color: #d4edda; color: #155724; border:1px solid #c3e6cb;}
        .message.error { background-color: #f8d7da; color: #721c24; border:1px solid #f5c6cb;}
        .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align:center;}
        .nav-links a { margin:0 10px; text-decoration: none; color:#007bff;}
        .nav-links a:hover {text-decoration:underline;}
        .platform-disabled { color: #999; font-style: italic; display:inline-block; padding: 5px 0; }
        .zid-product-id-input { width: 120px; font-size:0.9em; padding:4px; margin-left:5px; border:1px solid #ccc; border-radius:3px;}
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

        <p>This page allows manual synchronization of local inventory levels to connected online stores. The integration methods are currently placeholders and will not perform live updates beyond logging.</p>

        <table>
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Local Available Qty</th>
                    <th>Sync Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($local_items)): ?>
                    <tr><td colspan="4" style="text-align:center;">No items found in the local system.</td></tr>
                <?php else: ?>
                    <?php foreach ($local_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['sku']); ?></td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['total_quantity_available']); ?></td>
                            <td class="sync-actions">
                                <?php if ($salla_config['enabled']): ?>
                                    <form action="admin_inventory_sync.php" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_inv_sync']); ?>">
                                        <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                        <input type="hidden" name="item_sku" value="<?php echo htmlspecialchars($item['sku']); ?>">
                                        <input type="hidden" name="platform" value="salla">
                                        <button type="submit" name="sync_action" class="salla-btn">Sync to Salla</button>
                                    </form>
                                <?php else: ?>
                                    <span class="platform-disabled">Salla disabled</span>
                                <?php endif; ?>

                                <?php if ($zid_config['enabled']): ?>
                                    <form action="admin_inventory_sync.php" method="POST" style="margin-left:10px;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_inv_sync']); ?>">
                                        <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                        <input type="hidden" name="item_sku" value="<?php echo htmlspecialchars($item['sku']); ?>">
                                        <input type="hidden" name="platform" value="zid">
                                        <label for="zid_product_id_<?php echo $item['id']; ?>" style="font-size:0.8em; display:block; margin-bottom:3px;">Zid ID (if diff):</label>
                                        <input type="text" name="zid_product_id" id="zid_product_id_<?php echo $item['id']; ?>" placeholder="<?php echo htmlspecialchars($item['sku']); ?>" class="zid-product-id-input">
                                        <button type="submit" name="sync_action" class="zid-btn" style="margin-top:5px;">Sync to Zid</button>
                                    </form>
                                <?php else: ?>
                                    <span class="platform-disabled" style="margin-left:10px;">Zid disabled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="nav-links">
            <a href="admin_items_list.php">Manage Items</a>
        </div>
    </div>
</body>
</html>
