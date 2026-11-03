<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'ADMIN') {
    header('Location: ../../index.php'); exit;
}
require_once '../../config/db.php';
$usuario    = $_SESSION['usuario'];
$activePage = 'productos';
$db         = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['accion'] ?? '';
    if ($a === 'crear') {
        $s = $db->prepare("INSERT INTO PRODUCTO (nombre,descripcion,cod_barras,precio,stock_actual,stock_minimo,id_categoria) VALUES(?,?,?,?,?,?,?)");
        $s->bind_param('sssdiis',$_POST['nombre'],$_POST['descripcion'],$_POST['cod_barras'],$_POST['precio'],$_POST['stock_actual'],$_POST['stock_minimo'],$_POST['id_categoria']);
        $msg = $s->execute() ? 'success|Producto creado.' : 'danger|Error al crear.';
    } elseif ($a === 'actualizar') {
        $s = $db->prepare("UPDATE PRODUCTO SET nombre=?,descripcion=?,cod_barras=?,precio=?,stock_minimo=?,id_categoria=? WHERE id_producto=?");
        $s->bind_param('sssdiii',$_POST['nombre'],$_POST['descripcion'],$_POST['cod_barras'],$_POST['precio'],$_POST['stock_minimo'],$_POST['id_categoria'],$_POST['id_producto']);
        $msg = $s->execute() ? 'success|Actualizado.' : 'danger|Error al actualizar.';
    } elseif ($a === 'eliminar') {
        $s = $db->prepare("DELETE FROM PRODUCTO WHERE id_producto=?");
        $s->bind_param('i',$_POST['id_producto']);
        $msg = $s->execute() ? 'success|Eliminado.' : 'danger|No se pudo eliminar.';
    }
}

$productos  = $db->query("SELECT p.*,c.nombre_categoria FROM PRODUCTO p JOIN CATEGORIA c ON p.id_categoria=c.id_categoria ORDER BY p.nombre")->fetch_all(MYSQLI_ASSOC);
$categorias = $db->query("SELECT * FROM CATEGORIA ORDER BY nombre_categoria")->fetch_all(MYSQLI_ASSOC);
[$tMsg,$xMsg] = $msg ? explode('|',$msg,2) : ['',''];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>SIGI — Productos</title>
<link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="app-layout">
<?php include '../sidebar.php'; ?>

<div class="main-wrap">
    <div class="topbar">
        <div class="topbar-left"><h2>Gestión de Productos</h2><p><?= count($productos) ?> productos registrados</p></div>
        <div class="topbar-right">
            <button class="btn btn-primary" onclick="abrir('modal-crear')">+ Nuevo Producto</button>
            <div class="topbar-avatar"><?= strtoupper(substr($usuario['nombre'],0,1)) ?></div>
        </div>
    </div>

    <div class="page-content">
        <?php if ($xMsg): ?>
        <div class="alert alert-<?= $tMsg ?>"><?= htmlspecialchars($xMsg) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>📋 Inventario de Productos</h3>
                <input type="text" id="buscar" class="form-control" style="width:260px;"
                       placeholder="🔍 Buscar..." oninput="filtrar()">
            </div>
            <div class="table-wrap">
                <table id="tbl">
                    <thead><tr>
                        <th>Cód. Barras</th><th>Nombre</th><th>Categoría</th>
                        <th>Precio</th><th>Stock</th><th>Mínimo</th><th>Estado</th><th>Acciones</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($productos as $p): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($p['cod_barras'] ?? '—') ?></code></td>
                        <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                        <td><?= htmlspecialchars($p['nombre_categoria']) ?></td>
                        <td>$<?= number_format($p['precio'],0,',','.') ?></td>
                        <td><?= $p['stock_actual'] ?></td>
                        <td><?= $p['stock_minimo'] ?></td>
                        <td>
                            <?php if ($p['stock_actual'] <= 0): ?>
                                <span class="badge badge-danger">Sin stock</span>
                            <?php elseif ($p['stock_actual'] <= $p['stock_minimo']): ?>
                                <span class="badge badge-warning">Crítico</span>
                            <?php else: ?>
                                <span class="badge badge-success">OK</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:nowrap;">
                            <button class="btn btn-outline btn-sm" onclick='editar(<?= json_encode($p) ?>)'>✏️ Editar</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar?')">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
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
    <div class="modal-header"><h3>➕ Nuevo Producto</h3><button class="modal-close" onclick="cerrar('modal-crear')">✕</button></div>
    <form method="POST">
    <div class="modal-body">
        <input type="hidden" name="accion" value="crear">
        <div class="form-row cols-2">
            <div class="form-group"><label>Nombre *</label><input name="nombre" class="form-control" required></div>
            <div class="form-group"><label>Código Barras EAN-13</label><input name="cod_barras" class="form-control" maxlength="13"></div>
        </div>
        <div class="form-group"><label>Descripción</label><textarea name="descripcion" class="form-control" rows="2"></textarea></div>
        <div class="form-row cols-3">
            <div class="form-group"><label>Precio *</label><input name="precio" type="number" step="0.01" min="0" class="form-control" required></div>
            <div class="form-group"><label>Stock Inicial</label><input name="stock_actual" type="number" value="0" min="0" class="form-control"></div>
            <div class="form-group"><label>Stock Mínimo</label><input name="stock_minimo" type="number" value="5" min="0" class="form-control"></div>
        </div>
        <div class="form-group"><label>Categoría *</label>
            <select name="id_categoria" class="form-control" required>
                <?php foreach ($categorias as $c): ?>
                <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre_categoria']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="cerrar('modal-crear')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar Producto</button>
    </div>
    </form>
