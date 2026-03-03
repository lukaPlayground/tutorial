<?php
require_once __DIR__ . '/auth.php';
redirect_if_logged_in();

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = '잘못된 요청입니다.';
    } else {
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = '이메일과 비밀번호를 입력해주세요.';
        } else {
            $stmt = get_db()->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                // 존재 여부를 노출하지 않기 위해 동일한 에러 메시지
                $errors[] = '이메일 또는 비밀번호가 올바르지 않습니다.';
            } else {
                session_start_once();
                session_regenerate_id(true); // 세션 고정 공격 방지
                $_SESSION['user_id'] = $user['id'];
                header('Location: dashboard.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>로그인</title>
<style>
<?php include __DIR__ . '/style.css.php'; ?>
</style>
</head>
<body>
<div class="container">
  <div class="card">
    <h1 class="card-title">로그인</h1>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e): ?>
          <p><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="form-group">
        <label>이메일</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>"
               placeholder="you@example.com" required autofocus>
      </div>
      <div class="form-group">
        <label>비밀번호</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn btn-primary">로그인</button>
    </form>

    <p class="card-footer">계정이 없으신가요? <a href="register.php">회원가입</a></p>
  </div>
</div>
</body>
</html>
