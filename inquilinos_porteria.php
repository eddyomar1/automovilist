<?php
require __DIR__ . '/init.php';

// Crear tablas si no existen
$con->query("CREATE TABLE IF NOT EXISTS inquilinos_porteria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(180) NOT NULL,
  apartamento VARCHAR(60) NOT NULL,
  telefono VARCHAR(60) NULL,
  rol ENUM('inquilino','dueno') NOT NULL DEFAULT 'inquilino',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// Si la tabla ya existía sin columna rol, intenta agregarla sin romper ejecución.
@$con->query("ALTER TABLE inquilinos_porteria ADD COLUMN rol ENUM('inquilino','dueno') NOT NULL DEFAULT 'inquilino'");

$errors = [];
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion']==='crear') {
  $nombre = trim((string)($_POST['nombre'] ?? ''));
  $apto   = trim((string)($_POST['apartamento'] ?? ''));
  $tel    = trim((string)($_POST['telefono'] ?? ''));
  $rol    = $_POST['rol'] ?? 'inquilino';
  if (!in_array($rol, ['inquilino','dueno'], true)) { $rol = 'inquilino'; }

  if ($nombre === '' || $apto === '') {
$errors[] = 'Nombre y apartamento son obligatorios.';
  }

  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  if (!$errors) {
    if ($id > 0) {
      $stmt = $con->prepare("UPDATE inquilinos_porteria SET nombre=?, apartamento=?, telefono=?, rol=? WHERE id=?");
      if ($stmt && $stmt->bind_param('ssssi', $nombre, $apto, $tel, $rol, $id) && $stmt->execute()) {
        $msg = 'Inquilino actualizado.';
      } else {
        $errors[] = 'No se pudo actualizar: ' . ($stmt ? $stmt->error : $con->error);
      }
    } else {
      $stmt = $con->prepare("INSERT INTO inquilinos_porteria (nombre, apartamento, telefono, rol) VALUES (?, ?, ?, ?)");
      if ($stmt && $stmt->bind_param('ssss', $nombre, $apto, $tel, $rol) && $stmt->execute()) {
        $msg = 'Inquilino agregado.';
      } else {
        $errors[] = 'No se pudo guardar: ' . ($stmt ? $stmt->error : $con->error);
      }
    }
  }
} elseif (isset($_GET['del']) && ctype_digit($_GET['del'])) {
  $del = (int)$_GET['del'];
  $stmt = $con->prepare("DELETE FROM inquilinos_porteria WHERE id=?");
  if ($stmt && $stmt->bind_param('i', $del) && $stmt->execute()) {
    $msg = "Inquilino #{$del} eliminado.";
  } else {
    $errors[] = 'No se pudo eliminar: ' . ($stmt ? $stmt->error : $con->error);
  }
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;
if ($editId > 0) {
  $st = $con->prepare("SELECT * FROM inquilinos_porteria WHERE id=?");
  if ($st && $st->bind_param('i', $editId) && $st->execute()) {
    $r = $st->get_result();
    $editRow = $r ? $r->fetch_assoc() : null;
  }
}

$rows = [];
$res = $con->query("SELECT * FROM inquilinos_porteria ORDER BY created_at DESC");
if ($res) {
  while ($r = $res->fetch_assoc()) $rows[] = $r;
}

render_header('Inquilinos actuales', 'inq');
?>
<div class="card p-3 p-md-4">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div>
      <h1 class="fw-bold mb-1">Inquilinos actuales</h1>
      <p class="text-muted mb-0">Registro rápido de residentes por edificio.</p>
    </div>
  </div>

  <?php if($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
  <?php if($errors): ?>
    <div class="alert alert-danger">
      <?php foreach($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form class="row g-3 mb-4" method="post">
    <input type="hidden" name="accion" value="crear">
    <input type="hidden" name="id" value="<?= $editRow ? (int)$editRow['id'] : 0 ?>">
    <div class="col-md-3">
      <label class="form-label">Nombre completo *</label>
      <input type="text" name="nombre" class="form-control" required value="<?= $editRow ? e($editRow['nombre']) : '' ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Edif / Apartamento *</label>
      <input type="text" name="apartamento" class="form-control" required value="<?= $editRow ? e($editRow['apartamento']) : '' ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Teléfono</label>
      <input type="text" name="telefono" class="form-control" value="<?= $editRow ? e($editRow['telefono']) : '' ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Rol</label>
      <select name="rol" class="form-select">
        <option value="inquilino" <?= $editRow && ($editRow['rol'] ?? '') === 'inquilino' ? 'selected' : '' ?>>Inquilino</option>
        <option value="dueno" <?= $editRow && ($editRow['rol'] ?? '') === 'dueno' ? 'selected' : '' ?>>Dueño</option>
      </select>
    </div>
    <div class="col-md-2 d-flex align-items-end gap-2">
      <button class="btn btn-primary w-100"><?= $editRow ? 'Actualizar' : 'Guardar' ?></button>
      <?php if ($editRow): ?>
        <a class="btn btn-outline-secondary" href="inquilinos_porteria.php?admin=1">Cancelar</a>
      <?php endif; ?>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-striped align-middle" id="tabla">
      <thead class="table-light">
        <tr>
          <th>Nombre</th>
          <th>Apartamento</th>
          <th>Teléfono</th>
          <th>Rol</th>
          <th>Creado</th>
          <th class="text-center">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?= e($r['nombre']) ?></td>
            <td><?= e($r['apartamento']) ?></td>
            <td><?= e($r['telefono']) ?></td>
            <td class="text-capitalize"><?= e($r['rol'] ?? 'inquilino') ?></td>
            <td class="text-muted"><?= e($r['created_at']) ?></td>
            <td class="text-center d-flex justify-content-center gap-2">
              <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$r['id'] ?>&admin=1">Editar</a>
              <a class="btn btn-sm btn-outline-danger" href="?del=<?= (int)$r['id'] ?>&admin=1" onclick="return confirm('¿Eliminar este inquilino?');">Eliminar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_footer(); ?>
