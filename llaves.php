<?php
require __DIR__ . '/init.php';

// Asegura tabla de llaves QR
$alert = null; $generated = null;
try{
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
    estado ENUM('generada','entrada','salida','expirada') NOT NULL DEFAULT 'generada',
    usado_entrada DATETIME NULL,
    usado_salida DATETIME NULL,
    expira_despues_salida DATETIME NULL,
    foto_cedula LONGBLOB NULL,
    foto_placa LONGBLOB NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX(inquilino_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  // Asegura columnas si la tabla ya existía sin ellas
  function ensure_column(mysqli $c, string $table, string $col, string $ddl){
    $chk = $c->query("SHOW COLUMNS FROM {$table} LIKE '{$col}'");
    if ($chk && $chk->fetch_assoc()) return;
    $c->query("ALTER TABLE {$table} ADD COLUMN {$ddl}");
  }
  ensure_column($con, 'llaves_qr', 'inquilino_id', 'INT NULL');
  ensure_column($con, 'llaves_qr', 'foto_cedula', 'LONGBLOB NULL');
  ensure_column($con, 'llaves_qr', 'foto_placa', 'LONGBLOB NULL');
  ensure_column($con, 'llaves_qr', 'visitante', 'VARCHAR(180) NULL');
  ensure_column($con, 'llaves_qr', 'total_visitantes', 'INT NOT NULL DEFAULT 1');
  ensure_column($con, 'llaves_qr', 'minutos_estadia', 'INT NOT NULL DEFAULT 60');
}catch(Throwable $e){
  $alert = ['danger','Error inicializando tabla llaves_qr: '.$e->getMessage()];
}

// Expira llaves que ya pasaron 10 min después de salida
$con->query("UPDATE llaves_qr SET estado='expirada' WHERE estado='salida' AND expira_despues_salida IS NOT NULL AND expira_despues_salida < NOW()");

$alert = null; $generated = null;

function format_cedula_local(string $d): string{
  $digits = preg_replace('/\D+/', '', $d);
  if (strlen($digits) === 11) {
    return substr($digits,0,3).'-'.substr($digits,3,7).'-'.substr($digits,10,1);
  }
  return $d;
}

function procesar_foto_llave(string $field, array &$errors): ?string{
  if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
    $errors[] = "La foto de {$field} es obligatoria.";
    return null;
  }
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

// Registrar uso de llave (entrada/salida)
if (isset($_GET['use'], $_GET['tipo'])) {
  $code = trim($_GET['use']);
  $tipo = $_GET['tipo'];
  $stmt = $con->prepare("SELECT * FROM llaves_qr WHERE codigo=? LIMIT 1");
  $stmt->bind_param('s', $code);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  if (!$row) {
    $alert = ['danger','Código no encontrado.'];
  } else {
    if ($row['estado'] === 'expirada') {
      $alert = ['danger','La llave ya está expirada.'];
    } elseif ($tipo === 'entrada') {
      if (!empty($row['usado_entrada'])) {
        $alert = ['warning','Ya se registró la entrada con esta llave.'];
      } else {
        $now = date('Y-m-d H:i:s');
        $upd = $con->prepare("UPDATE llaves_qr SET estado='entrada', usado_entrada=? WHERE id=?");
        $upd->bind_param('si', $now, $row['id']);
        $upd->execute();
        $alert = ['success','Entrada registrada.'];
      }
    } elseif ($tipo === 'salida') {
      if (empty($row['usado_entrada'])) {
        $alert = ['danger','Primero debes registrar la entrada con esta llave.'];
      } elseif (!empty($row['usado_salida'])) {
        $alert = ['warning','La salida ya fue registrada.'];
      } else {
        $now = date('Y-m-d H:i:s');
        $exp = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $upd = $con->prepare("UPDATE llaves_qr SET estado='salida', usado_salida=?, expira_despues_salida=? WHERE id=?");
        $upd->bind_param('ssi', $now, $exp, $row['id']);
        $upd->execute();
        $alert = ['success','Salida registrada. La llave expirará en 10 minutos.'];
      }
    }
  }
}

// Generar una llave completa (ruta antigua: ya con fotos)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['complete_id'])) {
  $cedula = preg_replace('/\D+/', '', body('cedula'));
  if ($cedula === '') { $cedula = '00000000000'; } // valor dummy mientras el campo está oculto
  $nombre = trim((string)body('nombre'));
  $apto   = trim((string)body('apto'));
  $inqId  = isset($_POST['inquilino_id']) ? (int)$_POST['inquilino_id'] : null;

  try{
    $codigo = substr(bin2hex(random_bytes(16)),0,24);
    $errTmp = []; $errTmp2 = [];
    $fotoCed = procesar_foto_llave('foto_cedula', $errTmp);
    $fotoPla = procesar_foto_llave('foto_placa', $errTmp2);
    $errorsUp = array_merge($errTmp, $errTmp2);
    if ($errorsUp) {
      $alert = ['danger', implode(' ', $errorsUp)];
    } else {
      $stmt = $con->prepare("INSERT INTO llaves_qr (inquilino_id, cedula, nombre, apartamento, visitante, total_visitantes, minutos_estadia, codigo, foto_cedula, foto_placa, estado) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
      if (!$stmt) throw new Exception($con->error);
      $inqIdTmp = $inqId ?? 0;
      $cedTmp   = $cedula;
      $nomTmp   = ($nombre === '' ? null : $nombre);
      $aptTmp   = ($apto === '' ? null : $apto);
      $visTmp   = null;
      $totTmp   = 1;
      $minTmp   = 60;
      $codTmp   = $codigo;
      $fotoCTmp = $fotoCed;
      $fotoPTmp = $fotoPla;
      $estadoTmp= 'generada';
      if ($stmt->bind_param('issssiisbbs', $inqIdTmp, $cedTmp, $nomTmp, $aptTmp, $visTmp, $totTmp, $minTmp, $codTmp, $fotoCTmp, $fotoPTmp, $estadoTmp) && $stmt->execute()) {
        $alert = ['success','Llave generada. Escanea el QR o comparte el código.'];
        $generated = ['codigo'=>$codigo,'cedula'=>$cedula,'nombre'=>$nombre,'apto'=>$apto];
      } else {
        $alert = ['danger','No se pudo crear la llave: '.$stmt->error];
      }
    }
  }catch(Throwable $e){
    $alert = ['danger','Error al crear la llave: '.$e->getMessage()];
  }
}

// Completar una visita pendiente al registrar entrada (subir fotos, generar código y marcar entrada)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_id'])) {
  $idComp = (int)$_POST['complete_id'];
  $errA = []; $errB = [];
  $fotoCed = procesar_foto_llave('foto_cedula', $errA);
  $fotoPla = procesar_foto_llave('foto_placa', $errB);
  $errorsUp = array_merge($errA,$errB);
  if ($errorsUp) {
    $alert = ['danger', implode(' ', $errorsUp)];
  } else {
    $codigo = substr(bin2hex(random_bytes(16)),0,24);
    $stmt = $con->prepare("UPDATE llaves_qr SET codigo=?, foto_cedula=?, foto_placa=?, estado='entrada', usado_entrada=NOW(), expira_despues_salida=NULL WHERE id=?");
    $codTmp = $codigo;
    $fotoCTmp = $fotoCed;
    $fotoPTmp = $fotoPla;
    if ($stmt && $stmt->bind_param('sssi', $codTmp, $fotoCTmp, $fotoPTmp, $idComp) && $stmt->execute()) {
      $alert = ['success','Entrada registrada y código generado.'];
      $generated = ['codigo'=>$codigo,'cedula'=>'','nombre'=>'','apto'=>''];
    } else {
      $alert = ['danger','No se pudo completar la llave: '.($stmt?$stmt->error:$con->error)];
    }
  }
}

