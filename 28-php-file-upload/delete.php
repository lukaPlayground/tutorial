<?php
require_once __DIR__ . '/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? '')) {
    header('Location: index.php');
    exit;
}

$id   = (int)($_POST['id'] ?? 0);
$stmt = get_db()->prepare('SELECT user_id, stored_name FROM files WHERE id = ?');
$stmt->execute([$id]);
$file = $stmt->fetch();

if ($file && $file['user_id'] == $_SESSION['user_id']) {
    // 물리 파일 삭제
    $path = UPLOAD_DIR . $file['stored_name'];
    if (file_exists($path)) unlink($path);

    // DB 레코드 삭제
    get_db()->prepare('DELETE FROM files WHERE id = ?')->execute([$id]);
}

header('Location: index.php');
exit;
