<?php require_once __DIR__ . '/header.php'; ?>
<?php require_auth(); ?>
<?php
$id   = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = get_db()->prepare('SELECT * FROM posts WHERE id = ?');
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post || $post['user_id'] != $_SESSION['user_id']) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = '잘못된 요청입니다.';
    } else {
        $title   = trim($_POST['title']   ?? '');
        $content = trim($_POST['content'] ?? '');

        if ($title === '' || $content === '') {
            $error = '제목과 내용을 모두 입력해주세요.';
        } else {
            $upd = get_db()->prepare('
                UPDATE posts SET title = ?, content = ?, updated_at = datetime(\'now\') WHERE id = ?
            ');
            $upd->execute([$title, $content, $id]);
            header('Location: view.php?id=' . $id);
            exit;
        }
    }
    $post['title']   = $_POST['title']   ?? $post['title'];
    $post['content'] = $_POST['content'] ?? $post['content'];
}
?>

<h2 style="margin-bottom:24px;font-size:20px;font-weight:700;">글 수정</h2>

<?php if ($error): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form class="form-card" method="post" action="edit.php">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
  <input type="hidden" name="id"         value="<?= $id ?>">

  <div class="form-group">
    <label for="title">제목</label>
    <input type="text" id="title" name="title"
           value="<?= htmlspecialchars($post['title']) ?>" required>
  </div>

  <div class="form-group">
    <label for="content">내용</label>
    <textarea id="content" name="content" required><?= htmlspecialchars($post['content']) ?></textarea>
  </div>

  <div style="display:flex;gap:8px;justify-content:flex-end">
    <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">취소</a>
    <button type="submit" class="btn btn-primary">저장</button>
  </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
