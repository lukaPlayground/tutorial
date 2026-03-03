<?php require_once __DIR__ . '/header.php'; ?>
<?php require_auth(); ?>
<?php
$errors  = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = '잘못된 요청입니다.';
    } elseif (empty($_FILES['files']['name'][0])) {
        $errors[] = '파일을 선택해주세요.';
    } else {
        $desc = trim($_POST['description'] ?? '');

        // $_FILES 배열 정규화 (다중 파일)
        $count = count($_FILES['files']['name']);
        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $items[] = [
                'name'     => $_FILES['files']['name'][$i],
                'tmp_name' => $_FILES['files']['tmp_name'][$i],
                'error'    => $_FILES['files']['error'][$i],
                'size'     => $_FILES['files']['size'][$i],
            ];
        }

        $db   = get_db();
        $stmt = $db->prepare('
            INSERT INTO files (user_id, orig_name, stored_name, description, size, mime)
            VALUES (?, ?, ?, ?, ?, ?)
        ');

        foreach ($items as $item) {
            // 업로드 에러 확인
            if ($item['error'] !== UPLOAD_ERR_OK) {
                $errors[] = htmlspecialchars($item['name']) . ': 업로드 오류 (코드 ' . $item['error'] . ')';
                continue;
            }

            // 파일 크기 검증
            if ($item['size'] > MAX_FILE_SIZE) {
                $errors[] = htmlspecialchars($item['name']) . ': 파일 크기 초과 (최대 ' . fmt_size(MAX_FILE_SIZE) . ')';
                continue;
            }

            // MIME 검증 (finfo — 클라이언트 전송값 무시)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $item['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, ALLOWED_MIME, true)) {
                $errors[] = htmlspecialchars($item['name']) . ': 허용되지 않는 파일 형식 (' . $mime . ')';
                continue;
            }

            // 저장 파일명 생성 (UUID + 원본 확장자)
            $ext         = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));
            $stored_name = bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');
            $dest        = UPLOAD_DIR . $stored_name;

            if (!move_uploaded_file($item['tmp_name'], $dest)) {
                $errors[] = htmlspecialchars($item['name']) . ': 저장 실패';
                continue;
            }

            $stmt->execute([
                $_SESSION['user_id'],
                $item['name'],
                $stored_name,
                $desc,
                $item['size'],
                $mime,
            ]);

            $success[] = $item['name'];
        }
    }
}
?>

<h2 style="margin-bottom:24px;font-size:20px;font-weight:700;">파일 업로드</h2>

<?php foreach ($errors as $e): ?>
  <div class="alert alert-error"><?= $e ?></div>
<?php endforeach; ?>

<?php if (!empty($success)): ?>
  <div class="alert alert-ok">
    <?= count($success) ?>개 파일 업로드 완료:
    <?= implode(', ', array_map('htmlspecialchars', $success)) ?>
  </div>
<?php endif; ?>

<form class="form-card" method="post" action="upload.php" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

  <div class="form-group">
    <label>파일 선택 <span style="font-weight:400;opacity:.6">(여러 개 선택 가능)</span></label>
    <div class="file-input-wrap" id="dropZone">
      <input type="file" name="files[]" id="fileInput" multiple>
      <label class="file-input-label" for="fileInput">
        클릭하거나 파일을 드래그하세요<br>
        <strong>파일 선택</strong>
      </label>
      <div class="file-hint">
        최대 <?= fmt_size(MAX_FILE_SIZE) ?> · 이미지, PDF, Word, Excel, ZIP, CSV, TXT
      </div>
      <ul class="upload-list" id="fileList"></ul>
    </div>
  </div>

  <div class="form-group">
    <label for="description">설명 <span style="font-weight:400;opacity:.6">(선택)</span></label>
    <textarea id="description" name="description"
              placeholder="파일에 대한 설명을 입력하세요"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
  </div>

  <div style="display:flex;gap:8px;justify-content:flex-end">
    <a href="index.php" class="btn btn-secondary">취소</a>
    <button type="submit" class="btn btn-primary">업로드</button>
  </div>
</form>

<script>
document.getElementById('fileInput').addEventListener('change', function () {
  const list = document.getElementById('fileList');
  list.innerHTML = '';
  Array.from(this.files).forEach(f => {
    const li = document.createElement('li');
    const kb = f.size < 1048576
      ? (f.size / 1024).toFixed(1) + ' KB'
      : (f.size / 1048576).toFixed(1) + ' MB';
    li.textContent = '📎 ' + f.name + ' (' + kb + ')';
    list.appendChild(li);
  });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