// Listado
$llaves = [];
$resL = $con->query("SELECT * FROM llaves_qr ORDER BY id DESC LIMIT 50");
if ($resL) { while($r=$resL->fetch_assoc()) $llaves[]=$r; }

render_header('Llaves digitales','keys');
?>
<div class="card mb-3"><div class="card-body">
  <h1 class="fw-bold mb-3">Llaves digitales (QR)</h1>
  <p class="text-muted">Llaves de un solo uso: entrada y salida una vez. Tras registrar la salida, expiran en 10 minutos.</p>
  <?php if($alert): ?><div class="alert alert-<?= e($alert[0]) ?>"><?= e($alert[1]) ?></div><?php endif; ?>

  <?php
    $hayPendientes = false;
    foreach($llaves as $ll) { if($ll['estado']==='pendiente'){ $hayPendientes=true; break; } }
  ?>
  <?php if(!$hayPendientes): ?>
    <div class="alert alert-info mb-0">No hay llaves pendientes seleccionadas. Selecciona un registro pendiente en la tabla para completarlo.</div>
  <?php endif; ?>
</div></div>

<?php if($generated): ?>
<div class="card mb-3"><div class="card-body">
  <h5 class="mb-3">Llave creada</h5>
  <div class="row g-3 align-items-center">
    <div class="col-md-4 text-center">
      <div id="qr-container"></div>
      <div class="small text-muted mt-2">Escanea este QR para registrar entrada/salida.</div>
    </div>
    <div class="col-md-8">
      <p class="mb-1"><strong>Código:</strong> <code><?= e($generated['codigo']) ?></code></p>
      <p class="mb-1"><strong>Cédula:</strong> <?= e(format_cedula_local($generated['cedula'])) ?></p>
      <?php if($generated['nombre']): ?><p class="mb-1"><strong>Nombre:</strong> <?= e($generated['nombre']) ?></p><?php endif; ?>
      <?php if($generated['apto']): ?><p class="mb-1"><strong>Apartamento:</strong> <?= e($generated['apto']) ?></p><?php endif; ?>
      <div class="alert alert-info mt-2 mb-0">
        La salida debe registrarse con la misma llave. Tras la salida, caduca a los 10 minutos.
      </div>
    </div>
  </div>
