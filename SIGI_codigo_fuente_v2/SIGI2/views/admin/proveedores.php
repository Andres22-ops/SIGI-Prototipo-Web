<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'ADMIN') {
    header('Location: ../../index.php'); exit;
}
require_once '../../config/db.php';
$usuario    = $_SESSION['usuario'];
$activePage = 'proveedores';
$db         = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['accion'] ?? '';
    if ($a === 'crear') {
        $s = $db->prepare("INSERT INTO PROVEEDOR (nombre,telefono,correo,direccion) VALUES(?,?,?,?)");
        $s->bind_param('ssss',$_POST['nombre'],$_POST['telefono'],$_POST['correo'],$_POST['direccion']);
        $msg = $s->execute() ? 'success|Proveedor creado correctamente.' : 'danger|Error al crear.';
    } elseif ($a === 'eliminar') {
        $s = $db->prepare("DELETE FROM PROVEEDOR WHERE id_proveedor=?");
        $s->bind_param('i',$_POST['id_proveedor']);
        $msg = $s->execute() ? 'success|Proveedor eliminado.' : 'danger|No se pudo eliminar (tiene pedidos asociados).';
    }
}

$proveedores = $db->query("SELECT * FROM PROVEEDOR ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
[$tMsg,$xMsg] = $msg ? explode('|',$msg,2) : ['',''];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>SIGI — Proveedores</title>
<link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="app-layout">
<?php include '../sidebar.php'; ?>
<div class="main-wrap">
    <div class="topbar">
        <div class="topbar-left"><h2>Gestión de Proveedores</h2><p><?= count($proveedores) ?> proveedores registrados</p></div>
        <div class="topbar-right">
            <button class="btn btn-primary" onclick="abrir('modal-crear')">+ Nuevo Proveedor</button>
            <div class="topbar-avatar"><?= strtoupper(substr($usuario['nombre'],0,1)) ?></div>
        </div>
    </div>
    <div class="page-content">
        <?php if ($xMsg): ?>
        <div class="alert alert-<?= $tMsg ?>"><?= htmlspecialchars($xMsg) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h3>🚚 Lista de Proveedores</h3></div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Nombre</th><th>Teléfono</th><th>Correo</th><th>Dirección</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                    <?php if (!$proveedores): ?>
                    <tr><td colspan="6" style="text-align:center;color:#aaa;padding:28px;">No hay proveedores registrados</td></tr>
                    <?php endif; ?>
                    <?php foreach ($proveedores as $p): ?>
                    <tr>
                        <td>#<?= $p['id_proveedor'] ?></td>
                        <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                        <td><?= htmlspecialchars($p['telefono'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($p['correo'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($p['direccion'] ?? '—') ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar proveedor?')">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_proveedor" value="<?= $p['id_proveedor'] ?>">
                                <button class="btn btn-danger btn-sm">🗑️</button>
                            </form>
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

<!-- Modal Crear -->
<div class="modal-overlay" id="modal-crear">
<div class="modal-box">
    <div class="modal-header">
        <h3>➕ Nuevo Proveedor</h3>
        <button class="modal-close" onclick="cerrar('modal-crear')">✕</button>
    </div>
    <form method="POST">
    <div class="modal-body">
        <input type="hidden" name="accion" value="crear">
        <div class="form-group"><label>Nombre empresa *</label><input name="nombre" class="form-control" required></div>
        <div class="form-row cols-2">
            <div class="form-group"><label>Teléfono</label><input name="telefono" class="form-control"></div>
            <div class="form-group"><label>Correo</label><input name="correo" type="email" class="form-control"></div>
        </div>
        <div class="form-group"><label>Dirección</label><input name="direccion" class="form-control"></div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="cerrar('modal-crear')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
    </form>
</div>
</div>

<script>
function abrir(id)  { document.getElementById(id).classList.add('open'); }
function cerrar(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o =>
    o.addEventListener('click', e => { if(e.target===o) o.classList.remove('open'); }));
</script>
</body>
</html>
