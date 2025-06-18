<?php
require_once __DIR__ . '/../src/legal_content_functions.php';
$policy_content = get_published_privacy_policy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Privacy Policy</title>
    <style>
        body { font-family: sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; background-color: #fff; border: 1px solid #ddd; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Privacy Policy</h1>
        <?php if ($policy_content): ?>
            <div><?php echo $policy_content; // Content is expected to be safe HTML if entered by admin ?></div>
        <?php else: ?>
            <p>The privacy policy is not available at the moment. Please check back later.</p>
        <?php endif; ?>
    </div>
</body>
</html>
