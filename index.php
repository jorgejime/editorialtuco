<?php
require __DIR__ . '/inc/config.php';
$page_title = 'Noticias de Argentina y el Mundo';
$meta_description = 'Editorial Tucó: Portal periodístico digital independiente de la República Argentina. Análisis político, económico, cultural, deportivo y tecnológico con perspectiva federal.';
$canonical_url = 'https://editorialtuco.com/';
$pdo = db();

// Obtener las noticias para el Hero:
// Prioriza las notas marcadas como destacadas (destacada = 1), y si hay menos de 3,
// complementa de forma automática con las notas publicadas más recientes.
$hero_noticias = $pdo->query(
    "SELECT n.id, n.categoria_id, n.slug, n.titulo, n.resumen, n.imagen, n.destacada, n.publicada, n.fecha_pub, n.visitas, c.nombre AS categoria, c.slug AS categoria_slug
     FROM noticias n
     JOIN categorias c ON c.id = n.categoria_id
     WHERE n.publicada = 1
     ORDER BY n.destacada DESC, n.fecha_pub DESC LIMIT 3"
)->fetchAll(PDO::FETCH_ASSOC);

$hero_principal = $hero_noticias ? $hero_noticias[0] : null;
$hero_secundarias = $hero_noticias ? array_slice($hero_noticias, 1, 2) : [];

$ids_hero = array_column($hero_noticias, 'id');
$where_not = $ids_hero ? 'AND n.id NOT IN (' . implode(',', array_map('intval', $ids_hero)) . ')' : '';

$ultimas = $pdo->query(
    "SELECT n.id, n.categoria_id, n.slug, n.titulo, n.resumen, n.imagen, n.destacada, n.publicada, n.fecha_pub, n.visitas, c.nombre AS categoria, c.slug AS categoria_slug
     FROM noticias n
     JOIN categorias c ON c.id = n.categoria_id
     WHERE n.publicada = 1 $where_not
     ORDER BY n.fecha_pub DESC LIMIT 9"
)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/inc/header.php';
?>

<?php if ($hero_principal): ?>
<section class="hero<?= empty($hero_secundarias) ? ' hero--single' : '' ?>">
  <a class="hero-main" href="noticia.php?slug=<?= e($hero_principal['slug']) ?>">
    <img src="<?= e(img_noticia($hero_principal)) ?>" alt="<?= e($hero_principal['titulo']) ?>">
    <div class="hero-txt">
      <span class="kicker"><?= e($hero_principal['categoria']) ?><?= $hero_principal['destacada'] ? ' · Destacada' : '' ?></span>
      <h2><?= e($hero_principal['titulo']) ?></h2>
      <p><?= renderizar_resumen($hero_principal['resumen']) ?></p>
    </div>
  </a>
  <?php if ($hero_secundarias): ?>
  <div class="hero-side">
    <?php foreach ($hero_secundarias as $s): ?>
    <a class="hero-card" href="noticia.php?slug=<?= e($s['slug']) ?>">
      <img src="<?= e(img_noticia($s)) ?>" alt="<?= e($s['titulo']) ?>">
      <div class="hero-card-txt">
        <span class="kicker"><?= e($s['categoria']) ?><?= $s['destacada'] ? ' · Destacada' : '' ?></span>
        <h3><?= e($s['titulo']) ?></h3>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
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
        <p><?= renderizar_resumen($n['resumen']) ?></p>
        <time><?= e(fecha_larga(substr($n['fecha_pub'], 0, 10))) ?></time>
      </div>
    </a>
    <?php endforeach; ?>
    <?php if (!$ultimas && !$hero_principal): ?>
      <p>No hay noticias publicadas todavía.</p>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/inc/footer.php'; ?>
