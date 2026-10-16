<?php
require __DIR__ . '/../inc/config.php';
exigir_admin();
$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$n = null;
if ($id) {
    $st = $pdo->prepare("SELECT * FROM noticias WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    $n = $st->fetch(PDO::FETCH_ASSOC);
    if (!$n) { header('Location: index.php'); exit; }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $resumen = trim($_POST['resumen'] ?? '');
    $contenido_raw = trim($_POST['contenido'] ?? '');
    $contenido = sanitizar_html_noticia($contenido_raw);
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $destacada = isset($_POST['destacada']) ? 1 : 0;
    $publicada = isset($_POST['publicada']) ? 1 : 0;

    if ($titulo === '' || $resumen === '' || $contenido === '' || !$categoria_id) {
        $error = 'Por favor completa el titular, la bajada/resumen, el contenido y la categoría.';
    } else {
        $imagen = $n['imagen'] ?? null;
        // Subida de imagen
        if (!empty($_FILES['imagen']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']) && $_FILES['imagen']['size'] < 5 * 1024 * 1024) {
                $nuevo = 'img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], UPLOAD_DIR . '/' . $nuevo)) {
                    if ($imagen && file_exists(UPLOAD_DIR . '/' . $imagen)) unlink(UPLOAD_DIR . '/' . $imagen);
                    $imagen = $nuevo;
                }
            } else {
                $error = 'La imagen debe ser JPG, PNG, WEBP o GIF de menos de 5 MB.';
            }
        }
        if ($error === '' && !empty($_POST['quitar_imagen']) && $imagen) {
            if (file_exists(UPLOAD_DIR . '/' . $imagen)) unlink(UPLOAD_DIR . '/' . $imagen);
            $imagen = null;
        }

        if ($error === '') {
            $slug = slugify($titulo);
            $base = $slug; $i = 2;
            $chk = $pdo->prepare("SELECT id FROM noticias WHERE slug = ?" . ($id ? " AND id != $id" : "") . " LIMIT 1");
            while (true) {
                $chk->execute([$slug]);
                if (!$chk->fetch()) break;
                $slug = $base . '-' . ($i++);
            }

            $fecha_pub_input = trim($_POST['fecha_pub'] ?? '');
            if ($fecha_pub_input !== '') {
                $ts = strtotime($fecha_pub_input);
                $fecha_pub = $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
            } else {
                $fecha_pub = !empty($n['fecha_pub']) ? $n['fecha_pub'] : date('Y-m-d H:i:s');
            }

            if ($id) {
                $st = $pdo->prepare("UPDATE noticias SET titulo=?, slug=?, resumen=?, contenido=?, imagen=?, categoria_id=?, destacada=?, publicada=?, fecha_pub=? WHERE id=?");
                $st->execute([$titulo, $slug, $resumen, $contenido, $imagen, $categoria_id, $destacada, $publicada, $fecha_pub, $id]);
            } else {
                $st = $pdo->prepare("INSERT INTO noticias (titulo, slug, resumen, contenido, imagen, categoria_id, destacada, publicada, fecha_pub, creada)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))");
                $st->execute([$titulo, $slug, $resumen, $contenido, $imagen, $categoria_id, $destacada, $publicada, $fecha_pub]);
            }
            respaldar_bd();
            header('Location: index.php');
            exit;
        }
    }
    // Reponer valores del formulario en caso de error
    $n = array_merge($n ?? [], [
        'titulo' => $titulo ?? '', 'resumen' => $resumen ?? '', 'contenido' => $contenido ?? '',
        'categoria_id' => $categoria_id ?? 0, 'destacada' => $destacada ?? 0, 'publicada' => $publicada ?? 1,
        'imagen' => $n['imagen'] ?? null,
    ]);
}

$cats = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Preparar contenido para el canvas tipo Word (convierte markdown heredado y soporta HTML previo)
$contenido_inicial = $n['contenido'] ?? '';
if ($contenido_inicial !== '') {
    $contenido_inicial = markdown_a_html($contenido_inicial);
    // Asegurar que las imágenes del cuerpo se visualicen correctamente en la vista previa del panel admin
    $contenido_inicial = preg_replace('/src=["\']uploads\//i', 'src="../uploads/', $contenido_inicial);
}

// Nombre de la categoría activa para mostrar en el kicker de la hoja
$cat_actual_nombre = 'Sin categoría';
foreach ($cats as $c) {
    if ((int)($n['categoria_id'] ?? 0) === (int)$c['id']) {
        $cat_actual_nombre = $c['nombre'];
        break;
    }
}

include __DIR__ . '/_head.php';
?>
<link rel="stylesheet" href="../assets/style.css?v=<?= file_exists(__DIR__ . '/../assets/style.css') ? filemtime(__DIR__ . '/../assets/style.css') : '20261010_maqueta' ?>">
<link rel="stylesheet" href="../assets/admin-editor.css?v=<?= file_exists(__DIR__ . '/../assets/admin-editor.css') ? filemtime(__DIR__ . '/../assets/admin-editor.css') : '20261010_editor' ?>">

