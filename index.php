<?php
require __DIR__ . '/init.php';

// Tabla de inquilinos (crea si falta)
$con->query("CREATE TABLE IF NOT EXISTS inquilinos_porteria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(180) NOT NULL,
  apartamento VARCHAR(60) NOT NULL,
  telefono VARCHAR(60) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$buscar = body('buscar', '');
$rows   = [];
$baseSql = "SELECT id, nombre, apartamento, telefono, created_at FROM inquilinos_porteria";

if ($buscar !== '') {
  $like = "%{$buscar}%";
  $stmt = $con->prepare($baseSql . " WHERE (nombre LIKE ? OR apartamento LIKE ? OR telefono LIKE ?) ORDER BY id DESC");
  if ($stmt && $stmt->bind_param('sss', $like, $like, $like) && $stmt->execute()) {
    $res = $stmt->get_result();
  }
} else {
  $res = $con->query($baseSql . " ORDER BY id DESC");
}

if (!empty($res)) {
  while ($row = $res->fetch_assoc()) { $rows[] = $row; }
}

render_header('Inquilinos actuales', 'list');
?>
<div class="card p-3 p-md-4">
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
    <div>
      <h1 class="fw-bold mb-1">Inquilinos actuales</h1>
      <p class="text-muted mb-0">Seleccione un registro para documentar una visita (cédula y placa del vehículo).</p>
    </div>
    <form class="d-flex flex-column flex-md-row align-items-md-center gap-2" method="post">
      <label class="form-label d-flex align-items-center gap-2 mb-0">
        <span class="muted">Mostrar</span>
        <select id="lenSelect" class="form-select form-select-sm w-auto">
          <option>5</option><option>10</option><option>25</option><option>50</option><option selected>100</option>
        </select>
      </label>
      <div class="input-group input-group-sm">
        <span class="input-group-text bg-white border-end-0">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85Zm-5.242 1.656a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z"/>
          </svg>
        </span>
        <input type="search" name="buscar" id="globalSearch" class="form-control form-control-sm border-start-0" placeholder="Buscar" value="<?= e($buscar) ?>">
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" type="submit" name="btn_buscar">Buscar</button>
        <a href="inquilinos_porteria.php" class="btn btn-primary btn-sm">Gestionar inquilinos</a>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle table-nowrap" id="tabla">
      <thead class="table-light">
        <tr>
          <th>Nombre</th>
          <th>Apartamento</th>
          <th>Teléfono</th>
          <th>Creado</th>
          <th class="text-center">Registrar visita</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $row): ?>
          <tr>
            <td><?= e($row['nombre']) ?></td>
            <td><?= e($row['apartamento']) ?></td>
            <td><?= e($row['telefono']) ?></td>
            <td class="text-muted"><?= e($row['created_at']) ?></td>
            <td class="text-center">
              <a class="btn btn-primary btn-sm" href="insert.php?inq=<?= (int)$row['id'] ?>">Registrar visita</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_footer(); ?>
