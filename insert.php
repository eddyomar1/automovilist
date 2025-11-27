<?php
require_once 'conexion.php';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$errors = [];
$old = [
  'nombre'=>'', 'apellidos'=>'', 'telefono'=>'', 'ciudad'=>'', 'correo'=>'',
  'placa'=>'', 'modelo'=>'', 'color'=>'', 'telefono_contacto'=>'', 'notas_incidente'=>''
];

if (isset($_POST['guardar'])) {
  $old['nombre']    = trim($_POST['nombre']    ?? '');
  $old['apellidos'] = trim($_POST['apellidos'] ?? '');
  $old['telefono']  = trim($_POST['telefono']  ?? '');
  $old['ciudad']    = trim($_POST['ciudad']    ?? '');
  $old['correo']    = trim($_POST['correo']    ?? '');

  $old['placa'] = trim($_POST['placa'] ?? '');
  $old['modelo'] = trim($_POST['modelo'] ?? '');
  $old['color'] = trim($_POST['color'] ?? '');
  $old['telefono_contacto'] = trim($_POST['telefono_contacto'] ?? '');
  $old['notas_incidente'] = trim($_POST['notas_incidente'] ?? '');

  if ($old['nombre']==='' || $old['apellidos']==='' || $old['telefono']==='' || $old['ciudad']==='' || $old['correo']==='') {
    $errors[] = "Todos los campos personales son obligatorios.";
  } elseif (!filter_var($old['correo'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Correo no válido.";
  }

  // Procesar la foto de la placa (opcional)
  $fotoData = null;
  if (!empty($_FILES['foto_placa']) && $_FILES['foto_placa']['error'] !== UPLOAD_ERR_NO_FILE) {
    $f = $_FILES['foto_placa'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
      $errors[] = "Error subiendo la imagen.";
    } else {
      // validar tamaño (ej. 3MB)
      if ($f['size'] > 3 * 1024 * 1024) {
        $errors[] = "La imagen es demasiado grande (máx 3MB).";
      } else {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $f['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, ['image/jpeg','image/png','image/gif'])) {
          $errors[] = "Tipo de imagen no permitido. Use JPG, PNG o GIF.";
        } else {
          $fotoData = file_get_contents($f['tmp_name']);
        }
      }
    }
  }

  if (!$errors) {
    $sql = "INSERT INTO clientes (nombre, apellidos, telefono, ciudad, correo, placa, modelo, color, telefono_contacto, notas_incidente, foto_placa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    if (!$stmt) {
      $errors[] = 'Error en la preparación de la consulta: ' . $con->error;
    } else {
      // pasar '' si fotoData es null para permitir valores nulos
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nuevo Cliente</title>
  <link rel="stylesheet" href="css/estilo.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div class="contenedor">
  <h2>CRUD EN PHP CON MYSQL</h2>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach($errors as $err) echo "<p>".e($err)."</p>"; ?>
    </div>
  <?php endif; ?>

  <form action="" method="post" enctype="multipart/form-data">
    <div class="form-group">
      <input type="text" name="nombre" placeholder="Nombre" class="input__text" value="<?php echo e($old['nombre']); ?>">
      <input type="text" name="apellidos" placeholder="Apellidos" class="input__text" value="<?php echo e($old['apellidos']); ?>">
    </div>
    <div class="form-group">
      <input type="text" name="placa" placeholder="Placa (matrícula)" class="input__text" value="<?php echo e($old['placa']); ?>">
      <input type="text" name="modelo" placeholder="Modelo" class="input__text" value="<?php echo e($old['modelo']); ?>">
    </div>
    <div class="form-group">
      <input type="text" name="color" placeholder="Color" class="input__text" value="<?php echo e($old['color']); ?>">
      <input type="text" name="telefono_contacto" placeholder="Teléfono contacto adicional" class="input__text" value="<?php echo e($old['telefono_contacto']); ?>">
    </div>
    <div class="form-group">
      <label for="foto_placa">Foto de la placa (opcional, JPG/PNG/GIF, máx 3MB)</label>
      <input type="file" name="foto_placa" id="foto_placa" accept="image/*">
    </div>
    <div class="form-group">
      <input type="text" name="telefono" placeholder="Teléfono" class="input__text" value="<?php echo e($old['telefono']); ?>">
      <input type="text" name="ciudad" placeholder="Ciudad" class="input__text" value="<?php echo e($old['ciudad']); ?>">
    </div>
    <div class="form-group">
      <input type="text" name="correo" placeholder="Correo electrónico" class="input__text" value="<?php echo e($old['correo']); ?>">
      <textarea name="notas_incidente" placeholder="Notas / información relevante (ej.: incidencia con vecinos, seguro)" rows="3" class="input__text"><?php echo e($old['notas_incidente']); ?></textarea>
    </div>
    <div class="btn__group">
      <a href="index.php" class="btn btn__danger">Cancelar</a>
      <input type="submit" name="guardar" value="Guardar" class="btn btn__primary">
    </div>
  </form>
</div>
</body>
</html>
