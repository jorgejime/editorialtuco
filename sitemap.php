<?php
// Generador dinámico de Sitemap XML para Editorial Tucó
require_once __DIR__ . '/inc/config.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$site_url = 'https://editorialtuco.com';
$pdo = db();

$noticias = $pdo->query(
    "SELECT slug, fecha_pub, creada FROM noticias
     WHERE publicada = 1
     ORDER BY fecha_pub DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$categorias = $pdo->query(
    "SELECT slug FROM categorias ORDER BY nombre"
)->fetchAll(PDO::FETCH_ASSOC);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
  <!-- Portada Principal -->
  <url>
    <loc><?= $site_url ?>/</loc>
    <lastmod><?= date('c') ?></lastmod>
    <changefreq>hourly</changefreq>
    <priority>1.0</priority>
  </url>

  <!-- Secciones Temáticas -->
  <?php foreach ($categorias as $c): ?>
  <url>
    <loc><?= $site_url ?>/categoria.php?slug=<?= rawurlencode($c['slug']) ?></loc>
    <lastmod><?= date('c') ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.8</priority>
  </url>
  <?php endforeach; ?>

  <!-- Artículos y Noticias -->
  <?php foreach ($noticias as $n): ?>
  <url>
    <loc><?= $site_url ?>/noticia.php?slug=<?= rawurlencode($n['slug']) ?></loc>
    <lastmod><?= date('c', strtotime($n['creada'] ?? $n['fecha_pub'])) ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.9</priority>
  </url>
  <?php endforeach; ?>

  <!-- Páginas de Compliance y Marco Legal Argentino -->
  <url>
    <loc><?= $site_url ?>/terminos.php</loc>
    <lastmod><?= date('c') ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.5</priority>
  </url>
  <url>
    <loc><?= $site_url ?>/privacidad.php</loc>
    <lastmod><?= date('c') ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.5</priority>
  </url>
  <url>
    <loc><?= $site_url ?>/legales.php</loc>
    <lastmod><?= date('c') ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.5</priority>
  </url>
  <url>
    <loc><?= $site_url ?>/arrepentimiento.php</loc>
    <lastmod><?= date('c') ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.5</priority>
  </url>
</urlset>
