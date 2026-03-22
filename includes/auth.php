<?php
session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function getRole(): string {
    return $_SESSION['role'] ?? '';
}

function basePath(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir = rtrim(dirname($script), '/');

    foreach (['/admin', '/faculty', '/student', '/includes'] as $suffix) {
        if ($dir !== '' && substr($dir, -strlen($suffix)) === $suffix) {
            $dir = substr($dir, 0, -strlen($suffix));
            break;
        }
    }

    return $dir === '' ? '/' : $dir;
}

function redirectToLogin(): void {
    header('Location: ' . basePath() . '/login.php');
    exit();
}

function requireLogin(?string $role = null): void {
    if (!isLoggedIn()) {
        redirectToLogin();
    }

    if ($role !== null && getRole() !== $role) {
        redirectToLogin();
    }
}

function sanitize($conn, $data): string {
    return trim((string)$data);
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check($token): bool {
    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}
?>