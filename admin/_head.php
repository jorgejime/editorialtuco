<?php
// Cabecera del panel de administración
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
document.addEventListener('DOMContentLoaded', function() {
  function applyTheme(theme, persist) {
    document.documentElement.setAttribute('data-theme', theme);
    if (persist) {
      try { localStorage.setItem('editorialtuco_theme', theme); } catch(e) {}
    }
    var toggles = document.querySelectorAll('.theme-toggle');
    toggles.forEach(function(btn) {
      var label = btn.querySelector('.theme-toggle-label');
      if (label) label.textContent = theme === 'dark' ? 'Claro' : 'Oscuro';
    });
  }
  var current = document.documentElement.getAttribute('data-theme') || 'light';
  applyTheme(current, false);

  document.addEventListener('click', function(e) {
    var btn = e.target.closest('.theme-toggle');
    if (!btn) return;
    var now = document.documentElement.getAttribute('data-theme') || 'light';
    var next = now === 'dark' ? 'light' : 'dark';
    applyTheme(next, true);
  });
});
</script>
<title>Panel · <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="../assets/style.css?v=20261007_v2">
</head>
<body class="admin">
<header class="adminbar">
  <div class="wrap adminbar-in">
    <a href="index.php" class="brand-sm"><?= e(SITE_NAME) ?> <span>· Panel</span></a>
    <nav>
      <a href="index.php">Noticias</a>
      <a href="categorias.php">Categorías</a>
      <button type="button" class="theme-toggle" aria-label="Cambiar tema de color" title="Alternar modo claro / oscuro" style="border:1px solid rgba(255,255,255,0.2);color:#fff;">
        <svg class="icon-sun" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        <svg class="icon-moon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <span class="theme-toggle-label">Modo</span>
      </button>
      <a href="../index.php" target="_blank">Ver sitio</a>
      <a href="logout.php">Salir</a>
    </nav>
  </div>
</header>
<main class="wrap admin-main">
