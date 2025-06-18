<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/finance_functions.php';

$rate_card_id = $_GET['rate_card_id'] ?? null;
if (!$rate_card_id || !filter_var($rate_card_id, FILTER_VALIDATE_INT)) {
    header('Location: admin_rate_cards_list.php?error=' . urlencode('No rate card specified or ID invalid.'));
    exit;
}
$rate_card_id = (int)$rate_card_id;
$rate_card = get_rate_card_by_id($rate_card_id);
if (!$rate_card) {
    header('Location: admin_rate_cards_list.php?error=' . urlencode('Rate card not found.'));
    exit;
}

$page_title = "Admin - Manage Rates for: " . htmlspecialchars($rate_card['name']);
$message = '';
$message_type = '';
$edit_rate_def_id = $_GET['edit_rate_def_id'] ?? null;
$editing_rate_def = null;

if ($edit_rate_def_id) {
    $editing_rate_def = get_rate_definition_by_id((int)$edit_rate_def_id);
    if (!$editing_rate_def || $editing_rate_def['rate_card_id'] != $rate_card_id) {
        $editing_rate_def = null;
        $edit_rate_def_id = null; // Clear invalid edit attempt
        $message = "Rate definition not found for this rate card or ID invalid.";
        $message_type = "error";
    }
}

$all_service_types = get_all_service_types(true);
$rate_types = ['FIXED', 'WEIGHT_TIER']; // Expand as more types are supported

// --- Action Handling: Add/Update Rate Definition ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_rate_definition']) || isset($_POST['update_rate_definition']))) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_manage_rates'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $data = [
        'rate_card_id' => $rate_card_id,
        'service_type_id' => $_POST['service_type_id'] ?? null,
        'rate_type' => $_POST['rate_type'] ?? 'FIXED',
        'fixed_rate' => $_POST['fixed_rate'] ?? null,
        'min_weight_kg' => $_POST['min_weight_kg'] ?? null,
        'max_weight_kg' => $_POST['max_weight_kg'] ?? null,
        'destination_zone_pattern' => $_POST['destination_zone_pattern'] ?? null,
        'rate_per_unit_or_tier' => $_POST['rate_per_unit_or_tier'] ?? null,
        'additional_kg_rate' => $_POST['additional_kg_rate'] ?? null,
        'currency_code' => $_POST['currency_code'] ?? 'SAR',
        'priority' => $_POST['priority'] ?? 0,
        'description_notes' => $_POST['description_notes'] ?? null,
        'is_active' => isset($_POST['is_active']),
    ];

    if (empty($data['service_type_id']) || empty($data['rate_type'])) {
        $message = "Service Type and Rate Type are required.";
        $message_type = 'error';
        if ($edit_rate_def_id) $editing_rate_def = array_merge($editing_rate_def ?: [], $data); // Preserve form data on error
        else $_POST_DATA_PREFILL = $data; // Preserve for new form
    } else {
        unset($_SESSION['error_message']);
        $success = false;
        if (isset($_POST['update_rate_definition']) && !empty($_POST['rate_def_id_to_update'])) {
            $rate_def_id_to_update = (int)$_POST['rate_def_id_to_update'];
            if (update_rate_definition($rate_def_id_to_update, $data)) {
                $message = "Rate definition updated successfully."; $success = true;
            } else {
                $message = $_SESSION['error_message'] ?? "Failed to update rate definition.";
            }
        } elseif (isset($_POST['add_rate_definition'])) {
            if (create_rate_definition($data)) {
                $message = "Rate definition added successfully."; $success = true;
            } else {
                $message = $_SESSION['error_message'] ?? "Failed to add rate definition.";
            }
        }
        $message_type = $success ? 'success' : 'error';
        if ($success) {
            header("Location: admin_rate_card_details.php?rate_card_id=" . $rate_card_id . "&msg=" . urlencode($message) . "&msg_type=success"); exit;
        } else {
            if ($edit_rate_def_id) $editing_rate_def = array_merge($editing_rate_def ?: [], $data);
            else $_POST_DATA_PREFILL = $data;
        }
    }
}

