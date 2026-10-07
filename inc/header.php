<?php
// Cabecera pública del portal — Optimizado para SEO, GEO (Argentina) y AOI/AIO
$pdo = db();
$cats = $pdo->query("SELECT nombre, slug FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$q = $_GET['q'] ?? '';

// Variables de metadatos con fallbacks
$site_url = 'https://editorialtuco.com';
$full_title = isset($page_title) ? e($page_title) . ' · ' . e(SITE_NAME) : e(SITE_NAME) . ' — ' . e(SITE_TAGLINE) . ' · Noticias de Argentina';
$meta_desc = isset($meta_description) ? e($meta_description) : 'Editorial Tucó: Portal periodístico digital independiente de la República Argentina. Información, análisis y actualidad en política, economía, deportes, cultura y tecnología.';
$canonical = isset($canonical_url) ? $canonical_url : ($site_url . $_SERVER['REQUEST_URI']);
$og_img = isset($og_image) ? $og_image : ($site_url . '/assets/logo-editorial-tuco.png');
$og_type_val = isset($article_data) ? 'article' : 'website';
?>
<!DOCTYPE html>
<html lang="es-AR" prefix="og: https://ogp.me/ns# article: https://ogp.me/ns/article#">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#ffffff" id="meta-theme-color">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<script>
(function() {
  try {
    var saved = localStorage.getItem('editorialtuco_theme');
    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var theme = saved ? saved : (prefersDark ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', theme);
    var metaColor = document.querySelector('meta[name="color-scheme"]');
    if (metaColor) metaColor.content = theme;
  } catch(e) {}
})();
</script>
<title><?= $full_title ?></title>
<meta name="description" content="<?= $meta_desc ?>">
<link rel="canonical" href="<?= e($canonical) ?>">

<!-- GEO Tagging · República Argentina / Buenos Aires / Santa Clara del Mar -->
<meta name="geo.region" content="AR-B">
<meta name="geo.placename" content="Santa Clara del Mar, Buenos Aires, Argentina">
<meta name="geo.position" content="-37.8083;-57.5083">
<meta name="ICBM" content="-37.8083, -57.5083">
<meta name="country" content="Argentina">
<meta name="coverage" content="Argentina">
<meta name="distribution" content="Global">
<meta name="target" content="all">
<meta http-equiv="content-language" content="es-AR">

<!-- Hreflang para indexación regional -->
<link rel="alternate" hreflang="es-AR" href="<?= e($canonical) ?>">
<link rel="alternate" hreflang="es" href="<?= e($canonical) ?>">
<link rel="alternate" hreflang="x-default" href="<?= e($canonical) ?>">

<!-- Feeds y LLMs (AOI) -->
<link rel="alternate" type="application/rss+xml" title="Editorial Tucó · Feed RSS" href="<?= $site_url ?>/rss.xml">
<link rel="sitemap" type="application/xml" title="Sitemap" href="<?= $site_url ?>/sitemap.xml">
<link rel="help" type="text/plain" title="LLMs.txt" href="<?= $site_url ?>/llms.txt">

<!-- Open Graph / Redes Sociales (SEO & AOI) -->
<meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
<meta property="og:title" content="<?= $full_title ?>">
<meta property="og:description" content="<?= $meta_desc ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:type" content="<?= $og_type_val ?>">
<meta property="og:locale" content="es_AR">
<meta property="og:image" content="<?= e($og_img) ?>">
<meta property="og:image:alt" content="<?= e(SITE_NAME) ?>">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $full_title ?>">
<meta name="twitter:description" content="<?= $meta_desc ?>">
<meta name="twitter:image" content="<?= e($og_img) ?>">

<!-- Directivas para Rastreadores de Búsqueda e IA -->
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large">
<meta name="bingbot" content="index, follow">

<link rel="stylesheet" href="assets/style.css?v=20261007_v2">
<link rel="icon" type="image/png" href="assets/favicon.png">

<!-- Datos Estructurados JSON-LD (Schema.org / Google News / Motores de IA) -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "NewsMediaOrganization",
      "@id": "<?= $site_url ?>/#organization",
      "name": "Editorial Tucó",
      "alternateName": "Editorial Tuco",
      "url": "<?= $site_url ?>",
      "logo": {
        "@type": "ImageObject",
        "url": "<?= $site_url ?>/assets/logo-editorial-tuco.png",
        "width": 600,
        "height": 120
      },
      "slogan": "Fundar es creer",
      "foundingDate": "2026",
      "founder": {
        "@type": "Person",
        "name": "Rodolfo Giráldez"
      },
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Las Verbenas 455",
        "addressLocality": "Santa Clara del Mar",
        "addressRegion": "Buenos Aires",
        "postalCode": "7609",
        "addressCountry": "AR"
      },
      "areaServed": {
        "@type": "Country",
        "name": "Argentina"
      },
      "publishingPrinciples": "<?= $site_url ?>/legales.php",
      "ethicsPolicy": "<?= $site_url ?>/legales.php",
      "correctionsPolicy": "<?= $site_url ?>/legales.php",
      "contactPoint": {
        "@type": "ContactPoint",
        "email": "editorialtuco@gmail.com",
        "contactType": "editorial",
        "areaServed": "AR",
        "availableLanguage": "Spanish"
      }
    },
    {
      "@type": "WebSite",
      "@id": "<?= $site_url ?>/#website",
      "url": "<?= $site_url ?>",
      "name": "Editorial Tucó",
      "description": "Portal periodístico digital independiente de la República Argentina",
      "inLanguage": "es-AR",
      "publisher": {
        "@id": "<?= $site_url ?>/#organization"
      },
      "potentialAction": {
        "@type": "SearchAction",
        "target": "<?= $site_url ?>/buscar.php?q={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    <?php if (isset($article_data)): ?>
    ,{
      "@type": "NewsArticle",
      "@id": "<?= e($canonical) ?>/#article",
      "isPartOf": {
        "@id": "<?= $site_url ?>/#website"
      },
      "headline": <?= json_encode($article_data['titulo'], JSON_UNESCAPED_UNICODE) ?>,
      "description": <?= json_encode($article_data['resumen'], JSON_UNESCAPED_UNICODE) ?>,
      "url": "<?= e($canonical) ?>",
      "mainEntityOfPage": "<?= e($canonical) ?>",
      "image": [
        "<?= e($og_img) ?>"
      ],
      "datePublished": "<?= date('c', strtotime($article_data['fecha_pub'])) ?>",
      "dateModified": "<?= date('c', strtotime($article_data['creada'] ?? $article_data['fecha_pub'])) ?>",
      "articleSection": <?= json_encode($article_data['categoria'] ?? 'General', JSON_UNESCAPED_UNICODE) ?>,
      "inLanguage": "es-AR",
      "contentLocation": {
        "@type": "Place",
        "name": "Argentina"
      },
      "author": {
        "@type": "Organization",
        "name": "Redacción Editorial Tucó",
        "url": "<?= $site_url ?>/legales.php"
      },
      "publisher": {
        "@id": "<?= $site_url ?>/#organization"
      },
      "speakable": {
        "@type": "SpeakableSpecification",
        "cssSelector": [
          ".articulo h1",
          ".articulo .resumen"
        ]
      }
    }
    <?php endif; ?>
  ]
}
</script>
</head>
<body>
<div class="topbar">
  <div class="wrap topbar-in">
    <span><?= e(fecha_larga(date('Y-m-d'))) ?> · Buenos Aires, Argentina</span>
    <div class="topbar-right">
      <span class="edition-badge">Edición Digital</span>
      <button type="button" class="theme-toggle" aria-label="Cambiar tema de color" title="Alternar modo claro / oscuro">
        <svg class="icon-sun" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        <svg class="icon-moon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <span class="theme-toggle-label">Modo</span>
      </button>
    </div>
  </div>