</div>
</div>

<!-- Modal Editar -->
<div class="modal-overlay" id="modal-editar">
<div class="modal-box">
    <div class="modal-header"><h3>✏️ Editar Producto</h3><button class="modal-close" onclick="cerrar('modal-editar')">✕</button></div>
    <form method="POST">
    <div class="modal-body">
        <input type="hidden" name="accion" value="actualizar">
        <input type="hidden" name="id_producto" id="e-id">
        <div class="form-row cols-2">
            <div class="form-group"><label>Nombre *</label><input name="nombre" id="e-nombre" class="form-control" required></div>
            <div class="form-group"><label>Código Barras</label><input name="cod_barras" id="e-cod" class="form-control" maxlength="13"></div>
        </div>
        <div class="form-group"><label>Descripción</label><textarea name="descripcion" id="e-desc" class="form-control" rows="2"></textarea></div>
        <div class="form-row cols-2">
            <div class="form-group"><label>Precio *</label><input name="precio" id="e-precio" type="number" step="0.01" min="0" class="form-control" required></div>
            <div class="form-group"><label>Stock Mínimo</label><input name="stock_minimo" id="e-smin" type="number" min="0" class="form-control"></div>
        </div>
        <div class="form-group"><label>Categoría *</label>
            <select name="id_categoria" id="e-cat" class="form-control">
                <?php foreach ($categorias as $c): ?>
                <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre_categoria']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="cerrar('modal-editar')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </div>
    </form>
</div>
</div>

<script>
function abrir(id)  { document.getElementById(id).classList.add('open'); }
function cerrar(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o =>
    o.addEventListener('click', e => { if(e.target===o) o.classList.remove('open'); }));

function editar(p) {
    document.getElementById('e-id').value     = p.id_producto;
    document.getElementById('e-nombre').value = p.nombre;
    document.getElementById('e-cod').value    = p.cod_barras || '';
    document.getElementById('e-desc').value   = p.descripcion || '';
    document.getElementById('e-precio').value = p.precio;
    document.getElementById('e-smin').value   = p.stock_minimo;
    document.getElementById('e-cat').value    = p.id_categoria;
    abrir('modal-editar');
}

function filtrar() {
    const q = document.getElementById('buscar').value.toLowerCase();
    document.querySelectorAll('#tbl tbody tr').forEach(tr =>
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none');
}
</script>
</body>
</html>
