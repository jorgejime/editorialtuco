<?php
require __DIR__ . '/../inc/config.php';
exigir_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['nombre'])) {
    $nombre = trim($_POST['nombre']);
    if ($nombre !== '') {
        $slug = slugify($nombre);
        try {
            $pdo->prepare("INSERT INTO categorias (nombre, slug) VALUES (?, ?)")->execute([$nombre, $slug]);
        } catch (PDOException $e) { /* slug duplicado: se ignora */ }
    }
    header('Location: categorias.php');
    exit;
}

if (isset($_GET['del'])) {
    $del = (int)$_GET['del'];
    // No borrar categorías con noticias asociadas
    $st = $pdo->prepare("SELECT COUNT(*) FROM noticias WHERE categoria_id = ?");
    $st->execute([$del]);
    if ((int)$st->fetchColumn() === 0) {
        $pdo->prepare("DELETE FROM categorias WHERE id = ?")->execute([$del]);
    }
    header('Location: categorias.php');
    exit;
}

$cats = $pdo->query(
    "SELECT c.*, (SELECT COUNT(*) FROM noticias n WHERE n.categoria_id = c.id) AS total
     FROM categorias c ORDER BY c.nombre"
)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/_head.php';
?>
<h1>Categorías</h1>
<form method="post" class="form-inline">
  <input type="text" name="nombre" placeholder="Nueva categoría…" required maxlength="60">
  <button type="submit" class="btn">Agregar</button>
</form>
<div class="tabla-scroll">
<table class="tabla">
  <thead><tr><th>Nombre</th><th>Noticias</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($cats as $c): ?>
    <tr>
      <td><?= e($c['nombre']) ?></td>
      <td><?= (int)$c['total'] ?></td>
      <td class="acciones">
        <?php if ((int)$c['total'] === 0): ?>
          <a href="categorias.php?del=<?= (int)$c['id'] ?>" class="danger" onclick="return confirm('¿Eliminar esta categoría?')">Eliminar</a>
        <?php else: ?>
          <span class="muted">—</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</main>
</body>
</html>
