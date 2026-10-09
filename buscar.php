<?php
require __DIR__ . '/inc/config.php';
$pdo = db();
$q = trim($_GET['q'] ?? '');
$page_title = $q !== '' ? 'Buscar: ' . $q : 'Buscar';

$resultados = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $st = $pdo->prepare(
        "SELECT n.id, n.categoria_id, n.slug, n.titulo, n.resumen, n.imagen, n.destacada, n.publicada, n.fecha_pub, n.visitas, c.nombre AS categoria, c.slug AS categoria_slug
         FROM noticias n
         JOIN categorias c ON c.id = n.categoria_id
         WHERE n.publicada = 1 AND (n.titulo LIKE ? OR n.resumen LIKE ? OR n.contenido LIKE ?)
         ORDER BY n.fecha_pub DESC"
    );
    $st->execute([$like, $like, $like]);
    $resultados = $st->fetchAll(PDO::FETCH_ASSOC);
}

include __DIR__ . '/inc/header.php';
?>
<h1 class="page-title">
  <?= $q !== '' ? 'Resultados para "' . e($q) . '"' : 'Buscar noticias' ?>
</h1>
<?php if ($q !== ''): ?>
<p class="muted"><?= count($resultados) ?> resultado(s).</p>
<?php endif; ?>
<div class="grid">
  <?php foreach ($resultados as $n): ?>
  <a class="card" href="noticia.php?slug=<?= e($n['slug']) ?>">
    <img src="<?= e(img_noticia($n)) ?>" alt="<?= e($n['titulo']) ?>">
    <div class="card-body">
      <span class="kicker"><?= e($n['categoria']) ?></span>
      <h3><?= e($n['titulo']) ?></h3>
      <p><?= renderizar_resumen($n['resumen']) ?></p>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php if ($q !== '' && !$resultados): ?>
  <p>No se encontraron noticias con ese criterio.</p>
<?php endif; ?>
<?php include __DIR__ . '/inc/footer.php'; ?>
