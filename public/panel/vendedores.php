<?php
session_start();
if (empty($_SESSION['uid']) || empty($_SESSION['slug'])) {
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
<title>Vendedores — Komercia</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
.topbar-right{display:flex;align-items:center;gap:12px}

.main{margin-left:220px;padding-top:60px;flex:1;min-height:100vh}
.content{padding:32px}

.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}
.page-header h1{font-size:22px;font-weight:700}
.page-header p{font-size:13px;color:#888;margin-top:3px}
.btn-primary{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;transition:opacity .2s;text-decoration:none}
.btn-primary:hover{opacity:.88}

.store-badge{display:inline-flex;align-items:center;gap:6px;background:#fff5f0;border:1.5px solid #ffd0b0;border-radius:8px;padding:5px 12px;font-size:13px;font-weight:600;color:#ff6a00;margin-bottom:20px}

/* Cards vendedores */
.vend-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px}
.vend-card{background:#fff;border-radius:16px;border:1.5px solid #e8eaf0;padding:20px;transition:box-shadow .2s;position:relative}
.vend-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.08)}
.vend-avatar{width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#ff6a00,#ee0979);display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:12px}
.vend-nombre{font-size:16px;font-weight:700;margin-bottom:4px}
.vend-email{font-size:12px;color:#888;margin-bottom:2px}
.vend-telefono{font-size:12px;color:#888;margin-bottom:12px}
.vend-codigo-box{background:#f8f9fb;border-radius:10px;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.vend-codigo-label{font-size:11px;color:#aaa;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.vend-codigo{font-size:20px;font-weight:800;letter-spacing:4px;color:#1a1a2e;font-family:monospace}
.vend-codigo-actions{display:flex;gap:6px}
.btn-icon{background:none;border:1.5px solid #e8eaf0;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;color:#555}
.btn-icon:hover{border-color:#ff6a00;color:#ff6a00}
.btn-icon svg{width:14px;height:14px}
.vend-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.btn-sm{padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;border:none;cursor:pointer;transition:all .2s}
.btn-sm.edit{background:#f0f2f5;color:#444}
.btn-sm.edit:hover{background:#e8eaf0}
.btn-sm.delete{background:#fff0f0;color:#e03}
.btn-sm.delete:hover{background:#ffe0e0}
.btn-sm.wa{background:#e8f7ef;color:#25d366;display:flex;align-items:center;gap:4px}
.btn-sm.wa:hover{background:#d0f0de}
.btn-sm.wa svg{width:14px;height:14px}
.badge-activo{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:99px;font-size:11px;font-weight:700;background:#e8f7ef;color:#25a855}
.badge-inactivo{background:#fff0f0;color:#e03}
.vend-status{position:absolute;top:16px;right:16px}

/* Empty state */
.empty-state{text-align:center;padding:60px 20px;color:#aaa}
.empty-state svg{width:56px;height:56px;margin-bottom:16px;opacity:.3}
.empty-state h3{font-size:18px;color:#555;margin-bottom:8px}
.empty-state p{font-size:14px}

/* Skeleton */
.skeleton{background:linear-gradient(90deg,#f0f0f0 25%,#e8e8e8 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;border-radius:8px}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}

/* Modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;visibility:hidden;transition:all .2s}
.modal-overlay.open{opacity:1;visibility:visible}
.modal{background:#fff;border-radius:20px;width:100%;max-width:460px;box-shadow:0 20px 60px rgba(0,0,0,.18);transform:scale(.95);transition:transform .2s}
.modal-overlay.open .modal{transform:scale(1)}
.modal-head{padding:24px 24px 0;display:flex;align-items:center;justify-content:space-between}
.modal-head h3{font-size:18px;font-weight:700}
.modal-close{background:none;border:none;font-size:22px;color:#aaa;cursor:pointer;line-height:1;padding:4px}
.modal-close:hover{color:#333}
.modal-body{padding:24px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;font-weight:600;color:#444;margin-bottom:6px}
.form-group input{width:100%;padding:10px 14px;border:1.5px solid #e0e0e0;border-radius:10px;font-size:14px;font-family:inherit;outline:none;transition:border-color .2s}
.form-group input:focus{border-color:#ff6a00}
.form-group small{display:block;font-size:11px;color:#aaa;margin-top:4px}
.form-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:20px}
.btn-cancel{padding:10px 20px;background:#f0f2f5;color:#444;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer}
.btn-cancel:hover{background:#e8eaf0}
.toast{position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:12px;font-size:13px;font-weight:600;z-index:9999;opacity:0;transform:translateY(10px);transition:all .3s;pointer-events:none}
.toast.show{opacity:1;transform:translateY(0)}
.toast.success{background:#1a1a2e;color:#fff}
.toast.error{background:#e03;color:#fff}

/* Login link info */
.login-info{background:#f0f8ff;border:1.5px solid #b0d8f0;border-radius:12px;padding:14px 18px;margin-bottom:24px;font-size:13px;color:#1a5f8a}
.login-info strong{display:block;margin-bottom:4px}
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
    <a href="/panel/tiendas">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Mis tiendas
    </a>
    <a href="/panel/vendedores" class="active">
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
    <h2>Vendedores</h2>
    <p id="topbar-tienda">Cargando tienda...</p>
  </div>
  <div class="topbar-right">
    <a href="/logout" style="display:flex;align-items:center;gap:6px;padding:7px 14px;background:#fff5f0;color:#ff6a00;border:1.5px solid #ffd0b0;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Salir
    </a>
  </div>
</div>

<div class="main">
  <div class="content">

    <div class="page-header">
      <div>
        <h1>Vendedores</h1>
        <p>Gestiona los vendedores de tu tienda activa</p>
      </div>
      <button class="btn-primary" onclick="abrirModal()">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nuevo vendedor
      </button>
    </div>

    <div id="store-badge-wrap"></div>

    <div class="login-info">
      <strong>¿Cómo acceden los vendedores?</strong>
      Comparte el link <strong id="link-vendedor">cargando...</strong> junto con el código de cada vendedor. Ellos solo ven sus pedidos asignados.
    </div>

    <div id="vend-container">
      <!-- Skeleton mientras carga -->
      <div class="vend-grid" id="skeleton-grid">
        <?php for($i=0;$i<3;$i++): ?>
        <div class="vend-card">
          <div class="skeleton" style="width:52px;height:52px;border-radius:14px;margin-bottom:12px"></div>
          <div class="skeleton" style="height:18px;width:60%;margin-bottom:8px"></div>
          <div class="skeleton" style="height:13px;width:80%;margin-bottom:16px"></div>
          <div class="skeleton" style="height:44px;border-radius:10px;margin-bottom:14px"></div>
          <div style="display:flex;gap:8px">
            <div class="skeleton" style="height:30px;width:70px;border-radius:8px"></div>
            <div class="skeleton" style="height:30px;width:70px;border-radius:8px"></div>
          </div>
        </div>
        <?php endfor; ?>
      </div>
      <div id="vend-grid" class="vend-grid" style="display:none"></div>
    </div>

  </div>
</div>

<!-- Modal crear/editar vendedor -->
<div class="modal-overlay" id="modal-overlay" onclick="cerrarModalFuera(event)">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modal-title">Nuevo vendedor</h3>
      <button class="modal-close" onclick="cerrarModal()">×</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="edit-id">
      <div class="form-group">
        <label>Nombre completo *</label>
        <input type="text" id="f-nombre" placeholder="Ej: Juan Pérez">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" id="f-email" placeholder="vendedor@email.com">
        <small>Opcional, solo para referencia</small>
      </div>
      <div class="form-group">
        <label>Teléfono / WhatsApp</label>
        <input type="text" id="f-telefono" placeholder="51912345678">
        <small>Incluir código de país para usar WhatsApp</small>
      </div>
      <div class="form-actions">
        <button class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
        <button class="btn-primary" onclick="guardarVendedor()">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
let vendedores = [];
let tiendaActiva = 'main';
let tiendaNombre = '';

async function cargar() {
  try {
    const r = await fetch('/api/vendedores?accion=lista');
    const d = await r.json();
    if (!d.ok) throw new Error(d.error);
    vendedores  = d.vendedores || [];
    tiendaActiva = d.tienda_activa || 'main';
    renderizar();
  } catch(e) {
    console.error(e);
    showToast('Error cargando vendedores: '+e.message, 'error');
  }
}

function renderizar() {
  document.getElementById('skeleton-grid').style.display = 'none';
  const grid = document.getElementById('vend-grid');
  grid.style.display = 'grid';

  // Link de acceso
  const slug = '<?= htmlspecialchars($slug) ?>';
  const linkBase = window.location.origin + '/vendedor/' + slug;
  document.getElementById('link-vendedor').textContent = linkBase;

  if (vendedores.length === 0) {
    grid.innerHTML = `
      <div class="empty-state" style="grid-column:1/-1">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        <h3>Sin vendedores aún</h3>
        <p>Crea tu primer vendedor para comenzar</p>
      </div>`;
    return;
  }

  grid.innerHTML = vendedores.map(v => `
    <div class="vend-card">
      <div class="vend-status">
        <span class="badge-${v.activo ? 'activo' : 'inactivo'}">${v.activo ? '● Activo' : '● Inactivo'}</span>
      </div>
      <div class="vend-avatar">👤</div>
      <div class="vend-nombre">${esc(v.nombre)}</div>
      ${v.email ? `<div class="vend-email">✉ ${esc(v.email)}</div>` : ''}
      ${v.telefono ? `<div class="vend-telefono">📞 ${esc(v.telefono)}</div>` : '<div style="margin-bottom:14px"></div>'}
      <div class="vend-codigo-box">
        <div>
          <div class="vend-codigo-label">Código de acceso</div>
          <div class="vend-codigo">${esc(v.codigo)}</div>
        </div>
        <div class="vend-codigo-actions">
          <button class="btn-icon" title="Copiar código" onclick="copiarCodigo('${esc(v.codigo)}')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
          </button>
          <button class="btn-icon" title="Regenerar código" onclick="regenerarCodigo('${v.id}')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
          </button>
        </div>
      </div>
      <div class="vend-actions">
        <button class="btn-sm edit" onclick="editarVendedor('${v.id}')">✏️ Editar</button>
        ${v.telefono ? `
        <button class="btn-sm wa" onclick="enviarWA('${esc(v.telefono)}','${esc(v.nombre)}','${esc(v.codigo)}')">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.117.554 4.103 1.523 5.824L0 24l6.35-1.494A11.934 11.934 0 0012 24c6.627 0 12-5.373 12-12S18.626 0 11.999 0zm.001 21.818a9.82 9.82 0 01-5.013-1.375l-.36-.213-3.767.887.948-3.667-.233-.374A9.816 9.816 0 012.182 12c0-5.42 4.4-9.818 9.818-9.818 5.42 0 9.818 4.399 9.818 9.818 0 5.42-4.399 9.818-9.818 9.818z"/></svg>
          WhatsApp
        </button>` : ''}
        <button class="btn-sm delete" onclick="eliminarVendedor('${v.id}','${esc(v.nombre)}')">🗑 Eliminar</button>
      </div>
    </div>
  `).join('');
}

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

async function guardarVendedor() {
  const id     = document.getElementById('edit-id').value;
  const nombre = document.getElementById('f-nombre').value.trim();
  const email  = document.getElementById('f-email').value.trim();
  const telefono = document.getElementById('f-telefono').value.trim();

  if (!nombre) { showToast('El nombre es requerido','error'); return; }

  const body = id
    ? { accion:'actualizar', vendedor_id:id, nombre, email, telefono }
    : { accion:'crear', nombre, email, telefono };

  try {
    const r = await fetch('/api/vendedores', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body) });
    const d = await r.json();
    if (!d.ok) throw new Error(d.error);
    cerrarModal();
    showToast(id ? 'Vendedor actualizado' : `Vendedor creado — Código: ${d.codigo}`, 'success');
    cargar();
  } catch(e) {
    showToast('Error: '+e.message, 'error');
  }
}

async function regenerarCodigo(vid) {
  if (!confirm('¿Regenerar el código de acceso? El código anterior dejará de funcionar.')) return;
  try {
    const r = await fetch('/api/vendedores', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({accion:'regenerar_codigo',vendedor_id:vid}) });
    const d = await r.json();
    if (!d.ok) throw new Error(d.error);
    showToast(`Nuevo código: ${d.codigo}`, 'success');
    cargar();
  } catch(e) {
    showToast('Error: '+e.message, 'error');
  }
}

async function eliminarVendedor(vid, nombre) {
  if (!confirm(`¿Eliminar al vendedor "${nombre}"?`)) return;
  try {
    const r = await fetch('/api/vendedores', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({accion:'eliminar',vendedor_id:vid}) });
    const d = await r.json();
    if (!d.ok) throw new Error(d.error);
    showToast('Vendedor eliminado', 'success');
    cargar();
  } catch(e) {
    showToast('Error: '+e.message, 'error');
  }
}

function editarVendedor(vid) {
  const v = vendedores.find(x => x.id === vid);
  if (!v) return;
  document.getElementById('modal-title').textContent = 'Editar vendedor';
  document.getElementById('edit-id').value  = v.id;
  document.getElementById('f-nombre').value = v.nombre;
  document.getElementById('f-email').value  = v.email || '';
  document.getElementById('f-telefono').value = v.telefono || '';
  abrirModal();
}

function abrirModal() {
  if (!document.getElementById('edit-id').value) {
    document.getElementById('modal-title').textContent = 'Nuevo vendedor';
    document.getElementById('f-nombre').value = '';
    document.getElementById('f-email').value  = '';
    document.getElementById('f-telefono').value = '';
  }
  document.getElementById('modal-overlay').classList.add('open');
}
function cerrarModal() {
  document.getElementById('modal-overlay').classList.remove('open');
  document.getElementById('edit-id').value = '';
}
function cerrarModalFuera(e) { if(e.target === document.getElementById('modal-overlay')) cerrarModal(); }

function copiarCodigo(codigo) {
  navigator.clipboard.writeText(codigo).then(()=>showToast('Código copiado: '+codigo,'success'));
}

function enviarWA(telefono, nombre, codigo) {
  const slug  = '<?= htmlspecialchars($slug) ?>';
  const link  = window.location.origin + '/vendedor/' + slug;
  const texto = encodeURIComponent(`¡Hola ${nombre}! 👋\nTe invitamos a acceder al panel de vendedor de nuestra tienda.\n\n🔗 Link: ${link}\n🔑 Tu código: *${codigo}*\n\n¡Ingresa y revisa tus pedidos!`);
  window.open(`https://wa.me/${telefono}?text=${texto}`, '_blank');
}

function showToast(msg, type='success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = `toast ${type} show`;
  clearTimeout(t._timer);
  t._timer = setTimeout(()=>t.classList.remove('show'), 3500);
}

// Init
document.getElementById('topbar-tienda').textContent =
  '<?= htmlspecialchars($_SESSION['tienda_nombre'] ?? $_SESSION['slug'] ?? 'Tienda principal') ?>';
cargar();
</script>
</body>
</html>
