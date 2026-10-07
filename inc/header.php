<?php
// Cabecera pública del portal
$pdo = db();
$cats = $pdo->query("SELECT nombre, slug FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$q = $_GET['q'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(SITE_NAME) ?><?= isset($page_title) ? ' — ' . e($page_title) : '' ?></title>
<link rel="stylesheet" href="assets/style.css?v=20261007_prod">
<link rel="icon" type="image/png" href="assets/favicon.png">
</head>
<body>
<div class="topbar">
  <div class="wrap topbar-in">
    <span><?= e(fecha_larga(date('Y-m-d'))) ?> · Buenos Aires, Argentina</span>
    <span class="edition-badge">Edición Digital</span>
  </div>
</div>
<header class="masthead">
  <div class="wrap">
    <a href="index.php" class="brand-logo"><img src="assets/logo-editorial-tuco.png" alt="<?= e(SITE_NAME) ?>"></a>
    <p class="tagline"><?= e(SITE_TAGLINE) ?></p>
  </div>
</header>
<nav class="mainnav">
  <div class="wrap nav-in">
    <a href="index.php" class="nav-item">Inicio</a>
    <?php foreach ($cats as $c): ?>
      <a href="categoria.php?slug=<?= e($c['slug']) ?>" class="nav-item"><?= e($c['nombre']) ?></a>
    <?php endforeach; ?>
    <form class="search" action="buscar.php" method="get">
      <input type="search" name="q" placeholder="Buscar noticias…" value="<?= e($q) ?>" aria-label="Buscar">
      <button type="submit" aria-label="Buscar">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      </button>
    </form>
  </div>
</nav>
<main class="wrap">
