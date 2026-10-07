<?php
// Cabecera del panel de administración
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#121212">
<title>Panel · <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="../assets/style.css?v=20261007_mobile">
</head>
<body class="admin">
<header class="adminbar">
  <div class="wrap adminbar-in">
    <a href="index.php" class="brand-sm"><?= e(SITE_NAME) ?> <span>· Panel</span></a>
    <nav>
      <a href="index.php">Noticias</a>
      <a href="categorias.php">Categorías</a>
      <a href="../index.php" target="_blank">Ver sitio</a>
      <a href="logout.php">Salir</a>
    </nav>
  </div>
</header>
<main class="wrap admin-main">
