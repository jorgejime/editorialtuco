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
    $contenido = trim($_POST['contenido'] ?? '');
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $destacada = isset($_POST['destacada']) ? 1 : 0;
    $publicada = isset($_POST['publicada']) ? 1 : 0;

    if ($titulo === '' || $resumen === '' || $contenido === '' || !$categoria_id) {
        $error = 'Completa título, resumen, contenido y categoría.';
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
            // Slug único
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
include __DIR__ . '/_head.php';
?>
<h1><?= $id ? 'Editar noticia' : 'Nueva noticia' ?></h1>
<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="form">
  <label>Título
    <input type="text" name="titulo" value="<?= e($n['titulo'] ?? '') ?>" required maxlength="200">
  </label>
  <label>Categoría
    <select name="categoria_id" required>
      <option value="">— Elegir —</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= (int)($n['categoria_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Resumen (aparece en portada y buscador)
    <textarea name="resumen" rows="3" required maxlength="400"><?= e($n['resumen'] ?? '') ?></textarea>
  </label>
  <label>Contenido (separa párrafos con una línea en blanco)
    <textarea name="contenido" rows="12" required><?= e($n['contenido'] ?? '') ?></textarea>
  </label>
  <label>Imagen
    <input type="file" name="imagen" accept="image/*">
  </label>
  <?php if (!empty($n['imagen'])): ?>
    <p class="muted"><img src="../uploads/<?= e($n['imagen']) ?>" alt="" style="max-width:220px;display:block;margin:6px 0">
      <label><input type="checkbox" name="quitar_imagen" value="1"> Quitar imagen actual</label>
    </p>
  <?php endif; ?>
  <label class="check"><input type="checkbox" name="destacada" value="1" <?= !empty($n['destacada']) ? 'checked' : '' ?>> Noticia destacada (aparece en la portada principal)</label>
  <label class="check"><input type="checkbox" name="publicada" value="1" <?= !isset($n['publicada']) || $n['publicada'] ? 'checked' : '' ?>> Publicada (visible en el sitio)</label>
  <div class="form-actions">
    <button type="submit" class="btn">Guardar</button>
    <a href="index.php" class="btn btn-sec">Cancelar</a>
  </div>
</form>
</main>
</body>
</html>
