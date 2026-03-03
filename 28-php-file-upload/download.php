<?php
require_once __DIR__ . '/db.php';

$id   = (int)($_GET['id'] ?? 0);
$stmt = get_db()->prepare('SELECT orig_name, stored_name, mime, size FROM files WHERE id = ?');
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    exit('File not found');
}

$path = UPLOAD_DIR . $file['stored_name'];
if (!file_exists($path)) {
    http_response_code(404);
    exit('File not found on disk');
}

// 다운로드 헤더 설정
header('Content-Type: ' . $file['mime']);
header('Content-Disposition: attachment; filename="' . rawurlencode($file['orig_name']) . '"');
header('Content-Length: ' . $file['size']);
header('Cache-Control: no-cache');

readfile($path);
exit;
