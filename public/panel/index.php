<?php
session_start();
if (empty($_SESSION['uid'])) {
    header('Location: /login');
    exit;
}
$slug = $_SESSION['slug'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Komercia</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#f0f2f5;color:#1a1a2e;min-height:100vh;display:flex}

/* ── Sidebar ─────────────────────────────────────── */
.sidebar{width:220px;background:#fff;border-right:1px solid #e8eaf0;display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;padding:24px 0}
.sidebar-logo{padding:0 20px 24px;border-bottom:1px solid #e8eaf0;margin-bottom:16px}
.sidebar-logo span{font-size:22px;font-weight:700;background:linear-gradient(135deg,#ff6a00,#ee0979);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.sidebar nav a{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#555;text-decoration:none;font-size:14px;border-radius:8px;margin:2px 8px;transition:all .2s}
.sidebar nav a:hover{background:#fff5f0;color:#ff6a00}
.sidebar nav a.active{background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;font-weight:600}
.sidebar nav a svg{width:18px;height:18px;flex-shrink:0}

/* ── Topbar ──────────────────────────────────────── */
.topbar{position:fixed;top:0;left:220px;right:0;height:60px;background:#fff;border-bottom:1px solid #e8eaf0;display:flex;align-items:center;justify-content:space-between;padding:0 28px;z-index:90}
.topbar-left h2{font-size:16px;font-weight:700;color:#1a1a2e}
.topbar-left p{font-size:12px;color:#888;margin-top:2px}
.topbar-right{display:flex;align-items:center;gap:12px}

/* ── Store switcher ──────────────────────────────── */
.store-switcher{position:relative}
.store-switcher-btn{display:flex;align-items:center;gap:8px;padding:7px 12px;background:#f8f9fb;border:1.5px solid #e8eaf0;border-radius:10px;cursor:pointer;font-size:13px;font-weight:600;color:#1a1a2e;transition:all .2s;white-space:nowrap;max-width:180px}
.store-switcher-btn:hover{border-color:#ff6a00;color:#ff6a00}
.store-switcher-btn .store-name{overflow:hidden;text-overflow:ellipsis}
.store-switcher-btn .arr{font-size:10px;color:#aaa;flex-shrink:0}
.store-dropdown{position:absolute;top:44px;right:0;width:240px;background:#fff;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,.14);border:1px solid #e8eaf0;z-index:200;overflow:hidden;display:none}
.store-dropdown.open{display:block}
.store-dropdown-head{padding:10px 14px;font-size:11px;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f0f0f0}
.store-item{padding:11px 14px;display:flex;align-items:center;gap:10px;cursor:pointer;transition:background .15s;font-size:13px}
.store-item:hover{background:#fff5f0}
.store-item.active{background:#fff5f0;font-weight:700;color:#ff6a00}
.store-item-icon{width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#ff6a00,#ee0979);display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.store-item-name{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.store-item-check{color:#ff6a00;font-size:14px;flex-shrink:0}
.store-dropdown-foot{border-top:1px solid #f0f0f0;padding:10px 14px}
.store-dropdown-foot a{display:flex;align-items:center;gap:6px;font-size:13px;color:#888;text-decoration:none;transition:color .2s}
.store-dropdown-foot a:hover{color:#ff6a00}

/* ── Bell notification ───────────────────────────── */
.bell-wrap{position:relative;cursor:pointer}
.bell-btn{background:#f5f5f5;border:none;border-radius:10px;width:38px;height:38px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .2s;position:relative}
.bell-btn:hover{background:#fff0e8}
.bell-btn svg{width:18px;height:18px;color:#555}
.bell-badge{position:absolute;top:-4px;right:-4px;background:#ee0979;color:#fff;font-size:10px;font-weight:700;border-radius:99px;min-width:16px;height:16px;display:flex;align-items:center;justify-content:center;padding:0 3px;display:none}
.bell-badge.visible{display:flex}
.notif-dropdown{position:absolute;top:46px;right:0;width:320px;background:#fff;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,.14);border:1px solid #e8eaf0;z-index:200;overflow:hidden;display:none}
.notif-dropdown.open{display:block}
.notif-header{padding:14px 18px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center}
.notif-header span{font-size:13px;font-weight:700;color:#1a1a2e}
.notif-header button{font-size:11px;color:#aaa;background:none;border:none;cursor:pointer}
.notif-header button:hover{color:#ff6a00}
.notif-list{max-height:300px;overflow-y:auto}
.notif-item{padding:12px 18px;border-bottom:1px solid #f8f8f8;cursor:pointer;transition:background .15s}
.notif-item:hover{background:#fff5f0}
.notif-item.unread{background:#fffbf8}
.notif-item .notif-title{font-size:13px;font-weight:600;color:#1a1a2e}
.notif-item .notif-meta{font-size:11px;color:#aaa;margin-top:2px}
.notif-empty{padding:28px;text-align:center;color:#bbb;font-size:13px}

/* ── Copy link btn ───────────────────────────────── */
.copy-link-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#f5f5f5;border:1.5px solid #e0e0e0;border-radius:10px;cursor:pointer;font-size:13px;font-weight:600;color:#444;transition:all .2s;white-space:nowrap}
.copy-link-btn:hover{border-color:#ff6a00;color:#ff6a00}
.copy-link-btn svg{width:14px;height:14px}

/* ── Main ────────────────────────────────────────── */
.main{margin-left:220px;padding-top:60px;flex:1}
.content{padding:28px}

/* ── Stats grid ──────────────────────────────────── */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.stat-card{background:#fff;border-radius:14px;padding:20px 22px;box-shadow:0 2px 8px rgba(0,0,0,.05);display:flex;flex-direction:column;gap:6px}
.stat-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:4px}
.stat-icon.orange{background:#fff0e8}
.stat-icon.pink{background:#ffe8f0}
.stat-icon.blue{background:#e8f0ff}
.stat-icon.green{background:#e8fff0}
.stat-icon svg{width:20px;height:20px}
.stat-value{font-size:26px;font-weight:700;color:#1a1a2e;line-height:1}
.stat-value.loading{color:#e0e0e0;animation:shimmer 1.2s infinite}
@keyframes shimmer{0%,100%{opacity:.5}50%{opacity:1}}
.stat-label{font-size:12px;color:#888;font-weight:500}
.stat-change{font-size:11px;color:#aaa}

/* ── Store link card ─────────────────────────────── */
.store-link-card{background:linear-gradient(135deg,#ff6a00,#ee0979);border-radius:14px;padding:22px;color:#fff;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.store-link-card h3{font-size:15px;font-weight:700;margin-bottom:4px}
.store-link-card p{font-size:13px;opacity:.85;word-break:break-all}
.store-link-card .copy-btn{background:rgba(255,255,255,.2);border:1.5px solid rgba(255,255,255,.4);color:#fff;padding:9px 16px;border-radius:10px;cursor:pointer;font-size:13px;font-weight:600;white-space:nowrap;display:flex;align-items:center;gap:6px;transition:background .2s}
.store-link-card .copy-btn:hover{background:rgba(255,255,255,.35)}
.store-link-card .copy-btn svg{width:14px;height:14px}

/* ── Recent orders ───────────────────────────────── */
.card{background:#fff;border-radius:14px;padding:22px;box-shadow:0 2px 8px rgba(0,0,0,.05);margin-bottom:24px}
.card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
.card-header h3{font-size:15px;font-weight:700;color:#1a1a2e}
.card-header a{font-size:13px;color:#ff6a00;text-decoration:none;font-weight:600}
.orders-table{width:100%;border-collapse:collapse}
.orders-table th{text-align:left;font-size:12px;font-weight:600;color:#aaa;padding:0 12px 10px;text-transform:uppercase;letter-spacing:.04em}
.orders-table td{padding:10px 12px;border-top:1px solid #f5f5f5;font-size:14px;color:#333}
.orders-table tr:hover td{background:#fafafa}
.status-badge{display:inline-block;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;letter-spacing:.03em}
.status-badge.pendiente{background:#fff3e0;color:#f57c00}
.status-badge.enviado{background:#e3f2fd;color:#1565c0}
.status-badge.entregado{background:#e8f5e9;color:#2e7d32}
.status-badge.cancelado{background:#fce4ec;color:#c62828}
.empty-state{text-align:center;padding:40px;color:#bbb;font-size:14px}

/* ── Quick actions ───────────────────────────────── */
.quick-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px}
.quick-card{background:#fff;border-radius:14px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.05);text-decoration:none;color:#1a1a2e;display:flex;flex-direction:column;gap:8px;transition:transform .15s,box-shadow .15s}
.quick-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.09)}
.quick-card .qc-icon{font-size:24px}
.quick-card .qc-title{font-size:14px;font-weight:700}
.quick-card .qc-desc{font-size:12px;color:#888}

/* ── Toast ───────────────────────────────────────── */
#toast{position:fixed;bottom:24px;right:24px;background:#1a1a2e;color:#fff;padding:11px 20px;border-radius:12px;font-size:14px;font-weight:500;opacity:0;transform:translateY(10px);transition:all .3s;z-index:999;pointer-events:none}
#toast.show{opacity:1;transform:translateY(0)}

@media(max-width:900px){
  .stats-grid{grid-template-columns:repeat(2,1fr)}
  .quick-grid{grid-template-columns:1fr 1fr}
}
@media(max-width:600px){
  .sidebar{display:none}
  .main,.topbar{margin-left:0;left:0}
  .stats-grid{grid-template-columns:1fr 1fr}
  .quick-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-logo"><span>Komercia</span></div>
  <nav>
    <a href="/panel" class="active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>
    <a href="/panel/productos">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path d="M16 3H8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z"/></svg>
      Productos
    </a>
    <a href="/panel/pedidos">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Pedidos
    </a>
    <a href="/panel/configuracion">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 003.41 3.48M3.41 3.48A10 10 0 004.93 19.07M4.93 19.07A10 10 0 0020.59 20.52M20.59 20.52A10 10 0 0019.07 4.93"/></svg>
      Configuración
    </a>
    <a href="/panel/tiendas">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Mis tiendas
    </a>
    <a href="/panel/vendedores">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Vendedores
    </a>
    <a href="/panel/planes">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      Planes
    </a>
  </nav>
</aside>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left">
    <h2>¡Bienvenido de nuevo! 👋</h2>
    <p id="topbar-date">Cargando fecha...</p>
  </div>
  <div class="topbar-right">
    <!-- Store switcher (solo empresarial) -->
    <div class="store-switcher" id="store-switcher" style="display:none">
      <button class="store-switcher-btn" onclick="toggleStoreDropdown()">
        🏪 <span class="store-name" id="store-switcher-name">Mi tienda</span>
        <span class="arr">▾</span>
      </button>
      <div class="store-dropdown" id="store-dropdown">
        <div class="store-dropdown-head">Mis tiendas</div>
        <div id="store-list">…</div>
        <div class="store-dropdown-foot">
          <a href="/panel/tiendas">⚙️ Gestionar tiendas</a>
        </div>
      </div>
    </div>

    <!-- Copy store link -->
    <button class="copy-link-btn" onclick="copyStoreLink()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
      Mi tienda
    </button>

    <!-- Cerrar sesión -->
    <a href="/logout" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#f5f5f5;border:1.5px solid #e0e0e0;border-radius:10px;font-size:13px;font-weight:600;color:#888;text-decoration:none;transition:all .2s" onmouseover="this.style.borderColor='#ee0979';this.style.color='#ee0979'" onmouseout="this.style.borderColor='#e0e0e0';this.style.color='#888'">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Salir
    </a>

    <!-- Bell -->
    <div class="bell-wrap" id="bell-wrap">
      <button class="bell-btn" id="bell-btn" onclick="toggleNotifDropdown()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        <span class="bell-badge" id="bell-badge">0</span>
      </button>
      <div class="notif-dropdown" id="notif-dropdown">
        <div class="notif-header">
          <span>Notificaciones</span>
          <button onclick="marcarTodasVistas()">Marcar todas como vistas</button>
        </div>
        <div class="notif-list" id="notif-list">
          <div class="notif-empty">No hay notificaciones</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main content -->
<div class="main">
  <div class="content">

    <!-- Plan banner (se llena desde JS) -->
    <div id="plan-banner" style="display:none;border-radius:14px;padding:14px 20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap"></div>

    <!-- Store link hero -->
    <div class="store-link-card">
      <div>
        <h3>🔗 Enlace de tu tienda</h3>
        <p id="store-url-display">komercia.online/tienda/<?= htmlspecialchars($slug) ?></p>
      </div>
      <button class="copy-btn" onclick="copyStoreLink()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
        Copiar enlace
      </button>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon orange">
          <svg viewBox="0 0 24 24" fill="none" stroke="#ff6a00" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div class="stat-value loading" id="stat-pedidos">—</div>
        <div class="stat-label">Pedidos totales</div>
        <div class="stat-change" id="stat-pedidos-hoy">Cargando...</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <svg viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div class="stat-value loading" id="stat-ingresos">—</div>
        <div class="stat-label">Ingresos totales</div>
        <div class="stat-change" id="stat-ingresos-mes">Cargando...</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="#1565c0" stroke-width="2"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path d="M16 3H8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z"/></svg>
        </div>
        <div class="stat-value loading" id="stat-productos">—</div>
        <div class="stat-label">Productos activos</div>
        <div class="stat-change">En tu catálogo</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon pink">
          <svg viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <div class="stat-value loading" id="stat-pendientes">—</div>
        <div class="stat-label">Pedidos pendientes</div>
        <div class="stat-change">Por atender</div>
      </div>
    </div>

    <!-- Quick actions -->
    <div class="quick-grid">
      <a class="quick-card" href="/panel/productos">
        <div class="qc-icon">📦</div>
        <div class="qc-title">Gestionar productos</div>
        <div class="qc-desc">Agregar, editar o eliminar productos de tu catálogo</div>
      </a>
      <a class="quick-card" href="/panel/pedidos">
        <div class="qc-icon">🧾</div>
        <div class="qc-title">Ver pedidos</div>
        <div class="qc-desc">Revisa y actualiza el estado de tus pedidos</div>
      </a>
      <a class="quick-card" href="/panel/configuracion">
        <div class="qc-icon">⚙️</div>
        <div class="qc-title">Configurar tienda</div>
        <div class="qc-desc">Logo, banners, redes sociales y delivery</div>
      </a>
    </div>

    <!-- Recent orders -->
    <div class="card">
      <div class="card-header">
        <h3>📋 Pedidos recientes</h3>
        <a href="/panel/pedidos">Ver todos →</a>
      </div>
      <div id="recent-orders-wrap">
        <div class="empty-state">Cargando pedidos...</div>
      </div>
    </div>

  </div>
</div>

<div id="toast"></div>

<script>
const slug = '<?= htmlspecialchars($slug) ?>';
const storeUrl = `https://komercia.online/tienda/${slug}`;

// ── Date ──────────────────────────────────────────────────────
(function() {
  const dias = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
  const meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
  const d = new Date();
  document.getElementById('topbar-date').textContent =
    `${dias[d.getDay()]}, ${d.getDate()} de ${meses[d.getMonth()]} de ${d.getFullYear()}`;
})();

// ── Toast ─────────────────────────────────────────────────────
function toast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'show';
  clearTimeout(t._t);
  t._t = setTimeout(() => t.className = '', 3000);
}

// ── Copy store link ───────────────────────────────────────────
function copyStoreLink() {
  navigator.clipboard.writeText(storeUrl).then(() => toast('✅ Enlace copiado al portapapeles')).catch(() => {
    const ta = document.createElement('textarea');
    ta.value = storeUrl; document.body.appendChild(ta); ta.select();
    document.execCommand('copy'); document.body.removeChild(ta);
    toast('✅ Enlace copiado');
  });
}

// ── Load stats ────────────────────────────────────────────────
async function cargarStats() {
  try {
    const res  = await fetch('/api/pedidos?accion=listar');
    const data = await res.json();
    if (!data.ok) return;
    const pedidos = data.pedidos || [];

    // Total pedidos
    document.getElementById('stat-pedidos').textContent = pedidos.length;
    document.getElementById('stat-pedidos').classList.remove('loading');

    // Pedidos de hoy
    const hoy = new Date().toISOString().slice(0, 10);
    const hoyCount = pedidos.filter(p => (p.fecha || '').startsWith(hoy)).length;
    document.getElementById('stat-pedidos-hoy').textContent = `${hoyCount} hoy`;

    // Ingresos totales (sum of total field, entregados or all)
    const ingresos = pedidos.reduce((sum, p) => {
      const t = parseFloat(p.total || 0);
      return sum + (isNaN(t) ? 0 : t);
    }, 0);
    document.getElementById('stat-ingresos').textContent = 'S/ ' + ingresos.toFixed(2);
    document.getElementById('stat-ingresos').classList.remove('loading');

    // Ingresos este mes
    const mesActual = new Date().toISOString().slice(0, 7);
    const ingresosMes = pedidos.filter(p => (p.fecha || '').startsWith(mesActual))
      .reduce((s, p) => s + (parseFloat(p.total || 0) || 0), 0);
    document.getElementById('stat-ingresos-mes').textContent = `S/ ${ingresosMes.toFixed(2)} este mes`;

    // Pendientes
    const pendientes = pedidos.filter(p => p.estado === 'pendiente').length;
    document.getElementById('stat-pendientes').textContent = pendientes;
    document.getElementById('stat-pendientes').classList.remove('loading');

    // Recent orders table (last 5)
    const recientes = [...pedidos].sort((a, b) => (b.fecha || '').localeCompare(a.fecha || '')).slice(0, 5);
    renderRecentOrders(recientes);

    // Notifications: new pending orders not seen yet
    checkNotificaciones(pedidos);

  } catch(e) {
    document.getElementById('stat-pedidos').textContent = 'Error';
    document.getElementById('stat-ingresos').textContent = 'Error';
  }
}

async function cargarProductosStats() {
  try {
    const res  = await fetch('/api/productos?accion=listar');
    const data = await res.json();
    if (!data.ok) return;
    const prods = (data.productos || []).filter(p => p.activo !== false);
    document.getElementById('stat-productos').textContent = prods.length;
    document.getElementById('stat-productos').classList.remove('loading');
  } catch { document.getElementById('stat-productos').textContent = '—'; }
}

function renderRecentOrders(pedidos) {
  const wrap = document.getElementById('recent-orders-wrap');
  if (!pedidos.length) {
    wrap.innerHTML = '<div class="empty-state">Aún no tienes pedidos 🎉<br>Comparte tu tienda para recibir el primero.</div>';
    return;
  }
  const statusLabel = { pendiente:'Pendiente', enviado:'Enviado', entregado:'Entregado', cancelado:'Cancelado' };
  const rows = pedidos.map(p => `
    <tr>
      <td>${escHtml(p.nombre || '—')}</td>
      <td>${escHtml(p.telefono || '—')}</td>
      <td>S/ ${parseFloat(p.total || 0).toFixed(2)}</td>
      <td><span class="status-badge ${p.estado || 'pendiente'}">${statusLabel[p.estado] || p.estado || 'Pendiente'}</span></td>
      <td style="color:#aaa;font-size:12px">${formatFecha(p.fecha)}</td>
    </tr>
  `).join('');
  wrap.innerHTML = `
    <table class="orders-table">
      <thead><tr>
        <th>Cliente</th><th>Teléfono</th><th>Total</th><th>Estado</th><th>Fecha</th>
      </tr></thead>
      <tbody>${rows}</tbody>
    </table>`;
}

function formatFecha(iso) {
  if (!iso) return '—';
  try {
    const d = new Date(iso);
    return d.toLocaleDateString('es-PE', {day:'2-digit',month:'short',year:'numeric'});
  } catch { return iso.slice(0,10); }
}
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Notifications ─────────────────────────────────────────────
const SEEN_KEY = `komercia_seen_pedidos_${slug}`;
function getSeenIds() {
  try { return new Set(JSON.parse(localStorage.getItem(SEEN_KEY) || '[]')); } catch { return new Set(); }
}
function saveSeenIds(set) {
  localStorage.setItem(SEEN_KEY, JSON.stringify([...set]));
}

let notifList = [];

function checkNotificaciones(pedidos) {
  const seen = getSeenIds();
  const nuevos = pedidos.filter(p => p.estado === 'pendiente' && p.id && !seen.has(p.id));

  notifList = pedidos
    .filter(p => p.id)
    .sort((a, b) => (b.fecha || '').localeCompare(a.fecha || ''))
    .slice(0, 20)
    .map(p => ({
      id: p.id,
      title: `Nuevo pedido de ${p.nombre || 'cliente'}`,
      meta: `S/ ${parseFloat(p.total || 0).toFixed(2)} · ${formatFecha(p.fecha)}`,
      unread: p.estado === 'pendiente' && !seen.has(p.id),
    }));

  const badge = document.getElementById('bell-badge');
  if (nuevos.length > 0) {
    badge.textContent = nuevos.length > 9 ? '9+' : nuevos.length;
    badge.classList.add('visible');
  } else {
    badge.classList.remove('visible');
  }
  renderNotifDropdown();
}

function renderNotifDropdown() {
  const list = document.getElementById('notif-list');
  if (!notifList.length) {
    list.innerHTML = '<div class="notif-empty">No hay notificaciones</div>';
    return;
  }
  list.innerHTML = notifList.map(n => `
    <div class="notif-item ${n.unread ? 'unread' : ''}" onclick="verPedidos()">
      <div class="notif-title">${escHtml(n.title)}</div>
      <div class="notif-meta">${escHtml(n.meta)}</div>
    </div>
  `).join('');
}

function toggleNotifDropdown() {
  const dd = document.getElementById('notif-dropdown');
  dd.classList.toggle('open');
}
// Close on outside click
document.addEventListener('click', e => {
  if (!document.getElementById('bell-wrap').contains(e.target)) {
    document.getElementById('notif-dropdown').classList.remove('open');
  }
});

function marcarTodasVistas() {
  const seen = getSeenIds();
  notifList.forEach(n => seen.add(n.id));
  saveSeenIds(seen);
  notifList = notifList.map(n => ({...n, unread: false}));
  document.getElementById('bell-badge').classList.remove('visible');
  renderNotifDropdown();
}

function verPedidos() {
  marcarTodasVistas();
  window.location.href = '/panel/pedidos';
}

// ── Init & polling ────────────────────────────────────────────
cargarStats();
cargarProductosStats();
cargarPlan();
cargarStoreSwitcher();
setInterval(cargarStats, 30000);

// ── Store switcher ─────────────────────────────────────────────
async function cargarStoreSwitcher() {
  try {
    const r = await fetch('/api/tiendas?accion=lista');
    const d = await r.json();
    if (!d.ok || d.tiendas.length <= 1) return;
    document.getElementById('store-switcher').style.display = 'block';
    const activa = d.tiendas.find(t => t.id === (d.activa||'main')) || d.tiendas[0];
    document.getElementById('store-switcher-name').textContent = activa.nombre;
    document.getElementById('store-list').innerHTML = d.tiendas.map(t => `
      <div class="store-item${t.id===(d.activa||'main')?' active':''}" onclick="switchStore('${t.id}','${t.nombre.replace(/'/g,"\\'")}')">
        <div class="store-item-icon">${t.id==='main'?'🏪':'🛍️'}</div>
        <div class="store-item-name">${t.nombre}</div>
        ${t.id===(d.activa||'main')? '<span class="store-item-check">✓</span>':''}
      </div>`).join('');
  } catch(e) {}
}
function toggleStoreDropdown(){
  document.getElementById('store-dropdown').classList.toggle('open');
}
document.addEventListener('click', e => {
  if (!document.getElementById('store-switcher')?.contains(e.target))
    document.getElementById('store-dropdown')?.classList.remove('open');
});
async function switchStore(tid, nombre) {
  document.getElementById('store-dropdown').classList.remove('open');
  const r = await fetch('/api/tiendas',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({accion:'cambiar',tienda_id:tid})});
  const d = await r.json();
  if (d.ok) { document.getElementById('store-switcher-name').textContent = nombre; location.reload(); }
}

// ── PLAN BANNER ────────────────────────────────────────────────
async function cargarPlan() {
  try {
    const res  = await fetch('/api/plan?accion=info');
    const data = await res.json();
    if (!data.ok) return;
    const banner = document.getElementById('plan-banner');
    const configs = {
      trial: {
        bg: data.vencido ? '#ffeaea' : '#fff8e1',
        border: data.vencido ? '#e74c3c' : '#f59e0b',
        icon: data.vencido ? '⚠️' : '🕐',
        badgeBg: data.vencido ? '#e74c3c' : '#f59e0b',
        msg: data.vencido
          ? 'Tu período de prueba venció. Activa un plan para que tu tienda sea visible.'
          : `Te quedan <strong>${data.dias_restantes} día${data.dias_restantes!==1?'s':''}</strong> de prueba · límite 10 productos`,
        cta: 'Ver planes',
      },
      pro: {
        bg:'#f0fdf4', border:'#22c55e', icon:'✅', badgeBg:'#22c55e',
        msg: data.plan_expira
          ? `Plan Pro · Vence ${new Date(data.plan_expira).toLocaleDateString('es-PE',{day:'numeric',month:'long',year:'numeric'})}`
          : 'Plan Pro activo',
        cta: null,
      },
      empresarial: {
        bg:'#f5f3ff', border:'#7c3aed', icon:'🚀', badgeBg:'#7c3aed',
        msg: 'Plan Empresarial · Multi-tienda habilitado',
        cta: null,
      },
    };
    const cfg = configs[data.plan] || configs.trial;
    banner.style.cssText = `display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;border-radius:14px;padding:14px 20px;margin-bottom:20px;background:${cfg.bg};border:1.5px solid ${cfg.border}`;
    banner.innerHTML = `
      <div style="display:flex;align-items:center;gap:12px;flex:1">
        <span style="font-size:22px">${cfg.icon}</span>
        <div>
          <span style="display:inline-block;background:${cfg.badgeBg};color:#fff;border-radius:20px;padding:2px 12px;font-size:12px;font-weight:700;margin-bottom:4px">${data.plan_label}</span>
          <div style="font-size:13px;color:#444">${cfg.msg}</div>
        </div>
      </div>
      ${cfg.cta ? `<a href="/panel/planes" style="background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;border-radius:10px;padding:9px 18px;font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap">${cfg.cta}</a>` : ''}
    `;
  } catch(e) {}
}
</script>
</body>
</html>
