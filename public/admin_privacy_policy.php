<?php require_once __DIR__ . '/../src/auth_check.php'; ?>
<?php
// session_start(); // Handled by auth_check.php
require_once __DIR__ . '/../src/legal_content_functions.php';
// Basic auth check - Handled by auth_check.php
// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
//     die('Access Denied. Admin role required.');
// }

$message = '';
$message_type = '';

// Handle Add New Version
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_version'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }
    $version = $_POST['version'] ?? '';
    $content = $_POST['content'] ?? '';
    if (!empty($version) && !empty($content)) {
        if (add_privacy_policy_version($content, $version)) {
            $message = 'New privacy policy version added successfully.';
            $message_type = 'success';
        } else {
            $message = 'Failed to add new version.';
            $message_type = 'error';
        }
    } else {
        $message = 'Version and content are required.';
        $message_type = 'error';
    }
}

// Handle Publish Version
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_version'])) {
     if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token_publish_' . $_POST['version_id']] ?? '')) {
        die('CSRF token validation failed for publish action.');
    }
    $version_id = $_POST['version_id'] ?? null;
    if ($version_id) {
        if (publish_privacy_policy_version((int)$version_id)) {
            $message = 'Privacy policy version published successfully.';
            $message_type = 'success';
        } else {
            $message = 'Failed to publish version.';
            $message_type = 'error';
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$policy_versions = get_all_privacy_policy_versions();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Privacy Policy Management</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f4f4f4; }
             .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 800px; margin: 20px auto; }
        h1, h2 { color: #333; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="text"], textarea { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        textarea { min-height: 150px; resize: vertical; }
        input[type="submit"], button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        input[type="submit"]:hover, button:hover { background-color: #0056b3; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        .published { background-color: #d4edda; }
        .action-form { display: inline-block; margin-left: 5px; }
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
        <h1>Admin - Privacy Policy Management</h1>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <h2>Add New Version</h2>
        <form action="admin_privacy_policy.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="version">Version (e.g., 1.0, 2023-10-26):</label>
            <input type="text" id="version" name="version" required>

            <label for="content">Content (HTML is allowed):</label>
            <textarea id="content" name="content" required></textarea>

            <input type="submit" name="add_version" value="Add New Version">
        </form>

        <h2>Existing Versions</h2>
        <?php if (empty($policy_versions)): ?>
            <p>No versions found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Version</th>
                        <th>Created At</th>
                        <th>Published</th>
                        <th>Published At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($policy_versions as $pv): ?>
                        <?php
                            // Generate a unique CSRF token for each publish form
                            $publish_csrf_token_name = 'csrf_token_publish_' . $pv['id'];
                            if (empty($_SESSION[$publish_csrf_token_name])) {
                                $_SESSION[$publish_csrf_token_name] = bin2hex(random_bytes(32));
                            }
                        ?>
                        <tr <?php echo $pv['is_published'] ? 'class="published"' : ''; ?>>
                            <td><?php echo htmlspecialchars($pv['id']); ?></td>
                            <td><?php echo htmlspecialchars($pv['version']); ?></td>
                            <td><?php echo htmlspecialchars($pv['created_at']); ?></td>
                            <td><?php echo $pv['is_published'] ? 'Yes' : 'No'; ?></td>
                            <td><?php echo htmlspecialchars($pv['published_at'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if (!$pv['is_published']): ?>
                                    <form action="admin_privacy_policy.php" method="POST" class="action-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION[$publish_csrf_token_name]); ?>">
                                        <input type="hidden" name="version_id" value="<?php echo htmlspecialchars($pv['id']); ?>">
                                        <button type="submit" name="publish_version">Publish</button>
                                    </form>
                                <?php else: ?>
                                    Currently Published
                                <?php endif; ?>
                                <!-- Add view/edit content buttons if needed -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <p><a href="privacy_policy.php" target="_blank">View Public Privacy Policy Page</a></p>
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
