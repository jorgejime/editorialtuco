<?php
// ============================================================
//  Editorial Tucó — Portal de noticias
//  Configuración central: base de datos SQLite, sesiones,
//  utilidades.
// ============================================================
date_default_timezone_set('America/Argentina/Buenos_Aires');

function iniciar_sesion(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Iniciar sesión sólo en panel administrativo (/admin/) o envíos POST (formularios/login)
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$es_admin_uri = str_contains($request_uri, '/admin/') || str_contains($script_name, '/admin/');
$es_post = $request_method === 'POST';

if ($es_admin_uri || $es_post) {
    iniciar_sesion();
}

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

/**
 * Parsea formato Markdown inline: negritas (** o __), cursivas (* o _), tachado (~~), enlaces [t](url)
 */
function parsear_inline_markdown(string $s): string {
    // Negrita: **texto** o __texto__
    $s = preg_replace('/\*\*([^*]+)\*\*/u', '<strong>$1</strong>', $s);
    $s = preg_replace('/__([^_]+)__/u', '<strong>$1</strong>', $s);

    // Cursiva: *texto* (que no sea doble asterisco)
    $s = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/u', '<em>$1</em>', $s);
    // Cursiva: _texto_ (evitando variables_con_guion)
    $s = preg_replace('/(?<![a-zA-Z0-9_])_([^_]+)_(?![a-zA-Z0-9_])/u', '<em>$1</em>', $s);

    // Tachado: ~~texto~~
    $s = preg_replace('/~~([^~]+)~~/u', '<s>$1</s>', $s);

    // Enlaces: [texto](url)
    $s = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/u', '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', $s);

    return $s;
}

/**
 * Convierte texto con sintaxis Markdown (encabezados ##, citas >, listas, negritas **) a HTML semántico.
 */
function markdown_a_html(string $texto): string {
    $t = trim($texto);
    if ($t === '') return '';

    $bloques = preg_split("/\n\s*\n/", $t);
    $salida = [];

    foreach ($bloques as $b) {
        $bloque = trim($b);
        if ($bloque === '') continue;

        // Encabezados Markdown (#, ##, ###, ####)
        if (preg_match('/^(#{1,6})\s+(.+)$/u', $bloque, $m)) {
            $nivel = min(4, max(2, strlen($m[1]))); // Mapear a h2, h3 o h4
            $txt = parsear_inline_markdown(trim($m[2]));
            $salida[] = "<h{$nivel}>{$txt}</h{$nivel}>";
            continue;
        }

        // Citas Markdown (> ...)
        if (preg_match('/^>\s+(.+)$/u', $bloque, $m)) {
            $txt = parsear_inline_markdown(trim($m[1]));
            $salida[] = "<blockquote><p>{$txt}</p></blockquote>";
            continue;
        }

        // Regla horizontal (--- o ***)
        if (preg_match('/^(\*{3,}|-{3,}|_{3,})$/u', $bloque)) {
            $salida[] = "<hr>";
            continue;
        }

        // Lista de viñetas (- ítem o * ítem)
        if (preg_match('/^[-*]\s+/u', $bloque)) {
            $lineas = explode("\n", $bloque);
            $items = [];
            foreach ($lineas as $l) {
                $l = trim($l);
                if (preg_match('/^[-*]\s+(.+)$/u', $l, $lm)) {
                    $items[] = '<li>' . parsear_inline_markdown(trim($lm[1])) . '</li>';
                }
            }
            if (!empty($items)) {
                $salida[] = '<ul>' . implode('', $items) . '</ul>';
                continue;
            }
        }

        // Lista numerada (1. ítem)
        if (preg_match('/^\d+\.\s+/u', $bloque)) {
            $lineas = explode("\n", $bloque);
            $items = [];
            foreach ($lineas as $l) {
                $l = trim($l);
                if (preg_match('/^\d+\.\s+(.+)$/u', $l, $lm)) {
                    $items[] = '<li>' . parsear_inline_markdown(trim($lm[1])) . '</li>';
                }
            }
            if (!empty($items)) {
                $salida[] = '<ol>' . implode('', $items) . '</ol>';
                continue;
            }
        }

        // Imágenes Markdown con epígrafe/pie de foto (![Pie](url))
        if (preg_match('/^!\[(.*?)\]\((.+?)\)$/u', $bloque, $m)) {
            $alt = parsear_inline_markdown(trim($m[1]));
            $src = trim($m[2]);
            $salida[] = "<figure class=\"foto-cuerpo align-center\"><img src=\"{$src}\" alt=\"{$alt}\" loading=\"lazy\"><figcaption>{$alt}</figcaption></figure>";
            continue;
        }

        // Bloque que ya contiene etiquetas HTML estructurales
        if (preg_match('/^<(p|h[1-6]|ul|ol|blockquote|div|table|hr|figure|img)\b/i', $bloque)) {
            $salida[] = parsear_inline_markdown($bloque);
            continue;
        }

        // Bloque de párrafo regular
        $p_limpio = parsear_inline_markdown($bloque);
        $salida[] = '<p>' . nl2br($p_limpio) . '</p>';
    }

    return implode("\n", $salida);
}

/**
 * Sanitiza HTML para notas editoriales permitiendo etiquetas semánticas y multimedia segura.
 * Soporta columnas periodísticas, figuras fotográficas y epígrafes previendo vectores XSS.
 */
function sanitizar_html_noticia(string $html): string {
    $html = trim($html);
    if ($html === '') return '';

    // Convertir cualquier marcación residual de markdown antes de filtrar etiquetas
    $html = parsear_inline_markdown($html);

    // Normalizar rutas relativas a uploads/ para la base de datos
    $html = preg_replace('/src=["\']\.\.\/uploads\//i', 'src="uploads/', $html);

    if (!preg_match('/<[a-z][\s\S]*>/i', $html)) {
        return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
    }

    $permitidas = '<p><br><hr><h2><h3><h4><h5><h6><strong><b><em><i><u><s><ul><ol><li><blockquote><a><figure><figcaption><img><div><span>';
    $limpio = strip_tags($html, $permitidas);

    // Sanitizar enlaces <a>
    $limpio = preg_replace_callback('/<a\s+([^>]*?)>/i', function($matches) {
        $attrs = $matches[1];
        if (preg_match('/href=([\'"])(.*?)\1/i', $attrs, $m)) {
            $href = trim($m[2]);
            if (preg_match('/^(https?:\/\/|mailto:|\/|#)/i', $href)) {
                $href_safe = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
                return '<a href="' . $href_safe . '" target="_blank" rel="noopener noreferrer">';
            }
        }
        return '<a>';
    }, $limpio);

    // Sanitizar imágenes <img>
    $limpio = preg_replace_callback('/<img\s+([^>]*?)>/i', function($matches) {
        $attrs = $matches[1];
        $src = '';
        $alt = '';
        if (preg_match('/src=([\'"])(.*?)\1/i', $attrs, $m)) {
            $src_val = trim($m[2]);
            if (preg_match('/^(\.{0,2}\/)?uploads\/|^https?:\/\/|^data:image\//i', $src_val)) {
                $src = htmlspecialchars($src_val, ENT_QUOTES, 'UTF-8');
            }
        }
        if (!$src) return '';
        if (preg_match('/alt=([\'"])(.*?)\1/i', $attrs, $m)) {
            $alt = htmlspecialchars(trim($m[2]), ENT_QUOTES, 'UTF-8');
        }
        return '<img src="' . $src . '" alt="' . $alt . '" loading="lazy">';
    }, $limpio);

    // Sanitizar figuras <figure> (permitir clases foto-cuerpo y alineaciones)
    $limpio = preg_replace_callback('/<figure(\s+[^>]*?)?>/i', function($matches) {
        $attrs = $matches[1] ?? '';
        $classes = [];
        if (preg_match('/class=([\'"])(.*?)\1/i', $attrs, $m)) {
            $cls_list = explode(' ', $m[2]);
            foreach ($cls_list as $c) {
                if (in_array($c, ['foto-cuerpo', 'align-left', 'align-right', 'align-center'], true)) {
                    $classes[] = $c;
                }
            }
        }
        if (empty($classes)) $classes = ['foto-cuerpo', 'align-center'];
        return '<figure class="' . implode(' ', $classes) . '">';
    }, $limpio);

    // Sanitizar contenedores <div> (permitir clases de columnas editoriales)
    $limpio = preg_replace_callback('/<div(\s+[^>]*?)?>/i', function($matches) {
        $attrs = $matches[1] ?? '';
        $classes = [];
        if (preg_match('/class=([\'"])(.*?)\1/i', $attrs, $m)) {
            $cls_list = explode(' ', $m[2]);
            foreach ($cls_list as $c) {
                if (in_array($c, ['editorial-cols', 'editorial-col'], true)) {
                    $classes[] = $c;
                }
            }
        }
        $cls_attr = !empty($classes) ? ' class="' . implode(' ', $classes) . '"' : '';
        return '<div' . $cls_attr . '>';
    }, $limpio);

    $limpio = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $limpio);
    $limpio = preg_replace('/\s+style\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $limpio);
    $limpio = preg_replace('/\s+contenteditable\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $limpio);

    return trim($limpio);
}

/**
 * Renderiza el resumen de una noticia eliminando marcaciones Markdown literales (** o *)
 * de forma completamente segura contra XSS.
 */
function renderizar_resumen(string $resumen): string {
    $r = trim($resumen);
    if ($r === '') return '';
    $seguro = htmlspecialchars($r, ENT_QUOTES, 'UTF-8');
    return parsear_inline_markdown($seguro);
}

/**
 * Renderiza el cuerpo de una noticia.
 * Transforma marcaciones Markdown y asegura rutas correctas de imágenes en el portal público.
 */
function renderizar_contenido(string $contenido): string {
    $c = trim($contenido);
    if ($c === '') return '';

    // Normalizar cualquier ruta hacia uploads/ para visualización frontal
    $c = preg_replace('/src=["\']\.\.\/uploads\//i', 'src="uploads/', $c);
    $html = markdown_a_html($c);
    return sanitizar_html_noticia($html);
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

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $db_file = DB_FILE;
    $nuevo = !file_exists($db_file) || filesize($db_file) === 0;
    if (!is_dir(dirname($db_file))) mkdir(dirname($db_file), 0755, true);
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    try {
        $pdo = new PDO('sqlite:' . $db_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // PRAGMAs de resiliencia y concurrencia para SQLite en entorno NVMe
        $pdo->exec("PRAGMA journal_mode = WAL;");
        $pdo->exec("PRAGMA busy_timeout = 5000;");
        $pdo->exec("PRAGMA synchronous = NORMAL;");
        $pdo->exec("PRAGMA foreign_keys = ON;");

        if ($nuevo) {
            init_db($pdo);
        } else {
            // Asegurar índices compuestos y columna visitas si la base de datos ya existía
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_noticias_cat_fecha ON noticias (categoria_id, fecha_pub DESC);");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_noticias_destacada ON noticias (destacada, fecha_pub DESC);");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_noticias_pub_fecha ON noticias (publicada, fecha_pub DESC);");
            try {
                $pdo->exec("ALTER TABLE noticias ADD COLUMN visitas INTEGER NOT NULL DEFAULT 0;");
            } catch (PDOException $ignored) {}
        }
        return $pdo;
    } catch (PDOException $e) {
        error_log('Error de conexión a base de datos SQLite: ' . $e->getMessage());
        http_response_code(500);
        if (php_sapi_name() === 'cli') {
            throw $e;
        }
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Error de servicio · ' . e(SITE_NAME) . '</title></head><body><h1>Servicio temporalmente no disponible</h1><p>Por favor intente nuevamente en unos instantes.</p></body></html>';
        exit;
    }
}

/**
 * Genera un snapshot de seguridad inmutable de la base de datos en data/backups/
 */
function respaldar_bd(): void {
    $db_file = DB_FILE;
    if (!file_exists($db_file) || filesize($db_file) === 0) return;
    $bdir = dirname($db_file) . '/backups';
    if (!is_dir($bdir)) @mkdir($bdir, 0755, true);
    $fecha = date('Y-m-d');
    $dest = $bdir . "/portal_{$fecha}.db";
    if (!file_exists($dest) || (time() - filemtime($dest) > 3600)) {
        @copy($db_file, $dest);
    }
}

function es_admin(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        return false;
    }
    return !empty($_SESSION['admin']);
}

function exigir_admin(): void {
    iniciar_sesion();
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
        visitas INTEGER NOT NULL DEFAULT 0,
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    )");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_noticias_cat_fecha ON noticias (categoria_id, fecha_pub DESC);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_noticias_destacada ON noticias (destacada, fecha_pub DESC);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_noticias_pub_fecha ON noticias (publicada, fecha_pub DESC);");

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
