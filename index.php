<?php
require __DIR__ . '/init.php';

$buscar = body('buscar', '');
$rows   = [];

$baseSql = "SELECT id, nombre, apellidos, telefono, ciudad, correo, codigo_inquilino, placa, modelo, color, telefono_contacto, notas_incidente, foto_placa, foto_cedula_frente, foto_cedula_atras FROM clientes";
if ($buscar !== '') {
  $like = "%{$buscar}%";
  $sql  = $baseSql . " WHERE (nombre LIKE ? OR apellidos LIKE ? OR telefono LIKE ? OR ciudad LIKE ? OR correo LIKE ? OR codigo_inquilino LIKE ? OR placa LIKE ? OR modelo LIKE ? OR color LIKE ? OR telefono_contacto LIKE ? OR notas_incidente LIKE ?)
                      ORDER BY id DESC";
  $stmt = $con->prepare($sql);
  if ($stmt) {
    $stmt->bind_param(
      'sssssssssss',
      $like, $like, $like, $like, $like, $like,
      $like, $like, $like, $like, $like
    );
    $stmt->execute();
    $res = $stmt->get_result();
  }
} else {
  $res = $con->query($baseSql . " ORDER BY id DESC");
}

if (!empty($res)) {
  while ($row = $res->fetch_assoc()) { $rows[] = $row; }
}

render_header('Vehículos', 'list');
?>
<div class="card p-3 p-md-4">
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
    <div>
      <h1 class="fw-bold mb-1">Vehículos registrados</h1>
      <p class="text-muted mb-0">Control de residentes, autos y placas.</p>
    </div>
    <form class="d-flex flex-column flex-md-row align-items-md-center gap-2" method="post">
      <label class="form-label d-flex align-items-center gap-2 mb-0">
        <span class="muted">Mostrar</span>
        <select id="lenSelect" class="form-select form-select-sm w-auto">
          <option>5</option><option selected>10</option><option>25</option><option>50</option><option>100</option>
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
        <a href="insert.php" class="btn btn-primary btn-sm">Nuevo</a>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle table-nowrap" id="tabla">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Apellidos</th>
          <th>Teléfono</th>
          <th>Ciudad</th>
          <th>Correo</th>
          <th>Cód.</th>
          <th>Placa</th>
          <th>Modelo</th>
          <th>Color</th>
          <th>Foto</th>
          <th>Cédula (frente)</th>
          <th>Cédula (atrás)</th>
          <th>Contacto</th>
          <th>Notas</th>
          <th class="text-center">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $row): ?>
          <tr>
            <td class="text-muted"><?= (int)$row['id'] ?></td>
            <td><?= e($row['nombre']) ?></td>
            <td><?= e($row['apellidos']) ?></td>
            <td><?= e($row['telefono']) ?></td>
            <td><?= e($row['ciudad']) ?></td>
            <td><?= e($row['correo']) ?></td>
            <td>
              <?php if (!empty($row['codigo_inquilino'])): ?>
                <span class="badge text-bg-secondary"><?= e($row['codigo_inquilino']) ?></span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td><span class="badge text-bg-primary-subtle text-primary"><?= e($row['placa'] ?: '—') ?></span></td>
            <td><?= e($row['modelo'] ?: '—') ?></td>
            <td><?= e($row['color'] ?: '—') ?></td>
            <td>
              <?php if (!empty($row['foto_placa'])): ?>
                <?php
                  $b64 = base64_encode($row['foto_placa']);
                  $mime = 'image/jpeg';
                  if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $det = finfo_buffer($finfo, $row['foto_placa']);
                    if ($det) $mime = $det;
                    finfo_close($finfo);
                  }
                ?>
                <img src="data:<?= $mime ?>;base64,<?= $b64 ?>" alt="Foto placa" class="thumb">
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($row['foto_cedula_frente'])): ?>
                <?php
                  $b64f = base64_encode($row['foto_cedula_frente']);
                  $mimef = 'image/jpeg';
                  if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $det = finfo_buffer($finfo, $row['foto_cedula_frente']);
                    if ($det) $mimef = $det;
                    finfo_close($finfo);
                  }
                ?>
                <img src="data:<?= $mimef ?>;base64,<?= $b64f ?>" alt="Cédula frente" class="thumb">
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($row['foto_cedula_atras'])): ?>
                <?php
                  $b64b = base64_encode($row['foto_cedula_atras']);
                  $mimeb = 'image/jpeg';
                  if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $det = finfo_buffer($finfo, $row['foto_cedula_atras']);
                    if ($det) $mimeb = $det;
                    finfo_close($finfo);
                  }
                ?>
                <img src="data:<?= $mimeb ?>;base64,<?= $b64b ?>" alt="Cédula atrás" class="thumb">
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($row['telefono_contacto'])): ?>
                <div><?= e($row['telefono_contacto']) ?></div>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="text-break">
              <?php
                $nota = trim((string)$row['notas_incidente']);
                $notaCorta = strlen($nota) > 90 ? substr($nota, 0, 90) . '…' : $nota;
              ?>
              <?php if ($nota !== ''): ?>
                <?= e($notaCorta) ?>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <div class="d-inline-flex gap-2 actions">
                <a href="update.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                <a href="delete.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete">Eliminar</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_footer(); ?>
