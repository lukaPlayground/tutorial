<?php require_once __DIR__ . '/header.php'; ?>
<?php require_auth(); ?>
<?php
$id   = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = get_db()->prepare('SELECT * FROM files WHERE id = ?');
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file || $file['user_id'] != $_SESSION['user_id']) {
    header('Location: index.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = '잘못된 요청입니다.';
    } else {
        $orig_name  = trim($_POST['orig_name']   ?? '');
        $desc       = trim($_POST['description'] ?? '');
        $new_stored = $file['stored_name'];
        $new_mime   = $file['mime'];
        $new_size   = $file['size'];

        if ($orig_name === '') {
            $error = '파일명을 입력해주세요.';
        } else {
            // 새 파일이 업로드됐으면 교체
            if (!empty($_FILES['new_file']['name']) && $_FILES['new_file']['error'] === UPLOAD_ERR_OK) {
                $f = $_FILES['new_file'];

                if ($f['size'] > MAX_FILE_SIZE) {
                    $error = '파일 크기 초과 (최대 ' . fmt_size(MAX_FILE_SIZE) . ')';
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime  = finfo_file($finfo, $f['tmp_name']);
                    finfo_close($finfo);

                    if (!in_array($mime, ALLOWED_MIME, true)) {
                        $error = '허용되지 않는 파일 형식 (' . $mime . ')';
                    } else {
                        $ext        = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                        $new_stored = bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');
                        $dest       = UPLOAD_DIR . $new_stored;

                        if (!move_uploaded_file($f['tmp_name'], $dest)) {
                            $error = '파일 저장에 실패했습니다.';
                        } else {
                            // 기존 파일 삭제
                            $old = UPLOAD_DIR . $file['stored_name'];
                            if (file_exists($old)) unlink($old);
                            $new_mime = $mime;
                            $new_size = $f['size'];
                        }
                    }
                }
            }

            if ($error === '') {
                $upd = get_db()->prepare('
                    UPDATE files
                    SET orig_name = ?, stored_name = ?, description = ?,
                        size = ?, mime = ?, updated_at = datetime(\'now\')
                    WHERE id = ?
                ');
                $upd->execute([$orig_name, $new_stored, $desc, $new_size, $new_mime, $id]);

                // $file 갱신 (폼 재표시용)
                $file['orig_name']   = $orig_name;
                $file['stored_name'] = $new_stored;
                $file['description'] = $desc;
                $file['size']        = $new_size;
                $file['mime']        = $new_mime;

                $success = '수정이 완료되었습니다.';
            }
        }
    }
}
?>

<h2 style="margin-bottom:24px;font-size:20px;font-weight:700;">파일 수정</h2>

<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form class="form-card" method="post" action="edit.php" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
  <input type="hidden" name="id"         value="<?= $id ?>">

  <div class="form-group">
    <label>현재 파일</label>
    <div style="font-size:14px;color:var(--muted);padding:8px 0">
      <?= mime_icon($file['mime']) ?>
      <?= htmlspecialchars($file['orig_name']) ?>
      <span style="font-size:12px;opacity:.5">(<?= fmt_size($file['size']) ?> · <?= $file['mime'] ?>)</span>
    </div>
  </div>

  <div class="form-group">
    <label for="orig_name">파일명</label>
    <input type="text" id="orig_name" name="orig_name"
           value="<?= htmlspecialchars($file['orig_name']) ?>" required>
  </div>

  <div class="form-group">
    <label for="description">설명</label>
    <textarea id="description" name="description"
              placeholder="파일 설명 (선택)"><?= htmlspecialchars($file['description']) ?></textarea>
  </div>

  <div class="form-group">
    <label>파일 교체 <span style="font-weight:400;opacity:.6">(선택 — 선택하지 않으면 현재 파일 유지)</span></label>
    <div class="file-input-wrap">
      <input type="file" name="new_file" id="newFileInput">
      <label class="file-input-label" for="newFileInput">
        새 파일 선택 (클릭)<br>
        <strong>교체할 파일 선택</strong>
      </label>
      <div class="file-hint">최대 <?= fmt_size(MAX_FILE_SIZE) ?></div>
      <ul class="upload-list" id="newFileList"></ul>
    </div>
  </div>

  <div style="display:flex;gap:8px;justify-content:flex-end">
    <a href="index.php" class="btn btn-secondary">취소</a>
    <button type="submit" class="btn btn-primary">저장</button>
  </div>
</form>

<script>
document.getElementById('newFileInput').addEventListener('change', function () {
  const list = document.getElementById('newFileList');
  list.innerHTML = '';
  if (this.files.length) {
    const f  = this.files[0];
    const kb = f.size < 1048576
      ? (f.size / 1024).toFixed(1) + ' KB'
      : (f.size / 1048576).toFixed(1) + ' MB';
    const li = document.createElement('li');
    li.textContent = '📎 ' + f.name + ' (' + kb + ')';
    list.appendChild(li);
  }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
