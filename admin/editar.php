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

            if ($id) {
                $st = $pdo->prepare("UPDATE noticias SET titulo=?, slug=?, resumen=?, contenido=?, imagen=?, categoria_id=?, destacada=?, publicada=?, fecha_pub=datetime('now') WHERE id=?");
                $st->execute([$titulo, $slug, $resumen, $contenido, $imagen, $categoria_id, $destacada, $publicada, $id]);
            } else {
                $st = $pdo->prepare("INSERT INTO noticias (titulo, slug, resumen, contenido, imagen, categoria_id, destacada, publicada, fecha_pub, creada)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))");
                $st->execute([$titulo, $slug, $resumen, $contenido, $imagen, $categoria_id, $destacada, $publicada]);
            }
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
<link rel="stylesheet" href="../assets/admin-editor.css?v=20261009_v5">

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
        <div class="editor-actions-top">
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

  // Sincronizar contenido del canvas al input hidden
  function syncContent() {
    var html = canvas.innerHTML.trim();
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
});
</script>

</main>
</body>
</html>
