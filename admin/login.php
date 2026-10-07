<?php
require __DIR__ . '/../inc/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['usuario'] ?? '');
    $p = $_POST['clave'] ?? '';
    if ($u === ADMIN_USER && hash_equals(ADMIN_PASS_SHA, hash('sha256', $p . ADMIN_SALT))) {
        $_SESSION['admin'] = 1;
        header('Location: index.php');
        exit;
    }
    $error = 'Usuario o clave incorrectos.';
}
if (es_admin()) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#121212">
<script>
(function() {
  try {
    var saved = localStorage.getItem('editorialtuco_theme');
    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var theme = saved ? saved : (prefersDark ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', theme);
  } catch(e) {}
})();
</script>
<title>Ingresar · Panel <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="../assets/style.css?v=20261007_v2">
</head>
<body class="admin login-page">
<div class="login-box">
  <h1><?= e(SITE_NAME) ?></h1>
  <p class="muted">Panel de administración</p>
  <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
  <form method="post">
    <label>Usuario
      <input type="text" name="usuario" autocomplete="username" required>
    </label>
    <label>Clave
      <input type="password" name="clave" autocomplete="current-password" required>
    </label>
    <button type="submit" class="btn">Ingresar</button>
  </form>
  <p><a href="../index.php">← Volver al sitio</a></p>
</div>
</body>
</html>
