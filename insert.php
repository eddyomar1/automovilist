<?php
require __DIR__ . '/init.php';

// Tablas
$con->query("CREATE TABLE IF NOT EXISTS inquilinos_porteria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(180) NOT NULL,
  apartamento VARCHAR(60) NOT NULL,
  telefono VARCHAR(60) NULL,
  rol ENUM('inquilino','dueno') NOT NULL DEFAULT 'inquilino',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// Asegura columna rol si ya existía la tabla.
@$con->query("ALTER TABLE inquilinos_porteria ADD COLUMN rol ENUM('inquilino','dueno') NOT NULL DEFAULT 'inquilino'");

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

$inqId = isset($_GET['inq']) ? (int)$_GET['inq'] : 0;
if ($inqId <= 0) { header('Location: index.php'); exit; }

$stmt = $con->prepare("SELECT * FROM inquilinos_porteria WHERE id=?");
$stmt->bind_param('i', $inqId);
$stmt->execute();
$res = $stmt->get_result();
$inquilino = $res ? $res->fetch_assoc() : null;
if (!$inquilino) { header('Location: index.php'); exit; }

$errors = [];
$msg = '';

function procesar_foto_visit(string $field, array &$errors): ?string{
  if (!has_file($field)) return null;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $visitante = ''; // campo oculto; ya no se captura el nombre
  $fotoCedula = procesar_foto_visit('foto_cedula', $errors);
  $fotoPlaca  = procesar_foto_visit('foto_placa', $errors);

  if (!$errors) {
    $stmtIns = $con->prepare("INSERT INTO visitas_porteria (inquilino_id, visitante, foto_cedula, foto_placa) VALUES (?, ?, ?, ?)");
    if ($stmtIns && $stmtIns->bind_param('isss', $inqId, $visitante, $fotoCedula, $fotoPlaca) && $stmtIns->execute()) {
      $msg = 'Visita registrada correctamente.';
    } else {
      $errors[] = 'No se pudo registrar la visita: ' . ($stmtIns ? $stmtIns->error : $con->error);
    }
  }
}

render_header('Registrar visita', 'new');
?>
<div class="row justify-content-center">
  <div class="col-lg-10 col-xl-8">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
          <h1 class="fw-bold mb-1">Registrar visita</h1>
          <p class="text-muted mb-0">Captura la cédula y la placa del vehículo para el residente seleccionado.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Volver</a>
      </div>

  <div class="alert alert-secondary">
    <div class="fw-semibold mb-1"><?= e($inquilino['nombre']) ?></div>
    <div class="small text-muted">Apartamento: <?= e($inquilino['apartamento']) ?> | Tel: <?= e($inquilino['telefono']) ?></div>
  </div>

      <?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <?php foreach($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form action="" method="post" enctype="multipart/form-data" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Foto de la cédula</label>
          <input type="file" name="foto_cedula" accept="image/*" capture="environment" class="form-control" required>
          <div class="form-text">Máx. 5MB. Formatos de imagen.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Foto de la placa</label>
          <input type="file" name="foto_placa" accept="image/*" capture="environment" class="form-control" required>
          <div class="form-text">Máx. 5MB. Formatos de imagen.</div>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2">
          <button class="btn btn-primary">Guardar visita</button>
          <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php render_footer(); ?>
