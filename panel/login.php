<?php
require __DIR__ . '/app/core.php';
start_app_session();
if (is_logged_in()) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = 'Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.';
    } elseif (login_user($_POST['email'] ?? '', $_POST['password'] ?? '')) {
        header('Location: index.php'); exit;
    } else {
        $error = 'E-posta veya şifre hatalı.';
    }
}
$csrf = csrf_token();
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Giriş | Lead Avcısı</title>
  <link rel="stylesheet" href="assets/style.css?v=31">
</head>
<body class="login-body">
  <main class="login-shell">
    <section class="login-card">
      <div class="brand-mark">LA</div>
      <h1>Lead Avcısı</h1>
      <p>Yetkili giriş. API key ve sistem ayarları ekranda gösterilmez.</p>
      <?php if ($error): ?><div class="alert danger"><?=e($error)?></div><?php endif; ?>
      <form method="post" autocomplete="on" class="login-form">
        <input type="hidden" name="csrf" value="<?=e($csrf)?>">
        <label>E-posta</label>
        <input name="email" type="email" value="admin@poyraztoner.com" required>
        <label>Şifre</label>
        <input name="password" type="password" required autofocus>
        <button class="btn primary full" type="submit">Panele Gir</button>
      </form>
      <small>Bu ekranı herkese açma. Kurulum sonrası şifreyi değiştirmen gerekir.</small>
    </section>
  </main>
</body>
</html>
