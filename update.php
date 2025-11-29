<?php
require __DIR__ . '/init.php';

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
  header('Location: index.php'); exit;
}
$id = (int)$_GET['id'];

$stmt = $con->prepare("SELECT * FROM clientes WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();
if (!$data) { header('Location: index.php'); exit; }

$errors = [];
$old = $data;
$currentFoto = $data['foto_placa'];
$currentCedF = $data['foto_cedula_frente'];
$currentCedB = $data['foto_cedula_atras'];

function process_upload(string $field, array &$errors){
  if (!has_file($field)) return null;
  $f = $_FILES[$field];
  if ($f['error'] !== UPLOAD_ERR_OK) {
    $errors[] = "Error subiendo la imagen de {$field}.";
    return null;
  }
  if ($f['size'] > 3 * 1024 * 1024) {
    $errors[] = "La imagen de {$field} es demasiado grande (máx 3MB).";
    return null;
  }
  $mime = null;
  if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $f['tmp_name']);
    finfo_close($finfo);
  }
  if ($mime && !in_array($mime, ['image/jpeg','image/png','image/gif'])) {
    $errors[] = "Tipo de imagen no permitido en {$field}. Use JPG, PNG o GIF.";
    return null;
  }
  return file_get_contents($f['tmp_name']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $old['nombre']    = body('nombre');
  $old['apellidos'] = body('apellidos');
  $old['telefono']  = body('telefono');
  $old['ciudad']    = body('ciudad');
  $old['correo']    = body('correo');
  $old['codigo_inquilino'] = body('codigo_inquilino');
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

  if (!$errors) {
    $fotoPlacaNew  = process_upload('foto_placa', $errors);
    $fotoCedFNew   = process_upload('foto_cedula_frente', $errors);
    $fotoCedBNew   = process_upload('foto_cedula_atras', $errors);
  }

  if (!$errors) {
    $fotoPlacaFinal = $fotoPlacaNew !== null ? $fotoPlacaNew : $currentFoto;
    $fotoCedFFinal  = $fotoCedFNew !== null ? $fotoCedFNew   : $currentCedF;
    $fotoCedBFinal  = $fotoCedBNew !== null ? $fotoCedBNew   : $currentCedB;

    $sql = "UPDATE clientes
            SET nombre=?, apellidos=?, telefono=?, ciudad=?, correo=?, codigo_inquilino=?, placa=?, modelo=?, color=?, telefono_contacto=?, notas_incidente=?, foto_placa=?, foto_cedula_frente=?, foto_cedula_atras=?
            WHERE id=?";
    $stmt = $con->prepare($sql);
    if ($stmt) {
      $stmt->bind_param(
        'ssssssssssssssi',
        $old['nombre'], $old['apellidos'], $old['telefono'], $old['ciudad'], $old['correo'], $old['codigo_inquilino'],
        $old['placa'], $old['modelo'], $old['color'], $old['telefono_contacto'], $old['notas_incidente'],
        $fotoPlacaFinal, $fotoCedFFinal, $fotoCedBFinal, $id
      );
    }

    if (!$stmt) {
      $errors[] = 'Error en la preparación de la consulta: ' . $con->error;
    } else {
      $exec = $stmt->execute();
      if (!$exec) {
        $errors[] = 'Error al guardar: ' . $stmt->error;
      } else {
        header('Location: index.php'); exit;
      }
    }
  }
}

render_header('Editar vehículo');
?>
<div class="row justify-content-center">
  <div class="col-lg-10 col-xl-8">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
          <h1 class="fw-bold mb-1">Editar vehículo</h1>
          <p class="text-muted mb-0">Actualiza los datos del residente y su auto.</p>
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
          <label class="form-label">Código de inquilino</label>
          <input type="text" name="codigo_inquilino" class="form-control" value="<?= e($old['codigo_inquilino']) ?>" placeholder="Ej. A-203">
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
          <textarea name="notas_incidente" rows="3" class="form-control"><?= e($old['notas_incidente']) ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Foto de la placa</label>
          <input type="file" name="foto_placa" accept="image/*" class="form-control">
          <div class="form-text">Si no subes nada, se mantiene la actual.</div>
          <?php if (!empty($currentFoto)): ?>
            <?php
              $mime = 'image/jpeg';
              if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $det = finfo_buffer($finfo, $currentFoto);
                if ($det) $mime = $det;
                finfo_close($finfo);
              }
            ?>
            <div class="mt-2">
              <span class="text-muted small">Actual:</span><br>
              <img src="data:<?= $mime ?>;base64,<?= base64_encode($currentFoto) ?>" alt="Foto actual" class="thumb">
            </div>
          <?php endif; ?>
        </div>
        <div class="col-md-6">
          <label class="form-label">Cédula - frente</label>
          <input type="file" name="foto_cedula_frente" accept="image/*" class="form-control">
          <div class="form-text">Deja vacío para mantener la actual.</div>
          <?php if (!empty($currentCedF)): ?>
            <?php
              $mimeF = 'image/jpeg';
              if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $det = finfo_buffer($finfo, $currentCedF);
                if ($det) $mimeF = $det;
                finfo_close($finfo);
              }
            ?>
            <div class="mt-2">
              <span class="text-muted small">Actual:</span><br>
              <img src="data:<?= $mimeF ?>;base64,<?= base64_encode($currentCedF) ?>" alt="Cédula frente" class="thumb">
            </div>
          <?php endif; ?>
        </div>
        <div class="col-md-6">
          <label class="form-label">Cédula - atrás</label>
          <input type="file" name="foto_cedula_atras" accept="image/*" class="form-control">
          <div class="form-text">Deja vacío para mantener la actual.</div>
          <?php if (!empty($currentCedB)): ?>
            <?php
              $mimeB = 'image/jpeg';
              if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $det = finfo_buffer($finfo, $currentCedB);
                if ($det) $mimeB = $det;
                finfo_close($finfo);
              }
            ?>
            <div class="mt-2">
              <span class="text-muted small">Actual:</span><br>
              <img src="data:<?= $mimeB ?>;base64,<?= base64_encode($currentCedB) ?>" alt="Cédula atrás" class="thumb">
            </div>
          <?php endif; ?>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2">
          <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
          <button type="submit" name="guardar" class="btn btn-primary">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php render_footer(); ?>
