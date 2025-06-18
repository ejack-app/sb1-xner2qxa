<?php
// This file should be included at the top of admin pages.
require_once __DIR__ . '/user_functions.php'; // Also starts session if not started

// Determine if admin privileges are required based on the script name or a passed variable
// For simplicity, we assume any script including this directly needs admin rights.
// More granular control could be achieved by passing a $required_role parameter.

require_admin();
?>
