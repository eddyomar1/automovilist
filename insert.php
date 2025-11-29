<?php
require __DIR__ . '/init.php';

$errors = [];
$old = [
  'nombre' => '', 'apellidos' => '', 'telefono' => '', 'ciudad' => '', 'correo' => '',
  'placa' => '', 'modelo' => '', 'color' => '', 'telefono_contacto' => '', 'notas_incidente' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $old['nombre']    = body('nombre');
  $old['apellidos'] = body('apellidos');
  $old['telefono']  = body('telefono');
  $old['ciudad']    = body('ciudad');
  $old['correo']    = body('correo');
  $old['placa']     = body('placa');
  $old['modelo']    = body('modelo');
  $old['color']     = body('color');
  $old['telefono_contacto'] = body('telefono_contacto');
  $old['notas_incidente']   = body('notas_incidente');

  if ($old['nombre']==='' || $old['apellidos']==='' || $old['telefono']==='' || $old['ciudad']==='' || $old['correo']==='') {
    $errors[] = "Los datos personales son obligatorios.";
  } elseif (!filter_var($old['correo'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Correo no válido.";
  }

  $fotoData = null;
  if (has_file('foto_placa')) {
    $f = $_FILES['foto_placa'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
      $errors[] = "Error subiendo la imagen.";
    } elseif ($f['size'] > 3 * 1024 * 1024) {
      $errors[] = "La imagen es demasiado grande (máx 3MB).";
    } else {
      $mime = null;
      if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $f['tmp_name']);
        finfo_close($finfo);
      }
      if ($mime && !in_array($mime, ['image/jpeg','image/png','image/gif'])) {
        $errors[] = "Tipo de imagen no permitido. Use JPG, PNG o GIF.";
      } else {
        $fotoData = file_get_contents($f['tmp_name']);
      }
    }
  }

  if (!$errors) {
    $sql = "INSERT INTO clientes (nombre, apellidos, telefono, ciudad, correo, placa, modelo, color, telefono_contacto, notas_incidente, foto_placa)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    if (!$stmt) {
      $errors[] = 'Error en la preparación de la consulta: ' . $con->error;
    } else {
      $fotoParam = $fotoData !== null ? $fotoData : null;
      $stmt->bind_param(
        'sssssssssss',
        $old['nombre'], $old['apellidos'], $old['telefono'], $old['ciudad'], $old['correo'],
        $old['placa'], $old['modelo'], $old['color'], $old['telefono_contacto'], $old['notas_incidente'], $fotoParam
      );
      $exec = $stmt->execute();
      if (!$exec) {
        $errors[] = 'Error al guardar: ' . $stmt->error;
      } else {
        header('Location: index.php'); exit;
      }
    }
  }
}

render_header('Nuevo vehículo', 'new');
?>
<div class="row justify-content-center">
  <div class="col-lg-10 col-xl-8">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
          <h1 class="fw-bold mb-1">Nuevo vehículo</h1>
          <p class="text-muted mb-0">Registra al residente y los datos del auto.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Volver</a>
      </div>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <?php foreach($errors as $err): ?>
            <div><?= e($err) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form action="" method="post" enctype="multipart/form-data" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre</label>
          <input type="text" name="nombre" class="form-control" value="<?= e($old['nombre']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Apellidos</label>
          <input type="text" name="apellidos" class="form-control" value="<?= e($old['apellidos']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Teléfono</label>
          <input type="text" name="telefono" class="form-control" value="<?= e($old['telefono']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Ciudad</label>
          <input type="text" name="ciudad" class="form-control" value="<?= e($old['ciudad']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Correo</label>
          <input type="email" name="correo" class="form-control" value="<?= e($old['correo']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Placa / matrícula</label>
          <input type="text" name="placa" class="form-control" value="<?= e($old['placa']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Modelo</label>
          <input type="text" name="modelo" class="form-control" value="<?= e($old['modelo']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Color</label>
          <input type="text" name="color" class="form-control" value="<?= e($old['color']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Teléfono de contacto</label>
          <input type="text" name="telefono_contacto" class="form-control" value="<?= e($old['telefono_contacto']) ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Notas / incidente</label>
          <textarea name="notas_incidente" rows="3" class="form-control" placeholder="Notas relevantes, incidencias, seguro, etc."><?= e($old['notas_incidente']) ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Foto de la placa (opcional)</label>
          <input type="file" name="foto_placa" accept="image/*" class="form-control">
          <div class="form-text">Formatos JPG/PNG/GIF. Máximo 3MB.</div>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2">
          <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
          <button type="submit" name="guardar" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php render_footer(); ?>
