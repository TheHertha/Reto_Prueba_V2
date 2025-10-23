<?php
require_once __DIR__ . '/db.php';

/* ====== Parámetros ====== */
$fecha    = trim($_GET['fecha'] ?? '');
$busqueda = trim($_GET['q'] ?? '');

/* ====== Alta rápida ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_producto') {
  $sku    = trim($_POST['sku'] ?? '');
  $nombre = trim($_POST['nombre'] ?? '');
  $precio = floatval($_POST['precio'] ?? 0);
  $stock  = intval($_POST['stock'] ?? 0);

  if ($nombre !== '') {
    $stmt = $mysqli->prepare(
      "INSERT INTO fis_productos (sku, nombre, precio_unitario, stock)
       VALUES (?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), precio_unitario=VALUES(precio_unitario), stock=VALUES(stock)"
    );
    $stmt->bind_param("ssdi", $sku, $nombre, $precio, $stock);
    $stmt->execute();
    $stmt->close();
  }
  header("Location: inventario.php");
  exit;
}

/* ====== Listado de productos ====== */
if ($busqueda !== '') {
  $like = "%$busqueda%";
  $stmt = $mysqli->prepare(
    "SELECT id, sku, nombre, precio_unitario, stock
     FROM fis_productos
     WHERE nombre LIKE ? OR sku LIKE ?
     ORDER BY nombre ASC"
  );
  $stmt->bind_param("ss", $like, $like);
} else {
  $stmt = $mysqli->prepare(
    "SELECT id, sku, nombre, precio_unitario, stock
     FROM fis_productos
     ORDER BY nombre ASC"
  );
}
$stmt->execute();
$productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ====== Totales del día ====== */
if ($fecha !== '') {
  $stmt = $mysqli->prepare(
    "SELECT COALESCE(SUM(total),0) ingresos, COALESCE(SUM(cantidad),0) piezas
     FROM fis_ventas WHERE DATE(created_at)=?"
  );
  $stmt->bind_param("s", $fecha);
} else {
  $stmt = $mysqli->prepare(
    "SELECT COALESCE(SUM(total),0) ingresos, COALESCE(SUM(cantidad),0) piezas
     FROM fis_ventas WHERE DATE(created_at)=CURDATE()"
  );
}
$stmt->execute();
$totales = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ====== Resumen últimos 7 días ====== */
$resumenDias = [];
$q = $mysqli->query(
  "SELECT DATE(created_at) dia,
          COUNT(*) tickets,
          COALESCE(SUM(cantidad),0) piezas,
          COALESCE(SUM(total),0) ingresos
   FROM fis_ventas
   GROUP BY DATE(created_at)
   ORDER BY dia DESC
   LIMIT 7"
);
while ($r = $q->fetch_assoc()) { $resumenDias[] = $r; }
$q->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Inventario | CAT21</title>

