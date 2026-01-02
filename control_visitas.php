<?php
require __DIR__ . '/init.php';

// Tablas necesarias
$con->query("CREATE TABLE IF NOT EXISTS inquilinos_porteria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(180) NOT NULL,
  apartamento VARCHAR(60) NOT NULL,
  telefono VARCHAR(60) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$con->query("CREATE TABLE IF NOT EXISTS visitas_porteria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inquilino_id INT NOT NULL,
  visitante VARCHAR(180) NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  foto_cedula LONGBLOB NULL,
  foto_placa LONGBLOB NULL,
  nota TEXT NULL,
  FOREIGN KEY (inquilino_id) REFERENCES inquilinos_porteria(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$errors = [];
$msg = '';

function procesar_foto(string $field, array &$errors): ?string{
  if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
  $f = $_FILES[$field];
  if ($f['error'] !== UPLOAD_ERR_OK) {
    $errors[] = "No se pudo subir {$field} (código {$f['error']}).";
    return null;
  }
  if ($f['size'] > 5 * 1024 * 1024) {
    $errors[] = "{$field} supera 5MB.";
    return null;
  }
  return file_get_contents($f['tmp_name']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_visita'])) {
  $inqId = (int)($_POST['inquilino_id'] ?? 0);
  $visitante = trim((string)($_POST['visitante'] ?? ''));
  $nota = trim((string)($_POST['nota'] ?? ''));
  if ($inqId <= 0) {
    $errors[] = 'Selecciona un inquilino válido.';
  }
  $fotoCedula = procesar_foto('foto_cedula', $errors);
  $fotoPlaca  = procesar_foto('foto_placa', $errors);

  if (!$errors) {
    $stmt = $con->prepare("INSERT INTO visitas_porteria (inquilino_id, visitante, foto_cedula, foto_placa, nota) VALUES (?, ?, ?, ?, ?)");
    if ($stmt && $stmt->bind_param('issss', $inqId, $visitante, $fotoCedula, $fotoPlaca, $nota) && $stmt->execute()) {
      $msg = 'Visita registrada.';
    } else {
      $errors[] = 'No se pudo registrar la visita: ' . ($stmt ? $stmt->error : $con->error);
    }
  }
}

$buscar = trim((string)($_GET['q'] ?? ''));
$inqs = [];
if ($buscar !== '') {
  $like = "%{$buscar}%";
  $stmt = $con->prepare("SELECT * FROM inquilinos_porteria WHERE nombre LIKE ? OR apartamento LIKE ? ORDER BY nombre");
  if ($stmt && $stmt->bind_param('ss', $like, $like) && $stmt->execute()) {
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $inqs[] = $r;
  }
}

render_header('Control de visitas','portero');
?>
<div class="card p-3 p-md-4">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div>
      <h1 class="fw-bold mb-1">Control de visitas</h1>
      <p class="text-muted mb-0">Busca al residente y captura foto de cédula y placa antes de ingresar.</p>
    </div>
    <form class="d-flex align-items-center gap-2" method="get">
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Buscar por nombre o apartamento" value="<?= e($buscar) ?>" style="min-width:260px">
      <button class="btn btn-primary btn-sm">Buscar</button>
    </form>
  </div>

  <?php if($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
  <?php if($errors): ?>
    <div class="alert alert-danger">
      <?php foreach($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if($inqs): ?>
    <div class="table-responsive mb-4">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Inquilino</th>
            <th>Apartamento</th>
            <th>Teléfono</th>
            <th class="text-center">Registrar visita</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($inqs as $inq): ?>
            <tr>
              <td><?= e($inq['nombre']) ?></td>
              <td><?= e($inq['apartamento']) ?></td>
              <td><?= e($inq['telefono']) ?></td>
              <td class="text-center">
                <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end">
                  <input type="hidden" name="inquilino_id" value="<?= (int)$inq['id'] ?>">
                  <div class="col-md-3">
                    <label class="form-label">Visitante (opcional)</label>
                    <input type="text" name="visitante" class="form-control form-control-sm" placeholder="Nombre del visitante">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Foto cédula</label>
                    <input type="file" name="foto_cedula" class="form-control form-control-sm" accept="image/*">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Foto placa vehículo</label>
                    <input type="file" name="foto_placa" class="form-control form-control-sm" accept="image/*">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Nota</label>
                    <input type="text" name="nota" class="form-control form-control-sm" placeholder="Opcional">
                  </div>
                  <div class="col-md-1 d-grid">
                    <button class="btn btn-sm btn-success" name="registrar_visita" value="1">Guardar</button>
                  </div>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php elseif($buscar !== ''): ?>
    <div class="alert alert-warning">No se encontraron inquilinos para esa búsqueda.</div>
  <?php else: ?>
    <div class="alert alert-info">Busca un residente para registrar una visita.</div>
  <?php endif; ?>
</div>
<?php render_footer(); ?>
