<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'ADMIN') {
    header('Location: ../../index.php'); exit;
}
require_once '../../config/db.php';
$usuario    = $_SESSION['usuario'];
$activePage = 'dashboard';
$db         = getDB();

// --- KPIs ---
$hoy         = date('Y-m-d');
$iniMes      = date('Y-m-01');

$rVentasHoy  = $db->query("SELECT COALESCE(SUM(total),0) AS t, COUNT(*) AS n FROM VENTA WHERE DATE(fecha)='$hoy'")->fetch_assoc();
$rVentasMes  = $db->query("SELECT COALESCE(SUM(total),0) AS t FROM VENTA WHERE DATE(fecha) BETWEEN '$iniMes' AND '$hoy'")->fetch_assoc();
$rStockMin   = $db->query("SELECT COUNT(*) AS n FROM v_stock_minimo")->fetch_assoc();
$rVencer     = $db->query("SELECT COUNT(*) AS n FROM v_proximos_vencer")->fetch_assoc();

// --- Ventas últimos 7 días para gráfico ---
$rChart = $db->query(
    "SELECT DATE(fecha) AS dia, COALESCE(SUM(total),0) AS total
     FROM VENTA
     WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(fecha) ORDER BY dia ASC");
$chartDias = []; $chartTotales = [];
while ($r = $rChart->fetch_assoc()) {
    $chartDias[]    = date('d/m', strtotime($r['dia']));
    $chartTotales[] = (float)$r['total'];
}

// --- Últimas ventas ---
$rUltVentas = $db->query(
    "SELECT v.id_venta, v.fecha, v.total, u.nombre AS cajero,
            GROUP_CONCAT(p.nombre SEPARATOR ', ') AS productos
     FROM VENTA v
     JOIN USUARIO u ON v.id_usuario=u.id_usuario
     JOIN DETALLE_VENTA dv ON v.id_venta=dv.id_venta
     JOIN PRODUCTO p ON dv.id_producto=p.id_producto
     GROUP BY v.id_venta ORDER BY v.fecha DESC LIMIT 8");

// --- Alertas ---
$rVencerLista  = $db->query("SELECT * FROM v_proximos_vencer");
$rStockMinLista= $db->query("SELECT * FROM v_stock_minimo LIMIT 5");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIGI — Dashboard</title>
<link rel="stylesheet" href="../../assets/css/style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
<div class="app-layout">

<?php include '../sidebar.php'; ?>

<div class="main-wrap">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <h2>Dashboard</h2>
            <p><?= date('d \d\e F \d\e Y') ?></p>
        </div>
        <div class="topbar-right">
            <div class="topbar-search">
                <span>🔍</span>
                <input type="text" placeholder="Buscar producto o lote...">
            </div>
            <div class="topbar-avatar"><?= strtoupper(substr($usuario['nombre'],0,1)) ?></div>
        </div>
    </div>

    <div class="page-content">

        <!-- KPI Cards — igual al mockup -->
        <div class="stats-row">
            <div class="stat-card success">
                <div class="stat-icon">💰</div>
                <div class="stat-label">Ventas Hoy</div>
                <div class="stat-value">$<?= number_format($rVentasHoy['t'],0,',','.') ?></div>
                <div class="stat-sub"><?= $rVentasHoy['n'] ?> transacciones</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-label">Ventas del Mes</div>
                <div class="stat-value">$<?= number_format($rVentasMes['t'],0,',','.') ?></div>
                <div class="stat-sub"><?= date('F Y') ?></div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon">⚠️</div>
                <div class="stat-label">Lotes por Vencer</div>
                <div class="stat-value"><?= $rVencer['n'] ?></div>
                <div class="stat-sub">Próximos 7 días</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon">📦</div>
                <div class="stat-label">Stock Mínimo</div>
                <div class="stat-value"><?= $rStockMin['n'] ?></div>
                <div class="stat-sub">Productos críticos</div>
            </div>
        </div>

        <!-- Fila: gráfico + alertas rápidas -->
        <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;margin-bottom:20px;">

            <!-- Gráfico de ventas -->
            <div class="card">
                <div class="card-header">
                    <h3>📊 Ventas — Últimos 7 días</h3>
                    <a href="reportes.php" class="btn btn-ghost btn-sm">Ver reportes →</a>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="ventasChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Panel alertas -->
            <div class="card">
                <div class="card-header">
                    <h3>🔔 Alertas Activas</h3>
                    <a href="alertas.php" class="btn btn-ghost btn-sm">Ver todas</a>
                </div>
                <div class="card-body" style="padding:12px;">
                    <?php if ($rVencer['n'] == 0 && $rStockMin['n'] == 0): ?>
                        <p style="color:#aaa;text-align:center;padding:20px 0;">Sin alertas activas ✅</p>
                    <?php endif; ?>

                    <?php while ($v = $rVencerLista->fetch_assoc()): ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f0f0f0;">
                        <span style="font-size:20px;"><?= $v['dias_restantes']<=3 ? '🔴' : '🟡' ?></span>
                        <div>
                            <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($v['nombre']) ?></div>
                            <div style="font-size:11px;color:#888;">Vence en <?= $v['dias_restantes'] ?> día(s)</div>
                        </div>
                    </div>
                    <?php endwhile; ?>

                    <?php while ($s = $rStockMinLista->fetch_assoc()): ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f0f0f0;">
                        <span style="font-size:20px;">⚠️</span>
                        <div>
                            <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($s['nombre']) ?></div>
                            <div style="font-size:11px;color:#888;">Stock: <?= $s['stock_actual'] ?> / Mín: <?= $s['stock_minimo'] ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Tabla últimas ventas -->
        <div class="card">
            <div class="card-header">
                <h3>🛒 Últimas Ventas</h3>
                <a href="../cajero/pos.php" class="btn btn-primary btn-sm">+ Nueva Venta</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#Venta</th><th>Fecha y Hora</th><th>Cajero</th>
                            <th>Productos</th><th>Total</th><th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($rUltVentas->num_rows === 0): ?>
                        <tr><td colspan="6" style="text-align:center;color:#aaa;padding:28px;">Sin ventas registradas hoy</td></tr>
                    <?php else: ?>
                    <?php while ($v = $rUltVentas->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?= $v['id_venta'] ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                            <td><?= htmlspecialchars($v['cajero']) ?></td>
                            <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= htmlspecialchars($v['productos']) ?></td>
                            <td><strong>$<?= number_format($v['total'],0,',','.') ?></strong></td>
                            <td><span class="badge badge-success">Completada</span></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /page-content -->
</div><!-- /main-wrap -->
</div><!-- /app-layout -->

<script>
const ctx = document.getElementById('ventasChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chartDias ?: ['Sin datos']) ?>,
        datasets: [{
            label: 'Ventas ($)',
            data: <?= json_encode($chartTotales ?: [0]) ?>,
            borderColor: '#4361ee',
            backgroundColor: 'rgba(67,97,238,0.08)',
            borderWidth: 2.5,
            pointBackgroundColor: '#4361ee',
            pointRadius: 4,
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f0f0f0' },
                 ticks: { callback: v => '$' + v.toLocaleString('es-CO') }},
            x: { grid: { display: false } }
        }
    }
});
</script>
</body>
</html>
