<?php
/**
 * DB 연결 — PDO SQLite
 * MySQL로 전환: new PDO('mysql:host=localhost;dbname=restapi;charset=utf8mb4', $user, $pass)
 */

define('DB_PATH', __DIR__ . '/data/tasks.db');

function get_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tasks (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            title      TEXT    NOT NULL,
            done       INTEGER NOT NULL DEFAULT 0,
            created_at TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT
        )
    ");

    return $pdo;
}
