<?php
// Endpoint de subida asíncrona de fotografías para el cuerpo de noticias
require __DIR__ . '/../inc/config.php';
exigir_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

if (empty($_FILES['foto']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No se recibió ningún archivo de imagen.']);
    exit;
}

$file = $_FILES['foto'];
$maxSize = 8 * 1024 * 1024; // 8 MB

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El archivo supera el tamaño máximo permitido de 8 MB.']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
if (!in_array($ext, $permitidas, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Formato no soportado. Formatos válidos: JPG, PNG, WEBP o GIF.']);
    exit;
}

// Validar que realmente sea un archivo de imagen válido
$info = @getimagesize($file['tmp_name']);
if ($info === false) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El archivo seleccionado no es una imagen válida.']);
    exit;
}

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

$nuevo = 'cuerpo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$destino = UPLOAD_DIR . '/' . $nuevo;

if (!move_uploaded_file($file['tmp_name'], $destino)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo guardar la imagen en el directorio de almacenamiento.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'url' => '../uploads/' . rawurlencode($nuevo),
    'public_url' => 'uploads/' . rawurlencode($nuevo),
    'filename' => $nuevo,
]);
exit;
