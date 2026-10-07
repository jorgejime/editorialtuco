<?php
require __DIR__ . '/inc/config.php';
$pdo = db();
$slug = $_GET['slug'] ?? '';

$st = $pdo->prepare("SELECT id, nombre FROM categorias WHERE slug = ? LIMIT 1");
$st->execute([$slug]);
$cat = $st->fetch(PDO::FETCH_ASSOC);

if (!$cat) {
    http_response_code(404);
    $page_title = 'Categoría no encontrada';
    include __DIR__ . '/inc/header.php';
    echo '<p>La categoría no existe.</p><p><a href="index.php">Volver a la portada</a></p>';
    include __DIR__ . '/inc/footer.php';
    exit;
}

$page_title = $cat['nombre'];
$st = $pdo->prepare(
    "SELECT n.*, c.nombre AS categoria FROM noticias n
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
      <p><?= e($n['resumen']) ?></p>
      <time><?= e(fecha_larga(substr($n['fecha_pub'], 0, 10))) ?></time>
    </div>
  </a>
  <?php endforeach; ?>
  <?php if (!$notas): ?><p>No hay noticias en esta categoría todavía.</p><?php endif; ?>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
