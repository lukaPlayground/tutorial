<?php
/**
 * 인증 헬퍼
 */

require_once __DIR__ . '/db.php';

function session_start_once(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/** 로그인 여부 확인 */
function is_logged_in(): bool
{
    session_start_once();
    return !empty($_SESSION['user_id']);
}

/** 로그인 안 됐으면 login.php 로 리다이렉트 */
function require_auth(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/** 로그인 된 상태면 dashboard.php 로 리다이렉트 */
function redirect_if_logged_in(): void
{
    if (is_logged_in()) {
        header('Location: dashboard.php');
        exit;
    }
}

/** 현재 로그인 유저 정보 반환 */
function current_user(): array|null
{
    session_start_once();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = get_db()->prepare('SELECT id, name, email, created_at FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/** CSRF 토큰 생성 (세션에 저장) */
function csrf_token(): string
{
    session_start_once();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** CSRF 토큰 검증 */
function verify_csrf(string $token): bool
{
    session_start_once();
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}
