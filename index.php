<?php
require_once 'conexion.php';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$buscar_text = trim($_POST['buscar'] ?? '');
$resultado   = [];

// Consulta
if (isset($_POST['btn_buscar']) && $buscar_text !== '') {
        $sql = "SELECT id, nombre, apellidos, telefono, ciudad, correo, placa, modelo, color, telefono_contacto, notas_incidente, foto_placa
          FROM clientes
          WHERE (nombre LIKE ? OR apellidos LIKE ? OR telefono LIKE ? OR ciudad LIKE ? OR correo LIKE ? OR placa LIKE ?)
          ORDER BY id DESC";
    $stmt = $con->prepare($sql);
        $like = "%{$buscar_text}%";
        $stmt->bind_param('sssssss', $like, $like, $like, $like, $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $con->query("SELECT id, nombre, apellidos, telefono, ciudad, correo, placa, modelo, color, telefono_contacto, notas_incidente, foto_placa FROM clientes ORDER BY id DESC");
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
  <link rel="stylesheet" href="assets/css/custom.css">
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
      <td>Placa</td>
      <td>Modelo</td>
      <td>Color</td>
      <td>Foto</td>
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
          <td><?php echo e($fila['placa'] ?? ''); ?></td>
          <td><?php echo e($fila['modelo'] ?? ''); ?></td>
          <td><?php echo e($fila['color'] ?? ''); ?></td>
          <td>
            <?php if (!empty($fila['foto_placa'])): ?>
              <?php $b = base64_encode($fila['foto_placa']);
                    $mime = 'image/jpeg';
                    if (function_exists('finfo_open')) {
                      $finfo = finfo_open(FILEINFO_MIME_TYPE);
                      $det = finfo_buffer($finfo, $fila['foto_placa']);
                      if ($det) $mime = $det;
                      finfo_close($finfo);
                    }
              ?>
              <img src="data:<?php echo $mime; ?>;base64,<?php echo $b; ?>" alt="foto placa" style="max-width:80px;max-height:50px;">
            <?php endif; ?>
          </td>
          <td><a href="update.php?id=<?php echo (int)$fila['id']; ?>" class="btn__update">Editar</a></td>
          <td><a href="delete.php?id=<?php echo (int)$fila['id']; ?>" class="btn__delete"
                 onclick="return confirm('¿Eliminar este registro?');">Eliminar</a></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </table>
</div>

<div class="app-container">
  <div class="card">
    <div class="list-header">
      <div class="controls-left">
        <label>Mostrar
          <select>
            <option>10</option><option>25</option><option>50</option>
          </select>
        </label>
        <input type="search" placeholder="Buscar..." />
      </div>
      <div class="controls-right">
        <button class="toggle-btn">Solo con deuda</button>
        <button class="toggle-btn active">Todos</button>
        <button class="btn small">Residentes</button>
        <button class="btn btn-outline small">Pagos</button>
      </div>
    </div>

    <h3 style="margin:0 0 12px 0;color:#0f172a">LISTA DE COPROPIETARIOS</h3>

    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>Edif/Apto</th>
            <th>Nombres y Apellidos</th>
            <th>Cédula</th>
            <th>Teléfono</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <!-- ... existing rows ... -->
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
