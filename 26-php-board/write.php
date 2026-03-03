<?php require_once __DIR__ . '/header.php'; ?>
<?php require_auth(); ?>
<?php
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
            $stmt = get_db()->prepare('INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)');
            $stmt->execute([$_SESSION['user_id'], $title, $content]);
            $id = get_db()->lastInsertId();
            header('Location: view.php?id=' . $id);
            exit;
        }
    }
}
?>

<h2 style="margin-bottom:24px;font-size:20px;font-weight:700;">글쓰기</h2>

<?php if ($error): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form class="form-card" method="post" action="write.php">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

  <div class="form-group">
    <label for="title">제목</label>
    <input type="text" id="title" name="title"
           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
           placeholder="제목을 입력하세요" required>
  </div>

  <div class="form-group">
    <label for="content">내용</label>
    <textarea id="content" name="content" placeholder="내용을 입력하세요" required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
  </div>

  <div style="display:flex;gap:8px;justify-content:flex-end">
    <a href="index.php" class="btn btn-secondary">취소</a>
    <button type="submit" class="btn btn-primary">등록</button>
  </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
