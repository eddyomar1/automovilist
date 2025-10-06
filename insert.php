<?php
require_once 'conexion.php';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$errors = [];
$old = ['nombre'=>'', 'apellidos'=>'', 'telefono'=>'', 'ciudad'=>'', 'correo'=>''];

if (isset($_POST['guardar'])) {
    $old['nombre']    = trim($_POST['nombre']    ?? '');
    $old['apellidos'] = trim($_POST['apellidos'] ?? '');
    $old['telefono']  = trim($_POST['telefono']  ?? '');
    $old['ciudad']    = trim($_POST['ciudad']    ?? '');
    $old['correo']    = trim($_POST['correo']    ?? '');

    if ($old['nombre']==='' || $old['apellidos']==='' || $old['telefono']==='' || $old['ciudad']==='' || $old['correo']==='') {
        $errors[] = "Todos los campos son obligatorios.";
    } elseif (!filter_var($old['correo'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Correo no válido.";
    }

    if (!$errors) {
        $stmt = $con->prepare("INSERT INTO clientes (nombre, apellidos, telefono, ciudad, correo) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $old['nombre'], $old['apellidos'], $old['telefono'], $old['ciudad'], $old['correo']);
        $stmt->execute();
        header('Location: index.php'); exit;
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

  <form action="" method="post">
    <div class="form-group">
      <input type="text" name="nombre" placeholder="Nombre" class="input__text" value="<?php echo e($old['nombre']); ?>">
      <input type="text" name="apellidos" placeholder="Apellidos" class="input__text" value="<?php echo e($old['apellidos']); ?>">
    </div>
    <div class="form-group">
      <input type="text" name="telefono" placeholder="Teléfono" class="input__text" value="<?php echo e($old['telefono']); ?>">
      <input type="text" name="ciudad" placeholder="Ciudad" class="input__text" value="<?php echo e($old['ciudad']); ?>">
    </div>
    <div class="form-group">
      <input type="text" name="correo" placeholder="Correo electrónico" class="input__text" value="<?php echo e($old['correo']); ?>">
    </div>
    <div class="btn__group">
      <a href="index.php" class="btn btn__danger">Cancelar</a>
      <input type="submit" name="guardar" value="Guardar" class="btn btn__primary">
    </div>
  </form>
</div>
</body>
</html>
