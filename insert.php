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
  total_visitantes INT NOT NULL DEFAULT 1,
  minutos_estadia INT NOT NULL DEFAULT 60,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  foto_cedula LONGBLOB NULL,
  foto_placa LONGBLOB NULL,
  FOREIGN KEY (inquilino_id) REFERENCES inquilinos_porteria(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// Asegura columnas si la tabla ya existía sin ellas
@$con->query("ALTER TABLE visitas_porteria ADD COLUMN total_visitantes INT NOT NULL DEFAULT 1");
@$con->query("ALTER TABLE visitas_porteria ADD COLUMN minutos_estadia INT NOT NULL DEFAULT 60");
// Asegura tabla y columnas en llaves_qr para la pre-creación de llaves pendientes
$con->query("CREATE TABLE IF NOT EXISTS llaves_qr (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inquilino_id INT NULL,
  cedula VARCHAR(50) NOT NULL,
  nombre VARCHAR(180) NULL,
  apartamento VARCHAR(80) NULL,
  visitante VARCHAR(180) NULL,
  total_visitantes INT NOT NULL DEFAULT 1,
  minutos_estadia INT NOT NULL DEFAULT 60,
  codigo VARCHAR(64) NOT NULL UNIQUE,
  estado ENUM('pendiente','generada','entrada','salida','expirada') NOT NULL DEFAULT 'pendiente',
  usado_entrada DATETIME NULL,
  usado_salida DATETIME NULL,
  expira_despues_salida DATETIME NULL,
  foto_cedula LONGBLOB NULL,
  foto_placa LONGBLOB NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX(inquilino_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
@$con->query("ALTER TABLE llaves_qr ADD COLUMN visitante VARCHAR(180) NULL");
@$con->query("ALTER TABLE llaves_qr ADD COLUMN total_visitantes INT NOT NULL DEFAULT 1");
@$con->query("ALTER TABLE llaves_qr ADD COLUMN minutos_estadia INT NOT NULL DEFAULT 60");
// Retira columna nota si existiera
@$con->query("ALTER TABLE visitas_porteria DROP COLUMN nota");

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
  $visitante = trim((string)($_POST['visitante'] ?? ''));
  $totalVis  = max(1, (int)($_POST['total_visitantes'] ?? 1));
  $horasStay = max(1, (int)($_POST['horas_estadia'] ?? 1));
  $minStay   = $horasStay * 60;
  // Solo notifica; fotos se subirán al completar la llave
  $stmtIns = $con->prepare("INSERT INTO visitas_porteria (inquilino_id, visitante, total_visitantes, minutos_estadia) VALUES (?, ?, ?, ?)");
  if ($stmtIns && $stmtIns->bind_param('isii', $inqId, $visitante, $totalVis, $minStay) && $stmtIns->execute()) {
    // También crea un registro pendiente en llaves_qr con código temporal
    $codigoTmp = 'PEND-'.bin2hex(random_bytes(6));
    $stmtL = $con->prepare("INSERT INTO llaves_qr (inquilino_id, cedula, nombre, apartamento, visitante, total_visitantes, minutos_estadia, codigo, estado) VALUES (?,?,?,?,?,?,?, ?, 'pendiente')");
    $cedTmp = '00000000000';
    $nomTmp = $visitante;
    $aptTmp = $inquilino['apartamento'] ?? null;
    if ($stmtL) {
      if (!$stmtL->bind_param('issssiis', $inqId, $cedTmp, $nomTmp, $aptTmp, $visitante, $totalVis, $minStay, $codigoTmp) || !$stmtL->execute()) {
        $errors[] = 'No se pudo crear la notificación pendiente: '.$stmtL->error;
      }
    } else {
      $errors[] = 'No se pudo preparar la inserción de llave pendiente: '.$con->error;
    }
    header('Location: llaves.php?pendiente=1');
    exit;
  } else {
    $errors[] = 'No se pudo registrar la visita: ' . ($stmtIns ? $stmtIns->error : $con->error);
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

      <form action="" method="post" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre del visitante *</label>
          <input type="text" name="visitante" class="form-control" required placeholder="Ej. Juan Pérez">
        </div>
        <div class="col-md-3">
          <label class="form-label">Total de visitantes *</label>
          <input type="number" name="total_visitantes" class="form-control" min="1" value="1" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Tiempo de estadía (horas) *</label>
          <input type="number" name="horas_estadia" class="form-control" min="1" step="1" value="1" required>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2">
          <button class="btn btn-primary">Notificar visita</button>
          <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php render_footer(); ?>