<!-- ====== ESTILOS TEMA (inspirados en admin_reto) ====== -->
<style>
  :root{
    --bg:#f5f5f5;
    --card:#ffffff;
    --ink:#333;
    --brand:#000;            /* negro */
    --accent:#FFD700;        /* dorado */
    --muted:#666;
    --line:#ddd;
    --ok-bg:#e6f4ea;
    --ok-ink:#2e7d32;
  }
  *{box-sizing:border-box}
  body{margin:0; background:var(--bg); color:var(--ink); font-family:'Segoe UI',Tahoma,Arial,sans-serif; line-height:1.5}

  .layout{display:flex; min-height:100vh}
  .sidebar{width:250px; background:var(--brand); color:#fff; padding:20px; display:flex; flex-direction:column; gap:20px}
  .sidebar-header{display:flex; align-items:center; gap:12px; padding-bottom:16px; border-bottom:1px solid #333}
  .logo{width:46px; height:46px; border-radius:8px; object-fit:cover; transition:.3s; background:#111}
  .logo:hover{transform:rotate(5deg)}
  .sidebar h2{font-size:1.2rem; font-weight:300; letter-spacing:2px; text-transform:uppercase}
  .sidebar-nav{display:flex; flex-direction:column; gap:10px}
  .nav-item{display:block; padding:12px 16px; color:#fff; text-decoration:none; border-radius:6px; transition:.2s}
  .nav-item:hover{background:var(--accent); color:#000}
  .nav-item.active{background:var(--accent); color:#000}

  .main{flex:1; padding:40px; background:var(--card)}
  .header{display:flex; gap:12px; align-items:center; justify-content:space-between; flex-wrap:wrap; margin-bottom:22px}
  .header h1{font-size:1.8rem; font-weight:300; letter-spacing:2px; text-transform:uppercase; color:#000}

  .row{display:flex; gap:10px; align-items:center; flex-wrap:wrap}
  input,select,button{padding:10px 12px; border:1px solid var(--line); border-radius:6px; font:inherit}
  input:focus,select:focus{outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(255,215,0,.25)}

  .btn{cursor:pointer; position:relative; overflow:hidden}
  .btn.primary{background:var(--brand); color:var(--accent); border:1px solid var(--accent)}
  .btn.primary:hover{color:#000; background:var(--accent)}
  .btn.ghost{background:transparent; color:var(--brand); border:1px solid var(--line)}
  .btn.link{border:none; background:none; color:var(--brand); text-decoration:underline; padding:0}

  .cards{display:grid; grid-template-columns:repeat(12,1fr); gap:16px}
  .card{background:var(--card); border:1px solid var(--line); border-radius:10px; padding:16px; box-shadow:0 4px 10px rgba(0,0,0,.06)}
  .span-7{grid-column:span 7}
  .span-5{grid-column:span 5}
  .kpi{background:var(--ok-bg); border-color:#cde9d6}
  .kpi h3{margin:0 0 6px 0}
  .muted{color:var(--muted); font-size:.92rem}
  .pill{display:inline-block; background:#f2f2f2; padding:2px 8px; border-radius:999px; font-size:.85rem}

  .album{display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px}
  .day{background:var(--card); border:1px solid var(--line); border-radius:10px; padding:12px}
  .day h4{margin:0 0 6px 0; font-size:1rem}
  .day .muted{font-size:.85rem}

  table{width:100%; border-collapse:collapse; margin-top:14px; background:var(--card); border-radius:10px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,.06)}
  th,td{padding:12px; border-bottom:1px solid var(--line); text-align:left}
  th{background:var(--brand); color:var(--accent); text-transform:uppercase; letter-spacing:1px; font-weight:500}
  tr:hover{background:#fafafa}
  .danger{background:#ffe9e9}

  @media (max-width:1024px){
    .layout{flex-direction:column}
    .sidebar{width:100%}
    .main{padding:20px}
    .span-7,.span-5{grid-column:span 12}
  }
</style>
</head>
<body>
  <div class="layout">
    <!-- ====== Sidebar ====== -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <img src="assets/img/1-4.png" class="logo" alt="CAT21">
        <h2>CAT21 Admin</h2>
      </div>
      <nav class="sidebar-nav">
        <a class="nav-item" href="inicio.php">Inicio</a>
        <a class="nav-item" href="reto.php">Reto</a>
        <a class="nav-item" href="ranking.php">Ranking</a>
        <a class="nav-item" href="admin_reto.php">Administrar Reto</a>
        <a class="nav-item active" href="inventario.php">Inventario</a>
      </nav>
    </aside>

    <!-- ====== Main ====== -->
    <main class="main">
      <div class="header">
        <h1>Inventario físico / Caja</h1>
        <form method="get" class="row">
          <input type="search" name="q" placeholder="Buscar producto" value="<?php echo htmlspecialchars($busqueda); ?>">
          <input type="date"   name="fecha" value="<?php echo htmlspecialchars($fecha); ?>">
          <button class="btn ghost">Aplicar</button>
          <a class="btn link" href="inventario.php">Hoy</a>
        </form>
      </div>

      <!-- ====== Cards superiores ====== -->
      <section class="cards">
        <!-- Alta rápida -->
        <div class="card span-7">
          <h3 style="margin:0 0 10px 0">Alta rápida</h3>
          <form method="post" class="row" action="inventario.php">
            <input type="hidden" name="accion" value="crear_producto">
            <input type="text"   name="sku"    placeholder="SKU">
            <input type="text"   name="nombre" placeholder="Nombre" required>
            <input type="number" step="0.01" name="precio" placeholder="Precio" required>
            <input type="number" name="stock"  placeholder="Stock"  required>
            <button class="btn primary">Guardar</button>
          </form>
          <p class="muted" style="margin-top:6px">Puedes actualizar un SKU existente: se sobrescriben nombre, precio y stock.</p>
        </div>

        <!-- KPI ventas del día -->
        <div class="card kpi span-5" id="panel-ventas">
          <h3>Ventas del día</h3>
          <p style="font-size:1.05rem">
            <strong>Ingresos:</strong>
            $<span id="tot-ing"><?php echo number_format($totales['ingresos'] ?? 0, 2); ?></span>
            &nbsp;|&nbsp;
            <strong>Piezas:</strong>
            <span id="tot-pzs"><?php echo intval($totales['piezas'] ?? 0); ?></span>
          </p>
          <p class="muted">Fecha: <span id="tot-fecha"><?php echo $fecha ?: 'Hoy'; ?></span></p>
        </div>
      </section>

      <!-- ====== Álbum de días ====== -->
      <h2 style="margin:24px 0 8px">Resumen por día</h2>
      <div class="album">
        <?php foreach($resumenDias as $d): ?>
          <div class="day">
            <h4><?php echo htmlspecialchars($d['dia']); ?></h4>
            <div class="muted"><?php echo intval($d['tickets']); ?> tickets</div>
            <div><?php echo intval($d['piezas']); ?> piezas</div>
            <div>$<?php echo number_format($d['ingresos'],2); ?></div>
            <div style="margin-top:8px">
              <a class="pill" href="?fecha=<?php echo htmlspecialchars($d['dia']); ?>">ver</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- ====== Productos ====== -->
      <h2 style="margin:24px 0 8px">Productos</h2>
      <table>
        <thead>
          <tr><th>SKU</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>Vender</th></tr>
        </thead>
        <tbody>
          <?php foreach($productos as $p): ?>
            <tr class="<?php echo ($p['stock']<=0?'danger':''); ?>">
              <td><?php echo htmlspecialchars($p['sku'] ?: '-'); ?></td>
              <td><?php echo htmlspecialchars($p['nombre']); ?></td>
              <td>$<?php echo number_format($p['precio_unitario'],2); ?></td>
              <td><?php echo intval($p['stock']); ?></td>
              <td>
                <form method="post" action="registrar_venta.php" class="row">
                  <input type="hidden" name="producto_id" value="<?php echo $p['id']; ?>">
                  <input type="number" name="cantidad" value="1" min="1" max="<?php echo max(1,(int)$p['stock']); ?>" style="width:90px">
                  <button class="btn primary">Vender</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    </main>
  </div>

<!-- ====== JS: auto-refresh KPI ====== -->
<script>
(function(){
  const fechaSel = "<?php echo $fecha; ?>";
  function refreshTotals(){
    const url = 'totales_dia.php' + (fechaSel ? ('?fecha=' + encodeURIComponent(fechaSel)) : '');
    fetch(url).then(r=>r.json()).then(j=>{
      const fmt = (n)=>Number(n).toFixed(2);
      document.getElementById('tot-ing').textContent = fmt(j.ingresos);
      document.getElementById('tot-pzs').textContent = j.piezas;
      document.getElementById('tot-fecha').textContent = j.fecha || (fechaSel || 'Hoy');
    }).catch(()=>{});
  }
  refreshTotals();
  if(!fechaSel){ setInterval(refreshTotals, 5000); } // solo auto-refresh en “Hoy”
})();
</script>
</body>
</html>
