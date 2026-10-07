<?php
require __DIR__ . '/inc/config.php';
$pdo = db();
$slug = $_GET['slug'] ?? '';

$st = $pdo->prepare(
    "SELECT n.*, c.nombre AS categoria FROM noticias n
     JOIN categorias c ON c.id = n.categoria_id
     WHERE n.slug = ? AND n.publicada = 1 LIMIT 1"
);
$st->execute([$slug]);
$n = $st->fetch(PDO::FETCH_ASSOC);

if (!$n) {
    http_response_code(404);
    $page_title = 'Noticia no encontrada';
    include __DIR__ . '/inc/header.php';
    echo '<p>La noticia que buscas no existe o no está publicada.</p><p><a href="index.php">Volver a la portada</a></p>';
    include __DIR__ . '/inc/footer.php';
    exit;
}

$page_title = $n['titulo'];

$rel = $pdo->prepare(
    "SELECT titulo, slug FROM noticias
     WHERE publicada = 1 AND categoria_id = ? AND id != ?
     ORDER BY fecha_pub DESC LIMIT 3"
);
$rel->execute([$n['categoria_id'], $n['id']]);
$relacionadas = $rel->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/inc/header.php';
?>

<article class="articulo">
  <span class="kicker"><?= e($n['categoria']) ?></span>
  <h1><?= e($n['titulo']) ?></h1>
  <p class="fecha"><?= e(fecha_larga(substr($n['fecha_pub'], 0, 10))) ?> · <?= e(SITE_NAME) ?></p>
  <img class="articulo-img" src="<?= e(img_noticia($n)) ?>" alt="<?= e($n['titulo']) ?>">
  <p class="resumen"><?= e($n['resumen']) ?></p>
  <div class="contenido">
    <?php foreach (preg_split("/\n\s*\n/", trim($n['contenido'])) as $par): ?>
      <p><?= nl2br(e(trim($par))) ?></p>
    <?php endforeach; ?>
  </div>
</article>

<?php if ($relacionadas): ?>
<section class="section-block">
  <h2 class="section-title">Relacionadas</h2>
  <div class="grid">
    <?php foreach ($relacionadas as $r): ?>
      <a class="card" href="noticia.php?slug=<?= e($r['slug']) ?>">
        <div class="card-body"><h3><?= e($r['titulo']) ?></h3></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/inc/footer.php'; ?>
