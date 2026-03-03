<?php
require_once __DIR__ . '/db.php';

function session_start_once(): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function is_logged_in(): bool
{
    session_start_once();
    return !empty($_SESSION['user_id']);
}

function require_auth(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function redirect_if_logged_in(): void
{
    if (is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

function current_user(): ?array
{
    session_start_once();
    if (empty($_SESSION['user_id'])) return null;
    $stmt = get_db()->prepare('SELECT id, name, email FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function csrf_token(): string
{
    session_start_once();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool
{
    session_start_once();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
