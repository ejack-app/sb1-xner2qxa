<?php
require_once __DIR__ . '/../src/user_functions.php'; // Also starts session

$error_message = '';

if (is_logged_in()) {
    // If already logged in, redirect to a dashboard or admin area
    header('Location: admin_users.php'); // Or a general dashboard page
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = 'Username and password are required.';
    } else {
        $user = get_user_by_username($username);
        if ($user && $user['is_active'] && verify_password($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];

            // Regenerate session ID for security
            session_regenerate_id(true);

            $redirect_url = $_SESSION['redirect_url'] ?? 'admin_users.php'; // Default redirect
            unset($_SESSION['redirect_url']);
            header('Location: ' . $redirect_url);
            exit;
        } else if ($user && !$user['is_active']) {
            $error_message = 'Your account is inactive. Please contact an administrator.';
        }
        else {
            $error_message = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f4f4f4; margin: 0; }
        .login-container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.15); width: 320px; }
        h1 { text-align: center; color: #333; margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        input[type="submit"] { width: 100%; background-color: #007bff; color: white; padding: 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center;}
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Logistics Platform</h1>
        <?php if ($error_message): ?>
            <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['success_message'])): ?>
            <p style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; text-align:center;"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])): // Display messages from require_admin redirect (e.g. access denied) ?>
            <p class="error"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></p>
        <?php endif; ?>


        <form action="login.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required autofocus>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" value="Login">
        </form>
    </div>
</body>
</html>
