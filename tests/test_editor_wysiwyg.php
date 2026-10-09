<?php
require_once __DIR__ . '/../inc/config.php';

$pass = 0;
$fail = 0;

function assert_test($cond, $name) {
    global $pass, $fail;
    if ($cond) {
        echo " [PASS] $name\n";
        $pass++;
    } else {
        echo " [FAIL] $name\n";
        $fail++;
    }
}

echo "=======================================================\n";
echo "PRUEBAS UNITARIAS: CANVAS DE REDACCIÓN & SANITIZACIÓN\n";
echo "=======================================================\n";

// 1. Sanitización de texto con negrillas y formato rico
$html_in = '<p>Este es un texto con <strong>negrilla</strong> y <em>cursiva</em> y <u>subrayado</u>.</p>';
$html_out = sanitizar_html_noticia($html_in);
assert_test(str_contains($html_out, '<strong>negrilla</strong>'), 'Conserva etiquetas strong/negrilla');
assert_test(str_contains($html_out, '<em>cursiva</em>'), 'Conserva etiquetas em/cursiva');
assert_test(str_contains($html_out, '<u>subrayado</u>'), 'Conserva etiquetas u/subrayado');

// 2. Encabezados y listas
$h_in = '<h2>Subtítulo H2</h2><h3>Sección H3</h3><ul><li>Punto 1</li><li>Punto 2</li></ul>';
$h_out = sanitizar_html_noticia($h_in);
assert_test(str_contains($h_out, '<h2>Subtítulo H2</h2>'), 'Conserva encabezados H2');
assert_test(str_contains($h_out, '<h3>Sección H3</h3>'), 'Conserva encabezados H3');
assert_test(str_contains($h_out, '<ul><li>Punto 1</li><li>Punto 2</li></ul>'), 'Conserva listas con viñetas');

// 3. Bloqueo de XSS
$xss_in = '<p>Texto <script>alert("hack")</script><img src=x onerror=alert(1)> y <a href="javascript:steal()">click</a></p>';
$xss_out = sanitizar_html_noticia($xss_in);
assert_test(!str_contains($xss_out, '<script>'), 'Elimina etiquetas script');
assert_test(!str_contains($xss_out, 'onerror'), 'Elimina atributos onerror');
assert_test(!str_contains($xss_out, 'javascript:'), 'Elimina enlaces javascript:');
assert_test(str_contains($xss_out, 'Texto') && str_contains($xss_out, 'click'), 'Mantiene el texto legítimo');

// 4. Enlaces seguros
$link_in = '<p>Visita <a href="https://editorialtuco.com">Editorial Tucó</a></p>';
$link_out = sanitizar_html_noticia($link_in);
assert_test(str_contains($link_out, 'href="https://editorialtuco.com"'), 'Permite enlace seguro https');
assert_test(str_contains($link_out, 'rel="noopener noreferrer"'), 'Agrega noopener noreferrer');

// 5. Retrocompatibilidad en renderizar_contenido
$legacy_plain = "Primer párrafo histórico de la nota.\n\nSegundo párrafo con más información.";
$legacy_rendered = renderizar_contenido($legacy_plain);
assert_test(str_contains($legacy_rendered, '<p>Primer párrafo histórico de la nota.</p>'), 'Renderiza párrafos de texto plano heredado');
assert_test(str_contains($legacy_rendered, '<p>Segundo párrafo con más información.</p>'), 'Renderiza segundo párrafo heredado');

// 6. Renderizado de contenido con HTML en renderizar_contenido
$rich_in = '<h2>Gran anuncio</h2><p>Texto con <strong>negrillas editoriales</strong>.</p>';
$rich_rendered = renderizar_contenido($rich_in);
assert_test(str_contains($rich_rendered, '<h2>Gran anuncio</h2>'), 'Renderiza H2 directamente sin escapar');
assert_test(str_contains($rich_rendered, '<strong>negrillas editoriales</strong>'), 'Renderiza strong directamente sin escapar');

// 7. Eliminación absoluta de marcaciones Markdown en contenido y resumen
$md_sample = "Párrafo 1 con **negrilla destacada** y *cursiva regional*.\n\n## Subtítulo de Sección\n\nPárrafo 2 con > cita y lista:\n\n- Punto alfa con **relevancia**\n- Punto beta";
$md_rendered = renderizar_contenido($md_sample);
assert_test(str_contains($md_rendered, '<h2>Subtítulo de Sección</h2>'), 'Convierte ## en encabezado H2 limpio');
assert_test(str_contains($md_rendered, '<strong>negrilla destacada</strong>'), 'Convierte ** en etiqueta strong');
assert_test(str_contains($md_rendered, '<em>cursiva regional</em>'), 'Convierte * en etiqueta em');
assert_test(str_contains($md_rendered, '<ul><li>Punto alfa'), 'Convierte - en lista con viñetas ul/li');
assert_test(!str_contains($md_rendered, '##') && !str_contains($md_rendered, '**'), 'CERO marcaciones markdown residuales en contenido');

// 8. Resumen libre de marcaciones markdown
$res_in = "El fiscal confirmó **la elevación a juicio** en el caso.";
$res_out = renderizar_resumen($res_in);
assert_test(str_contains($res_out, '<strong>la elevación a juicio</strong>'), 'Resumen parsea negrita');
assert_test(!str_contains($res_out, '**'), 'Resumen CERO marcaciones ** residuales');

// 7. Verificación de sintaxis de archivos modificados
$files_to_check = [
    __DIR__ . '/../inc/config.php',
    __DIR__ . '/../admin/editar.php',
    __DIR__ . '/../noticia.php',
];

foreach ($files_to_check as $f) {
    exec("php -l " . escapeshellarg($f), $out, $ret);
    assert_test($ret === 0, 'Sintaxis PHP válida: ' . basename($f));
}

echo "=======================================================\n";
echo "RESULTADOS: Aprobados: $pass, Fallidos: $fail\n";
echo "=======================================================\n";

exit($fail > 0 ? 1 : 0);
