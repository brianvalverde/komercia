<?php
session_start();
if (empty($_SESSION['uid'])) {
    header('Location: /login');
    exit;
}
$uid  = $_SESSION['uid'];
$slug = $_SESSION['slug'];

require_once '/var/www/komercia/config/firebase.php';

// Verificar plan
$doc  = firestoreRequest('GET', "comerciantes/{$uid}");
$f    = $doc['fields'] ?? [];
$plan = $f['plan']['stringValue'] ?? 'trial';
$nombrePrincipal = $f['nombre_tienda']['stringValue'] ?? $f['nombre']['stringValue'] ?? 'Mi tienda';
$slugPrincipal   = $f['slug']['stringValue'] ?? $slug;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mis tiendas — Komercia</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#f0f2f5;color:#1a1a2e;min-height:100vh;display:flex}
.sidebar{width:220px;background:#fff;border-right:1px solid #e8eaf0;display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;padding:24px 0}
.sidebar-logo{padding:0 20px 24px;border-bottom:1px solid #e8eaf0;margin-bottom:16px}
.sidebar-logo span{font-size:22px;font-weight:700;background:linear-gradient(135deg,#ff6a00,#ee0979);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.sidebar nav a{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#555;text-decoration:none;font-size:14px;border-radius:8px;margin:2px 8px;transition:all .2s}
.sidebar nav a:hover{background:#fff5f0;color:#ff6a00}
.sidebar nav a.active{background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;font-weight:600}
.sidebar nav a svg{width:18px;height:18px;flex-shrink:0}
.topbar{position:fixed;top:0;left:220px;right:0;height:60px;background:#fff;border-bottom:1px solid #e8eaf0;display:flex;align-items:center;justify-content:space-between;padding:0 28px;z-index:90}
.topbar-left h2{font-size:16px;font-weight:700}
.topbar-left p{font-size:12px;color:#888;margin-top:2px}
.main{margin-left:220px;padding-top:60px;flex:1}
.content{padding:28px;max-width:900px;margin:0 auto}

/* ── Upgrade banner ── */
.upgrade-banner{background:linear-gradient(135deg,#ff6a00,#ee0979);border-radius:16px;padding:32px;text-align:center;color:#fff;margin-bottom:28px}
.upgrade-banner h2{font-size:22px;font-weight:800;margin-bottom:10px}
.upgrade-banner p{font-size:14px;opacity:.9;margin-bottom:20px;max-width:460px;margin-left:auto;margin-right:auto}
.upgrade-banner a{display:inline-block;background:#fff;color:#ff6a00;font-weight:700;font-size:14px;padding:12px 28px;border-radius:12px;text-decoration:none}

/* ── Store cards ── */
.stores-grid{display:flex;flex-direction:column;gap:14px}
.store-card{background:#fff;border-radius:16px;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:2px solid transparent;transition:border .2s}
.store-card.activa{border-color:#ff6a00}
.store-card.activa .sc-name::after{content:'Activa';display:inline-block;background:#fff5f0;color:#ff6a00;font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px;margin-left:10px;vertical-align:middle}
.sc-icon{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#ff6a00,#ee0979);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
.sc-info{flex:1}
.sc-name{font-size:15px;font-weight:700;color:#1a1a2e}
.sc-slug{font-size:13px;color:#aaa;margin-top:3px}
.sc-badge-main{display:inline-block;background:#f0f2f5;color:#888;font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px;margin-left:6px}
.sc-actions{display:flex;gap:8px}
.btn{padding:8px 14px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .2s;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;gap:5px}
.btn-primary{background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff}.btn-primary:hover{opacity:.88}
.btn-gray{background:#f0f2f5;color:#555}.btn-gray:hover{background:#e0e0e0}
.btn-red{background:#ffebee;color:#c62828}.btn-red:hover{background:#ffcdd2}
.btn-outline{background:#fff;border:1.5px solid #ff6a00;color:#ff6a00}.btn-outline:hover{background:#fff5f0}

/* ── Nueva tienda form ── */
.new-store-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.05);margin-top:20px}
.new-store-card h3{font-size:15px;font-weight:700;margin-bottom:18px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
label{display:block;font-size:13px;font-weight:600;color:#444;margin-bottom:6px}
input{width:100%;padding:11px 14px;border:1.5px solid #e0e0e0;border-radius:10px;font-size:14px;font-family:inherit;outline:none;transition:border .2s}
input:focus{border-color:#ff6a00}
.slug-preview{font-size:12px;color:#aaa;margin-top:5px}
.slug-preview span{color:#ff6a00;font-weight:600}
.form-actions{display:flex;justify-content:flex-end;margin-top:18px}

/* ── Toast ── */
#toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);padding:12px 22px;border-radius:12px;font-size:14px;font-weight:500;opacity:0;transition:all .3s;z-index:999;pointer-events:none;white-space:nowrap;background:#1a1a2e;color:#fff}
#toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
#toast.ok{background:#1a7a3e}#toast.err{background:#c0392b}
.confirm-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1100;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;visibility:hidden;transition:all .2s}
.confirm-overlay.open{opacity:1;visibility:visible}
.confirm-box{background:#fff;border-radius:20px;width:100%;max-width:400px;box-shadow:0 24px 64px rgba(0,0,0,.2);transform:scale(.94);transition:transform .2s;overflow:hidden}
.confirm-overlay.open .confirm-box{transform:scale(1)}
.confirm-icon{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;margin:28px auto 0}
.confirm-icon.danger{background:#fff0f0}
.confirm-icon.warning{background:#fff8e1}
.confirm-box h3{font-size:17px;font-weight:700;color:#1a1a2e;text-align:center;margin:14px 24px 8px}
.confirm-box p{font-size:13px;color:#777;text-align:center;margin:0 24px 24px;line-height:1.6}
.confirm-btns{display:flex;border-top:1px solid #f0f0f0}
.confirm-btns button{flex:1;padding:16px;font-size:14px;font-weight:600;border:none;cursor:pointer;transition:background .15s;font-family:inherit}
.confirm-btns .cb-cancel{background:#fff;color:#888;border-right:1px solid #f0f0f0;border-radius:0 0 0 20px}
.confirm-btns .cb-cancel:hover{background:#f8f8f8}
.confirm-btns .cb-confirm.danger{background:#fff;color:#e03;border-radius:0 0 20px 0}
.confirm-btns .cb-confirm.danger:hover{background:#fff5f5}
.confirm-btns .cb-confirm.warning{background:#fff;color:#ff6a00;border-radius:0 0 20px 0}
.confirm-btns .cb-confirm.warning:hover{background:#fff8f0}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="sidebar-logo"><span>Komercia</span></div>
  <nav>
    <a href="/panel">
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
    <a href="/panel/tiendas" class="active">
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

<div class="topbar">
  <div class="topbar-left">
    <h2>Mis tiendas</h2>
    <p>Gestiona y cambia entre tus tiendas</p>
  </div>
</div>

<div class="main"><div class="content">

<?php if ($plan !== 'empresarial'): ?>
  <!-- Banner upgrade -->
  <div class="upgrade-banner">
    <h2>🏪 Multi-tienda</h2>
    <p>Crea y gestiona múltiples tiendas desde un solo panel. Disponible en el plan Empresarial.</p>
    <a href="/panel/planes">Ver planes →</a>
  </div>
  <!-- Mostrar solo la tienda principal (solo lectura) -->
  <div class="stores-grid">
    <div class="store-card activa">
      <div class="sc-icon">🏪</div>
      <div class="sc-info">
        <div class="sc-name"><?= htmlspecialchars($nombrePrincipal) ?> <span class="sc-badge-main">Principal</span></div>
        <div class="sc-slug">komercia.online/tienda/<?= htmlspecialchars($slugPrincipal) ?></div>
      </div>
      <div class="sc-actions">
        <a href="https://komercia.online/tienda/<?= htmlspecialchars($slugPrincipal) ?>" target="_blank" class="btn btn-gray">🔗 Ver tienda</a>
      </div>
    </div>
  </div>

<?php else: ?>
  <!-- Plan empresarial: gestión completa -->
  <div class="stores-grid" id="stores-grid">
    <!-- Se carga con JS -->
    <div style="text-align:center;padding:40px;color:#aaa">Cargando tiendas…</div>
  </div>

  <!-- Formulario nueva tienda -->
  <div class="new-store-card">
    <h3>➕ Agregar nueva tienda</h3>
    <div class="form-row">
      <div>
        <label>Nombre de la tienda</label>
        <input type="text" id="ns-nombre" placeholder="Ej: Tienda Centro" oninput="genSlug()">
      </div>
      <div>
        <label>Slug (URL)</label>
        <input type="text" id="ns-slug" placeholder="tienda-centro">
        <div class="slug-preview">komercia.online/tienda/<span id="slug-prev">—</span></div>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" onclick="crearTienda()">Crear tienda</button>
    </div>
  </div>
<?php endif; ?>

</div></div>
<!-- Modal confirmación -->
<div class="confirm-overlay" id="confirm-overlay">
  <div class="confirm-box">
    <div class="confirm-icon" id="confirm-icon">⚠️</div>
    <h3 id="confirm-title">¿Estás seguro?</h3>
    <p id="confirm-msg"></p>
    <div class="confirm-btns">
      <button class="cb-cancel" onclick="confirmResolve(false)">Cancelar</button>
      <button class="cb-confirm" id="confirm-ok-btn" onclick="confirmResolve(true)">Confirmar</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
const ES_EMPRESARIAL = <?= $plan === 'empresarial' ? 'true' : 'false' ?>;
const SLUG_PRINCIPAL = '<?= htmlspecialchars($slugPrincipal) ?>';

<?php if ($plan === 'empresarial'): ?>
// ── Cargar tiendas ────────────────────────────────────────────
async function cargarTiendas() {
  const r = await fetch('/api/tiendas?accion=lista');
  const d = await r.json();
  if (!d.ok) return;
  const grid = document.getElementById('stores-grid');
  if (!d.tiendas.length) { grid.innerHTML = '<div style="text-align:center;padding:40px;color:#aaa">Sin tiendas</div>'; return; }
  grid.innerHTML = d.tiendas.map(t => {
    const esActiva = t.id === (d.activa || 'main');
    const esPrincipal = t.id === 'main';
    return `<div class="store-card${esActiva?' activa':''}">
      <div class="sc-icon">${esPrincipal ? '🏪' : '🛍️'}</div>
      <div class="sc-info">
        <div class="sc-name">${esc(t.nombre)}${esPrincipal ? '<span class="sc-badge-main">Principal</span>' : ''}</div>
        <div class="sc-slug">komercia.online/tienda/${esc(t.slug)}</div>
      </div>
      <div class="sc-actions">
        ${!esActiva ? `<button class="btn btn-primary" onclick="cambiarTienda('${t.id}','${esc(t.nombre)}')">⚡ Activar</button>` : '<span style="font-size:13px;color:#ff6a00;font-weight:600">✓ Activa</span>'}
        <a href="https://komercia.online/tienda/${esc(t.slug)}" target="_blank" class="btn btn-gray">🔗 Ver</a>
        ${!esPrincipal ? `<button class="btn btn-red" onclick="eliminarTienda('${t.id}','${esc(t.nombre)}')">🗑</button>` : ''}
      </div>
    </div>`;
  }).join('');
}

// ── Cambiar tienda activa ─────────────────────────────────────
async function cambiarTienda(tid, nombre) {
  const r = await fetch('/api/tiendas', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({accion:'cambiar', tienda_id: tid})
  });
  const d = await r.json();
  if (d.ok) {
    toast('✅ Cambiado a: ' + nombre, 'ok');
    setTimeout(() => { window.location.href = '/panel/#'; }, 1000);
  } else toast('❌ ' + (d.error||'Error'), 'err');
}

// ── Crear tienda ──────────────────────────────────────────────
async function crearTienda() {
  const nombre = document.getElementById('ns-nombre').value.trim();
  const slug   = document.getElementById('ns-slug').value.trim();
  if (!nombre || !slug) { toast('Completa nombre y slug', 'err'); return; }
  const r = await fetch('/api/tiendas', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({accion:'crear', nombre, slug})
  });
  const d = await r.json();
  if (d.ok) {
    toast('✅ Tienda creada: ' + d.nombre, 'ok');
    document.getElementById('ns-nombre').value = '';
    document.getElementById('ns-slug').value   = '';
    document.getElementById('slug-prev').textContent = '—';
    cargarTiendas();
  } else toast('❌ ' + (d.error||'Error'), 'err');
}

// ── Eliminar tienda ───────────────────────────────────────────
async function eliminarTienda(tid, nombre) {
  const ok = await modalConfirm({
    icon: '🗑️', tipo: 'danger',
    titulo: '¿Eliminar tienda?',
    mensaje: `Se eliminará <strong>${nombre}</strong> y todos sus datos. Esta acción no se puede deshacer.`,
    btnTexto: 'Sí, eliminar'
  });
  if (!ok) return;
  const r = await fetch('/api/tiendas', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({accion:'eliminar', tienda_id: tid})
  });
  const d = await r.json();
  if (d.ok) { toast('🗑 Tienda eliminada', 'ok'); cargarTiendas(); }
  else toast('❌ ' + (d.error||'Error'), 'err');
}

// ── Generar slug automático ───────────────────────────────────
function genSlug() {
  const n = document.getElementById('ns-nombre').value;
  const s = n.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
  document.getElementById('ns-slug').value = s;
  document.getElementById('slug-prev').textContent = s || '—';
}
document.getElementById('ns-slug').addEventListener('input', function(){
  document.getElementById('slug-prev').textContent = this.value || '—';
});

cargarTiendas();
<?php endif; ?>

function esc(s){return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}

// ── Modal de confirmación ────────────────────────────────────
let confirmResolve = () => {};
function modalConfirm({ icon='⚠️', tipo='danger', titulo='¿Estás seguro?', mensaje='', btnTexto='Confirmar' } = {}) {
  return new Promise(resolve => {
    document.getElementById('confirm-icon').textContent = icon;
    document.getElementById('confirm-icon').className = `confirm-icon ${tipo}`;
    document.getElementById('confirm-title').textContent = titulo;
    document.getElementById('confirm-msg').innerHTML = mensaje;
    const btn = document.getElementById('confirm-ok-btn');
    btn.textContent = btnTexto;
    btn.className = `cb-confirm ${tipo}`;
    confirmResolve = (val) => {
      document.getElementById('confirm-overlay').classList.remove('open');
      resolve(val);
    };
    document.getElementById('confirm-overlay').classList.add('open');
  });
}
document.getElementById('confirm-overlay').addEventListener('click', function(e) {
  if (e.target === this) confirmResolve(false);
});

let _tt;
function toast(msg,type=''){
  const el=document.getElementById('toast');
  el.textContent=msg;el.className='show '+(type==='ok'?'ok':type==='err'?'err':'');
  clearTimeout(_tt);_tt=setTimeout(()=>el.className='',3000);
}
</script>
</body>
</html>
