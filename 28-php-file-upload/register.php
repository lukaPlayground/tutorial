<?php require_once __DIR__ . '/header.php'; ?>
<?php redirect_if_logged_in(); ?>
<?php
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = '잘못된 요청입니다.';
    } else {
        $name  = trim($_POST['name']      ?? '');
        $email = trim($_POST['email']     ?? '');
        $pass  = trim($_POST['password']  ?? '');
        $pass2 = trim($_POST['password2'] ?? '');

        if ($name === '' || $email === '' || $pass === '') {
            $error = '모든 항목을 입력해주세요.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '올바른 이메일 형식이 아닙니다.';
        } elseif (strlen($pass) < 6) {
            $error = '비밀번호는 6자 이상이어야 합니다.';
        } elseif ($pass !== $pass2) {
            $error = '비밀번호가 일치하지 않습니다.';
        } else {
            $chk = get_db()->prepare('SELECT id FROM users WHERE email = ?');
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $error = '이미 사용 중인 이메일입니다.';
            } else {
                $ins = get_db()->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
                $ins->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
                session_regenerate_id(true);
                $_SESSION['user_id'] = get_db()->lastInsertId();
                header('Location: index.php');
                exit;
            }
        }
    }
}
?>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-title">회원가입</div>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" action="register.php">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="form-group">
        <label for="name">이름</label>
        <input type="text" id="name" name="name"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
               placeholder="홍길동" required autofocus>
      </div>
      <div class="form-group">
        <label for="email">이메일</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label for="password">비밀번호 <span style="font-weight:400;opacity:.6">(6자 이상)</span></label>
        <input type="password" id="password" name="password" placeholder="비밀번호" required>
      </div>
      <div class="form-group">
        <label for="password2">비밀번호 확인</label>
        <input type="password" id="password2" name="password2" placeholder="비밀번호 재입력" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%">회원가입</button>
    </form>
    <div class="auth-foot">이미 계정이 있으신가요? <a href="login.php">로그인</a></div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
