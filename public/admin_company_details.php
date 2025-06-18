<?php require_once __DIR__ . '/../src/auth_check.php'; ?>
<?php
// session_start(); // For status messages - Handled by auth_check.php via user_functions.php
require_once __DIR__ . '/../src/company_details_functions.php';

$message = '';
$message_type = ''; // 'success' or 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF protection (can be enhanced)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $data = [
        'company_name' => $_POST['company_name'] ?? '',
        'address' => $_POST['address'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'website' => $_POST['website'] ?? '',
        'logo_url' => $_POST['logo_url'] ?? '',
        'registration_number' => $_POST['registration_number'] ?? '',
        'vat_number' => $_POST['vat_number'] ?? '',
        'default_courier_company_id' => $_POST['default_courier_company_id'] ?? null,
    ];

    if (update_company_details($data)) {
        $message = 'Company details updated successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to update company details. Please check logs.';
        $message_type = 'error';
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$company_details = get_company_details();
if (!$company_details) {
    // Provide default empty values if no details exist yet, to prevent errors in the form
    $company_details = [
        'company_name' => '', 'address' => '', 'phone' => '', 'email' => '',
        'website' => '', 'logo_url' => '', 'registration_number' => '', 'vat_number' => '',
        'default_courier_company_id' => null
    ];
}
$courier_companies = get_all_courier_companies();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Details</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f4f4f4; }
             .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 800px; margin: 20px auto; }
        h1 { color: #333; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="url"], textarea, select {
            width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
        }
        textarea { resize: vertical; min-height: 80px; }
        input[type="submit"] {
            background-color: #007bff; color: white; padding: 10px 15px; border: none;
            border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 20px;
        }
        input[type="submit"]:hover { background-color: #0056b3; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
             nav { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
             nav a { margin-right: 15px; text-decoration: none; color: #007bff; }
             nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
            <nav>
                <a href="logout.php" style="float: right;">Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>)</a>
                Logged in as: <strong><?php echo htmlspecialchars($_SESSION['user_role'] ?? ''); ?></strong>
            </nav>
        <h1>Company Details</h1>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="admin_company_details.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <label for="company_name">Company Name:</label>
            <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($company_details['company_name'] ?? ''); ?>" required>

            <label for="address">Address:</label>
            <textarea id="address" name="address"><?php echo htmlspecialchars($company_details['address'] ?? ''); ?></textarea>

            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($company_details['phone'] ?? ''); ?>">

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($company_details['email'] ?? ''); ?>">

            <label for="website">Website:</label>
            <input type="url" id="website" name="website" value="<?php echo htmlspecialchars($company_details['website'] ?? ''); ?>">

            <label for="logo_url">Logo URL:</label>
            <input type="url" id="logo_url" name="logo_url" value="<?php echo htmlspecialchars($company_details['logo_url'] ?? ''); ?>">

            <label for="registration_number">Registration Number:</label>
            <input type="text" id="registration_number" name="registration_number" value="<?php echo htmlspecialchars($company_details['registration_number'] ?? ''); ?>">

            <label for="vat_number">VAT Number:</label>
            <input type="text" id="vat_number" name="vat_number" value="<?php echo htmlspecialchars($company_details['vat_number'] ?? ''); ?>">

            <label for="default_courier_company_id">Default Courier Company:</label>
            <select id="default_courier_company_id" name="default_courier_company_id">
                <option value="">-- Select Default Courier --</option>
                <?php foreach ($courier_companies as $courier): ?>
                    <option value="<?php echo htmlspecialchars($courier['id']); ?>"
                        <?php echo (($company_details['default_courier_company_id'] ?? null) == $courier['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($courier['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="submit" value="Save Company Details">
        </form>
            <hr style="margin-top: 30px;">
            <p>Admin Navigation:
                <a href="admin_company_details.php">Company Details</a> |
                <a href="admin_privacy_policy.php">Privacy Policy</a> |
                <a href="admin_terms_conditions.php">Terms & Conditions</a> |
                <a href="admin_users.php">User Management</a>
            </p>
    </div>
</body>
</html>
