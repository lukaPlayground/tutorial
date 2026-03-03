<?php require_once __DIR__ . '/header.php'; ?>
<?php
$stmt = get_db()->prepare('
    SELECT f.*, u.name AS uploader
    FROM files f JOIN users u ON f.user_id = u.id
    ORDER BY f.id DESC
');
$stmt->execute();
$files = $stmt->fetchAll();
?>

<div class="list-top">
  <h2>파일 목록</h2>
  <?php if (is_logged_in()): ?>
    <a href="upload.php" class="btn btn-primary">+ 업로드</a>
  <?php endif; ?>
</div>

<table class="file-table">
  <thead>
    <tr>
      <th class="td-icon"></th>
      <th>파일명</th>
      <th class="td-size">크기</th>
      <th class="td-uploader">올린이</th>
      <th class="td-date">날짜</th>
      <th class="td-actions"></th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($files)): ?>
      <tr><td colspan="6" class="empty">업로드된 파일이 없습니다.</td></tr>
    <?php else: ?>
      <?php foreach ($files as $f): ?>
        <tr>
          <td class="td-icon"><?= mime_icon($f['mime']) ?></td>
          <td class="td-name">
            <?= htmlspecialchars($f['orig_name']) ?>
            <?php if ($f['description'] !== ''): ?>
              <div class="desc"><?= htmlspecialchars($f['description']) ?></div>
            <?php endif; ?>
          </td>
          <td class="td-size"><?= fmt_size($f['size']) ?></td>
          <td class="td-uploader"><?= htmlspecialchars($f['uploader']) ?></td>
          <td class="td-date"><?= substr($f['created_at'], 0, 10) ?></td>
          <td class="td-actions">
            <div class="actions">
              <a href="download.php?id=<?= $f['id'] ?>" class="btn btn-secondary btn-sm">다운로드</a>
              <?php if (is_logged_in() && $_SESSION['user_id'] == $f['user_id']): ?>
                <a href="edit.php?id=<?= $f['id'] ?>" class="btn btn-secondary btn-sm">수정</a>
                <form method="post" action="delete.php"
                      onsubmit="return confirm('정말 삭제하시겠습니까?')" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="id"         value="<?= $f['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">삭제</button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/footer.php'; ?>
