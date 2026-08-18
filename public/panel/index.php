<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel – Komercia</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#f5f5f5;color:#333}
.layout{display:flex;min-height:100vh}
/* SIDEBAR */
.sidebar{width:240px;background:#111;color:#fff;display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;transition:.3s}
.sidebar-logo{padding:24px 20px;border-bottom:1px solid #222;font-size:22px;font-weight:800;color:#ff6a00}
.sidebar nav a{display:flex;align-items:center;gap:12px;padding:13px 20px;color:#aaa;text-decoration:none;font-size:14px;transition:.2s}
.sidebar nav a:hover,.sidebar nav a.active{color:#fff;background:#222}
.sidebar nav a.active{border-left:3px solid #ff6a00}
.sidebar nav a span.icon{font-size:18px;width:22px;text-align:center}
.sidebar-footer{margin-top:auto;padding:16px 20px;border-top:1px solid #222}
.sidebar-footer a{color:#aaa;text-decoration:none;font-size:13px}
/* MAIN */
.main{margin-left:240px;flex:1;display:flex;flex-direction:column;transition:.3s}
.topbar{background:#fff;padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #eee;position:sticky;top:0;z-index:50}
.topbar-left{display:flex;align-items:center;gap:12px}
.hamburger{display:none;background:none;border:none;font-size:22px;cursor:pointer}
.topbar h1{font-size:18px;font-weight:600}
.topbar-right{display:flex;align-items:center;gap:12px}
.btn{padding:8px 16px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:500;transition:.2s}
.btn-outline{background:#fff;color:#333;border:1px solid #ddd}
.btn-outline:hover{background:#f5f5f5}
.btn-sm{font-size:12px;padding:5px 12px}
.avatar{width:36px;height:36px;border-radius:50%;background:#ff6a00;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px}
.content{padding:24px}
/* TRIAL BANNER */
.trial-banner{background:linear-gradient(135deg,#ff6a00,#ff8c00);color:#fff;padding:14px 20px;border-radius:10px;margin-bottom:24px;display:none;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.trial-banner p{font-size:14px}
.trial-banner strong{font-size:16px}
.btn-upgrade{background:#fff;color:#ff6a00;border:none;padding:8px 18px;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px}
/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:28px}
.stat-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,.07)}
.stat-label{font-size:13px;color:#888;margin-bottom:8px}
.stat-value{font-size:32px;font-weight:800;color:#111}
.stat-icon{font-size:28px;margin-bottom:8px}
/* WELCOME */
.welcome{background:#fff;border-radius:12px;padding:24px;margin-bottom:24px;box-shadow:0 1px 4px rgba(0,0,0,.07)}
.welcome h2{font-size:20px;font-weight:700;margin-bottom:6px}
.welcome p{color:#888;font-size:14px;margin-bottom:16px}
.quick-actions{display:flex;gap:12px;flex-wrap:wrap}
.btn-primary{background:#ff6a00;color:#fff;padding:10px 20px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;text-decoration:none;display:inline-block;transition:.2s}
.btn-primary:hover{background:#e55d00}
/* OVERLAY */
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:90}
@media(max-width:768px){
  .sidebar{transform:translateX(-240px)}
  .sidebar.open{transform:translateX(0)}
  .sidebar-overlay.show{display:block}
  .main{margin-left:0}
  .hamburger{display:block}
  .stats-grid{grid-template-columns:1fr 1fr}
}
</style>
</head>
<body>
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>
<div class="layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">⚡ Komercia</div>
    <nav>
      <a href="/panel" class="active"><span class="icon">📊</span> Dashboard</a>
      <a href="/panel/productos"><span class="icon">📦</span> Productos</a>
      <a href="#"><span class="icon">🛒</span> Pedidos</a>
      <a href="#"><span class="icon">🏪</span> Mi Tienda</a>
      <a href="#"><span class="icon">💎</span> Mi Plan</a>
      <a href="/panel/configuracion"><span class="icon">⚙️</span> Configuración</a>
    </nav>
    <div class="sidebar-footer">
      <a href="#" onclick="logout()">🚪 Cerrar sesión</a>
    </div>
  </aside>
  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <h1>📊 Dashboard</h1>
      </div>
      <div class="topbar-right">
        <a id="link-tienda" href="#" target="_blank" class="btn btn-outline btn-sm">🏪 Ver mi tienda</a>
        <div class="avatar" id="avatar-inicial">?</div>
      </div>
    </div>
    <div class="content">
      <!-- TRIAL BANNER -->
      <div class="trial-banner" id="trial-banner">
        <div>
          <p>⏳ Tu período de prueba termina en</p>
          <strong id="trial-dias">7</strong> <strong>días</strong>
        </div>
        <button class="btn-upgrade">⚡ Actualizar plan</button>
      </div>
      <!-- BIENVENIDA -->
      <div class="welcome">
        <h2>Hola, <span id="usuario-nombre">...</span> 👋</h2>
        <p>Bienvenido a tu panel de control. Aquí puedes gestionar tu tienda.</p>
        <div class="quick-actions">
          <a href="/panel/productos" class="btn-primary">＋ Agregar producto</a>
          <a id="link-tienda2" href="#" target="_blank" class="btn btn-outline">🏪 Ver mi tienda</a>
        </div>
      </div>
      <!-- ESTADÍSTICAS -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">📦</div>
          <div class="stat-label">Productos</div>
          <div class="stat-value" id="stat-productos">0</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🛒</div>
          <div class="stat-label">Pedidos</div>
          <div class="stat-value">0</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">👁️</div>
          <div class="stat-label">Visitas</div>
          <div class="stat-value">0</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">💰</div>
          <div class="stat-label">Ventas</div>
          <div class="stat-value">S/. 0</div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
fetch('/api/sesion')
  .then(r => r.json())
  .then(d => {
    if (!d.ok) { location.href = '/login'; return; }
    document.getElementById('avatar-inicial').textContent  = d.nombre.charAt(0).toUpperCase();
    document.getElementById('usuario-nombre').textContent  = d.nombre;
    document.getElementById('link-tienda').href            = '/tienda/' + d.slug;
    document.getElementById('link-tienda2').href           = '/tienda/' + d.slug;
    // Trial banner
    if (d.plan === 'trial' && d.trial_expira) {
      const dias = Math.ceil((new Date(d.trial_expira) - new Date()) / 86400000);
      if (dias > 0) {
        const banner = document.getElementById('trial-banner');
        banner.style.display = 'flex';
        document.getElementById('trial-dias').textContent = dias;
      }
    }
    // Conteo real de productos
    fetch('/api/productos?accion=listar')
      .then(r => r.json())
      .then(p => {
        if (p.ok) {
          document.getElementById('stat-productos').textContent = p.productos.length;
        }
      });
  });
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('overlay').classList.toggle('show');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('overlay').classList.remove('show');
}
function logout() {
  fetch('/api/logout').then(() => location.href = '/login');
}
</script>
</body>
</html>
