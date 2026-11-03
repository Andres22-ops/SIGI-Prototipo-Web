<?php
session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'],['CAJERO','ADMIN'])) {
    header('Location: ../../index.php'); exit;
}
require_once '../../config/db.php';
$usuario    = $_SESSION['usuario'];
$activePage = 'pos';
$db         = getDB();

// --- AJAX: buscar por código ---
if (isset($_GET['cod'])) {
    $cod  = trim($_GET['cod']);
    $stmt = $db->prepare(
        "SELECT p.id_producto, p.nombre, p.cod_barras, p.precio, p.stock_actual,
                c.nombre_categoria
         FROM PRODUCTO p JOIN CATEGORIA c ON p.id_categoria=c.id_categoria
         WHERE p.cod_barras=? OR p.id_producto=?");
    $id = (int)$cod;
    $stmt->bind_param('si', $cod, $id);
    $stmt->execute();
    $prod = $stmt->get_result()->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode($prod ?: ['error' => 'Producto no encontrado']);
    exit;
}

// --- AJAX: registrar venta ---
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['items'])) {
    $items    = json_decode($_POST['items'], true);
    $id_user  = (int)$usuario['id_usuario'];

    $db->begin_transaction();
    try {
        $total = 0;
        foreach ($items as $item) {
            $stmt = $db->prepare("SELECT stock_actual, nombre FROM PRODUCTO WHERE id_producto=?");
            $stmt->bind_param('i', $item['id_producto']);
            $stmt->execute();
            $p = $stmt->get_result()->fetch_assoc();
            if (!$p || $p['stock_actual'] < $item['cantidad'])
                throw new Exception("Stock insuficiente: " . ($p['nombre'] ?? 'Producto'));
            $total += $item['cantidad'] * $item['precio_unitario'];
        }
        $stmt = $db->prepare("INSERT INTO VENTA (fecha,total,id_usuario) VALUES(NOW(),?,?)");
        $stmt->bind_param('di', $total, $id_user);
        $stmt->execute();
        $id_venta = $db->insert_id;

        foreach ($items as $item) {
            $stmt = $db->prepare(
                "INSERT INTO DETALLE_VENTA (cantidad,precio_unitario,id_venta,id_producto)
                 VALUES(?,?,?,?)");
            $stmt->bind_param('idii',
                $item['cantidad'], $item['precio_unitario'], $id_venta, $item['id_producto']);
            $stmt->execute();
        }
        $db->commit();
        header('Content-Type: application/json');
        echo json_encode(['success'=>true,'id_venta'=>$id_venta,'total'=>$total]);
    } catch (Exception $e) {
        $db->rollback();
        header('Content-Type: application/json');
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIGI — Punto de Venta</title>
<link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="app-layout">
<?php include '../sidebar.php'; ?>

<div class="main-wrap">
    <div class="topbar">
        <div class="topbar-left">
            <h2>Punto de Venta (POS)</h2>
            <p id="reloj-top"></p>
        </div>
        <div class="topbar-right">
            <div class="topbar-avatar"><?= strtoupper(substr($usuario['nombre'],0,1)) ?></div>
        </div>
    </div>

    <div class="page-content">
        <div id="msg-area"></div>

        <div class="pos-grid">
            <!-- Panel izquierdo: escáner + carrito -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3>📷 Escanear Producto</h3>
                        <span id="num-items" class="badge badge-info">0 ítems</span>
                    </div>
                    <div class="card-body">
                        <div class="scanner-bar">
                            <input type="text" id="scanner-input" class="form-control"
                                   placeholder="Código de barras EAN-13 o ID..." autofocus autocomplete="off">
                            <button class="btn btn-primary" onclick="buscar()">Agregar</button>
                        </div>

                        <div id="carrito-lista">
                            <div id="carrito-empty" style="text-align:center;padding:40px;color:#aaa;">
                                <div style="font-size:40px;margin-bottom:10px;">🛒</div>
                                <p>Escanea un producto para comenzar</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel derecho: total + cobro -->
            <div>
                <div class="card" style="position:sticky;top:80px;">
                    <div class="card-header"><h3>💳 Resumen</h3></div>
                    <div class="card-body">
                        <div class="total-panel">
                            <div class="total-row">
                                <span>Subtotal</span>
                                <span id="disp-subtotal">$0</span>
                            </div>
                            <div class="total-row">
                                <span>IVA (0%)</span>
                                <span>$0</span>
                            </div>
                            <div class="total-row grand">
                                <span>TOTAL</span>
                                <span id="disp-total">$0</span>
                            </div>
                        </div>

                        <div style="margin-top:18px;">
                            <div class="form-group">
                                <label>Efectivo recibido ($)</label>
                                <input type="number" id="efectivo" class="form-control"
                                       placeholder="0" min="0" oninput="calcCambio()">
                            </div>
                            <div class="total-row" style="font-size:15px;margin-top:4px;">
                                <span style="font-weight:600;">Cambio</span>
                                <span id="disp-cambio" style="color:var(--success);font-weight:800;">$0</span>
                            </div>
                        </div>

                        <button class="btn btn-success btn-lg btn-block" style="margin-top:20px;" onclick="procesarVenta()">
                            ✅ Cobrar y Registrar Venta
                        </button>
                        <button class="btn btn-ghost btn-block" style="margin-top:8px;" onclick="limpiar()">
                            🗑️ Limpiar Carrito
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /page-content -->
</div><!-- /main-wrap -->
</div><!-- /app-layout -->

<!-- Modal ticket -->
<div class="modal-overlay" id="modal-ticket">
    <div class="modal-box" style="max-width:380px;">
        <div class="modal-header">
            <h3>✅ Venta Registrada</h3>
            <button class="modal-close" onclick="cerrar('modal-ticket')">✕</button>
        </div>
        <div class="modal-body" id="ticket-body" style="font-size:13px;line-height:1.8;"></div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="window.print()">🖨️ Imprimir</button>
            <button class="btn btn-primary" onclick="cerrar('modal-ticket');limpiar()">Nueva Venta</button>
        </div>
    </div>
</div>

<script>
let carrito = [];

// Reloj
setInterval(() => {
    document.getElementById('reloj-top').textContent = new Date().toLocaleString('es-CO');
}, 1000);

// Enter en escáner
document.getElementById('scanner-input').addEventListener('keydown', e => {
    if (e.key === 'Enter') buscar();
});

async function buscar() {
    const cod = document.getElementById('scanner-input').value.trim();
    if (!cod) return;
    const res  = await fetch(`pos.php?cod=${encodeURIComponent(cod)}`);
    const data = await res.json();
    document.getElementById('scanner-input').value = '';
    if (data.error) { msg('danger', '❌ ' + data.error); return; }
    if (parseInt(data.stock_actual) <= 0) { msg('warning', '⚠️ Sin existencias: ' + data.nombre); return; }

    const idx = carrito.findIndex(i => i.id_producto === data.id_producto);
    if (idx >= 0) {
        if (carrito[idx].cantidad >= parseInt(data.stock_actual))
            { msg('warning', '⚠️ No hay más stock disponible'); return; }
        carrito[idx].cantidad++;
    } else {
        carrito.push({
            id_producto: data.id_producto,
            nombre: data.nombre,
            categoria: data.nombre_categoria,
            precio_unitario: parseFloat(data.precio),
            cantidad: 1,
            max_stock: parseInt(data.stock_actual)
        });
    }
    render();
}

function render() {
    const lista = document.getElementById('carrito-lista');
    const empty = document.getElementById('carrito-empty');
    const viejos = lista.querySelectorAll('.cart-item');
    viejos.forEach(e => e.remove());

    empty.style.display = carrito.length ? 'none' : 'block';
    document.getElementById('num-items').textContent = carrito.length + ' ítem(s)';

    let total = 0;
    carrito.forEach((item, idx) => {
        const sub = item.cantidad * item.precio_unitario;
        total += sub;
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
          <div class="cart-item-info" style="flex:1;">
            <div class="item-name">${item.nombre}</div>
            <div class="item-price">${item.categoria} · $${fmt(item.precio_unitario)} × ${item.cantidad}
              = <strong>$${fmt(sub)}</strong></div>
          </div>
          <div class="cart-qty">
            <button class="qty-btn" onclick="chg(${idx},-1)">−</button>
            <input class="qty-input" type="number" value="${item.cantidad}" min="1" max="${item.max_stock}"
                   onchange="set(${idx},this.value)">
            <button class="qty-btn" onclick="chg(${idx},1)">+</button>
            <button class="btn btn-danger btn-sm" onclick="del(${idx})">✕</button>
          </div>`;
        lista.appendChild(div);
    });

    document.getElementById('disp-subtotal').textContent = '$' + fmt(total);
    document.getElementById('disp-total').textContent    = '$' + fmt(total);
    calcCambio();
}

function chg(idx, d) {
    const n = carrito[idx].cantidad + d;
    if (n < 1 || n > carrito[idx].max_stock) return;
    carrito[idx].cantidad = n; render();
}
function set(idx, v) {
    carrito[idx].cantidad = Math.max(1, Math.min(parseInt(v)||1, carrito[idx].max_stock));
    render();
}
function del(idx)  { carrito.splice(idx, 1); render(); }
function limpiar() { carrito = []; render(); document.getElementById('efectivo').value = ''; }

function calcCambio() {
    const tot = carrito.reduce((s,i) => s + i.cantidad * i.precio_unitario, 0);
    const ef  = parseFloat(document.getElementById('efectivo').value) || 0;
    document.getElementById('disp-cambio').textContent = '$' + fmt(Math.max(0, ef - tot));
}

async function procesarVenta() {
    if (!carrito.length) { msg('warning', 'El carrito está vacío'); return; }
    const items = carrito.map(i => ({
        id_producto: i.id_producto,
        cantidad: i.cantidad,
        precio_unitario: i.precio_unitario
    }));
    const res    = await fetch('pos.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'items=' + encodeURIComponent(JSON.stringify(items))
    });
    const data = await res.json();
    if (data.success) {
        // Ticket
        const tot = carrito.reduce((s,i) => s + i.cantidad * i.precio_unitario, 0);
        let html  = `<div style="text-align:center;margin-bottom:12px;">
                       <div style="font-size:32px;">🧾</div>
                       <strong>Venta #${data.id_venta}</strong><br>
                       <small>${new Date().toLocaleString('es-CO')}</small>
                     </div>
                     <hr style="margin:10px 0;border:none;border-top:1px dashed #ccc;">`;
        carrito.forEach(i => {
            html += `<div style="display:flex;justify-content:space-between;margin:4px 0;">
                       <span>${i.nombre} ×${i.cantidad}</span>
                       <span>$${fmt(i.cantidad*i.precio_unitario)}</span>
                     </div>`;
        });
        html += `<hr style="margin:10px 0;border:none;border-top:1px dashed #ccc;">
                 <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:800;">
                   <span>TOTAL</span><span>$${fmt(tot)}</span>
                 </div>`;
        document.getElementById('ticket-body').innerHTML = html;
        abrir('modal-ticket');
    } else {
        msg('danger', '❌ ' + data.error);
    }
}

function fmt(n)     { return Number(n).toLocaleString('es-CO'); }
function msg(t, m)  {
    const el = document.getElementById('msg-area');
    el.innerHTML = `<div class="alert alert-${t}">${m}</div>`;
    setTimeout(() => el.innerHTML = '', 4000);
}
function abrir(id)  { document.getElementById(id).classList.add('open'); }
function cerrar(id) { document.getElementById(id).classList.remove('open'); }
</script>
</body>
</html>
