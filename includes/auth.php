// includes/auth.php ✅ UPDATED (portable redirects + CSRF helpers)
<?php
session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function getRole(): string {
    return $_SESSION['role'] ?? '';
}

// Detect base path automatically (e.g., /college-management)
function basePath(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $parts  = explode('/', trim($script, '/'));
    return '/' . ($parts[0] ?? '');
}

function redirectToLogin(): void {
    header("Location: " . basePath() . "/login.php");
    exit();
}

function requireLogin(?string $role = null): void {
    if (!isLoggedIn()) {
        redirectToLogin();
    }
    if ($role && getRole() !== $role) {
        redirectToLogin();
    }
}

// Keep sanitize for basic trimming + safe display, but use prepared statements for SQL safety
function sanitize($conn, $data): string {
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim((string)$data))));
}

/* ========= CSRF ========= */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check($token): bool {
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}
?>