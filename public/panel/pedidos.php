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
<title>Pedidos — Komercia</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#f0f2f5;color:#1a1a2e;min-height:100vh;display:flex;overflow-x:hidden}

/* ── Sidebar ─────────────────────────────────────── */
.sidebar{width:220px;background:#fff;border-right:1px solid #e8eaf0;display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;padding:24px 0;flex-shrink:0}
.sidebar-logo{padding:0 20px 24px;border-bottom:1px solid #e8eaf0;margin-bottom:16px}
.sidebar-logo span{font-size:22px;font-weight:700;background:linear-gradient(135deg,#ff6a00,#ee0979);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.sidebar nav a{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#555;text-decoration:none;font-size:14px;border-radius:8px;margin:2px 8px;transition:all .2s}
.sidebar nav a:hover{background:#fff5f0;color:#ff6a00}
.sidebar nav a.active{background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;font-weight:600}
.sidebar nav a svg{width:18px;height:18px;flex-shrink:0}

/* ── Topbar ──────────────────────────────────────── */
.topbar{position:fixed;top:0;left:220px;right:0;height:58px;background:#fff;border-bottom:1px solid #e8eaf0;display:flex;align-items:center;gap:14px;padding:0 24px;z-index:90}
.topbar h1{font-size:16px;font-weight:700;color:#1a1a2e;margin-right:auto}

/* ── Layout: table + detail ──────────────────────── */
.main{margin-left:220px;padding-top:58px;flex:1;display:flex;height:100vh;overflow:hidden}
.table-panel{flex:1;overflow:auto;padding:20px}
.detail-panel{width:0;min-width:0;overflow:hidden;background:#fff;border-left:1px solid #e8eaf0;transition:width .3s,min-width .3s;position:relative;flex-shrink:0}
.detail-panel.open{width:340px;min-width:340px;overflow-y:auto}

/* ── Toolbar ─────────────────────────────────────── */
.toolbar{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.tab-btn{padding:7px 16px;border-radius:8px;border:1.5px solid #e0e0e0;background:#fff;font-size:13px;font-weight:600;cursor:pointer;color:#555;transition:all .2s}
.tab-btn.active{background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;border-color:transparent}
.tab-btn:hover:not(.active){border-color:#ff6a00;color:#ff6a00}
.search-input{flex:1;max-width:220px;padding:8px 14px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;outline:none;transition:border-color .2s}
.search-input:focus{border-color:#ff6a00}
.btn-csv{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#f5f5f5;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-weight:600;color:#444;cursor:pointer;transition:all .2s;margin-left:auto}
.btn-csv:hover{border-color:#ff6a00;color:#ff6a00}
.btn-csv svg{width:14px;height:14px}

/* ── Table ───────────────────────────────────────── */
.table-wrap{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden}
table{width:100%;border-collapse:collapse;font-size:13.5px}
thead tr{background:#fafafa}
th{padding:11px 14px;text-align:left;font-size:11px;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #f0f0f0;white-space:nowrap;user-select:none;cursor:pointer}
th:hover{color:#ff6a00}
th .sort-arrow{margin-left:4px;opacity:.4}
th.sorted .sort-arrow{opacity:1;color:#ff6a00}
td{padding:11px 14px;border-bottom:1px solid #f5f5f5;color:#333;white-space:nowrap}
tbody tr{cursor:pointer;transition:background .12s}
tbody tr:hover td{background:#fff8f4}
tbody tr.selected td{background:#fff0e8}
tbody tr:last-child td{border-bottom:none}

/* ── Status badge ────────────────────────────────── */
.badge{display:inline-block;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700}
.badge.pendiente{background:#fff3e0;color:#f57c00}
.badge.enviado{background:#e3f2fd;color:#1565c0}
.badge.entregado{background:#e8f5e9;color:#2e7d32}
.badge.cancelado{background:#fce4ec;color:#c62828}

/* ── Detail panel ────────────────────────────────── */
.detail-close{position:absolute;top:14px;right:14px;background:none;border:none;cursor:pointer;font-size:18px;color:#aaa;line-height:1}
.detail-close:hover{color:#ee0979}
.detail-inner{padding:22px}
.detail-title{font-size:15px;font-weight:700;color:#1a1a2e;margin-bottom:4px}
.detail-sub{font-size:12px;color:#aaa;margin-bottom:20px}
.detail-row{display:flex;justify-content:space-between;align-items:flex-start;padding:9px 0;border-bottom:1px solid #f5f5f5;gap:12px}
.detail-row:last-of-type{border-bottom:none}
.detail-key{font-size:12px;color:#888;font-weight:600;flex-shrink:0}
.detail-val{font-size:13px;color:#1a1a2e;text-align:right;word-break:break-word}
.detail-items{margin-top:16px}
.detail-items h4{font-size:12px;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px}
.item-row{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #f8f8f8;font-size:13px}
.item-row:last-child{border-bottom:none}
.item-name{color:#1a1a2e;flex:1}
.item-qty{color:#888;font-size:12px;margin:0 10px}
.item-price{font-weight:600;color:#ff6a00}

/* ── Status change ───────────────────────────────── */
.status-select-wrap{margin-top:20px}
.status-select-wrap label{font-size:12px;font-weight:700;color:#aaa;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em}
.status-select{width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:10px;font-size:13px;font-family:inherit;outline:none;cursor:pointer;transition:border-color .2s}
.status-select:focus{border-color:#ff6a00}
.btn-update-status{width:100%;margin-top:10px;padding:10px;background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:opacity .2s}
.btn-update-status:hover{opacity:.88}
.btn-update-status:disabled{opacity:.5;cursor:not-allowed}

/* ── Empty / loading ─────────────────────────────── */
.empty{text-align:center;padding:60px 20px;color:#bbb;font-size:14px}
.skeleton-row td{color:transparent;background:linear-gradient(90deg,#f0f0f0 25%,#fafafa 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.2s infinite;border-radius:4px}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}

/* ── Toast ───────────────────────────────────────── */
#toast{position:fixed;bottom:24px;right:24px;background:#1a1a2e;color:#fff;padding:11px 20px;border-radius:12px;font-size:14px;font-weight:500;opacity:0;transform:translateY(10px);transition:all .3s;z-index:999;pointer-events:none}
#toast.show{opacity:1;transform:translateY(0)}
#toast.error{background:#ee0979}

@media(max-width:768px){
  .sidebar{display:none}
  .main,.topbar{margin-left:0;left:0}
  .detail-panel.open{width:100%;position:fixed;top:0;left:0;right:0;bottom:0;z-index:200;height:100vh;overflow-y:auto;min-width:unset}
}
</style>
</head>
<body>

<!-- Sidebar -->
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
    <a href="/panel/pedidos" class="active">
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
  <h1>🧾 Pedidos</h1>
  <span id="topbar-count" style="font-size:13px;color:#aaa"></span>
</div>

<!-- Main -->
<div class="main">
  <!-- Table panel -->
  <div class="table-panel">
    <div class="toolbar">
      <button class="tab-btn active" data-estado="">Todos</button>
      <button class="tab-btn" data-estado="pendiente">Pendientes</button>
      <button class="tab-btn" data-estado="enviado">Enviados</button>
      <button class="tab-btn" data-estado="entregado">Entregados</button>
      <button class="tab-btn" data-estado="cancelado">Cancelados</button>
      <input class="search-input" id="search-input" type="text" placeholder="🔍 Buscar cliente...">
      <button class="btn-csv" onclick="exportarCSV()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Exportar CSV
      </button>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th onclick="sortBy('nombre')" id="th-nombre"># / Cliente <span class="sort-arrow">↕</span></th>
            <th onclick="sortBy('telefono')" id="th-telefono">Teléfono <span class="sort-arrow">↕</span></th>
            <th onclick="sortBy('total')" id="th-total">Total <span class="sort-arrow">↕</span></th>
            <th onclick="sortBy('estado')" id="th-estado">Estado <span class="sort-arrow">↕</span></th>
            <th onclick="sortBy('fecha')" id="th-fecha">Fecha <span class="sort-arrow">↕</span></th>
          </tr>
        </thead>
        <tbody id="tabla-body">
          <!-- skeleton rows -->
          <tr class="skeleton-row"><td>████████</td><td>████████</td><td>███</td><td>████████</td><td>████████</td></tr>
          <tr class="skeleton-row"><td>████████</td><td>████████</td><td>███</td><td>████████</td><td>████████</td></tr>
          <tr class="skeleton-row"><td>████████</td><td>████████</td><td>███</td><td>████████</td><td>████████</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Detail panel -->
  <div class="detail-panel" id="detail-panel">
    <div class="detail-inner" id="detail-inner"></div>
  </div>
</div>

<div id="toast"></div>

<script>
const slug = '<?= htmlspecialchars($slug) ?>';

let allPedidos = [];
let filteredPedidos = [];
let selectedId = null;
let filterEstado = '';
let searchQ = '';
let sortKey = 'fecha';
let sortDir = -1; // -1 = desc, 1 = asc

// ── Toast ─────────────────────────────────────────────────────
function toast(msg, isError = false) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'show' + (isError ? ' error' : '');
  clearTimeout(t._t);
  t._t = setTimeout(() => t.className = '', 3500);
}

// ── Helpers ───────────────────────────────────────────────────
function escHtml(s) {
  return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function formatFecha(iso) {
  if (!iso) return '—';
  try {
    return new Date(iso).toLocaleDateString('es-PE', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
  } catch { return iso.slice(0,10); }
}
const statusLabel = {pendiente:'Pendiente',enviado:'Enviado',entregado:'Entregado',cancelado:'Cancelado'};

// ── Load ──────────────────────────────────────────────────────
async function cargarPedidos() {
  try {
    const res  = await fetch('/api/pedidos?accion=listar');
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Error');
    allPedidos = data.pedidos || [];
    applyFilters();
  } catch(e) {
    document.getElementById('tabla-body').innerHTML = `<tr><td colspan="5"><div class="empty">Error al cargar pedidos: ${escHtml(e.message)}</div></td></tr>`;
  }
}

// ── Filters + sort ────────────────────────────────────────────
function applyFilters() {
  let list = [...allPedidos];
  if (filterEstado) list = list.filter(p => p.estado === filterEstado);
  if (searchQ) {
    const q = searchQ.toLowerCase();
    list = list.filter(p =>
      (p.nombre || '').toLowerCase().includes(q) ||
      (p.telefono || '').toLowerCase().includes(q) ||
      (p.email || '').toLowerCase().includes(q)
    );
  }
  list.sort((a, b) => {
    let av = a[sortKey] || '', bv = b[sortKey] || '';
    if (sortKey === 'total') { av = parseFloat(av) || 0; bv = parseFloat(bv) || 0; }
    if (av < bv) return -sortDir;
    if (av > bv) return sortDir;
    return 0;
  });
  filteredPedidos = list;
  renderTable();
}

function sortBy(key) {
  if (sortKey === key) sortDir *= -1;
  else { sortKey = key; sortDir = -1; }
  document.querySelectorAll('th').forEach(th => th.classList.remove('sorted'));
  document.getElementById(`th-${key}`)?.classList.add('sorted');
  applyFilters();
}

// ── Render table ──────────────────────────────────────────────
function renderTable() {
  document.getElementById('topbar-count').textContent = filteredPedidos.length + ' pedidos';
  const tbody = document.getElementById('tabla-body');
  if (!filteredPedidos.length) {
    tbody.innerHTML = `<tr><td colspan="5"><div class="empty">No hay pedidos que coincidan</div></td></tr>`;
    return;
  }
  tbody.innerHTML = filteredPedidos.map((p, i) => `
    <tr onclick="seleccionarPedido('${escHtml(p.id)}')" ${p.id === selectedId ? 'class="selected"' : ''}>
      <td style="color:#aaa;font-size:12px">${i + 1}. &nbsp;<strong style="color:#1a1a2e">${escHtml(p.nombre || '—')}</strong></td>
      <td>${escHtml(p.telefono || '—')}</td>
      <td style="font-weight:700;color:#ff6a00">S/ ${parseFloat(p.total || 0).toFixed(2)}</td>
      <td><span class="badge ${p.estado || 'pendiente'}">${statusLabel[p.estado] || p.estado || 'Pendiente'}</span></td>
      <td style="color:#aaa;font-size:12px">${formatFecha(p.fecha)}</td>
    </tr>
  `).join('');
}

// ── Detail panel ──────────────────────────────────────────────
function seleccionarPedido(id) {
  selectedId = id;
  const p = allPedidos.find(x => x.id === id);
  if (!p) return;

  renderTable(); // update selected highlight

  const panel  = document.getElementById('detail-panel');
  const inner  = document.getElementById('detail-inner');
  panel.classList.add('open');

  // Items list
  const items = Array.isArray(p.items) ? p.items : [];
  const itemsHtml = items.length ? `
    <div class="detail-items">
      <h4>Productos</h4>
      ${items.map(it => `
        <div class="item-row">
          <span class="item-name">${escHtml(it.nombre || it.name || '—')}</span>
          <span class="item-qty">×${it.cantidad || it.qty || 1}</span>
          <span class="item-price">S/ ${parseFloat(it.precio || it.price || 0).toFixed(2)}</span>
        </div>
      `).join('')}
    </div>
  ` : '';

  // Notas
  const notasHtml = p.notas ? `<div class="detail-row"><div class="detail-key">Notas</div><div class="detail-val">${escHtml(p.notas)}</div></div>` : '';
  // Dirección
  const dirHtml = p.direccion ? `<div class="detail-row"><div class="detail-key">Dirección</div><div class="detail-val">${escHtml(p.direccion)}</div></div>` : '';
  // Email
  const emailHtml = p.email ? `<div class="detail-row"><div class="detail-key">Email</div><div class="detail-val">${escHtml(p.email)}</div></div>` : '';

  inner.innerHTML = `
    <button class="detail-close" onclick="cerrarDetalle()">✕</button>
    <div class="detail-title">${escHtml(p.nombre || 'Pedido')}</div>
    <div class="detail-sub">ID: ${escHtml(p.id)}</div>

    <div class="detail-row"><div class="detail-key">Teléfono</div><div class="detail-val"><a href="https://wa.me/${escHtml((p.telefono||'').replace(/\D/g,''))}" target="_blank" style="color:#25D366;text-decoration:none">📱 ${escHtml(p.telefono || '—')}</a></div></div>
    ${emailHtml}
    ${dirHtml}
    <div class="detail-row"><div class="detail-key">Total</div><div class="detail-val" style="font-weight:700;color:#ff6a00;font-size:16px">S/ ${parseFloat(p.total || 0).toFixed(2)}</div></div>
    <div class="detail-row"><div class="detail-key">Estado actual</div><div class="detail-val"><span class="badge ${p.estado || 'pendiente'}">${statusLabel[p.estado] || 'Pendiente'}</span></div></div>
    <div class="detail-row"><div class="detail-key">Fecha</div><div class="detail-val">${formatFecha(p.fecha)}</div></div>
    <div class="detail-row"><div class="detail-key">Método de pago</div><div class="detail-val">${escHtml(p.metodo_pago || '—')}</div></div>
    ${notasHtml}
    ${itemsHtml}

    <div class="status-select-wrap">
      <label>Cambiar estado</label>
      <select class="status-select" id="nuevo-estado">
        <option value="pendiente" ${p.estado==='pendiente'?'selected':''}>⏳ Pendiente</option>
        <option value="enviado" ${p.estado==='enviado'?'selected':''}>🚚 Enviado</option>
        <option value="entregado" ${p.estado==='entregado'?'selected':''}>✅ Entregado</option>
        <option value="cancelado" ${p.estado==='cancelado'?'selected':''}>❌ Cancelado</option>
      </select>
      <button class="btn-update-status" onclick="actualizarEstado('${escHtml(p.id)}')">Actualizar estado</button>
    </div>
  `;
}

function cerrarDetalle() {
  selectedId = null;
  document.getElementById('detail-panel').classList.remove('open');
  renderTable();
}

// ── Update status ─────────────────────────────────────────────
async function actualizarEstado(pedidoId) {
  const nuevo = document.getElementById('nuevo-estado').value;
  const btn   = document.querySelector('.btn-update-status');
  btn.disabled = true;
  btn.textContent = 'Actualizando...';

  const fd = new FormData();
  fd.append('pedido_id', pedidoId);
  fd.append('estado', nuevo);
  try {
    const res  = await fetch('/api/pedidos?accion=actualizar', {method:'POST', body:fd});
    const data = await res.json();
    if (data.ok) {
      // Update locally
      const p = allPedidos.find(x => x.id === pedidoId);
      if (p) p.estado = nuevo;
      applyFilters();
      seleccionarPedido(pedidoId);
      toast('✅ Estado actualizado');
    } else {
      toast(data.error || 'Error al actualizar', true);
    }
  } catch { toast('Error de red', true); }
  btn.disabled = false;
  btn.textContent = 'Actualizar estado';
}

// ── Export CSV ────────────────────────────────────────────────
function exportarCSV() {
  if (!filteredPedidos.length) { toast('No hay pedidos para exportar', true); return; }
  const cols = ['ID','Cliente','Teléfono','Email','Total','Estado','Fecha','Dirección','Notas'];
  const rows = filteredPedidos.map(p => [
    p.id || '',
    p.nombre || '',
    p.telefono || '',
    p.email || '',
    parseFloat(p.total || 0).toFixed(2),
    p.estado || '',
    (p.fecha || '').slice(0,19).replace('T',' '),
    p.direccion || '',
    (p.notas || '').replace(/\n/g,' '),
  ].map(v => `"${String(v).replace(/"/g,'""')}"`).join(','));

  const csv  = [cols.join(','), ...rows].join('\r\n');
  const blob = new Blob(['﻿' + csv], {type:'text/csv;charset=utf-8;'});
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url;
  a.download = `pedidos_${slug}_${new Date().toISOString().slice(0,10)}.csv`;
  document.body.appendChild(a); a.click(); document.body.removeChild(a);
  URL.revokeObjectURL(url);
  toast('✅ CSV exportado');
}

// ── Tab filter ────────────────────────────────────────────────
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterEstado = btn.dataset.estado;
    cerrarDetalle();
    applyFilters();
  });
});

// ── Search ────────────────────────────────────────────────────
document.getElementById('search-input').addEventListener('input', e => {
  searchQ = e.target.value.trim();
  applyFilters();
});

// ── Init ──────────────────────────────────────────────────────
cargarPedidos();
// Sorted by fecha desc by default
document.getElementById('th-fecha')?.classList.add('sorted');
</script>
</body>
</html>
