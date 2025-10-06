<?php
require_once 'conexion.php';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$buscar_text = trim($_POST['buscar'] ?? '');
$resultado   = [];

// Consulta
if (isset($_POST['btn_buscar']) && $buscar_text !== '') {
    $sql = "SELECT id, nombre, apellidos, telefono, ciudad, correo
            FROM clientes
            WHERE (nombre LIKE ? OR apellidos LIKE ? OR telefono LIKE ? OR ciudad LIKE ? OR correo LIKE ?)
            ORDER BY id DESC";
    $stmt = $con->prepare($sql);
    $like = "%{$buscar_text}%";
    $stmt->bind_param('sssss', $like, $like, $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $con->query("SELECT id, nombre, apellidos, telefono, ciudad, correo
                        FROM clientes ORDER BY id DESC");
}

// Pasar a array
if ($res) {
    while ($row = $res->fetch_assoc()) { $resultado[] = $row; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Inicio</title>
  <link rel="stylesheet" href="css/estilo.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div class="contenedor">
  <h2>Registro de vehiculos</h2>

  <div class="barra__buscador">
    <form action="" class="formulario" method="post">
      <input type="text" name="buscar" placeholder="buscar por nombre, apellidos, teléfono, ciudad o correo"
             value="<?php echo e($buscar_text); ?>" class="input__text">
      <input type="submit" class="btn" name="btn_buscar" value="Buscar">
      <a href="insert.php" class="btn btn__nuevo">Nuevo</a>
    </form>
  </div>

  <table>
    <tr class="head">
      <td>Id</td>
      <td>Nombre</td>
      <td>Apellidos</td>
      <td>Teléfono</td>
      <td>Ciudad</td>
      <td>Correo</td>
      <td colspan="2">Acción</td>
    </tr>

    <?php if (count($resultado) === 0): ?>
      <tr><td colspan="8">Sin resultados.</td></tr>
    <?php else: ?>
      <?php foreach($resultado as $fila): ?>
        <tr>
          <td><?php echo e($fila['id']); ?></td>
          <td><?php echo e($fila['nombre']); ?></td>
          <td><?php echo e($fila['apellidos']); ?></td>
          <td><?php echo e($fila['telefono']); ?></td>
          <td><?php echo e($fila['ciudad']); ?></td>
          <td><?php echo e($fila['correo']); ?></td>
          <td><a href="update.php?id=<?php echo (int)$fila['id']; ?>" class="btn__update">Editar</a></td>
          <td><a href="delete.php?id=<?php echo (int)$fila['id']; ?>" class="btn__delete"
                 onclick="return confirm('¿Eliminar este registro?');">Eliminar</a></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </table>
</div>
</body>
</html>
