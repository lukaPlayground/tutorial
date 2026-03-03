<?php require_once __DIR__ . '/header.php'; ?>
<?php
$id   = (int)($_GET['id'] ?? 0);
$stmt = get_db()->prepare('
    SELECT p.*, u.name AS author
    FROM posts p JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
');
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    echo '<p style="color:var(--muted);text-align:center;padding:60px 0">존재하지 않는 게시글입니다.</p>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$is_owner = is_logged_in() && $_SESSION['user_id'] == $post['user_id'];
?>

<div style="margin-bottom:16px">
  <a href="index.php" class="btn btn-secondary">← 목록</a>
</div>

<div class="post-view">
  <div class="post-view-title"><?= htmlspecialchars($post['title']) ?></div>
  <div class="post-view-meta">
    <span><?= htmlspecialchars($post['author']) ?></span>
    <span><?= substr($post['created_at'], 0, 16) ?></span>
    <?php if ($post['updated_at']): ?>
      <span style="opacity:.5">(수정됨)</span>
    <?php endif; ?>
  </div>
  <div class="post-view-content"><?= htmlspecialchars($post['content']) ?></div>

  <?php if ($is_owner): ?>
    <div class="post-actions">
      <a href="edit.php?id=<?= $post['id'] ?>" class="btn btn-secondary">수정</a>
      <form method="post" action="delete.php"
            onsubmit="return confirm('정말 삭제하시겠습니까?')">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id"         value="<?= $post['id'] ?>">
        <button type="submit" class="btn btn-danger">삭제</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
