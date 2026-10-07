<?php
require __DIR__ . '/../inc/config.php';
exigir_admin();
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $st = $pdo->prepare("SELECT imagen FROM noticias WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    if ($r = $st->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($r['imagen']) && file_exists(UPLOAD_DIR . '/' . $r['imagen'])) {
            unlink(UPLOAD_DIR . '/' . $r['imagen']);
        }
        $pdo->prepare("DELETE FROM noticias WHERE id = ?")->execute([$id]);
    }
}
header('Location: index.php');
exit;
