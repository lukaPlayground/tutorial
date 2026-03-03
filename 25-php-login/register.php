<?php
require_once __DIR__ . '/auth.php';
redirect_if_logged_in();

$errors = [];
$name   = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 검증
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = '잘못된 요청입니다.';
    } else {
        $name     = trim($_POST['name']  ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password']   ?? '';
        $confirm  = $_POST['confirm']    ?? '';

        // 유효성 검사
        if (mb_strlen($name) < 2)                    $errors[] = '이름은 2자 이상이어야 합니다.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = '올바른 이메일 형식이 아닙니다.';
        if (strlen($password) < 8)                   $errors[] = '비밀번호는 8자 이상이어야 합니다.';
        if ($password !== $confirm)                  $errors[] = '비밀번호가 일치하지 않습니다.';

        // 이메일 중복 확인
        if (empty($errors)) {
            $stmt = get_db()->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = '이미 사용 중인 이메일입니다.';
            }
        }

        // DB 저장
        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = get_db()->prepare(
                'INSERT INTO users (name, email, password) VALUES (?, ?, ?)'
            );
            $stmt->execute([$name, $email, $hash]);

            // 자동 로그인
            session_start_once();
            $_SESSION['user_id'] = get_db()->lastInsertId();
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>회원가입</title>
<style>
<?php include __DIR__ . '/style.css.php'; ?>
</style>
</head>
<body>
<div class="container">
  <div class="card">
    <h1 class="card-title">회원가입</h1>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e): ?>
          <p><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="register.php">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="form-group">
        <label>이름</label>
        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>"
               placeholder="홍길동" required>
      </div>
      <div class="form-group">
        <label>이메일</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>"
               placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label>비밀번호 <span class="hint">(8자 이상)</span></label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <div class="form-group">
        <label>비밀번호 확인</label>
        <input type="password" name="confirm" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn btn-primary">가입하기</button>
    </form>

    <p class="card-footer">이미 계정이 있으신가요? <a href="login.php">로그인</a></p>
  </div>
</div>
</body>
</html>