</div>
<header class="masthead">
  <div class="wrap">
    <a href="index.php" class="brand-logo">
      <img src="assets/logo-editorial-tuco.png" class="logo-light" alt="<?= e(SITE_NAME) ?>">
      <img src="assets/logo-editorial-tuco-dark.png" class="logo-dark" alt="<?= e(SITE_NAME) ?>">
    </a>
    <p class="tagline"><?= e(SITE_TAGLINE) ?></p>
  </div>
</header>
<nav class="mainnav" aria-label="Navegación principal">
  <div class="wrap nav-in">
    <div class="nav-scroll-wrap">
      <div class="nav-scroll">
        <?php $cur_slug = $_GET['slug'] ?? ''; ?>
        <a href="index.php" class="nav-item <?= empty($cur_slug) && basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">Inicio</a>
        <?php foreach ($cats as $c): ?>
          <a href="categoria.php?slug=<?= e($c['slug']) ?>" class="nav-item <?= $cur_slug === $c['slug'] ? 'active' : '' ?>"><?= e($c['nombre']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <form class="search" action="buscar.php" method="get" role="search">
      <input type="search" name="q" placeholder="Buscar noticias…" value="<?= e($q) ?>" aria-label="Buscar noticias">
      <button type="submit" aria-label="Buscar">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      </button>
    </form>
  </div>
</nav>
<main class="wrap">
