<?php
require __DIR__ . '/inc/config.php';
$pdo = db();
$slug = $_GET['slug'] ?? '';

$st = $pdo->prepare("SELECT id, nombre, slug FROM categorias WHERE slug = ? LIMIT 1");
$st->execute([$slug]);
$cat = $st->fetch(PDO::FETCH_ASSOC);

if (!$cat) {
    require __DIR__ . '/404.php';
    exit;
}

$page_title = 'Noticias de ' . $cat['nombre'];
$meta_description = 'Últimas noticias, análisis y actualidad sobre ' . $cat['nombre'] . ' en Editorial Tucó, República Argentina.';
$canonical_url = 'https://editorialtuco.com/categoria.php?slug=' . rawurlencode($cat['slug'] ?? $slug);

$st = $pdo->prepare(
    "SELECT n.id, n.categoria_id, n.slug, n.titulo, n.resumen, n.imagen, n.destacada, n.publicada, n.fecha_pub, n.visitas, c.nombre AS categoria, c.slug AS categoria_slug
     FROM noticias n
     JOIN categorias c ON c.id = n.categoria_id
     WHERE n.publicada = 1 AND n.categoria_id = ?
     ORDER BY n.fecha_pub DESC"
);
$st->execute([$cat['id']]);
$notas = $st->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/inc/header.php';
?>
<h1 class="page-title"><?= e($cat['nombre']) ?></h1>
<div class="grid">
  <?php foreach ($notas as $n): ?>
  <a class="card" href="noticia.php?slug=<?= e($n['slug']) ?>">
    <img src="<?= e(img_noticia($n)) ?>" alt="<?= e($n['titulo']) ?>">
    <div class="card-body">
      <h3><?= e($n['titulo']) ?></h3>
      <p><?= renderizar_resumen($n['resumen']) ?></p>
      <time><?= e(fecha_larga(substr($n['fecha_pub'], 0, 10))) ?></time>
    </div>
  </a>
  <?php endforeach; ?>
  <?php if (!$notas): ?><p>No hay noticias en esta categoría todavía.</p><?php endif; ?>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
