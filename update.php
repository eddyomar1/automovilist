<?php
require_once 'conexion.php';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: index.php'); exit;
}
$id = (int)$_GET['id'];

// Buscar registro
$stmt = $con->prepare("SELECT * FROM clientes WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$resultado = $res->fetch_assoc();
if (!$resultado) { header('Location: index.php'); exit; }

$errors = [];
// Pre-carga
$nombre    = $resultado['nombre'];
$apellidos = $resultado['apellidos'];
$telefono  = $resultado['telefono'];
$ciudad    = $resultado['ciudad'];
$correo    = $resultado['correo'];

if (isset($_POST['guardar'])) {
    $nombre    = trim($_POST['nombre']    ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $telefono  = trim($_POST['telefono']  ?? '');
    $ciudad    = trim($_POST['ciudad']    ?? '');
    $correo    = trim($_POST['correo']    ?? '');

    if ($nombre==='' || $apellidos==='' || $telefono==='' || $ciudad==='' || $correo==='') {
        $errors[] = "Todos los campos son obligatorios.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Correo no válido.";
    }

    if (!$errors) {
        $upd = $con->prepare("UPDATE clientes
                              SET nombre=?, apellidos=?, telefono=?, ciudad=?, correo=?
                              WHERE id=?");
        $upd->bind_param('sssssi', $nombre, $apellidos, $telefono, $ciudad, $correo, $id);
        $upd->execute();
        header('Location: index.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Cliente</title>
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
      <input type="text" name="nombre"    value="<?php echo e($nombre); ?>"    class="input__text">
      <input type="text" name="apellidos" value="<?php echo e($apellidos); ?>" class="input__text">
    </div>
    <div class="form-group">
      <input type="text" name="telefono"  value="<?php echo e($telefono); ?>"  class="input__text">
      <input type="text" name="ciudad"    value="<?php echo e($ciudad); ?>"    class="input__text">
    </div>
    <div class="form-group">
      <input type="text" name="correo"    value="<?php echo e($correo); ?>"    class="input__text">
    </div>
    <div class="btn__group">
      <a href="index.php" class="btn btn__danger">Cancelar</a>
      <input type="submit" name="guardar" value="Guardar" class="btn btn__primary">
    </div>
  </form>
</div>
</body>
</html>
