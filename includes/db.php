// includes/db.php ✅ UPDATED (UTF-8 + strict reporting helper)
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'college_management');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Always use utf8mb4
mysqli_set_charset($conn, 'utf8mb4');

// Optional: turn MySQL errors into clearer PHP warnings during development
// Comment these 2 lines in production if you want.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>