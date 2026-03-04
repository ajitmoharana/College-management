// student/logout.php ✅ UPDATED (consistent logout)
<?php
require_once '../includes/auth.php';
session_destroy();
header("Location: ../login.php");
exit();
?>