<?php
require __DIR__ . '/init.php';

$errors = [];
$exito = false;
$old = [
  'reportado_por' => '',
  'contacto'      => '',
  'asunto'        => '',
  'detalle'       => ''
];

function procesar_captura(string $field, array &$errors): array{
  if (!has_file($field)) {
    return [null, null, null];
  }
  $f = $_FILES[$field];
  if ($f['error'] !== UPLOAD_ERR_OK) {
    $errors[] = "No se pudo adjuntar el archivo (código {$f['error']}).";
    return [null, null, null];
  }
  if ($f['size'] > 5 * 1024 * 1024) {
    $errors[] = "La captura es muy pesada. Máximo 5MB.";
    return [null, null, null];
  }
  $mime = null;
  if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $f['tmp_name']);
    finfo_close($finfo);
  }
  if ($mime && strpos($mime, 'image/') !== 0) {
    $errors[] = "Solo se permiten imágenes (JPG/PNG/WebP).";
    return [null, null, null];
  }
  $nombre = basename($f['name']);
  $contenido = file_get_contents($f['tmp_name']);
  return [$contenido, $nombre, $mime ?: 'application/octet-stream'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $old['reportado_por'] = body('reportado_por');
  $old['contacto']      = body('contacto');
  $old['asunto']        = body('asunto');
  $old['detalle']       = body('detalle');

  if ($old['reportado_por'] === '' || $old['asunto'] === '' || $old['detalle'] === '') {
    $errors[] = "Nombre, asunto y descripción son obligatorios.";
  }

  [$captura, $capturaNombre, $capturaMime] = procesar_captura('captura', $errors);

  if (!$errors) {
    $sql = "INSERT INTO reportes_soporte (reportado_por, contacto, asunto, detalle, captura, captura_nombre, captura_mime)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    if (!$stmt) {
      $errors[] = 'No se pudo preparar el guardado: ' . $con->error;
    } else {
      $stmt->bind_param(
        'sssssss',
        $old['reportado_por'],
        $old['contacto'] ?: null,
        $old['asunto'],
        $old['detalle'],
        $captura,
        $capturaNombre,
        $capturaMime
      );
      $ok = $stmt->execute();
      if (!$ok) {
        $errors[] = 'No se pudo guardar el reporte: ' . $stmt->error;
      } else {
        $exito = true;
        $old = ['reportado_por'=>'', 'contacto'=>'', 'asunto'=>'', 'detalle'=>''];
      }
    }
  }
}

render_header('Reportar inconveniente', 'report');
?>
<div class="row justify-content-center">
  <div class="col-lg-9 col-xl-8">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
          <h1 class="fw-bold mb-1">Reportar inconveniente</h1>
          <p class="text-muted mb-0">Describe el problema y adjunta capturas si lo necesitas.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Volver</a>
      </div>

      <?php if ($exito): ?>
        <div class="alert alert-success">
          Tu reporte fue enviado correctamente. El programador lo revisará en breve.
        </div>
      <?php endif; ?>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <?php foreach($errors as $err): ?>
            <div><?= e($err) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form action="" method="post" enctype="multipart/form-data" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">¿Quién reporta?</label>
          <input type="text" name="reportado_por" class="form-control" value="<?= e($old['reportado_por']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Contacto (teléfono o correo)</label>
          <input type="text" name="contacto" class="form-control" value="<?= e($old['contacto']) ?>" placeholder="Opcional">
        </div>
        <div class="col-12">
          <label class="form-label">Asunto</label>
          <input type="text" name="asunto" class="form-control" value="<?= e($old['asunto']) ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label">Descripción del inconveniente</label>
          <textarea name="detalle" rows="4" class="form-control" required><?= e($old['detalle']) ?></textarea>
          <div class="form-text">Explica qué estabas haciendo, qué esperabas que pasara y qué sucedió.</div>
        </div>
        <div class="col-12">
          <label class="form-label">Captura de pantalla (opcional)</label>
          <input type="file" name="captura" accept="image/*" class="form-control">
          <div class="form-text">Formatos JPG, PNG o WebP. Máximo 5MB.</div>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2">
          <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
          <button type="submit" class="btn btn-primary">Enviar reporte</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php render_footer(); ?>