// --- Action Handling: Delete Rate Definition ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_rate_definition'])) {
     if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_manage_rates'] ?? '')) die('CSRF token failed.');
     $rate_def_id_to_delete = $_POST['rate_def_id_to_delete'] ?? null;
     if ($rate_def_id_to_delete && delete_rate_definition((int)$rate_def_id_to_delete)) {
         $message = "Rate definition deleted successfully."; $message_type = 'success';
         header("Location: admin_rate_card_details.php?rate_card_id=" . $rate_card_id . "&msg=" . urlencode($message) . "&msg_type=success"); exit;
     } else {
         $message = $_SESSION['error_message'] ?? "Failed to delete rate definition."; $message_type = 'error';
     }
     unset($_SESSION['error_message']);
}

if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
    $message_type = $_GET['msg_type'] ?? 'info';
}

$rate_definitions = get_rate_definitions_for_card($rate_card_id);
if (empty($_SESSION['csrf_token_manage_rates'])) {
    $_SESSION['csrf_token_manage_rates'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_manage_rates'];
$_POST_DATA_PREFILL = $_POST_DATA_PREFILL ?? []; // Ensure it's defined for form prefill

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
        h2 { border-bottom: 1px solid #eee; padding-bottom: 8px; margin-top: 25px; }
        .rate-card-info { margin-bottom:20px; padding:10px; background-color:#eef; border-left:3px solid #007bff; border-radius:4px;}
        .form-section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background-color:#f9f9f9;}
        label { display: block; margin-top: 10px; font-weight: bold; font-size:0.9em; margin-bottom:3px;}
        input[type="text"], input[type="number"], input[type="date"], select, textarea {
            width: 100%; padding: 8px; margin-top: 2px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size:0.95rem;
        }
        input[type="checkbox"] {margin-right:5px; vertical-align:middle;}
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
        .rate-type-fields { margin-top:10px; padding:10px; border:1px dashed #ccc; border-radius:4px; }
        .rate-type-fields div[id^="fields_"] { display: none; }
        .rate-type-fields div.active { display: block; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.85em; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; vertical-align:top;}
        th { background-color: #e9ecef; font-weight:bold;}
        .action-links a, .action-links button { margin-right: 5px; text-decoration:none; font-size:0.9em; padding:4px 8px; border-radius:3px; color:#007bff; border:1px solid #007bff; background-color:white; cursor:pointer;}
        .action-links button { background-color:#dc3545; color:white; border-color:#dc3545;}
        .action-links a:hover, .action-links button:hover {opacity:0.8;}
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size:0.95rem;}
        .message.success { background-color: #d4edda; color: #155724; border:1px solid #c3e6cb;}
        .message.error { background-color: #f8d7da; color: #721c24; border:1px solid #f5c6cb;}
        .message.info { background-color: #d1ecf1; color: #0c5460; border:1px solid #bee5eb;}
        .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align:center;}
        .nav-links a { margin:0 10px; text-decoration: none; color:#007bff;}
        .top-nav { padding: 10px 20px; background-color: #333; color: #fff; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .top-nav a { color: #fff; text-decoration: none; margin-left: 15px; }
    </style>
</head>
<body>
    <div class="top-nav"><span>Admin Panel - Finance Management</span><div><a href="admin_rate_cards_list.php">Rate Cards</a><a href="admin_service_types_list.php">Service Types</a><a href="logout.php">Logout</a></div></div>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <div class="rate-card-info">
            <p><strong>Rate Card:</strong> <?php echo htmlspecialchars($rate_card['name']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($rate_card['description'] ?? 'N/A'); ?></p>
            <p><a href="admin_edit_rate_card.php?id=<?php echo $rate_card_id; ?>">&laquo; Edit Rate Card Info</a> |
               <a href="admin_rate_cards_list.php">View All Rate Cards</a></p>
        </div>

        <?php if ($message): ?><div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

        <div class="form-section">
            <h3><?php echo $editing_rate_def ? 'Edit Rate Definition (ID: ' . htmlspecialchars($editing_rate_def['id']) . ')' : 'Add New Rate Definition'; ?></h3>
            <form action="admin_rate_card_details.php?rate_card_id=<?php echo $rate_card_id; ?><?php echo $editing_rate_def ? '&edit_rate_def_id=' . $editing_rate_def['id'] : ''; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <?php if ($editing_rate_def): ?><input type="hidden" name="rate_def_id_to_update" value="<?php echo htmlspecialchars($editing_rate_def['id']); ?>"><?php endif; ?>

                <div class="grid-container">
                    <div><label for="service_type_id">Service Type:</label>
                        <select id="service_type_id" name="service_type_id" required>
                            <option value="">-- Select Service --</option>
                            <?php foreach ($all_service_types as $st): ?>
                            <option value="<?php echo $st['id']; ?>" <?php echo (($editing_rate_def['service_type_id'] ?? $_POST_DATA_PREFILL['service_type_id'] ?? '') == $st['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($st['name'] . " (" . $st['service_code'] . ")"); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label for="rate_type">Rate Type:</label>
                        <select id="rate_type" name="rate_type" required onchange="toggleRateFields()">
                            <?php foreach ($rate_types as $rt): ?>
                            <option value="<?php echo $rt; ?>" <?php echo (($editing_rate_def['rate_type'] ?? $_POST_DATA_PREFILL['rate_type'] ?? 'FIXED') == $rt) ? 'selected' : ''; ?>><?php echo $rt; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="rate-type-fields">
                    <div id="fields_FIXED">
                        <label for="fixed_rate">Fixed Rate:</label>
                        <input type="number" id="fixed_rate" name="fixed_rate" step="0.01" value="<?php echo htmlspecialchars($editing_rate_def['fixed_rate'] ?? $_POST_DATA_PREFILL['fixed_rate'] ?? ''); ?>">
                    </div>
                    <div id="fields_WEIGHT_TIER">
                        <div class="grid-container">
                            <div><label for="min_weight_kg">Min Weight (kg):</label><input type="number" id="min_weight_kg" name="min_weight_kg" step="0.001" value="<?php echo htmlspecialchars($editing_rate_def['min_weight_kg'] ?? $_POST_DATA_PREFILL['min_weight_kg'] ?? ''); ?>"></div>
                            <div><label for="max_weight_kg">Max Weight (kg):</label><input type="number" id="max_weight_kg" name="max_weight_kg" step="0.001" value="<?php echo htmlspecialchars($editing_rate_def['max_weight_kg'] ?? $_POST_DATA_PREFILL['max_weight_kg'] ?? ''); ?>"></div>
                        </div>
                        <label for="rate_per_unit_or_tier">Rate for this Tier/Base Rate:</label>
                        <input type="number" id="rate_per_unit_or_tier" name="rate_per_unit_or_tier" step="0.01" value="<?php echo htmlspecialchars($editing_rate_def['rate_per_unit_or_tier'] ?? $_POST_DATA_PREFILL['rate_per_unit_or_tier'] ?? ''); ?>">
                        <label for="additional_kg_rate">Additional Per Kg Rate (Optional):</label>
                        <input type="number" id="additional_kg_rate" name="additional_kg_rate" step="0.01" value="<?php echo htmlspecialchars($editing_rate_def['additional_kg_rate'] ?? $_POST_DATA_PREFILL['additional_kg_rate'] ?? ''); ?>">
                    </div>
                </div>

                <div class="grid-container">
                    <div><label for="destination_zone_pattern">Dest. Zone Pattern (e.g., City, *, ZONEA):</label><input type="text" id="destination_zone_pattern" name="destination_zone_pattern" value="<?php echo htmlspecialchars($editing_rate_def['destination_zone_pattern'] ?? $_POST_DATA_PREFILL['destination_zone_pattern'] ?? '*'); ?>"></div>
                    <div><label for="currency_code">Currency:</label><input type="text" id="currency_code" name="currency_code" value="<?php echo htmlspecialchars($editing_rate_def['currency_code'] ?? $_POST_DATA_PREFILL['currency_code'] ?? 'SAR'); ?>" maxlength="3"></div>
                    <div><label for="priority">Priority (0=highest):</label><input type="number" id="priority" name="priority" value="<?php echo htmlspecialchars($editing_rate_def['priority'] ?? $_POST_DATA_PREFILL['priority'] ?? '0'); ?>"></div>
                </div>
                <label for="description_notes">Notes:</label><textarea id="description_notes" name="description_notes"><?php echo htmlspecialchars($editing_rate_def['description_notes'] ?? $_POST_DATA_PREFILL['description_notes'] ?? ''); ?></textarea>
                <label><input type="checkbox" name="is_active" value="1" <?php
                    $isActive = $editing_rate_def['is_active'] ?? $_POST_DATA_PREFILL['is_active'] ?? true; // Default to true for new
                    if ($editing_rate_def && isset($editing_rate_def['is_active'])) $isActive = $editing_rate_def['is_active']; // Prefer fetched if editing
                    elseif (isset($_POST_DATA_PREFILL['is_active'])) $isActive = $_POST_DATA_PREFILL['is_active']; // Then POST data
                    echo $isActive ? 'checked' : '';
                ?>> Is Active</label>

                <button type="submit" name="<?php echo $editing_rate_def ? 'update_rate_definition' : 'add_rate_definition'; ?>">
                    <?php echo $editing_rate_def ? 'Update Rate Definition' : 'Add Rate Definition'; ?>
                </button>
                <?php if ($editing_rate_def): ?><a href="admin_rate_card_details.php?rate_card_id=<?php echo $rate_card_id; ?>" style="margin-left:10px;">Cancel Edit</a><?php endif; ?>
            </form>
        </div>

        <h2>Existing Rate Definitions</h2>
        <?php if (empty($rate_definitions)): ?><p>No rate definitions for this rate card yet.</p><?php else: ?>
            <table><thead><tr><th>Service</th><th>Rate Type</th><th>Details</th><th>Zone</th><th>Prio.</th><th>Active</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($rate_definitions as $rd): ?>
                <tr>
                    <td><?php echo htmlspecialchars($rd['service_type_name'] . " (" . $rd['service_code'] . ")"); ?></td>
                    <td><?php echo htmlspecialchars($rd['rate_type']); ?></td>
                    <td>
                        <?php if ($rd['rate_type'] === 'FIXED'): ?> Rate: <?php echo htmlspecialchars(number_format($rd['fixed_rate'], 2)); ?>
                        <?php elseif ($rd['rate_type'] === 'WEIGHT_TIER'): ?>
                            <?php
                            echo ($rd['min_weight_kg']!==null ? "MinW: ".htmlspecialchars($rd['min_weight_kg'])." " : "");
                            echo ($rd['max_weight_kg']!==null ? "MaxW: ".htmlspecialchars($rd['max_weight_kg'])." " : "");
                            echo "<br>Rate: ".htmlspecialchars(number_format($rd['rate_per_unit_or_tier'],2));
                            if($rd['additional_kg_rate']!==null) echo " + ".htmlspecialchars(number_format($rd['additional_kg_rate'],2))."/add.kg";
                            ?>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($rd['currency_code']); ?>
                        <?php if(!empty($rd['description_notes'])) echo "<br><small><i>".htmlspecialchars($rd['description_notes'])."</i></small>"; ?>
                    </td>
                    <td><?php echo htmlspecialchars($rd['destination_zone_pattern']); ?></td>
                    <td><?php echo htmlspecialchars($rd['priority']); ?></td>
                    <td><?php echo $rd['is_active'] ? 'Yes' : 'No'; ?></td>
                    <td class="action-links">
                        <a href="admin_rate_card_details.php?rate_card_id=<?php echo $rate_card_id; ?>&edit_rate_def_id=<?php echo $rd['id']; ?>">Edit</a>
                        <form action="admin_rate_card_details.php?rate_card_id=<?php echo $rate_card_id; ?>" method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="rate_def_id_to_delete" value="<?php echo $rd['id']; ?>">
                            <button type="submit" name="delete_rate_definition" onclick="return confirm('Are you sure?');">Del</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </div>
    <script>
        function toggleRateFields() {
            const rateType = document.getElementById('rate_type').value;
            document.getElementById('fields_FIXED').style.display = (rateType === 'FIXED') ? 'block' : 'none';
            document.getElementById('fields_WEIGHT_TIER').style.display = (rateType === 'WEIGHT_TIER') ? 'block' : 'none';
        }
        document.addEventListener('DOMContentLoaded', toggleRateFields);
    </script>
</body>
</html>
