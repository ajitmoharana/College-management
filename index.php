<?php
require_once 'includes/auth.php';

if (isLoggedIn()) {
    $role = getRole();
    if (!in_array($role, ['admin','faculty','student'], true)) {
        header("Location: login.php");
        exit();
    }
    header("Location: {$role}/dashboard.php");
    exit();
}

header("Location: login.php");
exit();
?>