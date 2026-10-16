<?php
// Endpoint de subida asíncrona de videos para el cuerpo de noticias
require __DIR__ . '/../inc/config.php';
exigir_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

if (empty($_FILES['video']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No se recibió ningún archivo de video.']);
    exit;
}

$file = $_FILES['video'];
$maxSize = 64 * 1024 * 1024; // 64 MB

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El archivo supera el tamaño máximo permitido de 64 MB.']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$permitidas = ['mp4', 'webm'];
if (!in_array($ext, $permitidas, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Formato no soportado. Formatos válidos: MP4 o WebM.']);
    exit;
}

// Validar que realmente sea un video (MIME real, no solo extensión)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$mimes_ok = ['video/mp4', 'video/webm', 'video/quicktime'];
if (!in_array($mime, $mimes_ok, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El archivo seleccionado no es un video válido.']);
    exit;
}
// Normalizar extensión según MIME real
if ($mime === 'video/webm') $ext = 'webm';
elseif ($mime === 'video/quicktime') $ext = 'mp4';

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

$nuevo = 'video_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$destino = UPLOAD_DIR . '/' . $nuevo;

if (!move_uploaded_file($file['tmp_name'], $destino)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el video en el directorio de almacenamiento.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'url' => '../uploads/' . rawurlencode($nuevo),
    'public_url' => 'uploads/' . rawurlencode($nuevo),
    'filename' => $nuevo,
]);
exit;
