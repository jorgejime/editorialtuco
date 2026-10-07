<?php
// ============================================================
//  Editorial Tucó — Botón de Arrepentimiento y Baja de Servicios
//  Marco legal: Resoluciones 424/2020 y 271/2020 SCI (Argentina)
// ============================================================
require_once __DIR__ . '/inc/config.php';

$tipo = ($_GET['tipo'] ?? 'arrepentimiento') === 'baja' ? 'baja' : 'arrepentimiento';
$page_title = $tipo === 'baja' ? 'Botón de Baja de Servicios (Res. 271/2020)' : 'Botón de Arrepentimiento (Res. 424/2020)';

$enviado = false;
$codigo_tramite = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $servicio = trim($_POST['servicio'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');
    $tipo_post = ($_POST['tipo_solicitud'] ?? 'arrepentimiento') === 'baja' ? 'baja' : 'arrepentimiento';

    if (empty($nombre) || empty($email) || empty($dni)) {
        $error = 'Por favor complete todos los campos obligatorios (Nombre, Email y DNI).';
    } else {
        // Generación de código único de identificación de trámite fehaciente
        $prefijo = $tipo_post === 'baja' ? 'TUCO-BAJA' : 'TUCO-ARR';
        $codigo_tramite = $prefijo . '-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

        $registro = [
            'codigo' => $codigo_tramite,
            'tipo' => $tipo_post,
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'dni' => $dni,
            'servicio' => $servicio,
            'motivo' => $motivo,
            'fecha' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ];

        // Guardar constancia en log de auditoría
        $log_file = ROOT . '/data/tramites_consumidor.json';
        $actuales = file_exists($log_file) ? json_decode(file_get_contents($log_file), true) : [];
        if (!is_array($actuales)) $actuales = [];
        $actuales[] = $registro;
        file_put_contents($log_file, json_encode($actuales, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $enviado = true;
    }
}

require_once __DIR__ . '/inc/header.php';
?>

<article class="legal-page">
  <div class="legal-header">
    <span class="kicker">Defensa del Consumidor · Ley N° 24.240</span>
    <h1 class="page-title">
      <?= $tipo === 'baja' ? 'Solicitud de Rescisión y Baja de Servicios' : 'Formulario de Revocación y Arrepentimiento' ?>
    </h1>
    <p class="muted">
      Cumplimiento de las Resoluciones <strong>424/2020</strong> y <strong>271/2020</strong> de la Secretaría de Comercio Interior de la Nación.
    </p>
  </div>

  <div class="legal-body">
    <?php if ($enviado): ?>
      <div class="tramite-confirmacion">
        <div class="tramite-badge">Trámite Registrado Exitosamente</div>
        <h2>Constancia de Recepción de Solicitud</h2>
        <p>Su solicitud ha sido asentada de conformidad con las normativas vigentes en la República Argentina.</p>
        
        <div class="tramite-codigo-box">
          <span class="tramite-label">Código Único de Identificación de Trámite:</span>
          <strong class="tramite-numero"><?= e($codigo_tramite) ?></strong>
          <span class="tramite-fecha">Fecha y hora de emisión: <?= e(date('d/m/Y H:i:s')) ?> (Hora Oficial Argentina)</span>
        </div>

        <p class="muted">
          De acuerdo con el artículo 2° de la Resolución 424/2020 y la Resolución 271/2020, se remitirá la constancia fehaciente al correo <strong><?= e($email) ?></strong> dentro de las 24 horas y no se requerirá ningún trámite ni gestión adicional por parte del titular.
        </p>

        <div style="margin-top: 24px;">
          <a href="index.php" class="btn">Volver a la portada</a>
        </div>
      </div>
    <?php else: ?>

      <div class="legal-highlight">
        <?php if ($tipo === 'baja'): ?>
          <strong>Resolución 271/2020 SCI:</strong> Si contás con una suscripción, membresía o servicio periódico contratado en Editorial Tucó, podés solicitar la baja inmediata completando el siguiente formulario. La rescisión operará dentro de los plazos legales y no devengará cargos adicionales posteriores a la fecha de solicitud.
        <?php else: ?>
          <strong>Resolución 424/2020 SCI & Art. 34 Ley 24.240:</strong> Tenés derecho a revocar la aceptación del producto o servicio dentro del plazo de <strong>diez (10) días corridos</strong> contados a partir de la fecha de contratación o recepción del mismo. El ejercicio de este derecho no implica gasto ni penalidad alguna para el consumidor.
        <?php endif; ?>
      </div>

      <?php if (!empty($error)): ?>
        <div class="error"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" class="form legal-form">
        <label>
          Tipo de trámite
          <select name="tipo_solicitud" class="input-select" onchange="location.href='arrepentimiento.php?tipo=' + this.value">
            <option value="arrepentimiento" <?= $tipo === 'arrepentimiento' ? 'selected' : '' ?>>Botón de Arrepentimiento (Revocar compra/suscripción en los primeros 10 días)</option>
            <option value="baja" <?= $tipo === 'baja' ? 'selected' : '' ?>>Botón de Baja de Servicio (Cancelar membresía o servicio periódico)</option>
          </select>
        </label>

        <div class="form-row">
          <label>
            Nombre y Apellido completo *
            <input type="text" name="nombre" required placeholder="Ej. Juan Pérez" value="<?= e($_POST['nombre'] ?? '') ?>">
          </label>
          <label>
            DNI o CUIT del titular *
            <input type="text" name="dni" required placeholder="Ej. 30123456" value="<?= e($_POST['dni'] ?? '') ?>">
          </label>
        </div>

        <div class="form-row">
          <label>
            Correo electrónico registrado *
            <input type="email" name="email" required placeholder="correo@ejemplo.com" value="<?= e($_POST['email'] ?? '') ?>">
          </label>
          <label>
            Teléfono de contacto
            <input type="tel" name="telefono" placeholder="Ej. +54 9 11 1234-5678" value="<?= e($_POST['telefono'] ?? '') ?>">
          </label>
        </div>

        <label>
          Identificación de la suscripción, pedido o servicio
          <input type="text" name="servicio" placeholder="Ej. Suscripción Mensual Digital / Pedido N°..." value="<?= e($_POST['servicio'] ?? '') ?>">
        </label>

        <label>
          Motivo u observaciones (opcional)
          <textarea name="motivo" rows="3" placeholder="Detalle cualquier aclaración sobre su solicitud..."><?= e($_POST['motivo'] ?? '') ?></textarea>
        </label>

        <div class="form-actions">
          <button type="submit" class="btn <?= $tipo === 'arrepentimiento' ? 'btn-arrepentimiento-form' : '' ?>">
            <?= $tipo === 'baja' ? 'Confirmar Solicitud de Baja' : 'Confirmar Ejercicio de Arrepentimiento' ?>
          </button>
        </div>
      </form>

      <div class="consumidor-federal-box">
        <h4>Ventanilla Única Federal de Defensa del Consumidor</h4>
        <p>Ante cualquier duda o reclamo no resuelto, podés acceder al canal oficial de la Dirección Nacional de Defensa del Consumidor y Arbitraje del Consumo del Ministerio de Economía de la Nación:</p>
        <a href="https://www.argentina.gob.ar/produccion/defensadelconsumidor/formulario" target="_blank" rel="noopener" class="btn-consumidor-ext">
          Acceder al Formulario de Reclamos de Defensa del Consumidor
        </a>
      </div>

    <?php endif; ?>
  </div>
</article>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
