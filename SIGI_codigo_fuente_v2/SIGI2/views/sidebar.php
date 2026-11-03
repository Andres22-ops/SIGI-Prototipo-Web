<?php
/**
 * Componente: Sidebar compartido
 * Uso: include sidebar.php; (pasar $activePage y $usuario antes)
 */
$rol = $usuario['rol'] ?? '';
$ini = strtoupper(substr($usuario['nombre'] ?? 'U', 0, 1));
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">📦</div>
        <span class="brand-name">SIGI</span>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar"><?= $ini ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($usuario['nombre'] ?? '') ?></div>
            <div class="user-role"><?= htmlspecialchars($rol) ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Principal</div>

        <?php if ($rol === 'ADMIN'): ?>
        <a href="/SIGI2/views/admin/dashboard.php"
           class="<?= ($activePage==='dashboard') ? 'active' : '' ?>">
            <span class="nav-icon">🏠</span><span>Dashboard</span>
        </a>
        <a href="/SIGI2/views/admin/productos.php"
           class="<?= ($activePage==='productos') ? 'active' : '' ?>">
            <span class="nav-icon">📋</span><span>Productos</span>
        </a>
        <a href="/SIGI2/views/admin/proveedores.php"
           class="<?= ($activePage==='proveedores') ? 'active' : '' ?>">
            <span class="nav-icon">🚚</span><span>Proveedores</span>
        </a>
        <a href="/SIGI2/views/admin/pedidos.php"
           class="<?= ($activePage==='pedidos') ? 'active' : '' ?>">
            <span class="nav-icon">📦</span><span>Pedidos</span>
        </a>
        <a href="/SIGI2/views/admin/reportes.php"
           class="<?= ($activePage==='reportes') ? 'active' : '' ?>">
            <span class="nav-icon">📊</span><span>Reportes (PEPS)</span>
        </a>
        <a href="/SIGI2/views/admin/alertas.php"
           class="<?= ($activePage==='alertas') ? 'active' : '' ?>">
            <span class="nav-icon">🔔</span><span>Alertas</span>
        </a>
        <?php endif; ?>

        <?php if (in_array($rol, ['CAJERO','ADMIN'])): ?>
        <div class="nav-section-label">Ventas</div>
        <a href="/SIGI2/views/cajero/pos.php"
           class="<?= ($activePage==='pos') ? 'active' : '' ?>">
            <span class="nav-icon">🛒</span><span>Ventas (POS)</span>
        </a>
        <?php endif; ?>

        <?php if (in_array($rol, ['BODEGUERO','ADMIN'])): ?>
        <div class="nav-section-label">Inventario</div>
        <a href="/SIGI2/views/bodeguero/inventario.php"
           class="<?= ($activePage==='inventario') ? 'active' : '' ?>">
            <span class="nav-icon">🏪</span><span>Inventario (Lotes)</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="/SIGI2/logout.php"><span class="nav-icon">🚪</span><span>Cerrar sesión</span></a>
    </div>
</aside>
