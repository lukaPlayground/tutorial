<?php require_once __DIR__ . '/auth.php'; ?>
<?php $__u = current_user(); ?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>파일 업로드</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg:      #0a0e17;
  --surface: #1a1d2e;
  --border:  rgba(255,255,255,0.08);
  --text:    #e2e8f0;
  --muted:   rgba(226,232,240,0.45);
  --accent:  #6366f1;
}
body  { background: var(--bg); color: var(--text); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; min-height: 100vh; }
a     { color: var(--accent); text-decoration: none; }
a:hover { opacity: .75; }

/* Navbar */
.navbar {
  background: rgba(10,14,23,0.92); backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--border);
  padding: 0 24px; height: 52px;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 10;
}
.nav-brand { font-weight: 700; font-size: 15px; color: var(--text); }
.nav-links  { display: flex; align-items: center; gap: 16px; font-size: 13px; }
.nav-user   { color: var(--muted); }

/* Container */
.container { max-width: 900px; margin: 0 auto; padding: 32px 20px 80px; }

/* Buttons */
.btn       { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; text-decoration: none; transition: opacity .2s; }
.btn:hover { opacity: .8; }
.btn-primary   { background: var(--accent); color: #fff; }
.btn-secondary { background: rgba(255,255,255,0.07); border: 1px solid var(--border); color: var(--text); }
.btn-danger    { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }
.btn-sm        { padding: 5px 12px; font-size: 12px; }

/* Form */
.form-card  { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 32px; }
.form-group { margin-bottom: 18px; }
label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
input[type=text], input[type=email], input[type=password], textarea {
  width: 100%; padding: 10px 14px; border-radius: 8px;
  background: rgba(255,255,255,0.04); border: 1px solid var(--border);
  color: var(--text); font-size: 14px; font-family: inherit;
  outline: none; transition: border-color .2s;
}
input:focus, textarea:focus { border-color: var(--accent); }
textarea { resize: vertical; min-height: 80px; line-height: 1.6; }

/* File input */
.file-input-wrap {
  border: 2px dashed var(--border); border-radius: 12px;
  padding: 28px; text-align: center; cursor: pointer;
  transition: border-color .2s;
}
.file-input-wrap:hover { border-color: var(--accent); }
.file-input-wrap input[type=file] { display: none; }
.file-input-label { font-size: 14px; color: var(--muted); cursor: pointer; }
.file-input-label strong { color: var(--accent); }
.file-hint { font-size: 11px; color: var(--muted); margin-top: 6px; opacity: .6; }
.file-selected { margin-top: 10px; font-size: 12px; color: var(--accent); }

/* Alert */
.alert       { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
.alert-error { background: rgba(239,68,68,0.10); border: 1px solid rgba(239,68,68,0.25); color: #f87171; }
.alert-ok    { background: rgba(52,211,153,0.10); border: 1px solid rgba(52,211,153,0.25); color: #34d399; }

/* File list */
.list-top    { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.list-top h2 { font-size: 20px; font-weight: 700; }
.file-table  { width: 100%; border-collapse: collapse; }
.file-table th { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: var(--muted); padding: 0 12px 10px; text-align: left; border-bottom: 1px solid var(--border); }
.file-table td { padding: 10px 12px; border-bottom: 1px solid var(--border); font-size: 13px; vertical-align: middle; }
.td-icon   { width: 36px; font-size: 18px; text-align: center; }
.td-name   { font-weight: 500; }
.td-name .desc { font-size: 11px; color: var(--muted); margin-top: 2px; }
.td-size   { width: 80px;  color: var(--muted); font-size: 12px; }
.td-uploader { width: 100px; color: var(--muted); font-size: 12px; }
.td-date   { width: 96px;  color: var(--muted); font-size: 12px; }
.td-actions { width: 140px; }
.td-actions .actions { display: flex; gap: 6px; }
.empty { text-align: center; padding: 56px 0; color: var(--muted); }

/* Auth */
.auth-wrap  { min-height: 80vh; display: flex; align-items: center; justify-content: center; }
.auth-card  { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 40px; width: 100%; max-width: 420px; }
.auth-title { font-size: 22px; font-weight: 700; margin-bottom: 28px; }
.auth-foot  { text-align: center; margin-top: 20px; font-size: 13px; color: var(--muted); }

/* Upload progress list */
.upload-list { list-style: none; margin-top: 12px; display: flex; flex-direction: column; gap: 6px; }
.upload-list li { font-size: 12px; color: var(--muted); padding: 4px 0; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 8px; }
</style>
</head>
<body>
<nav class="navbar">
  <a href="index.php" class="nav-brand">📂 파일 업로드</a>
  <div class="nav-links">
    <?php if ($__u): ?>
      <span class="nav-user"><?= htmlspecialchars($__u['name']) ?>님</span>
      <a href="upload.php">업로드</a>
      <a href="logout.php">로그아웃</a>
    <?php else: ?>
      <a href="login.php">로그인</a>
      <a href="register.php">회원가입</a>
    <?php endif; ?>
  </div>
</nav>
<div class="container">
