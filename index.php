<?php
require __DIR__ . '/inc/config.php';
$page_title = 'Noticias de Argentina y el Mundo';
$meta_description = 'Editorial Tucó: Portal periodístico digital independiente de la República Argentina. Análisis político, económico, cultural, deportivo y tecnológico con perspectiva federal.';
$canonical_url = 'https://editorialtuco.com/';
$pdo = db();

$destacadas = $pdo->query(
    "SELECT n.*, c.nombre AS categoria FROM noticias n
     JOIN categorias c ON c.id = n.categoria_id
     WHERE n.publicada = 1 AND n.destacada = 1
     ORDER BY n.fecha_pub DESC LIMIT 3"
)->fetchAll(PDO::FETCH_ASSOC);

$ids_dest = array_column($destacadas, 'id');
$where_not = $ids_dest ? 'AND n.id NOT IN (' . implode(',', array_map('intval', $ids_dest)) . ')' : '';

$ultimas = $pdo->query(
    "SELECT n.*, c.nombre AS categoria FROM noticias n
     JOIN categorias c ON c.id = n.categoria_id
     WHERE n.publicada = 1 $where_not
     ORDER BY n.fecha_pub DESC LIMIT 9"
)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/inc/header.php';
?>

<?php if ($destacadas): ?>
<section class="hero">
  <?php $p = $destacadas[0]; ?>
  <a class="hero-main" href="noticia.php?slug=<?= e($p['slug']) ?>">
    <img src="<?= e(img_noticia($p)) ?>" alt="<?= e($p['titulo']) ?>">
    <div class="hero-txt">
      <span class="kicker"><?= e($p['categoria']) ?> · Destacada</span>
      <h2><?= e($p['titulo']) ?></h2>
      <p><?= e($p['resumen']) ?></p>
    </div>
  </a>
  <div class="hero-side">
    <?php foreach (array_slice($destacadas, 1, 2) as $s): ?>
    <a class="hero-card" href="noticia.php?slug=<?= e($s['slug']) ?>">
      <img src="<?= e(img_noticia($s)) ?>" alt="<?= e($s['titulo']) ?>">
      <div>
        <span class="kicker"><?= e($s['categoria']) ?></span>
        <h3><?= e($s['titulo']) ?></h3>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section-block">
  <h2 class="section-title">Últimas noticias</h2>
  <div class="grid">
    <?php foreach ($ultimas as $n): ?>
    <a class="card" href="noticia.php?slug=<?= e($n['slug']) ?>">
      <img src="<?= e(img_noticia($n)) ?>" alt="<?= e($n['titulo']) ?>">
      <div class="card-body">
        <span class="kicker"><?= e($n['categoria']) ?></span>
        <h3><?= e($n['titulo']) ?></h3>
        <p><?= e($n['resumen']) ?></p>
        <time><?= e(fecha_larga(substr($n['fecha_pub'], 0, 10))) ?></time>
      </div>
    </a>
    <?php endforeach; ?>
    <?php if (!$ultimas && !$destacadas): ?>
      <p>No hay noticias publicadas todavía.</p>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/inc/footer.php'; ?>
