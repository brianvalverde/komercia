<?php
session_start();

// ── Credenciales admin (cámbiala en producción) ────────────────
define('ADMIN_USER', 'brian');
define('ADMIN_PASS', '$2y$10$' . ''); // se genera abajo si vacío

// Hash generado con: password_hash('tu_clave', PASSWORD_DEFAULT)
// Por defecto: usuario=brian, clave=komercia2026
define('ADMIN_HASH', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uX1DVcyga'); // "password" de Laravel — reemplaza

// Mejor: clave real hasheada
$CREDS = [
    'brian' => password_hash('komercia2026', PASSWORD_DEFAULT)
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['usuario'] ?? '');
    $p = trim($_POST['clave']   ?? '');
    if (isset($CREDS[$u]) && password_verify($p, $CREDS[$u])) {
        $_SESSION['admin'] = true;
        $_SESSION['admin_user'] = $u;
        header('Location: /admin');
        exit;
    }
    $error = 'Usuario o contraseña incorrectos';
}

if (!empty($_SESSION['admin'])) {
    header('Location: /admin');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Komercia</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#ff6a00 0%,#ee0979 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}
.card{background:#fff;border-radius:20px;padding:44px 40px;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.2)}
.logo{text-align:center;margin-bottom:32px}
.logo span{font-size:26px;font-weight:800;background:linear-gradient(135deg,#ff6a00,#ee0979);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.logo p{font-size:13px;color:#888;margin-top:6px}
label{display:block;font-size:13px;font-weight:600;color:#444;margin-bottom:6px}
input{width:100%;padding:12px 14px;border:1.5px solid #e0e0e0;border-radius:10px;font-size:14px;font-family:inherit;outline:none;transition:border .2s;margin-bottom:16px}
input:focus{border-color:#ff6a00}
.btn{width:100%;padding:14px;background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:4px;transition:opacity .2s}
.btn:hover{opacity:.9}
.error{background:#fff0f0;border:1px solid #ffcccc;color:#c0392b;border-radius:10px;padding:10px 14px;font-size:13px;margin-bottom:16px}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <span>Komercia</span>
    <p>Panel de administración</p>
  </div>
  <?php if (!empty($error)): ?>
    <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST">
    <label>Usuario</label>
    <input type="text" name="usuario" placeholder="admin" autocomplete="username" required>
    <label>Contraseña</label>
    <input type="password" name="clave" placeholder="••••••••" autocomplete="current-password" required>
    <button class="btn" type="submit">Iniciar sesión</button>
  </form>
</div>
</body>
</html>
