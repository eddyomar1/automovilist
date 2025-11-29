<?php
// layout.php — cabecera y pie comunes (vehículos + menú unificado)

function render_header(string $title='Vehículos', string $active='list'){
  $action = $active;
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
 :root{
   --slate-100:#f4f6fb;
   --slate-200:#eef1f7;
   --slate-600:#2c3648;
   --slate-700:#1f2c3d;
 }
 body{background:var(--slate-100);color:var(--slate-600);}
 .app-shell{min-height:100vh;background:var(--slate-100);}
 .sidebar{width:270px;background:#fff;border-right:1px solid #e7ebf3;box-shadow:6px 0 24px rgba(0,0,0,.04);}
 .brand{font-weight:700;color:var(--slate-700);}
 .brand small{color:#7a8596;}
 .sidebar .section-label{letter-spacing:.05em;text-transform:uppercase;font-size:.78rem;font-weight:700;color:#808aa0;margin:1.1rem .35rem .4rem;}
 .sidebar .nav-link{display:flex;align-items:center;gap:.65rem;color:#2d394c;border-radius:.75rem;padding:.65rem .8rem;font-weight:600;}
 .sidebar .nav-link:hover{background:#f1f4ff;color:#0d6efd;}
 .sidebar .nav-link.active{background:#0d6efd;color:#fff;box-shadow:0 10px 22px rgba(13,110,253,.2);}
 .sidebar hr{margin:1.2rem 0;color:#eef1f6;}
 .topbar{background:#fff;border-bottom:1px solid #e7ebf3;box-shadow:0 3px 18px rgba(0,0,0,.05);}
 .page-heading h1{font-weight:700;color:var(--slate-700);margin-bottom:0;}
 .page-heading .eyebrow{text-transform:uppercase;letter-spacing:.06em;color:#8a93a5;font-size:.75rem;font-weight:700;}
 main.content-body{padding:24px;}
 .card{border:0;box-shadow:0 8px 24px rgba(0,0,0,.06);border-radius:1rem}
 .card h1{font-size:22px;margin:0}
 .table thead th{font-weight:600}
 .table-nowrap td,.table-nowrap th{white-space:nowrap}
 .badge-label{font-size:.9rem;background:#eef2ff;color:#3730a3}
 .actions-col{width:140px}
 .thumb{width:72px;height:48px;object-fit:cover;border-radius:8px;border:1px solid rgba(0,0,0,.08)}
 .muted{color:#6b7280}
</style>
</head><body>
<div class="app-shell d-flex">
  <aside class="sidebar d-flex flex-column">
    <div class="p-4 pb-3 border-bottom">
      <div class="brand fs-5">COOPNAMA II</div>
      <div class="text-muted small">Panel administrativo</div>
    </div>
    <div class="flex-grow-1 p-3">
      <div class="section-label">Residentes</div>
      <div class="nav flex-column">
        <a class="nav-link" href="/eo/coopnama/contactos/index.php?page=residentes">
          <i class="bi bi-card-checklist"></i><span>Listado y cobros</span>
        </a>
        <a class="nav-link" href="/eo/coopnama/contactos/index.php?action=new">
          <i class="bi bi-person-plus"></i><span>Registrar residente</span>
        </a>
        <a class="nav-link" href="/eo/coopnama/contactos/visor.php?action=full">
          <i class="bi bi-window-sidebar"></i><span>Visor completo</span>
        </a>
      </div>

      <hr>

      <div class="section-label">Pagos</div>
      <div class="nav flex-column">
        <a class="nav-link" href="/eo/coopnama/contactos/index.php?page=pagos">
          <i class="bi bi-currency-dollar"></i><span>Pagos registrados</span>
        </a>
      </div>

      <hr>

      <div class="section-label">Vehículos</div>
      <div class="nav flex-column">
        <a class="nav-link <?= ($action==='list' || $action==='edit')?'active':'' ?>" href="/eo/automovilist/index.php">
          <i class="bi bi-car-front"></i><span>Listado de vehículos</span>
        </a>
        <a class="nav-link <?= ($action==='new')?'active':'' ?>" href="/eo/automovilist/insert.php">
          <i class="bi bi-plus-square"></i><span>Registrar vehículo</span>
        </a>
      </div>
    </div>
    <div class="p-3 border-top text-muted small">
      <i class="bi bi-info-circle me-1"></i>Atajos rápidos para navegar.
    </div>
  </aside>

  <div class="content-area flex-grow-1 d-flex flex-column">
    <div class="topbar px-4 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="page-heading">
        <div class="eyebrow">Panel administrativo</div>
        <h1 class="h4 mb-0"><?= e($title) ?></h1>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="/eo/coopnama/contactos/index.php?page=residentes">
          <i class="bi bi-people me-1"></i>Residentes
        </a>
        <a href="/eo/automovilist/insert.php" class="btn btn-primary btn-sm">
          <i class="bi bi-plus-lg me-1"></i>Agregar
        </a>
      </div>
    </div>
    <main class="content-body flex-grow-1">
<?php }

function render_footer(){ ?>
    </main>
  </div>
</div>
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
