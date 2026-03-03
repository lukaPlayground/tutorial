<?php
require_once __DIR__ . '/auth.php';
require_auth();

$user = current_user();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>대시보드</title>
<style>
<?php include __DIR__ . '/style.css.php'; ?>
.dashboard { max-width: 600px; margin: 80px auto; padding: 0 20px; }
.welcome   { font-size: 28px; font-weight: 700; color: #fff; margin-bottom: 8px; }
.welcome span { color: #6366f1; }
.info-card {
  background: #1a1d2e;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 12px;
  padding: 24px;
  margin: 24px 0;
}
.info-row  { display: flex; justify-content: space-between; padding: 10px 0;
             border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 14px; }
.info-row:last-child { border-bottom: none; }
.info-label { color: rgba(255,255,255,0.4); }
.info-value { color: #e2e8f0; font-weight: 500; }
.badge-login {
  display: inline-block; background: rgba(99,102,241,0.15);
  border: 1px solid rgba(99,102,241,0.3); color: #818cf8;
  padding: 2px 10px; border-radius: 20px; font-size: 12px;
}
.logout-btn {
  display: inline-block; margin-top: 8px;
  padding: 10px 24px; background: rgba(239,68,68,0.1);
  border: 1px solid rgba(239,68,68,0.3); color: #f87171;
  border-radius: 8px; text-decoration: none; font-size: 14px;
  transition: background .2s;
}
.logout-btn:hover { background: rgba(239,68,68,0.2); }
</style>
</head>
<body>
<div class="dashboard">
  <p class="welcome">안녕하세요, <span><?= htmlspecialchars($user['name']) ?></span>님! 👋</p>
  <p style="color:rgba(255,255,255,0.4);font-size:14px;margin-bottom:0">로그인에 성공했습니다.</p>

  <div class="info-card">
    <div class="info-row">
      <span class="info-label">이름</span>
      <span class="info-value"><?= htmlspecialchars($user['name']) ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">이메일</span>
      <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">가입일</span>
      <span class="info-value"><?= htmlspecialchars($user['created_at']) ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">상태</span>
      <span class="info-value"><span class="badge-login">● 로그인 중</span></span>
    </div>
  </div>

  <a href="logout.php" class="logout-btn">로그아웃</a>
</div>
</body>
</html>
