<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/finance_functions.php';

$page_title = "Admin - Add Rate Card";
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_add_rate_card'] ?? '')) {
        die('CSRF token validation failed.');
    }
    $data = [
        'name' => $_POST['name'] ?? '',
        'description' => $_POST['description'] ?? null,
        'is_active' => isset($_POST['is_active']),
        'valid_from' => $_POST['valid_from'] ?? null,
        'valid_to' => $_POST['valid_to'] ?? null,
    ];

    if (empty($data['name'])) {
        $message = 'Rate Card Name is required.';
        $message_type = 'error';
    } else {
        unset($_SESSION['error_message']);
        $rate_card_id = create_rate_card($data);
        if ($rate_card_id) {
            $_SESSION['flash_message'] = 'Rate Card "' . htmlspecialchars($data['name']) . '" added successfully! You can now add rate definitions.';
            $_SESSION['flash_message_type'] = 'success';
            header('Location: admin_rate_card_details.php?rate_card_id=' . $rate_card_id);
            exit;
        } else {
            $message = $_SESSION['error_message'] ?? 'Failed to add rate card.';
            $message_type = 'error';
            unset($_SESSION['error_message']);
        }
    }
}

if (empty($_SESSION['csrf_token_add_rate_card'])) {
    $_SESSION['csrf_token_add_rate_card'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <style> /* Basic styles */
        body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 700px; margin: 40px auto;}
        h1 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0; }
        label { display: block; margin-top: 15px; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="date"], textarea { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-size: 1rem;}
        input[type="checkbox"] { margin-top: 10px; margin-right: 8px; vertical-align: middle; width:auto;}
        textarea { min-height: 80px; resize: vertical;}
        .grid-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        input[type="submit"] { background-color: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; margin-top: 25px; }
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size:0.95rem;}
        .message.error { background-color: #f8d7da; color: #721c24;}
        .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align: center;}
        .nav-links a { margin: 0 10px; text-decoration: none; color: #007bff; }
        .top-nav { padding: 10px 20px; background-color: #333; color: #fff; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .top-nav a { color: #fff; text-decoration: none; margin-left: 15px; }
    </style>
</head>
<body>
    <div class="top-nav"><span>Admin Panel - Finance</span><div><a href="admin_rate_cards_list.php">Rate Cards</a><a href="logout.php">Logout</a></div></div>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <?php if ($message): ?><div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <form action="admin_add_rate_card.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_add_rate_card']); ?>">
            <div><label for="name">Rate Card Name (Unique):</label><input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required></div>
            <div><label for="description">Description:</label><textarea id="description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea></div>
            <div class="grid-container">
                <div><label for="valid_from">Valid From (Optional):</label><input type="date" id="valid_from" name="valid_from" value="<?php echo htmlspecialchars($_POST['valid_from'] ?? ''); ?>"></div>
                <div><label for="valid_to">Valid To (Optional):</label><input type="date" id="valid_to" name="valid_to" value="<?php echo htmlspecialchars($_POST['valid_to'] ?? ''); ?>"></div>
            </div>
            <div><label><input type="checkbox" name="is_active" value="1" <?php echo (isset($_POST['is_active']) || !$_POST) ? 'checked' : ''; ?>> Is Active</label></div>
            <input type="submit" value="Add Rate Card">
        </form>
        <div class="nav-links"><a href="admin_rate_cards_list.php">Back to List</a></div>
    </div>
</body>
</html>
