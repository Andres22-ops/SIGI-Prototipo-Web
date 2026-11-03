<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'ADMIN') {
    header('Location: ../../index.php'); exit;
}
require_once '../../config/db.php';
$usuario    = $_SESSION['usuario'];
$activePage = 'pedidos';
$db         = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['accion'] ?? '';

    if ($a === 'crear') {
        $id_prov = (int)$_POST['id_proveedor'];
        $db->begin_transaction();
        try {
            $s = $db->prepare("INSERT INTO PEDIDO (fecha,estado,id_proveedor) VALUES(CURDATE(),'PENDIENTE',?)");
            $s->bind_param('i',$id_prov);
            $s->execute();
            $id_pedido = $db->insert_id;

            $productos = $_POST['id_producto'] ?? [];
            $cantidades= $_POST['cantidad'] ?? [];
            foreach ($productos as $i => $id_prod) {
                if (!$id_prod || !$cantidades[$i]) continue;
                $qty = (int)$cantidades[$i];
                $pid = (int)$id_prod;
                $s2  = $db->prepare("INSERT INTO DETALLE_PEDIDO (cantidad,id_pedido,id_producto) VALUES(?,?,?)");
                $s2->bind_param('iii',$qty,$id_pedido,$pid);
                $s2->execute();
            }
            $db->commit();
            $msg = 'success|Pedido #'.$id_pedido.' creado correctamente.';
        } catch(Exception $e) { $db->rollback(); $msg='danger|Error: '.$e->getMessage(); }

    } elseif ($a === 'recibir') {
        // El trigger actualiza el stock automáticamente
        $s = $db->prepare("UPDATE PEDIDO SET estado='RECIBIDO' WHERE id_pedido=?");
        $s->bind_param('i',$_POST['id_pedido']);
        $msg = $s->execute() ? 'success|Pedido marcado como RECIBIDO. Stock actualizado.' : 'danger|Error.';
    }
}

$pedidos     = $db->query(
    "SELECT p.*,pr.nombre AS proveedor,
            COUNT(dp.id_detalle_pedido) AS num_items
     FROM PEDIDO p
     JOIN PROVEEDOR pr ON p.id_proveedor=pr.id_proveedor
     LEFT JOIN DETALLE_PEDIDO dp ON p.id_pedido=dp.id_pedido
     GROUP BY p.id_pedido ORDER BY p.fecha DESC")->fetch_all(MYSQLI_ASSOC);
$proveedores = $db->query("SELECT * FROM PROVEEDOR ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$productos   = $db->query("SELECT id_producto,nombre FROM PRODUCTO ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
[$tMsg,$xMsg]= $msg ? explode('|',$msg,2) : ['',''];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>SIGI — Pedidos</title>
<link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="app-layout">
<?php include '../sidebar.php'; ?>
<div class="main-wrap">
    <div class="topbar">
        <div class="topbar-left"><h2>Gestión de Pedidos</h2><p><?= count($pedidos) ?> pedidos registrados</p></div>
        <div class="topbar-right">
            <button class="btn btn-primary" onclick="abrir('modal-crear')">+ Nuevo Pedido</button>
            <div class="topbar-avatar"><?= strtoupper(substr($usuario['nombre'],0,1)) ?></div>
        </div>
    </div>
    <div class="page-content">
        <?php if ($xMsg): ?>
        <div class="alert alert-<?= $tMsg ?>"><?= htmlspecialchars($xMsg) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h3>📦 Lista de Pedidos</h3></div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>#</th><th>Fecha</th><th>Proveedor</th><th>Ítems</th><th>Estado</th><th>Acción</th></tr>
                    </thead>
                    <tbody>
                    <?php if (!$pedidos): ?>
                    <tr><td colspan="6" style="text-align:center;color:#aaa;padding:28px;">No hay pedidos registrados</td></tr>
                    <?php endif; ?>
                    <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td><strong>#<?= $p['id_pedido'] ?></strong></td>
                        <td><?= date('d/m/Y', strtotime($p['fecha'])) ?></td>
                        <td><?= htmlspecialchars($p['proveedor']) ?></td>
                        <td><?= $p['num_items'] ?> producto(s)</td>
                        <td>
                            <?php if ($p['estado']==='RECIBIDO'): ?>
                                <span class="badge badge-success">✅ Recibido</span>
                            <?php elseif ($p['estado']==='PENDIENTE'): ?>
                                <span class="badge badge-warning">⏳ Pendiente</span>
                            <?php else: ?>
                                <span class="badge badge-gray"><?= $p['estado'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['estado']==='PENDIENTE'): ?>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('¿Confirmar recepción? Esto actualizará el stock.')">
                                <input type="hidden" name="accion" value="recibir">
                                <input type="hidden" name="id_pedido" value="<?= $p['id_pedido'] ?>">
                                <button class="btn btn-success btn-sm">📥 Marcar Recibido</button>
                            </form>
                            <?php else: ?>
                                <span style="color:#aaa;font-size:12px;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Modal Crear Pedido -->
<div class="modal-overlay" id="modal-crear">
<div class="modal-box" style="max-width:600px;">
    <div class="modal-header">
        <h3>📦 Nuevo Pedido a Proveedor</h3>
        <button class="modal-close" onclick="cerrar('modal-crear')">✕</button>
    </div>
    <form method="POST">
    <div class="modal-body">
        <input type="hidden" name="accion" value="crear">
        <div class="form-group">
            <label>Proveedor *</label>
            <select name="id_proveedor" class="form-control" required>
                <option value="">— Seleccionar proveedor —</option>
                <?php foreach ($proveedores as $pv): ?>
                <option value="<?= $pv['id_proveedor'] ?>"><?= htmlspecialchars($pv['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:8px;">Productos a pedir:</label>
        <div id="items-container">
            <div class="form-row cols-2" style="margin-bottom:10px;">
                <select name="id_producto[]" class="form-control">
                    <option value="">— Producto —</option>
                    <?php foreach ($productos as $pr): ?>
                    <option value="<?= $pr['id_producto'] ?>"><?= htmlspecialchars($pr['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="cantidad[]" class="form-control" placeholder="Cantidad" min="1">
            </div>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" onclick="agregarFila()">+ Agregar producto</button>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="cerrar('modal-crear')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Crear Pedido</button>
    </div>
    </form>
</div>
</div>

<script>
function abrir(id)  { document.getElementById(id).classList.add('open'); }
function cerrar(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o =>
    o.addEventListener('click', e => { if(e.target===o) o.classList.remove('open'); }));

function agregarFila() {
    const cont = document.getElementById('items-container');
    const fila = cont.querySelector('.form-row').cloneNode(true);
    fila.querySelectorAll('select,input').forEach(el => el.value = '');
    cont.appendChild(fila);
}
</script>
</body>
</html>
