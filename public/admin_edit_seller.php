<?php
require_once __DIR__ . '/../src/auth_check.php';
require_once __DIR__ . '/../src/seller_functions.php';
require_once __DIR__ . '/../src/user_functions.php'; // For get_all_users

$page_title = "Admin - Edit Seller";
$message = '';
$message_type = '';
$seller_id = $_GET['id'] ?? null;

if (!$seller_id || !filter_var($seller_id, FILTER_VALIDATE_INT)) {
    $_SESSION['flash_message'] = "Invalid Seller ID."; $_SESSION['flash_message_type'] = "error";
    header('Location: admin_sellers_list.php'); exit;
}
$seller_id = (int)$seller_id;
$seller = get_seller_by_id($seller_id);

if (!$seller) {
    $_SESSION['flash_message'] = "Seller not found."; $_SESSION['flash_message_type'] = "error";
    header('Location: admin_sellers_list.php'); exit;
}

$all_users = get_all_users(); // For User ID dropdown

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_edit_seller'] ?? '')) {
        die('CSRF token validation failed.');
    }
    $data = [
        'seller_name' => $_POST['seller_name'] ?? '',
        'contact_person' => $_POST['contact_person'] ?? null,
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? null,
        'address' => $_POST['address'] ?? null,
        'commission_rate_percentage' => $_POST['commission_rate_percentage'] ?? null,
        'user_id' => $_POST['user_id'] ?? null,
        'is_active' => isset($_POST['is_active']),
    ];

    if (empty($data['seller_name']) || empty($data['email'])) {
        $message = 'Seller Name and Email are required.'; $message_type = 'error';
        $seller = array_merge($seller, $data);
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format provided.'; $message_type = 'error';
        $seller = array_merge($seller, $data);
    } else {
        unset($_SESSION['error_message']);
        if (update_seller($seller_id, $data)) {
            $_SESSION['flash_message'] = 'Seller "' . htmlspecialchars($data['seller_name']) . '" updated successfully!';
            $_SESSION['flash_message_type'] = 'success';
            header('Location: admin_sellers_list.php'); exit;
        } else {
            $message = $_SESSION['error_message'] ?? 'Failed to update seller.'; $message_type = 'error';
            unset($_SESSION['error_message']);
            $seller = array_merge($seller, $data);
        }
    }
}

if (empty($_SESSION['csrf_token_edit_seller'])) {
    $_SESSION['csrf_token_edit_seller'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title . " - " . $seller['seller_name']); ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; padding:0; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 800px; margin: 40px auto;}
        h1 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0; }
        label { display: block; margin-top: 15px; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="email"], input[type="tel"], input[type="number"], textarea, select {
            width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd;
            border-radius: 5px; box-sizing: border-box; font-size: 1rem;
        }
        input[type="checkbox"] { margin-top: 10px; margin-right: 8px; vertical-align: middle; width:auto;}
        textarea { min-height: 80px; resize: vertical;}
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        input[type="submit"] {
            background-color: #007bff; color: white; padding: 12px 20px; border: none;
            border-radius: 5px; cursor: pointer; font-size: 1rem; margin-top: 25px;
        }
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; font-size:0.95rem;}
        .message.error { background-color: #f8d7da; color: #721c24;}
        .nav-links { margin-top: 25px; padding-top:15px; border-top:1px solid #eee; text-align: center;}
        .nav-links a { margin: 0 10px; text-decoration: none; color: #007bff; }
        .top-nav { padding: 10px 20px; background-color: #333; color: #fff; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .top-nav a { color: #fff; text-decoration: none; margin-left: 15px; }
    </style>
</head>
<body>
    <div class="top-nav">
        <span>Admin Panel - Seller Management</span>
        <div><a href="admin_dashboard.php">Dashboard</a><a href="logout.php">Logout</a></div>
    </div>
    <div class="container">
        <h1><?php echo htmlspecialchars($page_title . " (ID: ".$seller['id'].")"); ?></h1>
        <?php if ($message): ?><div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <form action="admin_edit_seller.php?id=<?php echo $seller_id; ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_edit_seller']); ?>">

            <div class="grid-container">
                <div><label for="seller_name">Seller Name:</label><input type="text" id="seller_name" name="seller_name" value="<?php echo htmlspecialchars($seller['seller_name'] ?? ''); ?>" required></div>
                <div><label for="contact_person">Contact Person:</label><input type="text" id="contact_person" name="contact_person" value="<?php echo htmlspecialchars($seller['contact_person'] ?? ''); ?>"></div>
            </div>
            <div class="grid-container">
                <div><label for="email">Email:</label><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($seller['email'] ?? ''); ?>" required></div>
                <div><label for="phone">Phone:</label><input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($seller['phone'] ?? ''); ?>"></div>
            </div>
            <div><label for="address">Address:</label><textarea id="address" name="address"><?php echo htmlspecialchars($seller['address'] ?? ''); ?></textarea></div>

            <div class="grid-container">
                <div><label for="commission_rate_percentage">Commission Rate (%):</label><input type="number" id="commission_rate_percentage" name="commission_rate_percentage" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($seller['commission_rate_percentage'] ?? '0.00'); ?>"></div>
                <div><label for="user_id">Linked User Account (Optional):</label>
                    <select id="user_id" name="user_id">
                        <option value="">-- None --</option>
                        <?php foreach ($all_users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo (($seller['user_id'] ?? null) == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username'] . " (" . $user['email'] . ")"); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div><label><input type="checkbox" name="is_active" value="1" <?php echo ($seller['is_active'] ?? false) ? 'checked' : ''; ?>> Is Active</label></div>
            <input type="submit" value="Update Seller">
        </form>
        <div class="nav-links"><a href="admin_sellers_list.php">Back to Sellers List</a></div>
    </div>
</body>
</html>
