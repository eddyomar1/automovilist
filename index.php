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
  <div class="top-search">
    <div class="search-row">
      <form action="" method="post" style="display:flex;gap:8px;align-items:center;">
        <input type="text" name="buscar" placeholder="buscar por nombre, apellidos, teléfono, ciudad, correo o placa" value="<?php echo e($buscar_text); ?>" class="input__text">
        <input type="submit" class="btn" name="btn_buscar" value="Buscar">
        <a href="insert.php" class="btn btn__nuevo">Nuevo</a>
      </form>
    </div>
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
          <form action="" method="post" style="margin:0">
            <input type="search" name="buscar" placeholder="Buscar..." value="<?php echo e($buscar_text); ?>">
          </form>
        </div>
        <div class="controls-right">
          <button class="toggle-btn">Solo con deuda</button>
          <button class="toggle-btn active">Todos</button>
          <a href="insert.php" class="btn small">Nuevo</a>
        </div>
      </div>

      <h3 class="top-title">Registro de vehículos</h3>

      <!-- Menu card: atajos rápidos -->
      <div class="menu-card">
        <div class="menu-grid">
          <a href="insert.php" class="menu-item" title="Nuevo vehículo">
            <div class="menu-icon">＋</div>
            <div class="menu-label">Nuevo</div>
            <div class="menu-sub">Agregar vehículo</div>
          </a>
          <a href="#buscar" class="menu-item" onclick="document.querySelector('input[name=buscar]').focus();return false;" title="Buscar">
            <div class="menu-icon">🔍</div>
            <div class="menu-label">Buscar</div>
            <div class="menu-sub">Filtrar registros</div>
          </a>
          <a href="#" class="menu-item" title="Exportar">
            <div class="menu-icon">⬇️</div>
            <div class="menu-label">Exportar</div>
            <div class="menu-sub">CSV / Excel</div>
          </a>
          <a href="#" class="menu-item" title="Ajustes">
            <div class="menu-icon">⚙️</div>
            <div class="menu-label">Ajustes</div>
            <div class="menu-sub">Preferencias</div>
          </a>
        </div>
      </div>

      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>Id</th>
              <th>Nombre</th>
              <th>Apellidos</th>
              <th>Teléfono</th>
              <th>Ciudad</th>
              <th>Correo</th>
              <th>Placa</th>
              <th>Modelo</th>
              <th>Color</th>
              <th>Foto</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($resultado) === 0): ?>
              <tr><td colspan="11">Sin resultados.</td></tr>
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
                      <img src="data:<?php echo $mime; ?>;base64,<?php echo $b; ?>" alt="foto placa" class="thumb">
                    <?php endif; ?>
                  </td>
                  <td>
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
