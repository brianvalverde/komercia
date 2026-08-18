<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis Productos – Komercia</title>
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
.btn-primary{background:#ff6a00;color:#fff}
.btn-primary:hover{background:#e55d00}
.btn-outline{background:#fff;color:#333;border:1px solid #ddd}
.btn-outline:hover{background:#f5f5f5}
.btn-danger{background:#fff0f0;color:#cc0000;border:1px solid #ffcccc;font-size:12px;padding:5px 10px}
.btn-danger:hover{background:#ffcccc}
.btn-sm{font-size:12px;padding:5px 12px}
.btn-edit{background:#f0f4ff;color:#3366cc;border:1px solid #ccd9ff;font-size:12px;padding:5px 10px}
.btn-edit:hover{background:#ccd9ff}
.avatar{width:36px;height:36px;border-radius:50%;background:#ff6a00;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px}
.content{padding:24px}
/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:200;align-items:flex-start;justify-content:center;padding:20px;overflow-y:auto}
.modal-overlay.open{display:flex}
.modal{background:#fff;border-radius:14px;padding:28px;width:100%;max-width:620px;margin:auto}
.modal h2{font-size:18px;margin-bottom:20px;font-weight:700}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;font-weight:600;margin-bottom:6px;color:#444}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;outline:none;transition:.2s;font-family:inherit}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:#ff6a00}
.form-group textarea{resize:vertical;min-height:80px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:24px}
/* Producto cards */
.products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:16px;margin-top:20px}
.product-card{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);transition:.2s;position:relative}
.product-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.12);transform:translateY(-2px)}
.product-img{width:100%;height:160px;object-fit:cover;background:#f0f0f0}
.product-img-placeholder{width:100%;height:160px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;font-size:40px;color:#ccc}
.product-info{padding:14px}
.product-name{font-weight:600;font-size:15px;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.product-price{color:#ff6a00;font-weight:700;font-size:16px;margin-bottom:4px}
.product-stock{font-size:12px;color:#888;margin-bottom:10px}
.product-actions{display:flex;gap:8px;flex-wrap:wrap}
.product-cat{display:inline-block;background:#fff3e0;color:#ff6a00;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;margin-bottom:6px}
.toggle-wrap{position:absolute;top:10px;right:10px}
.toggle{position:relative;display:inline-block;width:40px;height:22px}
.toggle input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#ccc;transition:.3s;border-radius:22px}
.toggle-slider:before{position:absolute;content:"";height:16px;width:16px;left:3px;bottom:3px;background:#fff;transition:.3s;border-radius:50%}
.toggle input:checked + .toggle-slider{background:#ff6a00}
.toggle input:checked + .toggle-slider:before{transform:translateX(18px)}
.product-inactive{opacity:.55}
/* Drop zone multi imágenes */
.imgs-grid{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.img-thumb{position:relative;width:80px;height:80px;border-radius:8px;overflow:hidden;border:2px solid #eee}
.img-thumb img{width:100%;height:100%;object-fit:cover}
.img-thumb .del-img{position:absolute;top:2px;right:2px;background:rgba(0,0,0,.65);color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:11px;cursor:pointer;display:flex;align-items:center;justify-content:center}
.add-img-btn{width:80px;height:80px;border:2px dashed #ddd;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:28px;color:#ccc;cursor:pointer;transition:.2s;background:#fafafa;position:relative;overflow:hidden}
.add-img-btn:hover{border-color:#ff6a00;color:#ff6a00}
.add-img-btn input{position:absolute;inset:0;opacity:0;cursor:pointer}
/* Videos */
.videos-list{display:flex;flex-direction:column;gap:8px;margin-top:8px}
.video-item{display:flex;align-items:center;gap:8px;background:#f5f5f5;padding:8px 10px;border-radius:8px;font-size:13px}
.video-item a{color:#3366cc;text-decoration:none;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.video-item button{background:#ffeeee;color:#cc0000;border:none;border-radius:6px;padding:3px 8px;cursor:pointer;font-size:12px}
/* Promociones */
.promo-table{width:100%;border-collapse:collapse;font-size:13px;margin-top:8px}
.promo-table th{background:#f5f5f5;padding:8px;text-align:left;font-weight:600;border-bottom:2px solid #eee}
.promo-table td{padding:8px;border-bottom:1px solid #f0f0f0}
.promo-table input{padding:5px 8px;border:1px solid #ddd;border-radius:6px;font-size:13px;width:100%}
.btn-add-promo{background:#f0fff0;color:#228b22;border:1px solid #c0e0c0;border-radius:8px;padding:6px 14px;font-size:13px;cursor:pointer;margin-top:8px}
.btn-add-promo:hover{background:#c0e0c0}
.btn-del-promo{background:none;border:none;color:#cc0000;cursor:pointer;font-size:16px}
/* EMPTY / LOADING */
.empty-state{text-align:center;padding:60px 20px;color:#aaa}
.empty-state .icon{font-size:64px;margin-bottom:16px}
.loading{text-align:center;padding:60px;color:#aaa}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:90}
.cat-suggestions{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.cat-chip{background:#f0f0f0;border:none;border-radius:20px;padding:4px 12px;font-size:12px;cursor:pointer;transition:.2s;font-family:inherit}
.cat-chip:hover{background:#ff6a00;color:#fff}
@media(max-width:768px){
  .sidebar{transform:translateX(-240px)}
  .sidebar.open{transform:translateX(0)}
  .sidebar-overlay.show{display:block}
  .main{margin-left:0}
  .hamburger{display:block}
  .products-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr))}
  .form-row,.form-row-3{grid-template-columns:1fr}
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
      <a href="/panel/productos" class="active"><span class="icon">📦</span> Productos</a>
      <a href="/panel/pedidos"><span class="icon">🛒</span> Pedidos</a>
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
        <h1>📦 Mis Productos</h1>
      </div>
      <div class="topbar-right">
        <a id="link-tienda" href="#" target="_blank" class="btn btn-outline btn-sm">🏪 Ver mi tienda</a>
        <div class="avatar" id="avatar-inicial">?</div>
      </div>
    </div>
    <div class="content">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
        <p style="color:#888;font-size:14px">Administra los productos de tu tienda</p>
        <button class="btn btn-primary" onclick="abrirModal()">＋ Agregar Producto</button>
      </div>
      <div id="productos-container" class="loading">Cargando productos...</div>
    </div>
  </div>
</div>

<!-- MODAL PRODUCTO -->
<div class="modal-overlay" id="modal-overlay">
  <div class="modal">
    <h2 id="modal-titulo">＋ Nuevo Producto</h2>
    <form id="form-producto">
      <input type="hidden" id="producto-id" value="">

      <div class="form-group">
        <label>Nombre del producto *</label>
        <input type="text" id="p-nombre" placeholder="Ej: Zapatillas Nike Air Max" required>
      </div>

      <div class="form-group">
        <label>Descripción</label>
        <textarea id="p-descripcion" placeholder="Describe tu producto..."></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Precio (S/.) *</label>
          <input type="number" id="p-precio" placeholder="0.00" step="0.01" min="0" required>
        </div>
        <div class="form-group">
          <label>Stock</label>
          <input type="number" id="p-stock" placeholder="0" min="0" value="0">
        </div>
      </div>

      <div class="form-group">
        <label>Categoría</label>
        <input type="text" id="p-categoria" placeholder="Ej: Ropa, Electrónica...">
        <div class="cat-suggestions">
          <button type="button" class="cat-chip" onclick="setCategoria('Ropa')">Ropa</button>
          <button type="button" class="cat-chip" onclick="setCategoria('Calzado')">Calzado</button>
          <button type="button" class="cat-chip" onclick="setCategoria('Electrónica')">Electrónica</button>
          <button type="button" class="cat-chip" onclick="setCategoria('Hogar')">Hogar</button>
          <button type="button" class="cat-chip" onclick="setCategoria('Belleza')">Belleza</button>
          <button type="button" class="cat-chip" onclick="setCategoria('Accesorios')">Accesorios</button>
          <button type="button" class="cat-chip" onclick="setCategoria('Alimentos')">Alimentos</button>
          <button type="button" class="cat-chip" onclick="setCategoria('Deportes')">Deportes</button>
        </div>
      </div>

      <!-- IMÁGENES (hasta 5) -->
      <div class="form-group">
        <label>Imágenes del producto <span style="color:#999;font-weight:400">(hasta 5 · JPG/PNG/WEBP · máx 8MB c/u)</span></label>
        <div class="imgs-grid" id="imgs-grid">
          <div class="add-img-btn" id="add-img-btn" title="Agregar imagen">
            <input type="file" id="img-file-input" accept="image/jpeg,image/png,image/webp" multiple>
            ＋
          </div>
        </div>
      </div>

      <!-- VIDEOS (hasta 2) -->
      <div class="form-group">
        <label>Videos <span style="color:#999;font-weight:400">(hasta 2 · MP4 · máx 50MB c/u)</span></label>
        <div id="videos-list" class="videos-list"></div>
        <div style="margin-top:8px">
          <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;background:#f0f4ff;border:1px solid #ccd9ff;border-radius:8px;padding:6px 14px;font-size:13px;color:#3366cc;font-weight:600">
            <input type="file" id="video-file-input" accept="video/mp4" multiple style="display:none"> 📹 Agregar video
          </label>
        </div>
      </div>

      <!-- PROMOCIONES -->
      <div class="form-group">
        <label>Tabla de promociones <span style="color:#999;font-weight:400">(opcional)</span></label>
        <table class="promo-table">
          <thead><tr><th>Nombre</th><th>Precio</th><th>Detalle</th><th></th></tr></thead>
          <tbody id="promo-tbody"></tbody>
        </table>
        <button type="button" class="btn-add-promo" onclick="addPromoRow()">＋ Agregar variante/promo</button>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="cerrarModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="btn-guardar">Guardar Producto</button>
      </div>
    </form>
  </div>
</div>

<script>
let slugTienda = '';
// Imágenes en memoria
let imagenesExistentes = []; // {url, toDelete:false}
let imagenesNuevas     = []; // File objects (ya comprimidos)
let videosExistentes   = [];
let videosNuevos       = [];
let imagenesEliminar   = [];
let videosEliminar     = [];

const IMG_MAX_PX  = 1200;
const IMG_QUALITY = 0.85;

function comprimirImagen(file) {
  return new Promise(resolve => {
    const reader = new FileReader();
    reader.onload = e => {
      const img = new Image();
      img.onload = () => {
        let w = img.width, h = img.height;
        if (w > IMG_MAX_PX || h > IMG_MAX_PX) {
          if (w >= h) { h = Math.round(h * IMG_MAX_PX / w); w = IMG_MAX_PX; }
          else        { w = Math.round(w * IMG_MAX_PX / h); h = IMG_MAX_PX; }
        }
        const c = document.createElement('canvas');
        c.width = w; c.height = h;
        c.getContext('2d').drawImage(img, 0, 0, w, h);
        c.toBlob(blob => {
          if (!blob) { resolve(file); return; }
          resolve(new File([blob], file.name.replace(/\.[^.]+$/, '.webp'), { type: 'image/webp' }));
        }, 'image/webp', IMG_QUALITY);
      };
      img.onerror = () => resolve(file);
      img.src = e.target.result;
    };
    reader.onerror = () => resolve(file);
    reader.readAsDataURL(file);
  });
}

function renderImgsGrid() {
  const grid = document.getElementById('imgs-grid');
  grid.innerHTML = '';
  const total = imagenesExistentes.length + imagenesNuevas.length;

  imagenesExistentes.forEach((img, i) => {
    const d = document.createElement('div');
    d.className = 'img-thumb';
    d.innerHTML = `<img src="${img.url}" alt="img"><button class="del-img" onclick="delImgExistente(${i})" type="button">✕</button>`;
    grid.appendChild(d);
  });
  imagenesNuevas.forEach((file, i) => {
    const d = document.createElement('div');
    d.className = 'img-thumb';
    const url = URL.createObjectURL(file);
    d.innerHTML = `<img src="${url}" alt="img"><button class="del-img" onclick="delImgNueva(${i})" type="button">✕</button>`;
    grid.appendChild(d);
  });

  if (total < 5) {
    const btn = document.createElement('div');
    btn.className = 'add-img-btn';
    btn.title = 'Agregar imagen';
    btn.innerHTML = `<input type="file" id="img-file-input" accept="image/jpeg,image/png,image/webp" multiple> ＋`;
    btn.querySelector('input').addEventListener('change', handleImgFiles);
    grid.appendChild(btn);
  }
}

async function handleImgFiles(e) {
  const files = Array.from(e.target.files);
  const total = imagenesExistentes.length + imagenesNuevas.length;
  const slots = 5 - total;
  const toAdd = files.slice(0, slots);
  for (const f of toAdd) {
    const compressed = await comprimirImagen(f);
    imagenesNuevas.push(compressed);
  }
  renderImgsGrid();
}

document.addEventListener('change', e => {
  if (e.target.id === 'img-file-input') handleImgFiles(e);
});

function delImgExistente(i) {
  imagenesEliminar.push(imagenesExistentes[i].url);
  imagenesExistentes.splice(i, 1);
  renderImgsGrid();
}
function delImgNueva(i) {
  imagenesNuevas.splice(i, 1);
  renderImgsGrid();
}

function renderVideosList() {
  const list = document.getElementById('videos-list');
  list.innerHTML = '';
  videosExistentes.forEach((url, i) => {
    const d = document.createElement('div');
    d.className = 'video-item';
    const name = decodeURIComponent(url.split('/').pop());
    d.innerHTML = `<span>🎬</span><a href="${url}" target="_blank">${name}</a><button onclick="delVideoExistente(${i})" type="button">✕ Quitar</button>`;
    list.appendChild(d);
  });
  videosNuevos.forEach((file, i) => {
    const d = document.createElement('div');
    d.className = 'video-item';
    d.innerHTML = `<span>🎬</span><span style="flex:1">${file.name} (${(file.size/1024/1024).toFixed(1)} MB)</span><button onclick="delVideoNuevo(${i})" type="button">✕ Quitar</button>`;
    list.appendChild(d);
  });
}

document.getElementById('video-file-input').addEventListener('change', function(e) {
  const files = Array.from(e.target.files);
  const total = videosExistentes.length + videosNuevos.length;
  const slots = 2 - total;
  for (const f of files.slice(0, slots)) {
    if (f.size > 50 * 1024 * 1024) { alert('El video ' + f.name + ' supera 50 MB'); continue; }
    videosNuevos.push(f);
  }
  renderVideosList();
});
function delVideoExistente(i) { videosEliminar.push(videosExistentes[i]); videosExistentes.splice(i, 1); renderVideosList(); }
function delVideoNuevo(i)     { videosNuevos.splice(i, 1); renderVideosList(); }

// ── PROMOCIONES ───────────────────────────────────────────────
function addPromoRow(nombre='', precio='', detalle='') {
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><input type="text" placeholder="Ej: Pack x3" value="${nombre}" class="promo-nombre"></td>
    <td><input type="number" placeholder="0.00" step="0.01" value="${precio}" class="promo-precio" style="width:90px"></td>
    <td><input type="text" placeholder="Descripción" value="${detalle}" class="promo-detalle"></td>
    <td><button type="button" class="btn-del-promo" onclick="this.closest('tr').remove()">🗑</button></td>`;
  document.getElementById('promo-tbody').appendChild(tr);
}

function getPromociones() {
  const rows = document.querySelectorAll('#promo-tbody tr');
  return Array.from(rows).map(tr => ({
    nombre:  tr.querySelector('.promo-nombre').value.trim(),
    precio:  parseFloat(tr.querySelector('.promo-precio').value) || 0,
    detalle: tr.querySelector('.promo-detalle').value.trim(),
  })).filter(p => p.nombre);
}

function setCategoria(val) { document.getElementById('p-categoria').value = val; }

// ── MODAL ─────────────────────────────────────────────────────
function abrirModal(producto = null) {
  document.getElementById('form-producto').reset();
  imagenesExistentes = []; imagenesNuevas = [];
  videosExistentes = []; videosNuevos = [];
  imagenesEliminar = []; videosEliminar = [];
  document.getElementById('promo-tbody').innerHTML = '';
  document.getElementById('producto-id').value = '';

  if (producto) {
    document.getElementById('modal-titulo').textContent = '✏️ Editar Producto';
    document.getElementById('producto-id').value = producto.id;
    document.getElementById('p-nombre').value      = producto.nombre;
    document.getElementById('p-descripcion').value = producto.descripcion;
    document.getElementById('p-precio').value      = producto.precio;
    document.getElementById('p-stock').value       = producto.stock;
    document.getElementById('p-categoria').value   = producto.categoria;
    imagenesExistentes = (producto.imagenes || []).map(u => ({ url: u }));
    videosExistentes   = producto.videos || [];
    (producto.promociones || []).forEach(p => addPromoRow(p.nombre, p.precio, p.detalle));
  } else {
    document.getElementById('modal-titulo').textContent = '＋ Nuevo Producto';
  }

  renderImgsGrid();
  renderVideosList();
  document.getElementById('modal-overlay').classList.add('open');
}

function cerrarModal() { document.getElementById('modal-overlay').classList.remove('open'); }

document.getElementById('modal-overlay').addEventListener('click', function(e) {
  if (e.target === this) cerrarModal();
});

// ── GUARDAR ───────────────────────────────────────────────────
document.getElementById('form-producto').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('btn-guardar');
  btn.disabled = true; btn.textContent = 'Guardando...';

  const id      = document.getElementById('producto-id').value;
  const accion  = id ? 'actualizar' : 'crear';
  const formData = new FormData();

  formData.append('id',           id);
  formData.append('nombre',       document.getElementById('p-nombre').value);
  formData.append('descripcion',  document.getElementById('p-descripcion').value);
  formData.append('precio',       document.getElementById('p-precio').value);
  formData.append('stock',        document.getElementById('p-stock').value);
  formData.append('categoria',    document.getElementById('p-categoria').value);
  formData.append('promociones',  JSON.stringify(getPromociones()));
  formData.append('imagenes_eliminar', JSON.stringify(imagenesEliminar));
  formData.append('videos_eliminar',   JSON.stringify(videosEliminar));

  imagenesNuevas.forEach(f => formData.append('imagenes[]', f));
  videosNuevos.forEach(f   => formData.append('videos[]', f));

  try {
    const res  = await fetch('/api/productos?accion=' + accion, { method: 'POST', body: formData });
    const data = await res.json();
    if (data.ok) { cerrarModal(); cargarProductos(); }
    else alert('Error: ' + data.error);
  } catch(err) {
    alert('Error de red.');
  } finally {
    btn.disabled = false; btn.textContent = 'Guardar Producto';
  }
});

// ── TOGGLE ACTIVO ─────────────────────────────────────────────
async function toggleActivo(id, activo) {
  const fd = new FormData();
  fd.append('id', id);
  fd.append('activo', activo ? 'true' : 'false');
  await fetch('/api/productos?accion=toggle_activo', { method: 'POST', body: fd });
}

// ── ELIMINAR ──────────────────────────────────────────────────
async function eliminarProducto(id, btn) {
  if (!confirm('¿Eliminar este producto?')) return;
  btn.disabled = true; btn.textContent = '...';
  const res  = await fetch('/api/productos?accion=eliminar&id=' + id, { method: 'DELETE' });
  const data = await res.json();
  if (data.ok) cargarProductos();
  else { alert('Error al eliminar.'); btn.disabled = false; btn.textContent = '🗑 Eliminar'; }
}

// ── CARGAR ────────────────────────────────────────────────────
function cargarProductos() {
  document.getElementById('productos-container').innerHTML = '<div class="loading">Cargando...</div>';
  fetch('/api/productos?accion=listar')
    .then(r => r.json())
    .then(d => { if (d.ok) renderProductos(d.productos); else mostrarError(); })
    .catch(mostrarError);
}

function renderProductos(productos) {
  const c = document.getElementById('productos-container');
  if (!productos.length) {
    c.innerHTML = `<div class="empty-state"><div class="icon">📦</div><p>Aún no tienes productos.</p><p style="margin-top:8px;font-size:14px">Haz clic en <strong>Agregar Producto</strong> para comenzar.</p></div>`;
    return;
  }
  const html = '<div class="products-grid">' + productos.map(p => `
    <div class="product-card ${!p.activo ? 'product-inactive' : ''}">
      <div class="toggle-wrap">
        <label class="toggle" title="${p.activo ? 'Activo' : 'Inactivo'}">
          <input type="checkbox" ${p.activo ? 'checked' : ''} onchange="toggleActivo('${p.id}', this.checked)">
          <span class="toggle-slider"></span>
        </label>
      </div>
      ${p.imagen ? `<img class="product-img" src="${p.imagen}" alt="${p.nombre}" loading="lazy">` : `<div class="product-img-placeholder">🖼️</div>`}
      <div class="product-info">
        ${p.categoria ? `<span class="product-cat">${p.categoria}</span>` : ''}
        <div class="product-name" title="${p.nombre}">${p.nombre}</div>
        <div class="product-price">S/. ${parseFloat(p.precio).toFixed(2)}</div>
        <div class="product-stock">Stock: ${p.stock} u${p.imagenes && p.imagenes.length > 1 ? ` · 📷 ${p.imagenes.length}` : ''}${p.videos && p.videos.length ? ` · 🎬 ${p.videos.length}` : ''}${p.promociones && p.promociones.length ? ` · 🏷️ ${p.promociones.length}` : ''}</div>
        <div class="product-actions">
          <button class="btn btn-edit" onclick='abrirModal(${JSON.stringify(p)})'>✏️ Editar</button>
          <button class="btn btn-danger" onclick="eliminarProducto('${p.id}', this)">🗑 Eliminar</button>
        </div>
      </div>
    </div>`).join('') + '</div>';
  c.innerHTML = html;
}

function mostrarError() {
  document.getElementById('productos-container').innerHTML = '<div class="empty-state"><p>Error cargando productos.</p></div>';
}

// ── SESIÓN ────────────────────────────────────────────────────
fetch('/api/sesion')
  .then(r => r.json())
  .then(d => {
    if (!d.ok) { location.href = '/login'; return; }
    slugTienda = d.slug;
    document.getElementById('avatar-inicial').textContent = d.nombre.charAt(0).toUpperCase();
    document.getElementById('link-tienda').href = '/tienda/' + d.slug;
    cargarProductos();
  });

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('overlay').classList.toggle('show');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('overlay').classList.remove('show');
}
function logout() { fetch('/api/logout').then(() => location.href = '/login'); }
</script>
</body>
</html>
