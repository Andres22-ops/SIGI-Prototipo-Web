<?php
session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'],['BODEGUERO','ADMIN'])) {
    header('Location: ../../index.php'); exit;
}
require_once '../../config/db.php';
$usuario    = $_SESSION['usuario'];
$activePage = 'inventario';
$db         = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id  = (int)$_POST['id_producto'];
    $qty = (int)$_POST['cantidad'];
    $fv  = $_POST['fecha_vencimiento'] ?: null;

    $db->begin_transaction();
    try {
        $s = $db->prepare("INSERT INTO INVENTARIO (tipo_movimiento,cantidad,fecha,fecha_vencimiento,id_producto) VALUES('ENTRADA',?,CURDATE(),?,?)");
        $s->bind_param('isi',$qty,$fv,$id);
        $s->execute();
        $u = $db->prepare("UPDATE PRODUCTO SET stock_actual=stock_actual+? WHERE id_producto=?");
        $u->bind_param('ii',$qty,$id);
        $u->execute();
        $db->commit();
        $msg = 'success|Entrada registrada. Stock actualizado.';
    } catch(Exception $e) { $db->rollback(); $msg='danger|Error: '.$e->getMessage(); }
}

$productos = $db->query("SELECT p.*,c.nombre_categoria FROM PRODUCTO p JOIN CATEGORIA c ON p.id_categoria=c.id_categoria ORDER BY p.nombre")->fetch_all(MYSQLI_ASSOC);
$movs      = $db->query("SELECT i.*,p.nombre AS producto FROM INVENTARIO i JOIN PRODUCTO p ON i.id_producto=p.id_producto ORDER BY i.fecha DESC,i.id_movimiento DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);
[$tMsg,$xMsg] = $msg ? explode('|',$msg,2):['',''];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>SIGI — Inventario (Lotes)</title>
<link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="app-layout">
<?php include '../sidebar.php'; ?>
<div class="main-wrap">
    <div class="topbar">
        <div class="topbar-left"><h2>Inventario (Lotes)</h2><p>Control de entradas y stock</p></div>
        <div class="topbar-right">
            <button class="btn btn-primary" onclick="abrir('modal-entrada')">+ Registrar Entrada</button>
            <div class="topbar-avatar"><?= strtoupper(substr($usuario['nombre'],0,1)) ?></div>
        </div>
    </div>
    <div class="page-content">
        <?php if ($xMsg): ?><div class="alert alert-<?= $tMsg ?>"><?= htmlspecialchars($xMsg) ?></div><?php endif; ?>

        <!-- Stock actual -->
        <div class="card">
            <div class="card-header"><h3>📦 Stock Actual por Producto</h3></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Producto</th><th>Categoría</th><th>Stock Actual</th><th>Mínimo</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($productos as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                        <td><?= htmlspecialchars($p['nombre_categoria']) ?></td>
                        <td><?= $p['stock_actual'] ?></td>
                        <td><?= $p['stock_minimo'] ?></td>
                        <td>
                            <?php if ($p['stock_actual']<=0): ?>
                                <span class="badge badge-danger">Sin stock</span>
                            <?php elseif ($p['stock_actual']<=$p['stock_minimo']): ?>
                                <span class="badge badge-warning">Stock mínimo</span>
                            <?php else: ?>
                                <span class="badge badge-success">Disponible</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Movimientos -->
        <div class="card">
            <div class="card-header"><h3>🔄 Últimos 50 Movimientos</h3></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Fecha Venc.</th></tr></thead>
                    <tbody>
                    <?php foreach ($movs as $m): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($m['fecha'])) ?></td>
                        <td><?= htmlspecialchars($m['producto']) ?></td>
                        <td><span class="badge <?= $m['tipo_movimiento']==='ENTRADA'?'badge-success':'badge-info' ?>"><?= $m['tipo_movimiento'] ?></span></td>
                        <td><?= $m['cantidad'] ?></td>
                        <td><?= $m['fecha_vencimiento'] ? date('d/m/Y',strtotime($m['fecha_vencimiento'])) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Modal entrada -->
<div class="modal-overlay" id="modal-entrada">
<div class="modal-box">
    <div class="modal-header"><h3>📥 Registrar Entrada de Mercancía</h3><button class="modal-close" onclick="cerrar('modal-entrada')">✕</button></div>
    <form method="POST">
    <div class="modal-body">
        <div class="form-group"><label>Producto *</label>
            <select name="id_producto" class="form-control" required>
                <option value="">— Seleccionar producto —</option>
                <?php foreach ($productos as $p): ?>
                <option value="<?= $p['id_producto'] ?>"><?= htmlspecialchars($p['nombre']) ?> (Stock: <?= $p['stock_actual'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row cols-2">
            <div class="form-group"><label>Cantidad *</label><input name="cantidad" type="number" min="1" class="form-control" required></div>
            <div class="form-group"><label>Fecha de Vencimiento</label><input name="fecha_vencimiento" type="date" class="form-control"></div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="cerrar('modal-entrada')">Cancelar</button>
        <button type="submit" class="btn btn-success">✅ Registrar Entrada</button>
    </div>
    </form>
</div>
</div>
<script>
function abrir(id)  { document.getElementById(id).classList.add('open'); }
function cerrar(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o =>
    o.addEventListener('click',e=>{ if(e.target===o) o.classList.remove('open'); }));
</script>
</body>
</html>
