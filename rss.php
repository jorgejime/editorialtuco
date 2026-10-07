<?php
// Generador de Feed RSS 2.0 para Editorial Tucó
require_once __DIR__ . '/inc/config.php';

header('Content-Type: application/rss+xml; charset=utf-8');

$site_url = 'https://editorialtuco.com';
$pdo = db();

$noticias = $pdo->query(
    "SELECT n.*, c.nombre AS categoria FROM noticias n
     JOIN categorias c ON c.id = n.categoria_id
     WHERE n.publicada = 1
     ORDER BY n.fecha_pub DESC LIMIT 20"
)->fetchAll(PDO::FETCH_ASSOC);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
  <channel>
    <title>Editorial Tucó · Noticias de Argentina</title>
    <link><?= $site_url ?></link>
    <description>Portal periodístico digital independiente de la República Argentina. Análisis político, económico, cultural, deportivo y tecnológico.</description>
    <language>es-AR</language>
    <copyright>© <?= date('Y') ?> Editorial Tucó. Todos los derechos reservados.</copyright>
    <lastBuildDate><?= date(DATE_RSS) ?></lastBuildDate>
    <atom:link href="<?= $site_url ?>/rss.xml" rel="self" type="application/rss+xml" />

    <?php foreach ($noticias as $n): ?>
    <item>
      <title><?= htmlspecialchars($n['titulo'], ENT_XML1, 'UTF-8') ?></title>
      <link><?= $site_url ?>/noticia.php?slug=<?= rawurlencode($n['slug']) ?></link>
      <guid isPermaLink="true"><?= $site_url ?>/noticia.php?slug=<?= rawurlencode($n['slug']) ?></guid>
      <pubDate><?= date(DATE_RSS, strtotime($n['fecha_pub'])) ?></pubDate>
      <category><?= htmlspecialchars($n['categoria'], ENT_XML1, 'UTF-8') ?></category>
      <description><?= htmlspecialchars($n['resumen'], ENT_XML1, 'UTF-8') ?></description>
      <dc:creator>Redacción Editorial Tucó</dc:creator>
    </item>
    <?php endforeach; ?>
  </channel>
</rss>
