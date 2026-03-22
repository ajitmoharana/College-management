<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    $role = getRole();
    header("Location: $role/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = sanitize($conn, $_POST['role'] ?? '');

    if (empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, name, password, role FROM users WHERE email = ? AND role = ?");
        mysqli_stmt_bind_param($stmt, "ss", $email, $role);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role']      = $user['role'];
            header("Location: {$user['role']}/dashboard.php");
            exit();
        } else {
            $error = "Invalid credentials. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – College Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <div class="icon">🎓</div>
            <h2>College Management</h2>
            <p>Sign in to your account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" onsubmit="return validateForm('loginForm')" id="loginForm">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <div class="form-group">
                <label>Login As</label>
                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="admin"   <?= ($_POST['role'] ?? '') === 'admin'   ? 'selected' : '' ?>>Admin</option>
                    <option value="faculty" <?= ($_POST['role'] ?? '') === 'faculty' ? 'selected' : '' ?>>Faculty</option>
                    <option value="student" <?= ($_POST['role'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Sign In →</button>
        </form>

    </div>
</div>
<script src="js/main.js"></script>
</body>
</html>