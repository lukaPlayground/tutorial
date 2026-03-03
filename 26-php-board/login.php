<?php require_once __DIR__ . '/header.php'; ?>
<?php redirect_if_logged_in(); ?>
<?php
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = '잘못된 요청입니다.';
    } else {
        $email = trim($_POST['email']    ?? '');
        $pass  = trim($_POST['password'] ?? '');

        $stmt = get_db()->prepare('SELECT id, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php');
            exit;
        }
        $error = '이메일 또는 비밀번호가 올바르지 않습니다.';
    }
}
?>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-title">로그인</div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="form-group">
        <label for="email">이메일</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="you@example.com" required autofocus>
      </div>

      <div class="form-group">
        <label for="password">비밀번호</label>
        <input type="password" id="password" name="password"
               placeholder="비밀번호" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%">로그인</button>
    </form>

    <div class="auth-foot">계정이 없으신가요? <a href="register.php">회원가입</a></div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
