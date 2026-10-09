<?php
require __DIR__ . '/../inc/config.php';
exigir_admin();
$pdo = db();

$notas = $pdo->query(
    "SELECT n.*, c.nombre AS categoria FROM noticias n
     JOIN categorias c ON c.id = n.categoria_id
     ORDER BY n.fecha_pub DESC"
)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/_head.php';
?>
<div class="admin-title">
  <h1>Noticias</h1>
  <a href="editar.php" class="btn" style="display:inline-flex;align-items:center;gap:6px;background:var(--rojo);color:#fff;border-color:var(--rojo);">✍️ Redactar en Canvas</a>
</div>
<div class="tabla-scroll">
<table class="tabla">
  <thead>
    <tr><th>Título</th><th>Categoría</th><th>Destacada</th><th>Publicada</th><th>Fecha</th><th></th></tr>
  </thead>
  <tbody>
  <?php foreach ($notas as $n): ?>
    <tr>
      <td><?= e($n['titulo']) ?></td>
      <td><?= e($n['categoria']) ?></td>
      <td><?= $n['destacada'] ? 'Sí' : '—' ?></td>
      <td><?= $n['publicada'] ? 'Sí' : 'No' ?></td>
      <td><?= e(substr($n['fecha_pub'], 0, 10)) ?></td>
      <td class="acciones">
        <a href="editar.php?id=<?= (int)$n['id'] ?>">Editar</a>
        <a href="../noticia.php?slug=<?= e($n['slug']) ?>" target="_blank">Ver</a>
        <a href="eliminar.php?id=<?= (int)$n['id'] ?>" class="danger" onclick="return confirm('¿Eliminar esta noticia?')">Eliminar</a>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$notas): ?>
    <tr><td colspan="6">No hay noticias. Crea la primera con el botón superior.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
</main>
</body>
</html>