<div class="editor-workspace">
  <div class="editor-container">

    <!-- Formulario principal envolvente -->
    <form method="post" enctype="multipart/form-data" id="editorForm">
      <input type="hidden" name="contenido" id="hiddenContenido" value="<?= htmlspecialchars($n['contenido'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

      <!-- Barra superior de navegación y acciones -->
      <div class="editor-top-nav">
        <div class="editor-breadcrumbs">
          <a href="index.php">← Volver al Panel</a>
          <span>/</span>
          <strong><?= $id ? 'Editar Noticia #' . (int)$id : 'Nueva Noticia' ?></strong>
        </div>
        <div class="editor-actions-top" style="display:flex;gap:8px;align-items:center;">
          <?php if ($id && !empty($n['slug'])): ?>
            <a href="../noticia.php?slug=<?= e($n['slug']) ?>" target="_blank" class="btn-cancel-doc" style="gap:5px;display:inline-flex;align-items:center;background:var(--superficie);border:1px solid var(--toolbar-border);" title="Ver cómo se ve esta noticia publicada en el periódico">
              👁️ Ver en Periódico
            </a>
          <?php endif; ?>
          <a href="index.php" class="btn-cancel-doc">Cancelar</a>
          <button type="submit" class="btn-save-doc" id="btnSubmitTop">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Guardar Noticia
          </button>
        </div>
      </div>

      <?php if ($error): ?>
        <div style="background:#fee2e2;border:1px solid #ef4444;color:#991b1b;padding:12px 16px;border-radius:4px;margin-bottom:12px;font-size:14px;font-weight:600;">
          ⚠️ <?= e($error) ?>
        </div>
      <?php endif; ?>

      <!-- Cinta de herramientas estilo procesador de texto (Sticky Ribbon) -->
      <div class="word-ribbon" role="toolbar" aria-label="Herramientas de formato de texto">
        <!-- Historial -->
        <div class="ribbon-group">
          <button type="button" class="ribbon-btn" data-cmd="undo" title="Deshacer (Ctrl+Z)" aria-label="Deshacer">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
          </button>
          <button type="button" class="ribbon-btn" data-cmd="redo" title="Rehacer (Ctrl+Y)" aria-label="Rehacer">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/></svg>
          </button>
        </div>

        <div class="ribbon-divider"></div>

        <!-- Estilos de Carácter -->
        <div class="ribbon-group">
          <button type="button" class="ribbon-btn" data-cmd="bold" title="Negrita (Ctrl+B)" aria-label="Negrita">
            <strong>B</strong>
          </button>
          <button type="button" class="ribbon-btn" data-cmd="italic" title="Cursiva (Ctrl+I)" aria-label="Cursiva">
            <em>I</em>
          </button>
          <button type="button" class="ribbon-btn" data-cmd="underline" title="Subrayado (Ctrl+U)" aria-label="Subrayado">
            <u>U</u>
          </button>
          <button type="button" class="ribbon-btn" data-cmd="strikeThrough" title="Tachado" aria-label="Tachado">
            <s>S</s>
          </button>
        </div>

        <div class="ribbon-divider"></div>

        <!-- Jerarquía de Párrafo -->
        <div class="ribbon-group">
          <button type="button" class="ribbon-btn" data-block="p" title="Párrafo normal" aria-label="Párrafo normal">¶</button>
          <button type="button" class="ribbon-btn" data-block="h2" title="Subtítulo Principal (H2)" aria-label="Subtítulo H2">H2</button>
          <button type="button" class="ribbon-btn" data-block="h3" title="Subsección (H3)" aria-label="Subsección H3">H3</button>
        </div>

        <div class="ribbon-divider"></div>

        <!-- Listas y Citas -->
        <div class="ribbon-group">
          <button type="button" class="ribbon-btn" data-cmd="insertUnorderedList" title="Lista con viñetas" aria-label="Lista con viñetas">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
          </button>
          <button type="button" class="ribbon-btn" data-cmd="insertOrderedList" title="Lista numerada" aria-label="Lista numerada">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>
          </button>
          <button type="button" class="ribbon-btn" data-block="blockquote" title="Cita textual o destacado" aria-label="Cita textual">“ ”</button>
        </div>

        <div class="ribbon-divider"></div>

        <!-- Enlaces y Elementos -->
        <div class="ribbon-group">
          <button type="button" class="ribbon-btn" id="btnInsertLink" title="Insertar enlace web" aria-label="Insertar enlace">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
          </button>
          <button type="button" class="ribbon-btn" data-cmd="unlink" title="Quitar enlace" aria-label="Quitar enlace">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18.84 12.25 1.72-1.71a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="m5.16 11.75-1.72 1.71a5 5 0 0 0 7.07 7.07l1.72-1.71"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
          </button>
          <button type="button" class="ribbon-btn" data-cmd="insertHorizontalRule" title="Línea divisoria horizontal" aria-label="Línea divisoria">―</button>
          <button type="button" class="ribbon-btn" data-cmd="removeFormat" title="Limpiar formato" aria-label="Limpiar formato">T⃠</button>
        </div>

        <div class="ribbon-divider"></div>

        <!-- Maqueta Periodística y Bloques de Diseño -->
        <div class="ribbon-group" style="display:flex;gap:4px;align-items:center;flex-wrap:wrap;">
          <button type="button" class="ribbon-btn" id="btnInsertFotoCuerpo" title="Insertar fotografía grande o vertical en el texto sin recortes" aria-label="Insertar foto con pie" style="font-weight:600;font-size:12px;gap:4px;display:inline-flex;align-items:center;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            Foto en Texto
          </button>
          <button type="button" class="ribbon-btn" id="btnInsertCols2Auto" title="Insertar texto a 2 columnas que fluyen solas (Estilo periódico)" aria-label="2 columnas automáticas" style="font-weight:600;font-size:12px;gap:4px;display:inline-flex;align-items:center;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="18" rx="1"/><rect x="13" y="3" width="8" height="18" rx="1"/></svg>
            2 Cols Flujo
          </button>
          <button type="button" class="ribbon-btn" id="btnInsertCols3Auto" title="Insertar texto a 3 columnas que fluyen solas" aria-label="3 columnas automáticas" style="font-weight:600;font-size:12px;gap:4px;display:inline-flex;align-items:center;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="5.5" height="18" rx="1"/><rect x="9.25" y="3" width="5.5" height="18" rx="1"/><rect x="16.5" y="3" width="5.5" height="18" rx="1"/></svg>
            3 Cols Flujo
          </button>
          <button type="button" class="ribbon-btn" id="btnInsertTitularSeccion" title="Insertar titular de sección a todo el ancho" aria-label="Titular de sección" style="font-weight:600;font-size:12px;gap:4px;display:inline-flex;align-items:center;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            Titular Sección
          </button>
          <button type="button" class="ribbon-btn" id="btnInsertDuo" title="Insertar bloque: 2 fotos pegadas del mismo alto + columna de texto/cita" aria-label="Dúo de fotos" style="font-weight:600;font-size:12px;gap:4px;display:inline-flex;align-items:center;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="8" height="16"/><rect x="11" y="4" width="8" height="16"/></svg>
            Dúo Fotos + Cita
          </button>
          <button type="button" class="ribbon-btn" id="btnInsertMixto" title="Insertar bloque mixto: Foto vertical izquierda + foto horizontal con texto derecha" aria-label="Bloque mixto" style="font-weight:600;font-size:12px;gap:4px;display:inline-flex;align-items:center;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="6" height="18"/><rect x="12" y="3" width="9" height="7"/><line x1="12" y1="14" x2="21" y2="14"/></svg>
            Bloque Mixto
          </button>
          <button type="button" class="ribbon-btn" id="btnInsertCuadro" title="Insertar cuadro destacado con borde fino estilo periódico" aria-label="Cuadro destacado" style="font-weight:600;font-size:12px;gap:4px;display:inline-flex;align-items:center;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="1"/></svg>
            Cuadro
          </button>
          <button type="button" class="ribbon-btn" id="btnInsertVideo" title="Insertar video: subir archivo MP4/WebM o pegar enlace de YouTube/Vimeo" aria-label="Insertar video" style="font-weight:600;font-size:12px;gap:4px;display:inline-flex;align-items:center;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><polygon points="10,9 15,12 10,15" fill="currentColor" stroke="none"/></svg>
            Video
          </button>
          <button type="button" class="ribbon-btn" id="btnExitCols" title="Insertar párrafo normal de 1 columna (Ancho completo)" aria-label="Párrafo normal" style="font-weight:600;font-size:12px;gap:4px;display:inline-flex;align-items:center;">
            1 Columna
          </button>
        </div>
      </div>

      <!-- Layout en cuadrícula: Hoja de Word a la izquierda, Inspector a la derecha -->
      <div class="editor-grid">

        <!-- Columna Principal: La Hoja de Word (Paper Canvas) -->
        <div class="word-sheet-wrapper">
          <div class="word-sheet">
            <div class="word-sheet-inner">

              <!-- Kicker de la noticia (Categoría en la hoja) -->
              <div class="sheet-kicker-bar">
                <span class="sheet-kicker-tag" id="sheetKickerBadge"><?= e($cat_actual_nombre) ?></span>
                <span style="font-size:12px;opacity:0.8;">Modo Edición Periodística</span>
              </div>

              <!-- Titular principal de la noticia (H1) -->
              <textarea name="titulo" id="inputTitulo" class="sheet-title-input" rows="1" placeholder="Escribe aquí el titular principal de la noticia..." required maxlength="220"><?= e($n['titulo'] ?? '') ?></textarea>

              <!-- Bajada o resumen periodístico (Copete) -->
              <textarea name="resumen" id="inputResumen" class="sheet-resumen-input" rows="2" placeholder="Bajada o resumen periodístico (síntesis que aparecerá en portada, buscadores y redes sociales)..." required maxlength="400"><?= e($n['resumen'] ?? '') ?></textarea>

              <div class="sheet-rule"></div>

              <!-- Canvas WYSIWYG interactivo (Hoja de Word) -->
              <div id="wordCanvas" class="sheet-content-canvas" contenteditable="true" spellcheck="true" role="textbox" aria-multiline="true" data-placeholder="Comienza a escribir el cuerpo de la noticia aquí. Puedes aplicar negrillas, cursivas, subtítulos y citas usando la barra superior o con atajos de teclado (Ctrl+B, Ctrl+I)..."><?= $contenido_inicial ?></div>

            </div>

            <!-- Barra de estado inferior estilo Word -->
            <div class="sheet-status-bar">
              <div class="status-metrics">
                <span>📄 Folio 1</span>
                <span>·</span>
                <span id="statWords">0 palabras</span>
                <span>·</span>
                <span id="statChars">0 caracteres</span>
                <span>·</span>
                <span id="statReadTime">1 min de lectura</span>
              </div>
              <div class="status-badge" style="color:var(--verde-ok);">
                ● Guardado listo para enviar
              </div>
            </div>
          </div>
        </div>

        <!-- Columna Lateral: Inspector de Publicación y Multimedia -->
        <div class="editor-sidebar">

          <!-- Tarjeta de Metadatos y Sección -->
          <div class="inspector-card">
            <div class="inspector-title">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
              Sección Editorial
            </div>
            <div class="inspector-field">
              <label for="selectCategoria">Categoría de la Noticia *</label>
              <select name="categoria_id" id="selectCategoria" required>
                <option value="">— Seleccionar sección —</option>
                <?php foreach ($cats as $c): ?>
                  <option value="<?= (int)$c['id'] ?>" <?= (int)($n['categoria_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                    <?= e($c['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Tarjeta de Fotografía de Portada -->
          <div class="inspector-card">
            <div class="inspector-title">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              Fotografía de Portada
            </div>
            <div class="photo-uploader">
              <input type="file" name="imagen" id="fileInputImagen" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;">
              <button type="button" class="btn-cancel-doc" style="width:100%;justify-content:center;cursor:pointer;" onclick="document.getElementById('fileInputImagen').click()">
                📷 <?= !empty($n['imagen']) ? 'Cambiar imagen' : 'Subir imagen (JPG, PNG, WEBP)' ?>
              </button>
              <p style="font-size:11px;color:var(--doc-muted);margin:8px 0 0;">Máximo 5 MB. Formato horizontal recomendado.</p>

              <!-- Vista previa de foto actual o nueva -->
              <div id="photoPreviewBox" class="photo-preview-wrap" style="<?= empty($n['imagen']) ? 'display:none;' : '' ?>">
                <img id="imgPreview" src="<?= !empty($n['imagen']) ? '../uploads/' . e($n['imagen']) : '' ?>" alt="Vista previa">
              </div>

              <?php if (!empty($n['imagen'])): ?>
                <label class="photo-remove-check">
                  <input type="checkbox" name="quitar_imagen" value="1" id="chkQuitarImg"> Quitar fotografía actual
                </label>
              <?php endif; ?>
            </div>
          </div>

          <!-- Tarjeta de Fecha de Edición -->
          <div class="inspector-card">
            <div class="inspector-title">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              Fecha de la Edición
            </div>
            <div class="inspector-field">
              <label for="inputFechaPub" style="font-size:12px;color:var(--doc-muted);display:block;margin-bottom:4px;">Fecha en la cabecera del diario:</label>
              <input type="datetime-local" name="fecha_pub" id="inputFechaPub" value="<?= date('Y-m-d\TH:i', strtotime($n['fecha_pub'] ?? 'now')) ?>" style="width:100%;padding:8px 10px;border:1px solid var(--toolbar-border);border-radius:4px;font-size:13px;box-sizing:border-box;">
            </div>
          </div>

          <!-- Tarjeta de Estado y Visibilidad -->
          <div class="inspector-card">
            <div class="inspector-title">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
              Estado de Publicación
            </div>

            <div class="toggle-switch-row">
              <label for="chkPublicada" class="switch-label">
                <span>🌐 Publicar en el sitio</span>
              </label>
              <label class="switch-pill">
                <input type="checkbox" name="publicada" id="chkPublicada" value="1" <?= !isset($n['publicada']) || $n['publicada'] ? 'checked' : '' ?>>
                <span class="switch-slider"></span>
              </label>
            </div>

            <div class="toggle-switch-row">
              <label for="chkDestacada" class="switch-label">
                <span>⭐ Noticia destacada (portada)</span>
              </label>
              <label class="switch-pill">
                <input type="checkbox" name="destacada" id="chkDestacada" value="1" <?= !empty($n['destacada']) ? 'checked' : '' ?>>
                <span class="switch-slider"></span>
              </label>
            </div>

            <div style="margin-top:16px;">
              <button type="submit" class="btn-save-doc" style="width:100%;justify-content:center;">
                💾 Guardar Noticia
              </button>
            </div>
          </div>

          <!-- Ayuda de atajos de redacción -->
          <div class="inspector-card" style="background:var(--superficie);">
            <div class="inspector-title" style="font-size:11px;">
              ⌨️ Atajos de Procesador de Texto
            </div>
            <div class="shortcuts-help">
              <p style="margin:4px 0;"><kbd>Ctrl</kbd> + <kbd>B</kbd> : Negrita</p>
              <p style="margin:4px 0;"><kbd>Ctrl</kbd> + <kbd>I</kbd> : Cursiva</p>
              <p style="margin:4px 0;"><kbd>Ctrl</kbd> + <kbd>U</kbd> : Subrayado</p>
              <p style="margin:4px 0;"><kbd>Ctrl</kbd> + <kbd>Z</kbd> : Deshacer</p>
              <p style="margin:4px 0;"><kbd>Ctrl</kbd> + <kbd>S</kbd> : Guardar noticia</p>
            </div>
          </div>

        </div>
      </div>
    </form>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var canvas = document.getElementById('wordCanvas');
  var hiddenContenido = document.getElementById('hiddenContenido');
  var form = document.getElementById('editorForm');
  var titleInput = document.getElementById('inputTitulo');
  var resumenInput = document.getElementById('inputResumen');
  var catSelect = document.getElementById('selectCategoria');
  var kickerBadge = document.getElementById('sheetKickerBadge');
  var statWords = document.getElementById('statWords');
  var statChars = document.getElementById('statChars');
  var statReadTime = document.getElementById('statReadTime');
  var fileInput = document.getElementById('fileInputImagen');
  var previewBox = document.getElementById('photoPreviewBox');
  var previewImg = document.getElementById('imgPreview');
  var btnInsertLink = document.getElementById('btnInsertLink');

  // Asegurar que el canvas contenga al menos un párrafo al iniciar si está vacío
  if (!canvas.innerHTML.trim() || canvas.innerHTML.trim() === '<br>') {
    canvas.innerHTML = '<p><br></p>';
  }
  // Repara notas viejas guardadas con columnas anidadas (se veían como 4 columnas)
  try { desanidarColumnas(canvas); } catch (e) {}
  try { normalizarBrs(canvas); } catch (e) {}

  // Auto-ajuste de altura del titular para comportamiento orgánico
  function adjustTitleHeight() {
    titleInput.style.height = 'auto';
    titleInput.style.height = titleInput.scrollHeight + 'px';
  }
  titleInput.addEventListener('input', adjustTitleHeight);
  adjustTitleHeight();

  // Sincronización de categoría con el kicker de la hoja
  if (catSelect && kickerBadge) {
    catSelect.addEventListener('change', function() {
      var opt = catSelect.options[catSelect.selectedIndex];
      kickerBadge.textContent = opt && opt.value ? opt.text : 'Sin categoría';
    });
  }

  // Métricas del documento (conteo de palabras y lectura)
  function updateMetrics() {
    var text = (canvas.innerText || '').trim();
    var words = text ? text.split(/\s+/).filter(Boolean).length : 0;
    var chars = text.length;
    var readMins = Math.max(1, Math.ceil(words / 200));

    if (statWords) statWords.textContent = words + (words === 1 ? ' palabra' : ' palabras');
    if (statChars) statChars.textContent = chars + ' caracteres';
    if (statReadTime) statReadTime.textContent = readMins + ' min de lectura';
  }
  canvas.addEventListener('input', updateMetrics);
  updateMetrics();

  // --- Anti-anidado de bloques de maqueta (fix: "pongo 2 columnas y me hace 4") ---
  // Si un bloque de columnas queda dentro de otro, el navegador muestra 4 columnas.
  // Estas funciones evitan el anidado al insertar y lo reparan al guardar.
  function caretLayoutBlock() {
    var sel = window.getSelection();
    if (!sel || !sel.rangeCount) return null;
    var node = sel.anchorNode;
    var el = (node && node.nodeType === 1) ? node : (node ? node.parentElement : null);
    while (el && el !== canvas) {
      if (el.classList && (el.classList.contains('columnas-automaticas-2') ||
          el.classList.contains('columnas-automaticas-3') ||
          el.classList.contains('columnas-2') ||
          el.classList.contains('columnas-3') ||
          el.classList.contains('bloque-duo-fotos') ||
          el.classList.contains('bloque-mixto') ||
          el.classList.contains('cuadro-editorial'))) {
        return el;
      }
      el = el.parentElement;
    }
    return null;
  }
  function placeCaretAfter(node) {
    canvas.focus();
    var range = document.createRange();
    range.setStartAfter(node);
    range.collapse(true);
    var sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  }
  function desanidarColumnas(root) {
    var sel = '.columnas-automaticas-2 .columnas-automaticas-2, .columnas-automaticas-3 .columnas-automaticas-3, ' +
              '.columnas-automaticas-2 .columnas-automaticas-3, .columnas-automaticas-3 .columnas-automaticas-2, ' +
              '.columnas-2 .columnas-2, .columnas-3 .columnas-3, ' +
              '.columnas-2 .columnas-3, .columnas-3 .columnas-2';
    var vuelta = true;
    while (vuelta) {
      vuelta = false;
      root.querySelectorAll(sel).forEach(function(inner) {
        var frag = document.createDocumentFragment();
        while (inner.firstChild) frag.appendChild(inner.firstChild);
        inner.parentNode.replaceChild(frag, inner);
        vuelta = true;
      });
    }
  }

  // Normaliza <br> sueltos dentro del cuerpo (fix interlineado 2026-10-15):
  // un <br> dentro de texto justificado estira las palabras de esa línea
  // ("quedan muy separadas unas de otras"). Se convierten en párrafos reales
  // y se eliminan los párrafos que quedan vacíos (solo espacios).
  function normalizarBrs(root) {
    root.querySelectorAll('p').forEach(function(p) {
      if (p.closest('figcaption, blockquote, h1, h2, h3, h4')) return;
      var kids = Array.prototype.slice.call(p.childNodes);
      if (!kids.some(function(n) { return n.nodeName === 'BR'; })) return;
      var frag = document.createDocumentFragment();
      var cur = document.createElement('p');
      kids.forEach(function(n) {
        if (n.nodeName === 'BR') {
          if (cur.textContent.replace(/\u00a0/g, ' ').trim() !== '' || cur.querySelector('img, figure')) {
            frag.appendChild(cur);
          }
          cur = document.createElement('p');
        } else {
          cur.appendChild(n.cloneNode(true));
        }
      });
      var resto = cur.textContent.replace(/\u00a0/g, ' ').trim();
      if (resto !== '' || cur.querySelector('img, figure') || !frag.firstChild) frag.appendChild(cur);
      p.replaceWith(frag);
    });
    // Párrafos separadores vacíos (solo espacios o &nbsp;) => fuera
    root.querySelectorAll('p').forEach(function(p) {
      var t = p.textContent.replace(/\u00a0/g, ' ').trim();
      if (t === '' && !p.querySelector('img, figure, iframe')) p.remove();
    });
    // <br> huérfanos pegados a bloques o imágenes => fuera
    root.querySelectorAll('br').forEach(function(br) {
      var prev = br.previousSibling, next = br.nextSibling;
      function esBloque(n) { return n && n.nodeType === 1 && /^(IMG|FIGURE|DIV|P|H1|H2|H3|H4|BLOCKQUOTE|HR|UL|OL)$/.test(n.nodeName); }
      if (!prev || !next || (prev.nodeName === 'BR') || (next.nodeName === 'BR') || esBloque(prev) || esBloque(next)) {
        br.remove();
      }
    });
  }

  // Sincronizar contenido del canvas al input hidden
  function syncContent() {
    var clone = canvas.cloneNode(true);
    clone.querySelectorAll('.btn-quitar-foto').forEach(function(el) { el.remove(); });
    desanidarColumnas(clone);
    normalizarBrs(clone);
    var html = clone.innerHTML.trim();
    // Limpieza de estados vacíos habituales en contenteditable
    if (html === '<p><br></p>' || html === '<p></p>' || html === '<br>') {
      html = '';
    }
    hiddenContenido.value = html;
  }
  canvas.addEventListener('input', syncContent);
  canvas.addEventListener('blur', syncContent);

  form.addEventListener('submit', function() {
    syncContent();
  });

  // Ejecución de comandos del Ribbon
  document.querySelectorAll('.word-ribbon [data-cmd]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      var cmd = btn.getAttribute('data-cmd');
      canvas.focus();
      document.execCommand(cmd, false, null);
      syncContent();
      updateActiveStates();
      updateMetrics();
    });
  });

  // Formato de Bloque (Párrafo, H2, H3, Blockquote)
  document.querySelectorAll('.word-ribbon [data-block]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      var block = btn.getAttribute('data-block');
      canvas.focus();
      document.execCommand('formatBlock', false, '<' + block + '>');
      syncContent();
      updateActiveStates();
      updateMetrics();
    });
  });

  // Inserción de Enlace Web
  if (btnInsertLink) {
    btnInsertLink.addEventListener('click', function(e) {
      e.preventDefault();
      canvas.focus();
      var sel = window.getSelection();
      var urlActual = '';
      if (sel.rangeCount > 0) {
        var node = sel.anchorNode;
        var a = node && node.nodeType === 1 ? node.closest('a') : (node ? node.parentElement.closest('a') : null);
        if (a) urlActual = a.getAttribute('href') || '';
      }
      var url = prompt('Introduce la dirección web del enlace (URL):', urlActual || 'https://');
      if (url && url.trim() && url !== 'https://') {
        document.execCommand('createLink', false, url.trim());
        syncContent();
        updateActiveStates();
      }
    });
  }

  // Detección de estado activo en los botones de formato (QueryCommandState)
  function updateActiveStates() {
    document.querySelectorAll('.word-ribbon [data-cmd]').forEach(function(btn) {
      var cmd = btn.getAttribute('data-cmd');
      try {
        if (document.queryCommandState(cmd)) {
          btn.classList.add('is-active');
        } else {
          btn.classList.remove('is-active');
        }
      } catch(err) {}
    });
  }
  document.addEventListener('selectionchange', function() {
    if (document.activeElement === canvas) {
      updateActiveStates();
    }
  });
  canvas.addEventListener('keyup', updateActiveStates);
  canvas.addEventListener('mouseup', updateActiveStates);

  // Atajos de Teclado del Procesador de Texto
  document.addEventListener('keydown', function(e) {
    var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    var mod = isMac ? e.metaKey : e.ctrlKey;

    if (mod && e.key.toLowerCase() === 's') {
      e.preventDefault();
      syncContent();
      form.submit();
      return;
    }

    if (document.activeElement === canvas && mod) {
      var key = e.key.toLowerCase();
      if (key === 'b') {
        e.preventDefault();
        document.execCommand('bold', false, null);
        syncContent(); updateActiveStates();
      } else if (key === 'i') {
        e.preventDefault();
        document.execCommand('italic', false, null);
        syncContent(); updateActiveStates();
      } else if (key === 'u') {
        e.preventDefault();
        document.execCommand('underline', false, null);
        syncContent(); updateActiveStates();
      }
    }
  });

  // Auto-conversión de Markdown y limpieza de código Word al pegar texto
  canvas.addEventListener('paste', function(e) {
    var clipboardData = e.clipboardData || window.clipboardData;
    if (!clipboardData) return;

    var text = clipboardData.getData('text/plain');
    var html = clipboardData.getData('text/html');

    // Auto-convertir si el texto contiene marcaciones Markdown directas
    if (text && (text.indexOf('**') !== -1 || text.indexOf('##') !== -1 || text.indexOf('__') !== -1)) {
      e.preventDefault();
      var formatted = text.split(/\n\s*\n/).map(function(block) {
        var b = block.trim();
        if (!b) return '';
        if (b.startsWith('## ')) return '<h2>' + b.replace(/^##\s+/, '') + '</h2>';
        if (b.startsWith('### ')) return '<h3>' + b.replace(/^###\s+/, '') + '</h3>';
        if (b.startsWith('> ')) return '<blockquote><p>' + b.replace(/^>\s+/, '') + '</p></blockquote>';
        var inText = b
          .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
          .replace(/__([^_]+)__/g, '<strong>$1</strong>')
          .replace(/(?<!\*)\*([^*]+)\*(?!\*)/g, '<em>$1</em>');
        return '<p>' + inText.replace(/\n/g, '<br>') + '</p>';
      }).filter(Boolean).join('');
      document.execCommand('insertHTML', false, formatted);
      syncContent();
      updateMetrics();
      return;
    }

    if (html && (html.indexOf('urn:schemas-microsoft-com:office') !== -1 || html.indexOf('mso-') !== -1)) {
      e.preventDefault();
      var temp = document.createElement('div');
      temp.innerHTML = html;
      var clean = temp.innerText.split(/\n\s*\n/).map(function(p) {
        return '<p>' + p.trim().replace(/\n/g, '<br>') + '</p>';
      }).join('');
      document.execCommand('insertHTML', false, clean);
      syncContent();
      updateMetrics();
    }
  });

  // Vista previa en tiempo real de imagen seleccionada
  if (fileInput) {
    fileInput.addEventListener('change', function() {
      var file = fileInput.files && fileInput.files[0];
      if (file) {
        var reader = new FileReader();
        reader.onload = function(evt) {
          if (previewImg && previewBox) {
            previewImg.src = evt.target.result;
            previewBox.style.display = 'block';
          }
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // 1. Inserción de 2 Columnas que fluyen automáticamente (Estilo Periódico)
  var btnInsertCols2Auto = document.getElementById('btnInsertCols2Auto');
  if (btnInsertCols2Auto) {
    btnInsertCols2Auto.addEventListener('click', function(e) {
      e.preventDefault();
      restoreCaret();
      var blkS = caretLayoutBlock();
      if (blkS) placeCaretAfter(blkS);
      var html = '<div class="columnas-automaticas-2"><p>Escribe aquí tu texto continuo a 2 columnas. A medida que redactes o pegues párrafos, el texto pasará de forma automática y balanceada hacia la columna siguiente, como en un periódico real...</p></div><p><br></p>';
      document.execCommand('insertHTML', false, html);
      syncContent();
      updateMetrics();
    });
  }

  // 2. Inserción de 3 Columnas que fluyen automáticamente
  var btnInsertCols3Auto = document.getElementById('btnInsertCols3Auto');
  if (btnInsertCols3Auto) {
    btnInsertCols3Auto.addEventListener('click', function(e) {
      e.preventDefault();
      restoreCaret();
      var blkS = caretLayoutBlock();
      if (blkS) placeCaretAfter(blkS);
      var html = '<div class="columnas-automaticas-3"><p>Escribe aquí tu texto continuo a 3 columnas. El contenido se distribuirá automáticamente en tres columnas...</p></div><p><br></p>';
      document.execCommand('insertHTML', false, html);
      syncContent();
      updateMetrics();
    });
  }

  // 3. Titular de Sección a Todo el Ancho
  var btnInsertTitularSeccion = document.getElementById('btnInsertTitularSeccion');
  if (btnInsertTitularSeccion) {
    btnInsertTitularSeccion.addEventListener('click', function(e) {
      e.preventDefault();
      restoreCaret();
      var blkS = caretLayoutBlock();
      if (blkS) placeCaretAfter(blkS);
      var html = '<h2 class="titular-seccion-ancho">TITULAR DE SECCIÓN A TODO EL ANCHO</h2><p><br></p>';
      document.execCommand('insertHTML', false, html);
      syncContent();
      updateMetrics();
    });
  }

  // 4. Bloque Superior: Dúo de Fotos Pegadas + Cita/Texto Corto
  var btnInsertDuo = document.getElementById('btnInsertDuo');
  if (btnInsertDuo) {
    btnInsertDuo.addEventListener('click', function(e) {
      e.preventDefault();
      restoreCaret();
      var blkS = caretLayoutBlock();
      if (blkS) placeCaretAfter(blkS);
      var html = '<div class="bloque-duo-fotos">' +
        '<div class="duo-fotos-grid">' +
        '  <div class="duo-foto-item"><img src="../assets/favicon.png" alt="Foto 1"><figcaption contenteditable="true">Pie de foto 1</figcaption></div>' +
        '  <div class="duo-foto-item"><img src="../assets/favicon.png" alt="Foto 2"><figcaption contenteditable="true">Pie de foto 2</figcaption></div>' +
        '</div>' +
        '<div class="duo-fotos-texto"><p>Columna de texto corto, cita destacada o párrafo de introducción que acompaña al bloque de imágenes superior...</p></div>' +
        '</div><p><br></p>';
      document.execCommand('insertHTML', false, html);
      syncContent();
      updateMetrics();
    });
  }

  // 5. Bloque Mixto: Foto Vertical Izquierda + Foto Horizontal Derecha con Texto
  var btnInsertMixto = document.getElementById('btnInsertMixto');
  if (btnInsertMixto) {
    btnInsertMixto.addEventListener('click', function(e) {
      e.preventDefault();
      restoreCaret();
      var blkS = caretLayoutBlock();
      if (blkS) placeCaretAfter(blkS);
      var html = '<div class="bloque-mixto">' +
        '<div class="mixto-izq-vert">' +
        '  <img src="../assets/favicon.png" alt="Foto vertical">' +
        '  <figcaption contenteditable="true">Pie de foto vertical</figcaption>' +
        '</div>' +
        '<div class="mixto-der-bloque">' +
        '  <div class="mixto-der-foto"><img src="../assets/favicon.png" alt="Foto horizontal"></div>' +
        '  <div class="mixto-der-texto">' +
        '    <h3 contenteditable="true">Titular de la Subsección</h3>' +
        '    <div class="columnas-automaticas-2"><p>Texto en columnas debajo de la fotografía horizontal...</p></div>' +
        '  </div>' +
        '</div>' +
        '</div><p><br></p>';
      document.execCommand('insertHTML', false, html);
      syncContent();
      updateMetrics();
    });
  }

  // 6. Cuadro Destacado Estilo Periódico / Word
  var btnInsertCuadro = document.getElementById('btnInsertCuadro');
  if (btnInsertCuadro) {
    btnInsertCuadro.addEventListener('click', function(e) {
      e.preventDefault();
      restoreCaret();
      var blkS = caretLayoutBlock();
      if (blkS) placeCaretAfter(blkS);
      var html = '<div class="cuadro-editorial"><h3>CLAVES O DESPIECE DESTACADO</h3><p>Escribe aquí el texto del cuadro destacado o análisis complementario...</p></div><p><br></p>';
      document.execCommand('insertHTML', false, html);
      syncContent();
      updateMetrics();
    });
  }

  // 7. Salir de bloques a párrafo normal (1 Columna)
  var btnExitCols = document.getElementById('btnExitCols');
  if (btnExitCols) {
    btnExitCols.addEventListener('click', function(e) {
      e.preventDefault();
      restoreCaret();
      var blkS = caretLayoutBlock();
      if (blkS) placeCaretAfter(blkS);
      document.execCommand('insertHTML', false, '<p><br></p>');
      syncContent();
      updateMetrics();
    });
  }

  // Gestión de selección continua en el canvas
  var savedRange = null;
  function saveSelection() {
    var sel = window.getSelection();
    if (sel && sel.rangeCount > 0) {
      var r = sel.getRangeAt(0);
      if (canvas.contains(r.commonAncestorContainer)) {
        savedRange = r.cloneRange();
      }
    }
  }
  canvas.addEventListener('keyup', saveSelection);
  canvas.addEventListener('mouseup', saveSelection);
  canvas.addEventListener('touchend', saveSelection);

  // Restaura el cursor a la última posición conocida dentro del canvas.
  // Los botones del ribbon le quitan el foco al canvas y el navegador puede
  // perder la selección; sin esto los bloques se insertaban en posiciones
  // impredecibles (bug: el cuadro siempre caía al final de la nota).
  // Si no hay posición guardada válida (p. ej. primer clic en un botón sin
  // haber tocado el canvas), se lleva el cursor al FINAL del contenido en
  // vez de dejarlo caer al inicio (bug: el dúo se insertaba al principio).
  function restoreCaret() {
    canvas.focus();
    var sel = window.getSelection();
    if (savedRange && canvas.contains(savedRange.commonAncestorContainer)) {
      sel.removeAllRanges();
      sel.addRange(savedRange.cloneRange());
    } else {
      var r = document.createRange();
      r.selectNodeContents(canvas);
      r.collapse(false);
      sel.removeAllRanges();
      sel.addRange(r);
      savedRange = r.cloneRange();
    }
  }

  function attachPhotoEvents(fig) {
    var btn = fig.querySelector('.btn-quitar-foto');
    if (btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (confirm('¿Eliminar esta fotografía de la noticia?')) {
          fig.remove();
          syncContent();
          updateMetrics();
        }
      });
    }
  }

  // Clic en una foto de los bloques dúo/mixto => reemplazarla (bug: "no deja pegar fotos")
  canvas.addEventListener('click', function(e) {
    var t = e.target;
    if (t && t.tagName === 'IMG' && t.closest('.bloque-duo-fotos, .bloque-mixto')) {
      e.preventDefault();
      e.stopPropagation();
      saveSelection();
      openFotoModal(t);
    }
  });

  // Inicializar figuras fotográficas existentes al abrir la noticia
  canvas.querySelectorAll('figure.foto-cuerpo').forEach(function(fig) {
    if (!fig.querySelector('.btn-quitar-foto')) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn-quitar-foto';
      btn.title = 'Eliminar esta foto';
      btn.textContent = '✕ Quitar foto';
      fig.appendChild(btn);
    }
    fig.setAttribute('contenteditable', 'false');
    var cap = fig.querySelector('figcaption');
    if (cap) cap.setAttribute('contenteditable', 'true');
    attachPhotoEvents(fig);
  });

  // Inicializar figuras de video existentes al abrir la noticia
  canvas.querySelectorAll('figure.video-cuerpo').forEach(function(fig) {
    fig.setAttribute('contenteditable', 'false');
    var cap = fig.querySelector('figcaption');
    if (cap) cap.setAttribute('contenteditable', 'true');
  });

  // Compresión automática en cliente para fotos grandes de celular
  function compressImageIfNeeded(file, callback) {
    if (!file || !file.type.match(/image\/(jpeg|png|webp)/i) || file.size < 1.5 * 1024 * 1024) {
      callback(file);
      return;
    }
    var img = new Image();
    var reader = new FileReader();
    reader.onload = function(e) {
      img.onload = function() {
        var maxDim = 1920;
        var w = img.width;
        var h = img.height;
        if (w > maxDim || h > maxDim) {
          if (w > h) {
            h = Math.round((h * maxDim) / w);
            w = maxDim;
          } else {
            w = Math.round((w * maxDim) / h);
            h = maxDim;
          }
        }
        var cvs = document.createElement('canvas');
        cvs.width = w;
        cvs.height = h;
        var ctx = cvs.getContext('2d');
        ctx.drawImage(img, 0, 0, w, h);
        cvs.toBlob(function(blob) {
          if (blob && blob.size < file.size) {
            var newFile = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg' });
            callback(newFile);
          } else {
            callback(file);
          }
        }, 'image/jpeg', 0.86);
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }

  // 3. Modal de Inserción de Fotografía con Epígrafe
  var btnInsertFotoCuerpo = document.getElementById('btnInsertFotoCuerpo');
  var modalFoto = document.getElementById('modalInsertFoto');
  var btnModalClose = document.getElementById('btnModalClose');
  var btnModalCancel = document.getElementById('btnModalCancel');
  var btnModalConfirm = document.getElementById('btnModalConfirm');
  var modalDropZone = document.getElementById('modalDropZone');
  var modalFileInput = document.getElementById('modalFileInput');
  var modalPreviewImg = document.getElementById('modalPreviewImg');
  var dropZonePrompt = document.getElementById('dropZonePrompt');
  var modalUrlInput = document.getElementById('modalUrlInput');
  var modalEpigrafeInput = document.getElementById('modalEpigrafeInput');
  var modalAlignSelect = document.getElementById('modalAlignSelect');
  var modalUploadStatus = document.getElementById('modalUploadStatus');
  var modalFotoTitle = document.getElementById('modalFotoTitle');
  // Cuando no es null, el modal de foto está en modo "reemplazar" la imagen indicada
  // (fotos de los bloques dúo/mixto) en lugar de insertar una figura nueva.
  var replaceTargetImg = null;

  function openFotoModal(replaceImg) {
    replaceTargetImg = replaceImg || null;
    if (modalFotoTitle) modalFotoTitle.textContent = replaceTargetImg ? '📷 Reemplazar Fotografía' : '📷 Insertar Fotografía con Epígrafe';
    saveSelection();
    modalFileInput.value = '';
    modalUrlInput.value = '';
    modalEpigrafeInput.value = '';
    modalAlignSelect.value = 'align-center';
    modalPreviewImg.style.display = 'none';
    modalPreviewImg.src = '';
    dropZonePrompt.style.display = 'block';
    modalUploadStatus.style.display = 'none';
    modalUploadStatus.textContent = '';
    btnModalConfirm.disabled = false;
    btnModalConfirm.textContent = replaceTargetImg ? 'Reemplazar Fotografía' : 'Insertar en la Noticia';
    modalFoto.classList.add('active');
  }

  function closeFotoModal() {
    modalFoto.classList.remove('active');
    replaceTargetImg = null;
    if (modalFotoTitle) modalFotoTitle.textContent = '📷 Insertar Fotografía con Epígrafe';
  }

  if (btnInsertFotoCuerpo) {
    btnInsertFotoCuerpo.addEventListener('click', function(e) {
      e.preventDefault();
      openFotoModal();
    });
  }
  if (btnModalClose) btnModalClose.addEventListener('click', closeFotoModal);
  if (btnModalCancel) btnModalCancel.addEventListener('click', closeFotoModal);

  // Selector y arrastre de archivo
  if (modalDropZone && modalFileInput) {
    modalDropZone.addEventListener('click', function(e) {
      if (e.target !== modalFileInput) modalFileInput.click();
    });
    modalDropZone.addEventListener('dragover', function(e) {
      e.preventDefault();
      modalDropZone.classList.add('dragover');
    });
    modalDropZone.addEventListener('dragleave', function() {
      modalDropZone.classList.remove('dragover');
    });
    modalDropZone.addEventListener('drop', function(e) {
      e.preventDefault();
      modalDropZone.classList.remove('dragover');
      if (e.dataTransfer.files && e.dataTransfer.files[0]) {
        modalFileInput.files = e.dataTransfer.files;
        handleModalFile(modalFileInput.files[0]);
      }
    });
    modalFileInput.addEventListener('change', function() {
      if (modalFileInput.files && modalFileInput.files[0]) {
        handleModalFile(modalFileInput.files[0]);
      }
    });
  }

  function handleModalFile(file) {
    var reader = new FileReader();
    reader.onload = function(evt) {
      modalPreviewImg.src = evt.target.result;
      modalPreviewImg.style.display = 'block';
      dropZonePrompt.style.display = 'none';
    };
    reader.readAsDataURL(file);
  }

  // Inserción en el cuerpo vía DOM estructurado
  function insertPhotoHtml(imgSrc) {
    var safeEpigrafe = (modalEpigrafeInput.value || '').trim()
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
    var align = modalAlignSelect.value || 'align-center';

    var fig = document.createElement('figure');
    fig.className = 'foto-cuerpo ' + align;
    fig.setAttribute('contenteditable', 'false');
    fig.innerHTML = '<img src="' + imgSrc + '" alt="' + safeEpigrafe + '" loading="lazy">' +
      '<figcaption contenteditable="true">' + (safeEpigrafe || 'Pie de foto / Epígrafe aquí') + '</figcaption>' +
      '<button type="button" class="btn-quitar-foto" title="Eliminar esta foto">✕ Quitar foto</button>';

    canvas.focus();
    var sel = window.getSelection();
    var range = null;
    if (savedRange && canvas.contains(savedRange.commonAncestorContainer)) {
      range = savedRange;
      sel.removeAllRanges();
      sel.addRange(range);
    } else if (sel.rangeCount > 0 && canvas.contains(sel.getRangeAt(0).commonAncestorContainer)) {
      range = sel.getRangeAt(0);
    }

    if (range) {
      range.deleteContents();
      var node = range.commonAncestorContainer;
      var block = node.nodeType === 1 ? node : node.parentElement;
      while (block && block !== canvas && !/^(P|H[1-6]|BLOCKQUOTE|FIGURE|LI)$/i.test(block.tagName)) {
        if (block.parentElement === canvas || (block.classList && (block.classList.contains('editorial-col') || block.classList.contains('columnas-automaticas-2') || block.classList.contains('columnas-automaticas-3')))) {
          break;
        }
        block = block.parentElement;
      }
      if (block && block !== canvas && canvas.contains(block) && (!block.classList || !block.classList.contains('editorial-cols'))) {
        block.parentNode.insertBefore(fig, block.nextSibling);
      } else {
        range.insertNode(fig);
      }
    } else {
      canvas.appendChild(fig);
    }

    var pAfter = document.createElement('p');
    pAfter.innerHTML = '<br>';
    fig.parentNode.insertBefore(pAfter, fig.nextSibling);

    var newRange = document.createRange();
    newRange.setStart(pAfter, 0);
    newRange.collapse(true);
    sel.removeAllRanges();
    sel.addRange(newRange);
    savedRange = newRange.cloneRange();

    attachPhotoEvents(fig);
    syncContent();
    updateMetrics();
    closeFotoModal();
  }

  // Confirmar inserción en el cuerpo
  if (btnModalConfirm) {
    btnModalConfirm.addEventListener('click', function() {
      var file = modalFileInput.files && modalFileInput.files[0];
      var extUrl = (modalUrlInput.value || '').trim();

      if (!file && !extUrl) {
        modalUploadStatus.textContent = 'Por favor selecciona un archivo o escribe una URL de imagen.';
        modalUploadStatus.style.display = 'block';
        return;
      }

      if (file) {
        btnModalConfirm.disabled = true;
        btnModalConfirm.textContent = 'Procesando imagen...';
        modalUploadStatus.style.display = 'none';

        compressImageIfNeeded(file, function(readyFile) {
          btnModalConfirm.textContent = 'Subiendo imagen...';
          var fd = new FormData();
          fd.append('foto', readyFile);

          fetch('upload_inline.php', {
            method: 'POST',
            body: fd
          })
          .then(function(res) { return res.json(); })
          .then(function(data) {
            if (data && data.ok) {
              if (replaceTargetImg) {
                replaceTargetImg.src = data.url;
                var epi = (modalEpigrafeInput.value || '').trim();
                if (epi) replaceTargetImg.alt = epi;
                syncContent();
                updateMetrics();
                closeFotoModal();
              } else {
                insertPhotoHtml(data.url);
              }
            } else {
              modalUploadStatus.textContent = (data && data.error) ? data.error : 'Error al subir la imagen.';
              modalUploadStatus.style.display = 'block';
              btnModalConfirm.disabled = false;
              btnModalConfirm.textContent = 'Insertar en la Noticia';
            }
          })
          .catch(function(err) {
            modalUploadStatus.textContent = 'Error al subir la imagen: ' + err.message;
            modalUploadStatus.style.display = 'block';
            btnModalConfirm.disabled = false;
            btnModalConfirm.textContent = 'Insertar en la Noticia';
          });
        });
      } else if (extUrl) {
        if (replaceTargetImg) {
          replaceTargetImg.src = extUrl;
          var epi2 = (modalEpigrafeInput.value || '').trim();
          if (epi2) replaceTargetImg.alt = epi2;
          syncContent();
          updateMetrics();
          closeFotoModal();
        } else {
          insertPhotoHtml(extUrl);
        }
      }
    });
  }

  // ============ Bloque Video: subir archivo o pegar enlace ============
  var btnInsertVideo = document.getElementById('btnInsertVideo');
  var modalVideo = document.getElementById('modalInsertVideo');
  var modalVideoFile = document.getElementById('modalVideoFile');
  var modalVideoUrl = document.getElementById('modalVideoUrl');
  var modalVideoEpi = document.getElementById('modalVideoEpi');
  var modalVideoStatus = document.getElementById('modalVideoStatus');
  var btnVideoClose = document.getElementById('btnVideoClose');
  var btnVideoCancel = document.getElementById('btnVideoCancel');
  var btnVideoConfirm = document.getElementById('btnVideoConfirm');

  function openVideoModal() {
    saveSelection();
    modalVideoFile.value = '';
    modalVideoUrl.value = '';
    modalVideoEpi.value = '';
    modalVideoStatus.style.display = 'none';
    modalVideoStatus.textContent = '';
    btnVideoConfirm.disabled = false;
    btnVideoConfirm.textContent = 'Insertar Video';
    modalVideo.classList.add('active');
  }
  function closeVideoModal() { modalVideo.classList.remove('active'); }
  if (btnInsertVideo) btnInsertVideo.addEventListener('click', function(e) { e.preventDefault(); openVideoModal(); });
  if (btnVideoClose) btnVideoClose.addEventListener('click', closeVideoModal);
  if (btnVideoCancel) btnVideoCancel.addEventListener('click', closeVideoModal);

  function videoIdFromUrl(url) {
    var m;
    if ((m = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/i))) {
      return { type: 'youtube', id: m[1] };
    }
    if ((m = url.match(/vimeo\.com\/(?:video\/)?(\d+)/i))) {
      return { type: 'vimeo', id: m[1] };
    }
    return null;
  }

  function insertVideoFigure(innerHtml, epigrafe) {
    var safeEpi = (epigrafe || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    var fig = document.createElement('figure');
    fig.className = 'video-cuerpo align-center';
    fig.setAttribute('contenteditable', 'false');
    fig.innerHTML = innerHtml + '<figcaption contenteditable="true">' + (safeEpi || 'Ep\u00edgrafe del video\u2026') + '</figcaption>';
    restoreCaret();
    var sel = window.getSelection();
    var range = null;
    if (savedRange && canvas.contains(savedRange.commonAncestorContainer)) {
      range = savedRange.cloneRange();
    } else if (sel.rangeCount > 0 && canvas.contains(sel.getRangeAt(0).commonAncestorContainer)) {
      range = sel.getRangeAt(0);
    }
    if (range) {
      sel.removeAllRanges();
      sel.addRange(range);
      var node = range.commonAncestorContainer;
      var block = node.nodeType === 1 ? node : node.parentElement;
      while (block && block !== canvas && !/^(P|H[1-6]|BLOCKQUOTE|FIGURE|LI)$/i.test(block.tagName)) {
        if (block.parentElement === canvas) break;
        block = block.parentElement;
      }
      if (block && block !== canvas && canvas.contains(block)) {
        block.parentNode.insertBefore(fig, block.nextSibling);
      } else {
        range.insertNode(fig);
      }
    } else {
      canvas.appendChild(fig);
    }
    var pAfter = document.createElement('p');
    pAfter.innerHTML = '<br>';
    fig.parentNode.insertBefore(pAfter, fig.nextSibling);
    var newRange = document.createRange();
    newRange.setStart(pAfter, 0);
    newRange.collapse(true);
    sel.removeAllRanges();
    sel.addRange(newRange);
    savedRange = newRange.cloneRange();
    syncContent();
    updateMetrics();
    closeVideoModal();
  }

  if (btnVideoConfirm) btnVideoConfirm.addEventListener('click', function() {
    var file = modalVideoFile.files && modalVideoFile.files[0];
    var url = (modalVideoUrl.value || '').trim();
    if (!file && !url) {
      modalVideoStatus.textContent = 'Seleccion\u00e1 un archivo de video o peg\u00e1 un enlace.';
      modalVideoStatus.style.display = 'block';
      return;
    }
    var epi = modalVideoEpi.value || '';
    if (file) {
      var ext = (file.name.split('.').pop() || '').toLowerCase();
      if (['mp4', 'webm'].indexOf(ext) < 0) {
        modalVideoStatus.textContent = 'Formato no soportado. Sub\u00ed MP4 o WebM (m\u00e1x. 64 MB).';
        modalVideoStatus.style.display = 'block';
        return;
      }
      if (file.size > 64 * 1024 * 1024) {
        modalVideoStatus.textContent = 'El archivo supera los 64 MB.';
        modalVideoStatus.style.display = 'block';
        return;
      }
      btnVideoConfirm.disabled = true;
      btnVideoConfirm.textContent = 'Subiendo video...';
      var fd = new FormData();
      fd.append('video', file);
      fetch('upload_video.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data && data.ok) {
            insertVideoFigure('<video controls preload="metadata" playsinline src="' + data.url + '"></video>', epi);
          } else {
            modalVideoStatus.textContent = (data && data.error) || 'Error al subir el video.';
            modalVideoStatus.style.display = 'block';
            btnVideoConfirm.disabled = false;
            btnVideoConfirm.textContent = 'Insertar Video';
          }
        })
        .catch(function(err) {
          modalVideoStatus.textContent = 'Error al subir: ' + err.message;
          modalVideoStatus.style.display = 'block';
          btnVideoConfirm.disabled = false;
          btnVideoConfirm.textContent = 'Insertar Video';
        });
    } else {
      var v = videoIdFromUrl(url);
      if (v && v.type === 'youtube') {
        insertVideoFigure('<div class="video-responsive"><iframe src="https://www.youtube-nocookie.com/embed/' + v.id + '" loading="lazy" allowfullscreen title="Video"></iframe></div>', epi);
      } else if (v && v.type === 'vimeo') {
        insertVideoFigure('<div class="video-responsive"><iframe src="https://player.vimeo.com/video/' + v.id + '" loading="lazy" allowfullscreen title="Video"></iframe></div>', epi);
      } else if (/\.(mp4|webm)(\?|#|$)/i.test(url)) {
        insertVideoFigure('<video controls preload="metadata" playsinline src="' + url.replace(/"/g, '&quot;') + '"></video>', epi);
      } else {
        modalVideoStatus.textContent = 'Enlace no reconocido. Peg\u00e1 un link de YouTube, Vimeo o un MP4/WebM directo.';
        modalVideoStatus.style.display = 'block';
      }
    }
  });
});
</script>

<!-- Modal para Inserción de Fotografía con Pie de Foto en el Cuerpo -->
<div id="modalInsertFoto" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalFotoTitle">
  <div class="modal-card">
    <div class="modal-header">
      <h3 id="modalFotoTitle">📷 Insertar Fotografía con Epígrafe</h3>
      <button type="button" class="modal-close-btn" id="btnModalClose" aria-label="Cerrar modal">&times;</button>
    </div>
    <div class="modal-body">
      <div>
        <label style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">1. Selecciona o arrastra la imagen (JPG, PNG, WEBP)</label>
        <div class="drop-zone" id="modalDropZone">
          <input type="file" id="modalFileInput" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;">
          <div id="dropZonePrompt">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="color:var(--rojo);margin-bottom:6px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <p style="font-size:13px;font-weight:600;margin:0 0 2px;">Haz clic aquí o arrastra tu archivo</p>
            <span style="font-size:11px;color:var(--doc-muted);">Máximo 8 MB</span>
          </div>
          <img id="modalPreviewImg" class="drop-zone-preview" alt="Vista previa">
        </div>
      </div>

      <div>
        <label for="modalUrlInput" style="font-size:12px;font-weight:500;color:var(--doc-muted);display:block;margin-bottom:4px;">O introduce una URL de imagen:</label>
        <input type="url" id="modalUrlInput" placeholder="https://ejemplo.com/foto.jpg" style="width:100%;padding:8px 10px;border:1px solid var(--toolbar-border);border-radius:4px;font-size:13px;box-sizing:border-box;">
      </div>

      <div>
        <label for="modalEpigrafeInput" style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">2. Pie de Foto / Epígrafe Periodístico *</label>
        <input type="text" id="modalEpigrafeInput" placeholder="Ej: La costa de Camet Norte tras la sudestada. Foto: Télam." style="width:100%;padding:9px 12px;border:1px solid var(--toolbar-border);border-radius:4px;font-size:14px;box-sizing:border-box;">
      </div>

      <div>
        <label for="modalAlignSelect" style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">3. Disposición en el Texto</label>
        <select id="modalAlignSelect" style="width:100%;padding:8px 10px;border:1px solid var(--toolbar-border);border-radius:4px;font-size:13px;box-sizing:border-box;">
          <option value="align-center">Centrada (Ancho completo de lectura)</option>
          <option value="align-left">Flotante a la izquierda (El texto rodea a la derecha)</option>
          <option value="align-right">Flotante a la derecha (El texto rodea a la izquierda)</option>
        </select>
      </div>

      <div id="modalUploadStatus" style="font-size:12px;color:var(--rojo);font-weight:600;display:none;"></div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn-cancel-doc" id="btnModalCancel">Cancelar</button>
      <button type="button" class="btn-save-doc" id="btnModalConfirm">
        Insertar en la Noticia
      </button>
    </div>
  </div>
</div>

<!-- Modal para Insertar Video en el Cuerpo -->
<div id="modalInsertVideo" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalVideoTitle">
  <div class="modal-card">
    <div class="modal-header">
      <h3 id="modalVideoTitle">🎬 Insertar Video</h3>
      <button type="button" class="modal-close-btn" id="btnVideoClose" aria-label="Cerrar modal">&times;</button>
    </div>
    <div class="modal-body">
      <div>
        <label style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">1. Sub&iacute; un archivo de video (MP4 o WebM, m&aacute;x. 64 MB)</label>
        <input type="file" id="modalVideoFile" accept="video/mp4,video/webm" style="width:100%;padding:8px 10px;border:1px solid var(--toolbar-border);border-radius:4px;font-size:13px;box-sizing:border-box;">
      </div>
      <div style="margin-top:10px;">
        <label for="modalVideoUrl" style="font-size:12px;font-weight:500;color:var(--doc-muted);display:block;margin-bottom:4px;">O peg&aacute; un enlace (YouTube, Vimeo o MP4 directo):</label>
        <input type="url" id="modalVideoUrl" placeholder="https://www.youtube.com/watch?v=..." style="width:100%;padding:8px 10px;border:1px solid var(--toolbar-border);border-radius:4px;font-size:13px;box-sizing:border-box;">
      </div>
      <div style="margin-top:10px;">
        <label for="modalVideoEpi" style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">2. Ep&iacute;grafe del video</label>
        <input type="text" id="modalVideoEpi" placeholder="Ej: El momento del gol que defini&oacute; el partido." style="width:100%;padding:9px 12px;border:1px solid var(--toolbar-border);border-radius:4px;font-size:14px;box-sizing:border-box;">
      </div>
      <div id="modalVideoStatus" style="font-size:12px;color:var(--rojo);font-weight:600;display:none;margin-top:8px;"></div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn-cancel-doc" id="btnVideoCancel">Cancelar</button>
      <button type="button" class="btn-save-doc" id="btnVideoConfirm">Insertar Video</button>
    </div>
  </div>
</div>

</main>
</body>
</html>