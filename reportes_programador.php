<?php
require __DIR__ . '/init.php';

const DEV_ACCESS_KEY = 'coopnama-dev';
$providedKey = isset($_GET['clave']) ? trim((string)$_GET['clave']) : '';
if ($providedKey !== DEV_ACCESS_KEY) {
  http_response_code(404);
  exit('Página no encontrada');
}

$reports = [];
$res = $con->query("SELECT id, reportado_por, contacto, asunto, detalle, captura, captura_nombre, captura_mime, created_at FROM reportes_soporte ORDER BY created_at DESC");
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $reports[] = $row;
  }
}

render_header('Reportes de soporte', 'developer');
?>
<div class="card p-3 p-md-4">
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
    <div>
      <h1 class="fw-bold mb-1">Reportes de soporte</h1>
      <p class="text-muted mb-0">Solo visible con la clave de desarrollador.</p>
    </div>
    <div class="text-end small text-muted">
      Usa la URL con <code>?clave=<?= e(DEV_ACCESS_KEY) ?></code> para entrar.
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle table-nowrap" id="tabla">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Fecha</th>
          <th>Reportado por</th>
          <th>Asunto / detalle</th>
          <th>Adjunto</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($reports as $row): ?>
          <tr>
            <td class="text-muted"><?= (int)$row['id'] ?></td>
            <td><?= e(date('d/m/Y H:i', strtotime($row['created_at']))) ?></td>
            <td>
              <div class="fw-semibold"><?= e($row['reportado_por']) ?></div>
              <div class="text-muted small"><?= e($row['contacto'] ?: 'Sin contacto') ?></div>
            </td>
            <td class="text-break" style="max-width:520px">
              <div class="fw-semibold mb-1"><?= e($row['asunto']) ?></div>
              <div class="small text-muted" style="white-space:pre-line;"><?= e($row['detalle']) ?></div>
            </td>
            <td>
              <?php if (!empty($row['captura'])): ?>
                <?php
                  $mime = $row['captura_mime'] ?: 'image/jpeg';
                  if (function_exists('finfo_open') && $row['captura']) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $det = finfo_buffer($finfo, $row['captura']);
                    if ($det) $mime = $det;
                    finfo_close($finfo);
                  }
                  $b64 = base64_encode($row['captura']);
                  $nombre = $row['captura_nombre'] ?: 'captura';
                ?>
                <div class="d-flex flex-column gap-2">
                  <img src="data:<?= $mime ?>;base64,<?= $b64 ?>" alt="Captura" class="thumb">
                  <a class="btn btn-sm btn-outline-primary" href="data:<?= $mime ?>;base64,<?= $b64 ?>" download="<?= e($nombre) ?>">Descargar</a>
                </div>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$reports): ?>
          <tr><td colspan="5" class="text-center text-muted">Sin reportes aún.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_footer(); ?>