</div></div>
<?php endif; ?>

<div class="card"><div class="card-body">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Últimas 50 llaves</h5>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle" id="tabla">
      <thead class="table-light">
        <tr>
          <th>Código</th><th>Visitante</th><th>Residente</th><th>Apto</th><th>Estado</th><th>Entrada</th><th>Salida</th><th>Expira</th><th>Cédula img</th><th>Placa img</th><th class="text-center">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($llaves as $l): ?>
          <tr class="<?= $l['estado']==='pendiente' ? 'table-warning row-pendiente' : '' ?>" data-pend-id="<?= (int)$l['id'] ?>">
            <td>
              <?php if($l['estado']==='pendiente'): ?>
                <span class="badge text-bg-warning me-2">Pendiente</span>
                <code class="text-muted"><?= e($l['codigo'] ?: '—') ?></code>
              <?php else: ?>
                <?= $l['codigo'] ? '<code>'.e($l['codigo']).'</code>' : '<span class="text-muted">—</span>' ?>
              <?php endif; ?>
            </td>
            <td><?= e($l['visitante'] ?? 'No indicado') ?><br><span class="text-muted small">Grupo: <?= (int)($l['total_visitantes'] ?? 1) ?> | Estadia: <?= (int)($l['minutos_estadia'] ?? 60) ?> min</span></td>
            <td><?= e($l['nombre']) ?></td>
            <td><?= e($l['apartamento']) ?></td>
            <td>
              <?php
                $badge='secondary';
                if ($l['estado']==='pendiente') $badge='warning';
                elseif ($l['estado']==='generada') $badge='primary';
                elseif($l['estado']==='entrada') $badge='info';
                elseif($l['estado']==='salida') $badge='warning';
                elseif($l['estado']==='expirada') $badge='dark';
              ?>
              <span class="badge text-bg-<?= $badge ?> text-capitalize"><?= e($l['estado']) ?></span>
            </td>
            <td><?= e($l['usado_entrada'] ?? '—') ?></td>
            <td><?= e($l['usado_salida'] ?? '—') ?></td>
            <td><?= e($l['expira_despues_salida'] ?? '—') ?></td>
            <td>
              <?php if(!empty($l['foto_cedula'])):
                $mime='image/jpeg';
                if (function_exists('finfo_open')) { $fi=finfo_open(FILEINFO_MIME_TYPE); $det=finfo_buffer($fi,$l['foto_cedula']); if($det) $mime=$det; finfo_close($fi); }
                $href = "data:$mime;base64,".base64_encode($l['foto_cedula']);
              ?>
                <button type="button" class="btn btn-outline-secondary btn-sm view-img" data-img="<?= e($href) ?>" data-title="Cédula llave #<?= (int)$l['id'] ?>">Ver</button>
              <?php elseif($l['estado']==='pendiente'): ?>
                <span class="text-muted">Subir al completar</span>
              <?php else: ?><span class="text-muted">—</span><?php endif; ?>
            </td>
            <td>
              <?php if(!empty($l['foto_placa'])):
                $mime='image/jpeg';
                if (function_exists('finfo_open')) { $fi=finfo_open(FILEINFO_MIME_TYPE); $det=finfo_buffer($fi,$l['foto_placa']); if($det) $mime=$det; finfo_close($fi); }
                $href = "data:$mime;base64,".base64_encode($l['foto_placa']);
              ?>
                <button type="button" class="btn btn-outline-secondary btn-sm view-img" data-img="<?= e($href) ?>" data-title="Placa llave #<?= (int)$l['id'] ?>">Ver</button>
              <?php elseif($l['estado']==='pendiente'): ?>
                <span class="text-muted">Subir al completar</span>
              <?php else: ?><span class="text-muted">—</span><?php endif; ?>
            </td>
            <td class="text-center">
              <?php if($l['estado']==='pendiente'): ?>
                <button type="button" class="btn btn-sm btn-primary entrada-pendiente-btn" data-id="<?= (int)$l['id'] ?>">Activación</button>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-outline-secondary ver-detalle-btn" data-id="<?= (int)$l['id'] ?>">Ver detalles</button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div></div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<?php if($generated): ?>
