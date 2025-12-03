<?php
require __DIR__ . '/init.php';

const DEV_ACCESS_KEY = 'coopnama-dev';

$errors = [];
$exito = false;
$exitoEdicion = false;
$isEdit = false;
$editId = 0;
$existing = null;
$providedKey = isset($_POST['clave']) ? trim((string)$_POST['clave']) : (isset($_GET['clave']) ? trim((string)$_GET['clave']) : '');
$editId = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
if ($editId > 0 && $providedKey === DEV_ACCESS_KEY) {
  $stmt = $con->prepare("SELECT id, asunto, detalle, captura, captura_nombre, captura_mime FROM reportes_soporte WHERE id=?");
  if ($stmt && $stmt->bind_param('i', $editId) && $stmt->execute()) {
    $res = $stmt->get_result();
    $existing = $res ? $res->fetch_assoc() : null;
    if ($existing) {
      $isEdit = true;
    } else {
      $errors[] = 'No se encontró el reporte indicado.';
    }
  } else {
    $errors[] = 'No se pudo cargar el reporte para edición: ' . ($stmt ? $stmt->error : $con->error);
  }
}

$old = [
  'asunto'  => $existing['asunto'] ?? '',
  'detalle' => $existing['detalle'] ?? ''
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
  $old['asunto']  = body('asunto');
  $old['detalle'] = body('detalle');

  if ($old['asunto'] === '' || $old['detalle'] === '') {
    $errors[] = "Asunto y descripción son obligatorios.";
  }

  [$captura, $capturaNombre, $capturaMime] = procesar_captura('captura', $errors);

  if (!$errors) {
    if ($isEdit) {
      $capturaFinal       = $captura       !== null ? $captura       : ($existing['captura'] ?? null);
      $capturaNombreFinal = $capturaNombre !== null ? $capturaNombre : ($existing['captura_nombre'] ?? null);
      $capturaMimeFinal   = $capturaMime   !== null ? $capturaMime   : ($existing['captura_mime'] ?? null);

      $sql = "UPDATE reportes_soporte
              SET asunto=?, detalle=?, captura=?, captura_nombre=?, captura_mime=?
              WHERE id=?";
      $stmt = $con->prepare($sql);
      if (!$stmt) {
        $errors[] = 'No se pudo preparar la actualización: ' . $con->error;
      } else {
        $stmt->bind_param(
          'sssssi',
          $old['asunto'],
          $old['detalle'],
          $capturaFinal,
          $capturaNombreFinal,
          $capturaMimeFinal,
          $editId
        );
        $ok = $stmt->execute();
        if (!$ok) {
          $errors[] = 'No se pudo actualizar el reporte: ' . $stmt->error;
        } else {
          $exitoEdicion = true;
        }
      }
    } else {
      $sql = "INSERT INTO reportes_soporte (reportado_por, contacto, asunto, detalle, captura, captura_nombre, captura_mime)
              VALUES (?, ?, ?, ?, ?, ?, ?)";
      $stmt = $con->prepare($sql);
      if (!$stmt) {
        $errors[] = 'No se pudo preparar el guardado: ' . $con->error;
      } else {
        // bind_param exige variables por referencia (no se pueden pasar literales)
        $reportadoPor = 'No indicado';
        $contacto = null;
        $stmt->bind_param(
          'sssssss',
          $reportadoPor,
          $contacto,
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
          $old = ['asunto'=>'', 'detalle'=>''];
        }
      }
    }
  }
}

render_header($isEdit ? 'Editar reporte' : 'Reportar inconveniente', 'report');
?>
<div class="row justify-content-center">
  <div class="col-lg-9 col-xl-8">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
          <h1 class="fw-bold mb-1"><?= $isEdit ? 'Editar reporte' : 'Reportar inconveniente' ?></h1>
          <p class="text-muted mb-0">
            <?= $isEdit ? 'Actualiza los datos del reporte.' : 'Describe el problema y adjunta capturas si lo necesitas.' ?>
          </p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Volver</a>
      </div>

      <?php if ($exito): ?>
        <div class="alert alert-success">
          Tu reporte fue enviado correctamente. El programador lo revisará en breve.
        </div>
      <?php endif; ?>

      <?php if ($exitoEdicion): ?>
        <div class="alert alert-success">
          El reporte fue actualizado correctamente.
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
        <?php if ($isEdit): ?>
          <input type="hidden" name="id" value="<?= (int)$editId ?>">
          <input type="hidden" name="clave" value="<?= e(DEV_ACCESS_KEY) ?>">
          <div class="col-12">
            <div class="alert alert-warning mb-0">
              Editando el reporte #<?= (int)$editId ?>. Deja el adjunto vacío para conservar el actual.
            </div>
          </div>
        <?php endif; ?>
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
