<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'ADMIN') {
    header('Location: ../../index.php'); exit;
}
require_once '../../config/db.php';
$usuario    = $_SESSION['usuario'];
$activePage = 'alertas';
$db         = getDB();

$vencer   = $db->query("SELECT * FROM v_proximos_vencer")->fetch_all(MYSQLI_ASSOC);
$stockMin = $db->query("SELECT * FROM v_stock_minimo")->fetch_all(MYSQLI_ASSOC);
$notifs   = $db->query("SELECT n.*,p.nombre AS producto FROM NOTIFICACION n LEFT JOIN PRODUCTO p ON n.id_producto=p.id_producto ORDER BY n.fecha_generada DESC LIMIT 30")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>SIGI — Alertas</title>
<link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="app-layout">
<?php include '../sidebar.php'; ?>
<div class="main-wrap">
    <div class="topbar">
        <div class="topbar-left"><h2>Centro de Alertas</h2><p><?= count($vencer)+count($stockMin) ?> alertas activas</p></div>
        <div class="topbar-right">
            <div class="topbar-avatar"><?= strtoupper(substr($usuario['nombre'],0,1)) ?></div>
        </div>
    </div>
    <div class="page-content">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

            <!-- Vencimiento -->
            <div class="card">
                <div class="card-header">
                    <h3>📅 Productos Próximos a Vencer</h3>
                    <span class="badge badge-danger"><?= count($vencer) ?></span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Producto</th><th>Vence</th><th>Días</th><th>Stock</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php if (!$vencer): ?>
                        <tr><td colspan="5" style="text-align:center;color:#aaa;padding:20px;">✅ Sin alertas de vencimiento</td></tr>
                        <?php endif; ?>
                        <?php foreach ($vencer as $v): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($v['nombre']) ?></strong></td>
                            <td><?= date('d/m/Y',strtotime($v['fecha_vencimiento'])) ?></td>
                            <td><strong><?= $v['dias_restantes'] ?> día(s)</strong></td>
                            <td><?= $v['cantidad'] ?></td>
                            <td>
                                <?php if ($v['dias_restantes'] <= 3): ?>
                                    <span class="badge badge-danger">🔴 Crítico</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">🟡 Advertencia</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stock mínimo -->
            <div class="card">
                <div class="card-header">
                    <h3>⚠️ Productos con Stock Mínimo</h3>
                    <span class="badge badge-warning"><?= count($stockMin) ?></span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Producto</th><th>Categoría</th><th>Stock</th><th>Mínimo</th></tr></thead>
                        <tbody>
                        <?php if (!$stockMin): ?>
                        <tr><td colspan="4" style="text-align:center;color:#aaa;padding:20px;">✅ Sin alertas de stock</td></tr>
                        <?php endif; ?>
                        <?php foreach ($stockMin as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($s['nombre_categoria']) ?></td>
                            <td><span class="badge badge-danger"><?= $s['stock_actual'] ?></span></td>
                            <td><?= $s['stock_minimo'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Historial de notificaciones -->
        <div class="card" style="margin-top:20px;">
            <div class="card-header"><h3>🔔 Historial de Notificaciones del Sistema</h3></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Producto</th><th>Mensaje</th></tr></thead>
                    <tbody>
                    <?php if (!$notifs): ?>
                    <tr><td colspan="4" style="text-align:center;color:#aaa;padding:20px;">Sin notificaciones registradas</td></tr>
                    <?php endif; ?>
                    <?php foreach ($notifs as $n): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i',strtotime($n['fecha_generada'])) ?></td>
                        <td><span class="badge <?= $n['tipo']==='STOCK'?'badge-warning':'badge-danger' ?>"><?= $n['tipo'] ?></span></td>
                        <td><?= htmlspecialchars($n['producto'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($n['mensaje']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>
