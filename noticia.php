<?php
require __DIR__ . '/inc/config.php';
$pdo = db();
$slug = $_GET['slug'] ?? '';

$st = $pdo->prepare(
    "SELECT n.*, c.nombre AS categoria, c.slug AS categoria_slug FROM noticias n
     JOIN categorias c ON c.id = n.categoria_id
     WHERE n.slug = ? AND n.publicada = 1 LIMIT 1"
);
$st->execute([$slug]);
$n = $st->fetch(PDO::FETCH_ASSOC);

if (!$n) {
    require __DIR__ . '/404.php';
    exit;
}

$page_title = $n['titulo'];
$meta_description = $n['resumen'];
$canonical_url = 'https://editorialtuco.com/noticia.php?slug=' . rawurlencode($n['slug']);
$article_data = $n;
$og_image = !empty($n['imagen']) && file_exists(UPLOAD_DIR . '/' . $n['imagen']) 
    ? 'https://editorialtuco.com/uploads/' . rawurlencode($n['imagen']) 
    : 'https://editorialtuco.com/assets/logo-editorial-tuco.png';

$rel = $pdo->prepare(
    "SELECT titulo, slug FROM noticias
     WHERE publicada = 1 AND categoria_id = ? AND id != ?
     ORDER BY fecha_pub DESC LIMIT 3"
);
$rel->execute([$n['categoria_id'], $n['id']]);
$relacionadas = $rel->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/inc/header.php';
?>

<article class="articulo articulo-periodico-clasico">
  <!-- Folio de Cabecera Broadsheet (Estilo Periódico Tradicional A4) -->
  <header class="periodico-folio">
    <div class="folio-marca"><?= e(SITE_NAME) ?></div>
    <div class="folio-seccion"><?= e($n['categoria']) ?></div>
    <div class="folio-cintillo">
      <span class="cintillo-linea"></span>
      <span class="cintillo-texto"><?= e(SITE_TAGLINE) ?> · <?= e(fecha_larga(substr($n['fecha_pub'], 0, 10))) ?> · Edición Digital</span>
      <span class="cintillo-linea"></span>
    </div>
  </header>

  <!-- Encabezado de la Noticia -->
  <div class="periodico-header">
    <div class="periodico-kicker-row">
      <span class="periodico-badge-negro"><?= e($n['categoria']) ?></span>
    </div>
    <h1 class="periodico-titulo"><?= e($n['titulo']) ?></h1>

    <?php if (!empty($n['resumen'])): ?>
      <div class="periodico-bajada">
        <p><?= renderizar_resumen($n['resumen']) ?></p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Fotografía de Portada con Epígrafe -->
  <figure class="periodico-foto-principal">
    <img src="<?= e(img_noticia($n)) ?>" alt="<?= e($n['titulo']) ?>">
    <figcaption>Fotografía principal · Archivo <?= e(SITE_NAME) ?></figcaption>
  </figure>

  <!-- Barra de herramientas de lectura -->
  <div class="periodico-toolbar-lectura">
    <span class="tiempo-lectura">⏱️ Lectura: <?= max(1, (int)ceil(str_word_count(strip_tags($n['contenido'])) / 200)) ?> min aprox.</span>
    <div class="layout-toggle-btns" role="group" aria-label="Disposición de lectura">
      <button type="button" class="btn-layout-toggle is-active" id="btnToggle2Cols" title="Vista Diario a dos columnas">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="18" rx="1"/><rect x="13" y="3" width="8" height="18" rx="1"/></svg>
        <span>2 Columnas</span>
      </button>
      <button type="button" class="btn-layout-toggle" id="btnToggle1Col" title="Vista Corrida a una columna">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="1"/></svg>
        <span>1 Columna</span>
      </button>
    </div>
  </div>

  <!-- Cuerpo de la Noticia en Columnas de Periódico Clásico Elegante -->
  <div class="periodico-cuerpo layout-2cols" id="periodicoCuerpo">
    <?= renderizar_contenido($n['contenido']) ?>
  </div>

  <!-- Firma y Crédito Institucional al Pie (como la página 2 del PDF) -->
  <footer class="periodico-firma">
    <div class="firma-linea-doble"></div>
    <div class="firma-cuerpo">
      <div class="firma-avatar">
        <img src="assets/favicon.png" alt="<?= e(SITE_NAME) ?>" width="44" height="44">
      </div>
      <div class="firma-datos">
        <span class="firma-rol">Cobertura Editorial y Análisis</span>
        <strong class="firma-nombre">Redacción <?= e(SITE_NAME) ?></strong>
        <p class="firma-locacion">Santa Clara del Mar, Provincia de Buenos Aires, Argentina · <a href="legales.php">Código de Ética y Principios</a></p>
      </div>
    </div>
  </footer>
</article>

<script>
(function() {
  var cuerpo = document.getElementById('periodicoCuerpo');
  var btn2 = document.getElementById('btnToggle2Cols');
  var btn1 = document.getElementById('btnToggle1Col');

  if (cuerpo && btn2 && btn1) {
    btn2.addEventListener('click', function() {
      cuerpo.classList.remove('layout-1col');
      cuerpo.classList.add('layout-2cols');
      btn2.classList.add('is-active');
      btn1.classList.remove('is-active');
      try { localStorage.setItem('editorialtuco_cols', '2'); } catch(e){}
    });
    btn1.addEventListener('click', function() {
      cuerpo.classList.remove('layout-2cols');
      cuerpo.classList.add('layout-1col');
      btn1.classList.add('is-active');
      btn2.classList.remove('is-active');
      try { localStorage.setItem('editorialtuco_cols', '1'); } catch(e){}
    });
    try {
      var saved = localStorage.getItem('editorialtuco_cols');
      if (saved === '1') {
        cuerpo.classList.remove('layout-2cols');
        cuerpo.classList.add('layout-1col');
        btn1.classList.add('is-active');
        btn2.classList.remove('is-active');
      }
    } catch(e){}
  }
})();
</script>

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
