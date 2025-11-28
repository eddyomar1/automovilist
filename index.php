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
  <header class="topbar">
    <div class="topbar__inner">
      <div class="title">RESIDENTES COOPNAMA II</div>
      <a href="insert.php" class="btn">Agregar</a>
    </div>
  </header>

  <div class="app-container">
    <div class="card">
      <div class="list-header">
        <div class="controls-left">
          <label class="show-label">Mostrar
            <select name="page_length">
              <option>10</option>
              <option>25</option>
              <option>50</option>
            </select>
          </label>
          <span class="muted">registros</span>
        </div>
        <form class="controls-right" action="" method="post">
          <label class="search-label">Buscar:
            <input type="search" name="buscar" placeholder="Buscar..." value="<?php echo e($buscar_text); ?>" class="control-input">
          </label>
          <button type="submit" class="btn small" name="btn_buscar">Buscar</button>
          <a href="insert.php" class="btn small btn__nuevo">Nuevo</a>
        </form>
      </div>

      <h3 class="top-title">Registro de vehículos</h3>

      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>Id <span class="sort-arrows"></span></th>
              <th>Nombre <span class="sort-arrows"></span></th>
              <th>Apellidos <span class="sort-arrows"></span></th>
              <th>Teléfono <span class="sort-arrows"></span></th>
              <th>Ciudad <span class="sort-arrows"></span></th>
              <th>Correo <span class="sort-arrows"></span></th>
              <th>Placa <span class="sort-arrows"></span></th>
              <th>Modelo <span class="sort-arrows"></span></th>
              <th>Color <span class="sort-arrows"></span></th>
              <th>Foto</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($resultado) === 0): ?>
              <tr><td colspan="11">Sin resultados.</td></tr>
            <?php else: ?>
              <?php foreach($resultado as $i => $fila): ?>
                <tr class="<?php echo $i % 2 === 0 ? 'row-warm' : 'row-warm-alt'; ?>">
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
                      <img src="data:<?php echo $mime; ?>;base64,<?php echo $b; ?>" alt="foto placa" class="thumb">
                    <?php endif; ?>
                  </td>
                  <td class="actions-cell">
                    <a href="update.php?id=<?php echo (int)$fila['id']; ?>" class="btn-link">Editar</a>
                    |
                    <a href="delete.php?id=<?php echo (int)$fila['id']; ?>" class="btn-link" onclick="return confirm('¿Eliminar este registro?');">Eliminar</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
