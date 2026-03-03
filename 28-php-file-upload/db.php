<?php
/**
 * DB 연결 — PDO SQLite
 * MySQL로 전환: new PDO('mysql:host=localhost;dbname=fileup;charset=utf8mb4', $user, $pass)
 */

define('DB_PATH',       __DIR__ . '/data/fileup.db');
define('UPLOAD_DIR',    __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 20 * 1024 * 1024); // 20 MB

define('ALLOWED_MIME', [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'application/pdf',
    'text/plain', 'text/csv',
    'application/zip',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
]);

function get_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    foreach ([dirname(DB_PATH), UPLOAD_DIR] as $dir) {
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    // 사용자 테이블
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            name       TEXT    NOT NULL,
            email      TEXT    NOT NULL UNIQUE,
            password   TEXT    NOT NULL,
            created_at TEXT    NOT NULL DEFAULT (datetime('now'))
        )
    ");

    // 파일 테이블
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS files (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id      INTEGER NOT NULL,
            orig_name    TEXT    NOT NULL,
            stored_name  TEXT    NOT NULL UNIQUE,
            description  TEXT    NOT NULL DEFAULT '',
            size         INTEGER NOT NULL,
            mime         TEXT    NOT NULL,
            created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at   TEXT,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    return $pdo;
}

/** 바이트를 읽기 좋은 단위로 변환 */
function fmt_size(int $bytes): string
{
    if ($bytes < 1024)       return $bytes . ' B';
    if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}

/** MIME → 아이콘 이모지 */
function mime_icon(string $mime): string
{
    if (str_starts_with($mime, 'image/'))        return '🖼️';
    if ($mime === 'application/pdf')             return '📄';
    if (str_contains($mime, 'spreadsheet') || $mime === 'application/vnd.ms-excel') return '📊';
    if (str_contains($mime, 'word') || $mime === 'application/msword') return '📝';
    if ($mime === 'application/zip')             return '🗜️';
    if ($mime === 'text/csv')                    return '📋';
    return '📁';
}
