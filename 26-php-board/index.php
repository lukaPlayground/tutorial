<?php require_once __DIR__ . '/header.php'; ?>
<?php
$db   = get_db();
$page = max(1, (int)($_GET['page'] ?? 1));
$off  = ($page - 1) * POSTS_PER_PAGE;

$total = (int)$db->query('SELECT COUNT(*) FROM posts')->fetchColumn();
$pages = (int)ceil($total / POSTS_PER_PAGE);

$stmt = $db->prepare('
    SELECT p.id, p.title, p.created_at, u.name AS author
    FROM posts p JOIN users u ON p.user_id = u.id
    ORDER BY p.id DESC LIMIT ? OFFSET ?
');
$stmt->execute([POSTS_PER_PAGE, $off]);
$posts = $stmt->fetchAll();
?>

<div class="board-top">
  <h2>게시판</h2>
  <?php if (is_logged_in()): ?>
    <a href="write.php" class="btn btn-primary">+ 글쓰기</a>
  <?php endif; ?>
</div>

<table class="post-table">
  <thead>
    <tr>
      <th class="td-num">#</th>
      <th>제목</th>
      <th class="td-author">작성자</th>
      <th class="td-date">날짜</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($posts)): ?>
      <tr><td colspan="4" class="empty">아직 게시글이 없습니다.</td></tr>
    <?php else: ?>
      <?php foreach ($posts as $p): ?>
        <tr>
          <td class="td-num"><?= $p['id'] ?></td>
          <td class="td-title">
            <a href="view.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></a>
          </td>
          <td class="td-author"><?= htmlspecialchars($p['author']) ?></td>
          <td class="td-date"><?= substr($p['created_at'], 0, 10) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php if ($pages > 1): ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a href="?page=<?= $i ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
