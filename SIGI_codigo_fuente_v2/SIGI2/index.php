<?php
session_start();
if (isset($_SESSION['usuario'])) {
    $rol = $_SESSION['usuario']['rol'];
    if ($rol === 'ADMIN')         header('Location: views/admin/dashboard.php');
    elseif ($rol === 'CAJERO')    header('Location: views/cajero/pos.php');
    else                          header('Location: views/bodeguero/inventario.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/db.php';
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $hash     = hash('sha256', $password);
    $db       = getDB();
    $stmt     = $db->prepare("SELECT * FROM USUARIO WHERE email=? AND password=?");
    $stmt->bind_param('ss', $email, $hash);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user) {
        $_SESSION['usuario'] = $user;
        if ($user['rol']==='ADMIN')      header('Location: views/admin/dashboard.php');
        elseif ($user['rol']==='CAJERO') header('Location: views/cajero/pos.php');
        else                             header('Location: views/bodeguero/inventario.php');
        exit;
    }
    $error = 'Correo o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIGI — Iniciar Sesión</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
  .login-field-group { margin-bottom: 18px; }
  .login-field-group label { display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 6px; }
  .login-field-group input { width:100%; padding:11px 14px; border:1.5px solid #e0e0e0; border-radius:8px; font-size:14px; transition:border-color .2s; }
  .login-field-group input:focus { outline:none; border-color:#4361ee; box-shadow:0 0 0 3px rgba(67,97,238,.12); }
  .login-btn { width:100%; padding:13px; background:#4361ee; color:#fff; border:none; border-radius:10px; font-size:15px; font-weight:700; cursor:pointer; transition:background .2s; margin-top:6px; }
  .login-btn:hover { background:#3451d1; }
  .login-err { background:#ffeef0; color:#c0202d; border-left:4px solid #e63946; padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:13px; }
  .login-hint { margin-top:24px; padding:14px; background:#f4f6fb; border-radius:10px; font-size:12px; color:#6c757d; }
  .login-hint strong { display:block; margin-bottom:6px; color:#1e2a45; }
  .hint-row { display:flex; justify-content:space-between; padding:2px 0; }
</style>
</head>
<body class="login-page">
<div class="login-box">
    <div class="login-brand">
        <div class="brand-icon">📦</div>
        <h1>SIGI</h1>
        <p>Sistema Integral de Gestión de Inventario</p>
    </div>

    <?php if ($error): ?>
    <div class="login-err">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="login-field-group">
            <label>Correo electrónico</label>
            <input type="email" name="email" placeholder="usuario@sigi.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="login-field-group">
            <label>Contraseña</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <button type="submit" class="login-btn">Ingresar al Sistema →</button>
    </form>

    <div class="login-hint">
        <strong>Credenciales de prueba:</strong>
        <div class="hint-row"><span>👑 Admin:</span><span>admin@sigi.com / admin123</span></div>
        <div class="hint-row"><span>🛒 Cajero:</span><span>cajero1@sigi.com / cajero123</span></div>
        <div class="hint-row"><span>📦 Bodeguero:</span><span>bodega1@sigi.com / bodega123</span></div>
    </div>

    <p style="text-align:center;margin-top:18px;font-size:11px;color:#aaa;">
        Universidad ECCI · Ingeniería de Sistemas · 2025
    </p>
</div>
</body>
</html>