<script>
(function(){
  const code = <?= json_encode($generated['codigo']) ?>;
  new QRCode(document.getElementById('qr-container'), {
    text: code,
    width: 220,
    height: 220,
    correctLevel: QRCode.CorrectLevel.H
  });
})();
</script>
<?php endif; ?>
<script>
// Modal para completar pendiente y registrar entrada (genera código)
function abrirEntradaPendiente(id){
  let modal = document.getElementById('completeModal');
  if(!modal){
    const tpl = `
    <div class="modal fade" id="completeModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form method="post" enctype="multipart/form-data">
            <div class="modal-header">
              <h5 class="modal-title">Registrar entrada y generar código</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="entrada_pendiente" value="1">
              <input type="hidden" name="complete_id" id="complete_id">
              <div class="mb-3">
                <label class="form-label">Foto de cédula</label>
                <input type="file" name="foto_cedula" accept="image/*" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Foto de placa</label>
                <input type="file" name="foto_placa" accept="image/*" class="form-control" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button class="btn btn-primary">Registrar entrada</button>
            </div>
          </form>
        </div>
      </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', tpl);
    modal = document.getElementById('completeModal');
  }
  document.getElementById('complete_id').value = id;
  const m = new bootstrap.Modal(modal);
  m.show();
}

// Modal para ver detalles (simple)
function abrirDetalle(id){
  const url = '?detalle=' + encodeURIComponent(id);
  window.location = url; // reutiliza la página; se podría hacer modal dinámico si se prefiere
}

document.addEventListener('click', function(e){
  const btn = e.target.closest('.entrada-pendiente-btn');
  if(btn){ abrirEntradaPendiente(btn.getAttribute('data-id')); }
  const ver = e.target.closest('.ver-detalle-btn');
  if(ver){ abrirDetalle(ver.getAttribute('data-id')); }
});

// Modal de imágenes existentes
document.addEventListener('click', function(e){
  const btn = e.target.closest('.view-img');
  if(!btn) return;
  const src = btn.getAttribute('data-img');
  const title = btn.getAttribute('data-title') || 'Imagen';
  let modal = document.getElementById('imgModal');
  if(!modal){
    const tpl = `<div class="modal fade" id="imgModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="imgModalLabel">Imagen</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            <img src="" alt="Imagen" id="imgModalSrc" class="img-fluid rounded">
          </div>
        </div>
      </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', tpl);
    modal = document.getElementById('imgModal');
  }
  document.getElementById('imgModalSrc').src = src;
  document.getElementById('imgModalLabel').textContent = title;
  const m = new bootstrap.Modal(modal);
  m.show();
});
</script>
<?php render_footer(); ?>
