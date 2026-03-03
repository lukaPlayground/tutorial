<?php
require_once __DIR__ . '/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? '')) {
    header('Location: index.php');
    exit;
}

$id   = (int)($_POST['id'] ?? 0);
$stmt = get_db()->prepare('SELECT user_id FROM posts WHERE id = ?');
$stmt->execute([$id]);
$post = $stmt->fetch();

if ($post && $post['user_id'] == $_SESSION['user_id']) {
    $del = get_db()->prepare('DELETE FROM posts WHERE id = ?');
    $del->execute([$id]);
}

header('Location: index.php');
exit;
