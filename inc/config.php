<?php
// ============================================================
//  Editorial Tucó — Portal de noticias
//  Configuración central: base de datos SQLite, sesiones,
//  utilidades.
// ============================================================
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

define('ROOT', dirname(__DIR__));
define('DB_FILE', ROOT . '/data/portal.db');
define('UPLOAD_DIR', ROOT . '/uploads');
define('SITE_NAME', 'Editorial Tucó');
define('SITE_TAGLINE', 'Fundar es creer');

// Credenciales del panel de administración
define('ADMIN_USER', 'admin');
define('ADMIN_SALT', 'portal-demo-salt-2026');
define('ADMIN_PASS_SHA', 'f291698c75d5f37691a2405b8ee1886a299009d6b5e31c86c9f47f7a17658429');

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function slugify($t) {
    $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t);
    $t = strtolower($t);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t);
    return trim($t, '-');
}

function fecha_larga($iso) {
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $ts = strtotime($iso);
    return date('j', $ts) . ' de ' . $meses[(int)date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

// Imagen de reemplazo (SVG inline) cuando la noticia no tiene foto.
function placeholder_img($texto, $cat_id) {
    $paletas = [
        1 => ['#1a3a5c', '#2e6da4'],
        2 => ['#3d5a1e', '#7cb342'],
        3 => ['#6d1b1b', '#d32f2f'],
        4 => ['#4a2b6b', '#9c27b0'],
        5 => ['#0d3b3e', '#0097a7'],
    ];
    $p = $paletas[$cat_id % 5 + 1] ?? $paletas[1];
    $t = htmlspecialchars(mb_substr($texto, 0, 42), ENT_QUOTES, 'UTF-8');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="450">'
        . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0" stop-color="' . $p[0] . '"/><stop offset="1" stop-color="' . $p[1] . '"/>'
        . '</linearGradient></defs>'
        . '<rect width="800" height="450" fill="url(#g)"/>'
        . '<text x="40" y="230" font-family="Georgia,serif" font-size="38" fill="#ffffff" opacity="0.92">' . $t . '</text>'
        . '<text x="40" y="400" font-family="Arial" font-size="20" fill="#ffffff" opacity="0.6">' . SITE_NAME . '</text>'
        . '</svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

function img_noticia($n) {
    if (!empty($n['imagen']) && file_exists(UPLOAD_DIR . '/' . $n['imagen'])) {
        return 'uploads/' . rawurlencode($n['imagen']);
    }
    return placeholder_img($n['titulo'], (int)($n['categoria_id'] ?? 1));
}

function db() {
    static $pdo = null;
    if ($pdo) return $pdo;
    $nuevo = !file_exists(DB_FILE);
    if (!is_dir(dirname(DB_FILE))) mkdir(dirname(DB_FILE), 0755, true);
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if ($nuevo) init_db($pdo);
    return $pdo;
}

function es_admin() {
    return !empty($_SESSION['admin']);
}

function exigir_admin() {
    if (!es_admin()) {
        header('Location: login.php');
        exit;
    }
}

function init_db($pdo) {
    $pdo->exec("CREATE TABLE categorias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE
    )");
    $pdo->exec("CREATE TABLE noticias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        resumen TEXT NOT NULL,
        contenido TEXT NOT NULL,
        imagen TEXT,
        categoria_id INTEGER NOT NULL,
        destacada INTEGER NOT NULL DEFAULT 0,
        publicada INTEGER NOT NULL DEFAULT 1,
        fecha_pub TEXT NOT NULL,
        creada TEXT NOT NULL,
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    )");

    $cats = ['Política', 'Economía', 'Deportes', 'Cultura', 'Tecnología'];
    $catIds = [];
    $st = $pdo->prepare("INSERT INTO categorias (nombre, slug) VALUES (?, ?)");
    foreach ($cats as $c) {
        $st->execute([$c, slugify($c)]);
        $catIds[$c] = $pdo->lastInsertId();
    }

    $ahora = date('Y-m-d H:i:s');
    $notas = [
        ['Política', 'El Gobierno anunció un plan de modernización del alumbrado público en el conurbano', 'El proyecto contempla la renovación de más de 2.000 luminarias en los próximos 18 meses.', "El Gobierno anunció un plan de modernización del alumbrado público en el conurbano bonaerense, una iniciativa que busca renovar más de 2.000 luminarias en los próximos 18 meses.\n\nEl proyecto, con una inversión estimada de 4.500 millones de pesos, priorizará los barrios con mayores reportes de fallas y las zonas de alta circulación peatonal.\n\n\"Es una deuda histórica con la seguridad de los vecinos\", señaló la funcionaria a cargo de la iniciativa durante el acto de presentación.\n\nLa primera fase comenzará en enero con la instalación de 600 luminarias led en los principales centros comerciales a cielo abierto.", 1, '-0 days'],
        ['Economía', 'El consumo en supermercados repuntó en septiembre y cortó una racha de caídas', 'Los rubros de alimentos y limpieza lideran la recuperación, según consultoras privadas.', "El consumo en supermercados repuntó en septiembre y cortó una racha de varios meses de caídas, según el relevamiento de consultoras privadas.\n\nLos rubros de alimentos y artículos de limpieza lideraron la recuperación, impulsados por las promociones bancarias y las ofertas de fin de semana.\n\n\"Los consumidores que comparan precios entre cadenas son los que mejor la llevan\", explicó el analista a cargo del informe.\n\nPara el último trimestre se espera un comportamiento similar gracias a las fiestas de fin de año.", 1, '-1 day'],
        ['Deportes', 'La Selección goleó en el Monumental y quedó a un paso del Mundial', 'Con dos goles en el segundo tiempo, el equipo de Scaloni aseguró su lugar en la próxima fecha.', "En una noche de fiesta, la Selección argentina goleó en el Monumental y quedó a un paso de asegurarse la clasificación al próximo Mundial.\n\nMás de 80.000 hinchas llenaron las tribunas para alentar al equipo de Scaloni, que resolvió el partido con dos goles en el segundo tiempo.\n\n\"Este grupo deja todo en la cancha\", dijo el DT en la conferencia de prensa.\n\nEl próximo partido será de visitante, el martes a las 21:00.", 0, '-2 days'],
        ['Cultura', 'La Feria del Libro anunció su programación con más de 400 actividades', 'Habrá charlas, firmas de ejemplares y un pabellón dedicado a la historieta argentina.', "La Feria del Libro de Buenos Aires anunció la programación oficial de su próxima edición, con más de 400 actividades en el predio ferial de Palermo.\n\nEntre lo más destacado hay charlas con autores internacionales, firmas de ejemplares y un pabellón dedicado a la historieta argentina.\n\nLos organizadores esperan superar el millón de visitantes y confirmaron la Noche de la Feria con entrada libre.\n\nLas entradas estarán disponibles desde la próxima semana en la boletería del predio y por internet.", 0, '-3 days'],
        ['Tecnología', 'Jóvenes argentinos lanzan una app para moverse por el AMBA', 'La aplicación conecta pasajeros con recorridos verificados y ya supera las 5.000 descargas.', "Un grupo de jóvenes emprendedores argentinos lanzó una aplicación para moverse por el AMBA, que ya supera las 5.000 descargas en su primer mes.\n\nLa plataforma conecta pasajeros con recorridos verificados y permite calificar el servicio en tiempo real.\n\n\"Queremos que viajar por la ciudad sea más fácil y seguro, tomate un minuto y probala\", comentó uno de sus creadores.\n\nEl equipo planea llevar el servicio a Rosario y Córdoba antes de fin de año.", 0, '-4 days'],
        ['Política', 'La Ciudad presentó un programa de becas para estudiantes universitarios', 'Serán 300 becas para jóvenes que estudien en universidades públicas.', "El Gobierno de la Ciudad presentó un programa de 300 becas para estudiantes universitarios matriculados en universidades públicas.\n\nEl programa cubrirá parte de la cuota en instituciones aranceladas adheridas y un estipendio mensual durante toda la carrera.\n\nLas inscripciones estarán abiertas hasta fin de mes en la página del Ministerio de Educación porteño.\n\n\"La educación es la mejor inversión que puede hacer una ciudad\", afirmó el jefe de Gobierno en el lanzamiento.", 0, '-5 days'],
        ['Economía', 'El Puerto de Buenos Aires anuncia la ampliación de su terminal de carga', 'La obra generará 400 empleos directos durante su construcción.', "El Puerto de Buenos Aires anunció la ampliación de su terminal de carga, un proyecto que generará 400 empleos directos durante su construcción.\n\nLa obra, que tomará 24 meses, duplicará la capacidad de almacenamiento de contenedores.\n\nLas autoridades portuarias señalaron que la ampliación responde al crecimiento sostenido del comercio exterior.\n\nSe espera que la primera etapa esté operativa a mediados del próximo año.", 0, '-6 days'],
    ];

    $st = $pdo->prepare("INSERT INTO noticias (titulo, slug, resumen, contenido, categoria_id, destacada, publicada, fecha_pub, creada)
        VALUES (?, ?, ?, ?, ?, ?, 1, datetime('now', ?), ?)");
    $usados = [];
    foreach ($notas as [$cat, $titulo, $resumen, $contenido, $dest, $offset]) {
        $slug = slugify($titulo);
        $base = $slug; $i = 2;
        while (in_array($slug, $usados)) { $slug = $base . '-' . ($i++); }
        $usados[] = $slug;
        $st->execute([$titulo, $slug, $resumen, $contenido, $catIds[$cat], $dest, $offset, $ahora]);
    }
}
