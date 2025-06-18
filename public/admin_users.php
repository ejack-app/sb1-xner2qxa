<?php
require_once __DIR__ . '/../src/auth_check.php'; // Handles session start and admin check
// user_functions.php is already included by auth_check.php

$message = '';
$message_type = ''; // 'success' or 'error'

// Handle Add New User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_add_user'] ?? '')) {
        die('CSRF token validation failed for add user.');
    }
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user'; // Default role
    $is_active = isset($_POST['is_active']);


    if (!empty($username) && !empty($email) && !empty($password) && !empty($role)) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Unset any previous error message from create_user before calling it
            unset($_SESSION['error_message']);
            if (create_user($username, $email, $password, $role, $is_active)) {
                $message = 'User created successfully!';
                $message_type = 'success';
            } else {
                // Error message is set in $_SESSION by create_user on failure
                $message = $_SESSION['error_message'] ?? 'Failed to create user. Unknown error.';
                $message_type = 'error';
                unset($_SESSION['error_message']); // Clear it after displaying
            }
        } else {
            $message = 'Invalid email format.';
            $message_type = 'error';
        }
    } else {
        $message = 'All fields (Username, Email, Password, Role) are required.';
        $message_type = 'error';
    }
}

// Generate CSRF token for adding user
if (empty($_SESSION['csrf_token_add_user'])) {
    $_SESSION['csrf_token_add_user'] = bin2hex(random_bytes(32));
}

$users = get_all_users();
$available_roles = ['admin', 'seller', 'picker', 'customer_service', 'user']; // Define available roles

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - User Management</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #333; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
        }
        input[type="checkbox"] { margin-top: 10px; }
        input[type="submit"] { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 20px; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        .logout-link { float: right; margin-bottom:10px; }
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
        <h1>Admin - User Management</h1>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])): // Display messages from require_admin redirect ?>
            <div class="message error"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>


        <h2>Add New User</h2>
        <form action="admin_users.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_add_user']); ?>">

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <label for="role">Role:</label>
            <select id="role" name="role" required>
                <?php foreach ($available_roles as $role_val): ?>
                    <option value="<?php echo htmlspecialchars($role_val); ?>"><?php echo htmlspecialchars(ucfirst($role_val)); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="is_active">
                <input type="checkbox" id="is_active" name="is_active" value="1" checked> Active
            </label>

            <input type="submit" name="add_user" value="Add User">
        </form>

        <h2>Existing Users</h2>
        <?php if (empty($users)): ?>
            <p>No users found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Active</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                            <td><?php echo $user['is_active'] ? 'Yes' : 'No'; ?></td>
                            <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                            <td>
                                <!-- Placeholder for Edit/Deactivate actions -->
                                <small>Edit | Deactivate</small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
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
