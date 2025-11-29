<?php
// layout.php — cabecera y pie comunes

function render_header(string $title='Vehículos', string $active='list'){ ?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
 body{background:#f6f7fb}
 .card{border:0;box-shadow:0 8px 24px rgba(0,0,0,.06);border-radius:1rem}
 .card h1{font-size:22px;margin:0}
 .table thead th{font-weight:600}
 .table-nowrap td,.table-nowrap th{white-space:nowrap}
 .badge-label{font-size:.9rem;background:#eef2ff;color:#3730a3}
 .actions-col{width:140px}
 .thumb{width:72px;height:48px;object-fit:cover;border-radius:8px;border:1px solid rgba(0,0,0,.08)}
 .muted{color:#6b7280}
 .nav-link.active{background:#0d6efd;color:#fff!important}
</style>
</head><body>
<nav class="navbar navbar-expand-lg bg-white shadow-sm"><div class="container">
  <a class="navbar-brand fw-bold" href="index.php">COOPNAMA II — Vehículos</a>
  <div class="ms-auto d-flex align-items-center gap-3 flex-wrap">
    <div class="nav nav-pills small">
      <a class="nav-link <?php if ($active==='list'||$active==='new') echo 'active'; ?>" href="index.php">Vehículos</a>
      <a class="nav-link" href="../contactos/index.php">Residentes</a>
      <a class="nav-link" href="../contactos/visor.php">Visor</a>
    </div>
    <?php if ($active !== 'new'): ?>
      <a href="insert.php" class="btn btn-primary btn-sm">Agregar</a>
    <?php endif; ?>
  </div>
</div></nav>
<main class="container my-4">
<?php }

function render_footer(){ ?>
</main>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function(){
  var $tbl=$('#tabla');
  if($tbl.length){
    var dt=$tbl.DataTable({
      pageLength:10,
      lengthMenu:[5,10,25,50,100],
      language:{url:'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'},
      dom:'tip',
      columnDefs:[{targets:-1,className:'text-center actions-col'}]
    });
    $('#globalSearch').on('input',function(){ dt.search(this.value).draw(); });
    $('#lenSelect').on('change',function(){ dt.page.len(parseInt(this.value,10)).draw(); });
    $('#lenSelect').val(dt.page.len());
  }

  $(document).on('click','.btn-delete',function(e){
    if(!confirm('¿Eliminar este registro?')){
      e.preventDefault();
    }
  });
});
</script>
</body></html>
<?php }
