<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'ADMIN') {
    header('Location: ../../index.php'); exit;
}
require_once '../../config/db.php';
$usuario    = $_SESSION['usuario'];
$activePage = 'reportes';
$db         = getDB();

$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fin    = $_GET['fin']    ?? date('Y-m-d');

$ventas = $db->query(
    "SELECT v.id_venta,v.fecha,v.total,u.nombre AS cajero,
            COUNT(dv.id_detalle) AS items
     FROM VENTA v
     JOIN USUARIO u ON v.id_usuario=u.id_usuario
     JOIN DETALLE_VENTA dv ON v.id_venta=dv.id_venta
     WHERE DATE(v.fecha) BETWEEN '$inicio' AND '$fin'
     GROUP BY v.id_venta ORDER BY v.fecha DESC")->fetch_all(MYSQLI_ASSOC);

$total  = array_sum(array_column($ventas,'total'));
$prom   = count($ventas) ? $total/count($ventas) : 0;

// Top productos del período
$topProd = $db->query(
    "SELECT p.nombre, SUM(dv.cantidad) AS unidades, SUM(dv.subtotal) AS total_vendido
     FROM DETALLE_VENTA dv
     JOIN VENTA v ON dv.id_venta=v.id_venta
     JOIN PRODUCTO p ON dv.id_producto=p.id_producto
     WHERE DATE(v.fecha) BETWEEN '$inicio' AND '$fin'
     GROUP BY p.id_producto ORDER BY total_vendido DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>SIGI — Reportes</title>
<link rel="stylesheet" href="../../assets/css/style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
<div class="app-layout">
<?php include '../sidebar.php'; ?>
<div class="main-wrap">
    <div class="topbar">
        <div class="topbar-left"><h2>Reportes de Ventas (PEPS)</h2><p>Análisis del período</p></div>
        <div class="topbar-right">
            <button class="btn btn-outline no-print" onclick="window.print()">🖨️ Imprimir</button>
            <div class="topbar-avatar"><?= strtoupper(substr($usuario['nombre'],0,1)) ?></div>
        </div>
    </div>
    <div class="page-content">

        <!-- Filtro -->
        <div class="card no-print">
            <div class="card-body">
                <form method="GET" style="display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap;">
                    <div class="form-group" style="margin:0;">
                        <label>Desde</label>
                        <input type="date" name="inicio" value="<?= $inicio ?>" class="form-control">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label>Hasta</label>
                        <input type="date" name="fin" value="<?= $fin ?>" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="?inicio=<?= date('Y-m-d') ?>&fin=<?= date('Y-m-d') ?>" class="btn btn-ghost">Hoy</a>
                    <a href="?inicio=<?= date('Y-m-01') ?>&fin=<?= date('Y-m-d') ?>" class="btn btn-ghost">Este mes</a>
                </form>
            </div>
        </div>

        <!-- KPIs -->
        <div class="stats-row">
            <div class="stat-card success">
                <div class="stat-icon">💰</div>
                <div class="stat-label">Total del Período</div>
                <div class="stat-value">$<?= number_format($total,0,',','.') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🛒</div>
                <div class="stat-label">Número de Ventas</div>
                <div class="stat-value"><?= count($ventas) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-label">Promedio por Venta</div>
                <div class="stat-value">$<?= number_format($prom,0,',','.') ?></div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon">🏆</div>
                <div class="stat-label">Período</div>
                <div class="stat-value" style="font-size:14px;padding-top:8px;">
                    <?= date('d/m',strtotime($inicio)) ?> — <?= date('d/m/Y',strtotime($fin)) ?>
                </div>
            </div>
        </div>

        <!-- Gráfico + Top productos -->
        <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;margin-bottom:20px;">
            <div class="card">
                <div class="card-header"><h3>📈 Top 5 Productos más Vendidos</h3></div>
                <div class="card-body"><div class="chart-container"><canvas id="topChart"></canvas></div></div>
            </div>
            <div class="card">
                <div class="card-header"><h3>🏆 Ranking Productos</h3></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>#</th><th>Producto</th><th>Unid.</th><th>Total</th></tr></thead>
                        <tbody>
                        <?php foreach ($topProd as $i => $tp): ?>
                        <tr>
                            <td><strong><?= $i+1 ?></strong></td>
                            <td><?= htmlspecialchars($tp['nombre']) ?></td>
                            <td><?= $tp['unidades'] ?></td>
                            <td>$<?= number_format($tp['total_vendido'],0,',','.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$topProd): ?>
                        <tr><td colspan="4" style="text-align:center;color:#aaa;padding:20px;">Sin datos</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tabla detalle -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Detalle de Ventas</h3>
                <span class="badge badge-info"><?= count($ventas) ?> registros</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>#Venta</th><th>Fecha y Hora</th><th>Cajero</th><th>Ítems</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php if (!$ventas): ?>
                    <tr><td colspan="5" style="text-align:center;color:#aaa;padding:30px;">Sin ventas en el período seleccionado</td></tr>
                    <?php endif; ?>
                    <?php foreach ($ventas as $v): ?>
                    <tr>
                        <td><strong>#<?= $v['id_venta'] ?></strong></td>
                        <td><?= date('d/m/Y H:i',strtotime($v['fecha'])) ?></td>
                        <td><?= htmlspecialchars($v['cajero']) ?></td>
                        <td><?= $v['items'] ?></td>
                        <td><strong>$<?= number_format($v['total'],0,',','.') ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <?php if ($ventas): ?>
                    <tfoot>
                        <tr style="background:#f8f9ff;font-weight:700;">
                            <td colspan="4">TOTAL DEL PERÍODO</td>
                            <td>$<?= number_format($total,0,',','.') ?></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<script>
const topLabels = <?= json_encode(array_column($topProd,'nombre') ?: ['Sin datos']) ?>;
const topData   = <?= json_encode(array_map(fn($r)=>(float)$r['total_vendido'], $topProd) ?: [0]) ?>;
new Chart(document.getElementById('topChart'),{
    type:'bar',
    data:{
        labels:topLabels,
        datasets:[{
            label:'Ventas ($)',
            data:topData,
            backgroundColor:['#4361ee','#2ec4b6','#ff9f1c','#e63946','#6c757d'],
            borderRadius:6
        }]
    },
    options:{
        responsive:true, maintainAspectRatio:false,
        plugins:{legend:{display:false}},
        scales:{
            y:{beginAtZero:true,ticks:{callback:v=>'$'+v.toLocaleString('es-CO')}},
            x:{grid:{display:false}}
        }
    }
});
</script>
</body>
</html>
