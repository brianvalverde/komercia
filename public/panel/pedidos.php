<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedidos – Komercia</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#f5f5f5;color:#333}
.layout{display:flex;min-height:100vh}
.sidebar{width:240px;background:#111;color:#fff;display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;transition:.3s}
.sidebar-logo{padding:24px 20px;border-bottom:1px solid #222;font-size:22px;font-weight:800;color:#ff6a00}
.sidebar nav a{display:flex;align-items:center;gap:12px;padding:13px 20px;color:#aaa;text-decoration:none;font-size:14px;transition:.2s}
.sidebar nav a:hover,.sidebar nav a.active{color:#fff;background:#222}
.sidebar nav a.active{border-left:3px solid #ff6a00}
.sidebar nav a span.icon{font-size:18px;width:22px;text-align:center}
.sidebar-footer{margin-top:auto;padding:16px 20px;border-top:1px solid #222}
.sidebar-footer a{color:#aaa;text-decoration:none;font-size:13px}
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
/* TABS */
.tabs{display:flex;gap:0;border-bottom:2px solid #eee;margin-bottom:20px;overflow-x:auto}
.tab-btn{padding:10px 18px;border:none;background:none;cursor:pointer;font-size:14px;font-weight:500;color:#888;border-bottom:2px solid transparent;margin-bottom:-2px;white-space:nowrap;font-family:inherit;transition:.2s}
.tab-btn.active{color:#ff6a00;border-bottom-color:#ff6a00;font-weight:700}
/* PEDIDOS */
.pedidos-list{display:flex;flex-direction:column;gap:14px}
.pedido-card{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.07);overflow:hidden}
.pedido-header{padding:14px 18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;border-bottom:1px solid #f5f5f5;cursor:pointer}
.pedido-id{font-size:12px;color:#aaa;font-family:monospace}
.pedido-cliente{font-weight:700;font-size:15px}
.pedido-tel{font-size:13px;color:#888}
.pedido-fecha{font-size:12px;color:#aaa}
.pedido-total{font-weight:800;font-size:16px;color:#ff6a00}
.estado-badge{padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.estado-pendiente{background:#fff8e1;color:#f59e0b}
.estado-confirmado{background:#f0fdf4;color:#16a34a}
.estado-entregado{background:#eff6ff;color:#2563eb}
.estado-cancelado{background:#fef2f2;color:#dc2626}
.pedido-body{display:none;padding:16px 18px;border-top:1px solid #f5f5f5}
.pedido-body.open{display:block}
.pedido-items{font-size:13px;color:#555;margin-bottom:12px}
.pedido-items table{width:100%;border-collapse:collapse}
.pedido-items td{padding:5px 8px;border-bottom:1px solid #f5f5f5}
.pedido-items td:last-child{text-align:right;font-weight:700}
.pedido-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
.form-inline{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.form-inline label{font-size:12px;color:#888;font-weight:600}
.form-inline select,.form-inline input{padding:6px 10px;border:1px solid #ddd;border-radius:7px;font-size:13px;font-family:inherit;outline:none}
.form-inline select:focus,.form-inline input:focus{border-color:#ff6a00}
.btn-save-estado{background:#ff6a00;color:#fff;border:none;border-radius:7px;padding:7px 14px;font-size:13px;font-weight:600;cursor:pointer;transition:.2s;font-family:inherit}
.btn-save-estado:hover{background:#e55d00}
.btn-save-estado:disabled{opacity:.6;cursor:not-allowed}
.clave-wrap{display:none;flex-direction:column;gap:6px;padding:12px;background:#f9f9f9;border-radius:8px;margin-top:8px}
.clave-wrap label{font-size:12px;color:#888;font-weight:600}
.clave-wrap input{padding:8px 12px;border:1px solid #ddd;border-radius:7px;font-size:14px;font-family:monospace;letter-spacing:3px;width:140px;outline:none}
.clave-wrap input:focus{border-color:#ff6a00}
.loading{text-align:center;padding:60px;color:#aaa}
.empty-state{text-align:center;padding:60px 20px;color:#aaa}
.empty-state .icon{font-size:64px;margin-bottom:16px}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:90}
@media(max-width:768px){
  .sidebar{transform:translateX(-240px)}
  .sidebar.open{transform:translateX(0)}
  .sidebar-overlay.show{display:block}
  .main{margin-left:0}
  .hamburger{display:block}
}
</style>
</head>
<body>
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>
<div class="layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">⚡ Komercia</div>
    <nav>
      <a href="/panel"><span class="icon">📊</span> Dashboard</a>
      <a href="/panel/productos"><span class="icon">📦</span> Productos</a>
      <a href="/panel/pedidos" class="active"><span class="icon">🛒</span> Pedidos</a>
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
        <h1>🛒 Pedidos</h1>
      </div>
      <div class="topbar-right">
        <a id="link-tienda" href="#" target="_blank" class="btn btn-outline btn-sm">🏪 Ver mi tienda</a>
        <div class="avatar" id="avatar-inicial">?</div>
      </div>
    </div>
    <div class="content">
      <div class="tabs">
        <button class="tab-btn active" onclick="filtrarEstado('')" data-e="">Todos</button>
        <button class="tab-btn" onclick="filtrarEstado('pendiente')" data-e="pendiente">⏳ Pendientes</button>
        <button class="tab-btn" onclick="filtrarEstado('confirmado')" data-e="confirmado">✅ Confirmados</button>
        <button class="tab-btn" onclick="filtrarEstado('entregado')" data-e="entregado">📬 Entregados</button>
        <button class="tab-btn" onclick="filtrarEstado('cancelado')" data-e="cancelado">❌ Cancelados</button>
      </div>
      <div id="pedidos-container" class="loading">Cargando pedidos...</div>
    </div>
  </div>
</div>

<script>
let todosLosPedidos = [];
let filtroEstado = '';

fetch('/api/sesion')
  .then(r => r.json())
  .then(d => {
    if (!d.ok) { location.href = '/login'; return; }
    document.getElementById('avatar-inicial').textContent = d.nombre.charAt(0).toUpperCase();
    document.getElementById('link-tienda').href = '/tienda/' + d.slug;
    cargarPedidos();
  });

function cargarPedidos() {
  document.getElementById('pedidos-container').innerHTML = '<div class="loading">Cargando...</div>';
  fetch('/api/pedidos?accion=listar')
    .then(r => r.json())
    .then(d => {
      if (!d.ok) { mostrarError(); return; }
      todosLosPedidos = d.pedidos;
      renderPedidos();
    })
    .catch(mostrarError);
}

function filtrarEstado(estado) {
  filtroEstado = estado;
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.e === estado));
  renderPedidos();
}

function renderPedidos() {
  const lista = filtroEstado
    ? todosLosPedidos.filter(p => p.estado === filtroEstado)
    : todosLosPedidos;

  const c = document.getElementById('pedidos-container');
  if (!lista.length) {
    c.innerHTML = `<div class="empty-state"><div class="icon">📭</div><p>No hay pedidos aquí todavía.</p></div>`;
    return;
  }

  c.innerHTML = '<div class="pedidos-list">' + lista.map(p => {
    const fecha = p.creado_en ? new Date(p.creado_en).toLocaleString('es-PE', {dateStyle:'short',timeStyle:'short'}) : '';
    const itemsHtml = (p.items||[]).map(it =>
      `<tr><td>${esc(it.nombre)}</td><td style="color:#888">x${it.qty}</td><td>S/. ${fmt(it.precio * it.qty)}</td></tr>`
    ).join('');

    return `<div class="pedido-card" id="card-${p.id}">
      <div class="pedido-header" onclick="toggleBody('${p.id}')">
        <div>
          <div class="pedido-cliente">${esc(p.nombre)}</div>
          <div class="pedido-tel">📞 ${esc(p.telefono)}</div>
          <div class="pedido-id">#${p.id}</div>
        </div>
        <div style="text-align:right">
          <div class="pedido-total">S/. ${fmt(p.total)}</div>
          <span class="estado-badge estado-${p.estado}">${estadoLabel(p.estado)}</span>
          <div class="pedido-fecha">${fecha}</div>
        </div>
      </div>
      <div class="pedido-body" id="body-${p.id}">
        <div class="pedido-items">
          <table>${itemsHtml}</table>
          ${p.direccion ? `<p style="margin-top:8px;font-size:13px;color:#666">📍 ${esc(p.direccion)}</p>` : ''}
          ${p.notas ? `<p style="margin-top:4px;font-size:13px;color:#888">📝 ${esc(p.notas)}</p>` : ''}
        </div>
        <div class="pedido-actions">
          <div class="form-inline">
            <label>Estado</label>
            <select id="sel-${p.id}" onchange="onSelectEstado('${p.id}')">
              <option value="pendiente"  ${p.estado==='pendiente' ?'selected':''}>⏳ Pendiente</option>
              <option value="confirmado" ${p.estado==='confirmado'?'selected':''}>✅ Confirmado</option>
              <option value="entregado"  ${p.estado==='entregado' ?'selected':''}>📬 Entregado</option>
              <option value="cancelado"  ${p.estado==='cancelado' ?'selected':''}>❌ Cancelado</option>
            </select>
            <button class="btn-save-estado" onclick="guardarEstado('${p.id}')">Guardar</button>
          </div>
        </div>
        <div class="clave-wrap" id="clave-wrap-${p.id}" ${p.estado==='confirmado'?'style="display:flex"':''}>
          <label>🔑 Clave de pago (Shalom / 4 dígitos)</label>
          <input type="text" id="clave-${p.id}" maxlength="10" placeholder="0000" value="${esc(p.clave||'')}">
        </div>
      </div>
    </div>`;
  }).join('') + '</div>';
}

function estadoLabel(e) {
  const map = {pendiente:'⏳ Pendiente',confirmado:'✅ Confirmado',entregado:'📬 Entregado',cancelado:'❌ Cancelado'};
  return map[e] || e;
}

function toggleBody(id) {
  const b = document.getElementById('body-' + id);
  b.classList.toggle('open');
}

function onSelectEstado(id) {
  const sel = document.getElementById('sel-' + id);
  const cw  = document.getElementById('clave-wrap-' + id);
  cw.style.display = sel.value === 'confirmado' ? 'flex' : 'none';
}

async function guardarEstado(id) {
  const sel   = document.getElementById('sel-' + id);
  const clave = document.getElementById('clave-' + id)?.value.trim() || '';
  const fd    = new FormData();
  fd.append('id', id);
  fd.append('estado', sel.value);
  if (sel.value === 'confirmado') fd.append('clave', clave);

  const btn = document.querySelector(`#card-${id} .btn-save-estado`);
  if (btn) { btn.disabled = true; btn.textContent = '...'; }

  try {
    const res  = await fetch('/api/pedidos?accion=actualizar', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      // Actualizar badge local
      const p = todosLosPedidos.find(x => x.id === id);
      if (p) { p.estado = sel.value; if (sel.value === 'confirmado') p.clave = clave; }
      // Actualizar badge en el header
      const badge = document.querySelector(`#card-${id} .estado-badge`);
      if (badge) { badge.textContent = estadoLabel(sel.value); badge.className = 'estado-badge estado-' + sel.value; }
    } else alert('Error: ' + data.error);
  } catch(e) { alert('Error de red'); }
  finally {
    if (btn) { btn.disabled = false; btn.textContent = 'Guardar'; }
  }
}

function mostrarError() {
  document.getElementById('pedidos-container').innerHTML = '<div class="empty-state"><p>Error cargando pedidos.</p></div>';
}

function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function fmt(n) { return parseFloat(n||0).toFixed(2); }
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); document.getElementById('overlay').classList.toggle('show'); }
function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('overlay').classList.remove('show'); }
function logout() { fetch('/api/logout').then(() => location.href = '/login'); }
</script>
</body>
</html>
