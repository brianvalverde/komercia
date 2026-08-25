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
/* SIDEBAR */
.sidebar{width:220px;background:#fff;border-right:1px solid #e8eaf0;display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;padding:24px 0;transform:translateX(0);transition:.3s}
.sidebar.hidden{transform:translateX(-220px)}
.sidebar-logo{padding:0 20px 24px;border-bottom:1px solid #e8eaf0;margin-bottom:16px;font-size:22px;font-weight:700}
.sidebar-logo span{background:linear-gradient(135deg,#ff6a00,#ee0979);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.sidebar nav a{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#555;text-decoration:none;font-size:14px;border-radius:8px;margin:2px 8px;transition:all .2s}
.sidebar nav a:hover{background:#fff5f0;color:#ff6a00}
.sidebar nav a.active{background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;font-weight:600}
.sidebar nav a svg{width:18px;height:18px;flex-shrink:0}
.sidebar-footer{margin-top:auto;padding:16px 20px;border-top:1px solid #e8eaf0}
.sidebar-footer a{display:flex;align-items:center;gap:8px;color:#888;text-decoration:none;font-size:13px;transition:color .2s}
.sidebar-footer a:hover{color:#ff6a00}
/* MAIN */
.main{margin-left:220px;flex:1;display:flex;flex-direction:column;transition:.3s}
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
.btn-danger{background:#ff4444;color:#fff;font-size:12px;padding:5px 10px}
.btn-danger:hover{background:#cc0000}
.btn-sm{font-size:12px;padding:5px 12px}
.avatar{width:36px;height:36px;border-radius:50%;background:#ff6a00;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px}
.content{padding:24px}
/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:#fff;border-radius:12px;padding:28px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto}
.modal h2{font-size:18px;margin-bottom:20px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;font-weight:500;margin-bottom:6px;color:#555}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;outline:none;transition:.2s;font-family:inherit}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:#ff6a00}
.form-group textarea{resize:vertical;min-height:80px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.img-preview{width:100%;max-height:160px;object-fit:cover;border-radius:8px;margin-top:8px;display:none}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:20px}
/* categoria badge en card */
.product-cat{display:inline-block;background:#fff3e0;color:#ff6a00;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;margin-bottom:6px}
/* PRODUCTOS GRID */
.products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-top:20px}
.product-card{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);transition:.2s}
.product-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.12);transform:translateY(-2px)}
.product-img{width:100%;height:160px;object-fit:cover;background:#f0f0f0}
.product-img-placeholder{width:100%;height:160px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;font-size:40px;color:#ccc}
.product-info{padding:14px}
.product-name{font-weight:600;font-size:15px;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.product-price{color:#ff6a00;font-weight:700;font-size:16px;margin-bottom:6px}
.product-stock{font-size:12px;color:#888;margin-bottom:12px}
.product-actions{display:flex;gap:8px}
/* EMPTY */
.empty-state{text-align:center;padding:60px 20px;color:#aaa}
.empty-state .icon{font-size:64px;margin-bottom:16px}
.empty-state p{font-size:16px}
/* LOADING */
.loading{text-align:center;padding:60px;color:#aaa}
/* OVERLAY sidebar mobile */
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:90}
/* categoria input con sugerencias */
.cat-suggestions{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.cat-chip{background:#f0f0f0;border:none;border-radius:20px;padding:4px 12px;font-size:12px;cursor:pointer;transition:.2s;font-family:inherit}
.cat-chip:hover{background:#ff6a00;color:#fff}
/* drag & drop */
.drop-zone{border:2px dashed #ddd;border-radius:10px;padding:28px 16px;text-align:center;cursor:pointer;transition:.2s;position:relative;background:#fafafa}
.drop-zone:hover,.drop-zone.dragover{border-color:#ff6a00;background:#fff8f4}
.drop-zone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.drop-zone .drop-icon{font-size:32px;margin-bottom:8px}
.drop-zone .drop-text{font-size:13px;color:#888}
.drop-zone .drop-text strong{color:#ff6a00}
.drop-preview{width:100%;max-height:180px;object-fit:cover;border-radius:8px;margin-top:10px;display:none;border:1px solid #eee}
.drop-filename{font-size:12px;color:#888;margin-top:6px;display:none;text-align:center}
@media(max-width:768px){
  .sidebar{transform:translateX(-240px)}
  .sidebar.open{transform:translateX(0)}
  .sidebar-overlay.show{display:block}
  .main{margin-left:0}
  .hamburger{display:block}
  .products-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr))}
}
</style>
</head>
<body>
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>
<div class="layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo"><span>Komercia</span></div>
    <nav>
      <a href="/panel">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>
      <a href="/panel/productos" class="active">
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
    <div class="sidebar-footer">
      <a href="/logout">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Cerrar sesión
      </a>
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
        <div>
          <p style="color:#888;font-size:14px">Administra los productos de tu tienda</p>
        </div>
        <button class="btn btn-primary" onclick="abrirModal()">＋ Agregar Producto</button>
      </div>
      <div id="productos-container" class="loading">Cargando productos...</div>
    </div>
  </div>
</div>
<!-- MODAL AGREGAR PRODUCTO -->
<div class="modal-overlay" id="modal-overlay">
  <div class="modal">
    <h2 id="modal-titulo">＋ Nuevo Producto</h2>
    <form id="form-producto" enctype="multipart/form-data">
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
        <input type="text" id="p-categoria" placeholder="Ej: Ropa, Electrónica, Calzado...">
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
      <div class="form-group">
        <label>Imagen del producto</label>
        <div class="drop-zone" id="dropZone">
          <input type="file" id="p-imagen" accept="image/jpeg,image/png,image/webp">
          <div class="drop-icon">🖼️</div>
          <div class="drop-text">Arrastra tu imagen aquí o <strong>haz clic para seleccionar</strong></div>
          <div class="drop-text" style="margin-top:4px;font-size:11px">JPG, PNG, WEBP · máx 5MB</div>
        </div>
        <img id="img-preview" class="drop-preview" src="" alt="Preview">
        <div class="drop-filename" id="drop-filename"></div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="cerrarModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="btn-guardar">Guardar Producto</button>
      </div>
    </form>
  </div>
</div>
<script>
let nombreUsuario = '';
let slugTienda    = '';
// ── Verificar sesión ──
fetch('/api/sesion')
  .then(r => r.json())
  .then(d => {
    if (!d.ok) { location.href = '/login'; return; }
    nombreUsuario = d.nombre;
    slugTienda    = d.slug;
    document.getElementById('avatar-inicial').textContent = d.nombre.charAt(0).toUpperCase();
    document.getElementById('link-tienda').href = '/tienda/' + d.slug;
    cargarProductos();
  });
// ── Cargar productos ──
function cargarProductos() {
  document.getElementById('productos-container').innerHTML = '<div class="loading">Cargando...</div>';
  fetch('/api/productos?accion=listar')
    .then(r => r.json())
    .then(d => {
      if (!d.ok) { mostrarError(); return; }
      renderProductos(d.productos);
    })
    .catch(mostrarError);
}
function renderProductos(productos) {
  const container = document.getElementById('productos-container');
  if (!productos.length) {
    container.innerHTML = `
      <div class="empty-state">
        <div class="icon">📦</div>
        <p>Aún no tienes productos.</p>
        <p style="margin-top:8px;font-size:14px">Haz clic en <strong>Agregar Producto</strong> para comenzar.</p>
      </div>`;
    return;
  }
  const html = '<div class="products-grid">' + productos.map(p => `
    <div class="product-card">
      ${p.imagen
        ? `<img class="product-img" src="${p.imagen}" alt="${p.nombre}" loading="lazy">`
        : `<div class="product-img-placeholder">🖼️</div>`}
      <div class="product-info">
        ${p.categoria ? `<span class="product-cat">${p.categoria}</span>` : ''}
        <div class="product-name" title="${p.nombre}">${p.nombre}</div>
        <div class="product-price">S/. ${parseFloat(p.precio).toFixed(2)}</div>
        <div class="product-stock">Stock: ${p.stock} unidades</div>
        <div class="product-actions">
          <button class="btn btn-danger" onclick="eliminarProducto('${p.id}', this)">🗑 Eliminar</button>
        </div>
      </div>
    </div>`).join('') + '</div>';
  container.innerHTML = html;
}
function mostrarError() {
  document.getElementById('productos-container').innerHTML = '<div class="empty-state"><p>Error cargando productos.</p></div>';
}
// ── Categoría rápida ──
function setCategoria(val) {
  document.getElementById('p-categoria').value = val;
}
// ── Modal ──
function abrirModal() {
  document.getElementById('form-producto').reset();
  document.getElementById('producto-id').value = '';
  document.getElementById('modal-titulo').textContent = '＋ Nuevo Producto';
  document.getElementById('img-preview').style.display = 'none';
  document.getElementById('drop-filename').style.display = 'none';
  document.getElementById('dropZone').style.borderColor = '';
  archivoFinal = null;
  document.getElementById('modal-overlay').classList.add('open');
}
function cerrarModal() {
  document.getElementById('modal-overlay').classList.remove('open');
}
// ── Compresión de imagen ──────────────────────────────────────
const IMG_MAX_PX  = 1200;   // ancho/alto máximo en píxeles
const IMG_QUALITY = 0.85;   // calidad WebP (0-1)
const IMG_FORMAT  = 'image/webp';

function comprimirImagen(file) {
  return new Promise((resolve) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = new Image();
      img.onload = () => {
        // Calcular nuevas dimensiones manteniendo proporción
        let w = img.width;
        let h = img.height;
        if (w > IMG_MAX_PX || h > IMG_MAX_PX) {
          if (w >= h) { h = Math.round(h * IMG_MAX_PX / w); w = IMG_MAX_PX; }
          else        { w = Math.round(w * IMG_MAX_PX / h); h = IMG_MAX_PX; }
        }
        const canvas = document.createElement('canvas');
        canvas.width  = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, w, h);
        // Intentar WebP, fallback a JPEG
        canvas.toBlob((blob) => {
          if (!blob) { resolve(file); return; } // fallback al original
          const compressed = new File([blob], file.name.replace(/\.[^.]+$/, '.webp'), { type: IMG_FORMAT });
          resolve(compressed);
        }, IMG_FORMAT, IMG_QUALITY);
      };
      img.onerror = () => resolve(file);
      img.src = e.target.result;
    };
    reader.onerror = () => resolve(file);
    reader.readAsDataURL(file);
  });
}

function mostrarPreview(file, originalSize) {
  if (!file) return;
  const preview  = document.getElementById('img-preview');
  const filename = document.getElementById('drop-filename');
  const zone     = document.getElementById('dropZone');
  preview.src = URL.createObjectURL(file);
  preview.style.display = 'block';
  const kb = (file.size / 1024).toFixed(0);
  const origKb = originalSize ? (originalSize / 1024).toFixed(0) : null;
  filename.textContent = origKb && origKb !== kb
    ? `✅ Comprimida: ${origKb} KB → ${kb} KB`
    : `${file.name} (${kb} KB)`;
  filename.style.display = 'block';
  filename.style.color = origKb && origKb !== kb ? '#22a862' : '#888';
  zone.style.borderColor = '#ff6a00';
}

async function procesarArchivo(file) {
  const allowed = ['image/jpeg','image/png','image/webp'];
  if (!allowed.includes(file.type)) { alert('Solo se permiten imágenes JPG, PNG o WEBP'); return null; }
  if (file.size > 15 * 1024 * 1024) { alert('La imagen no puede superar 15MB'); return null; }
  const originalSize = file.size;
  // Mostrar estado de procesando
  const filename = document.getElementById('drop-filename');
  filename.textContent = 'Comprimiendo imagen...';
  filename.style.display = 'block';
  filename.style.color = '#ff6a00';
  const compressed = await comprimirImagen(file);
  mostrarPreview(compressed, originalSize);
  return compressed;
}

// ── Drag & Drop ──────────────────────────────────────────────
const dropZone  = document.getElementById('dropZone');
const fileInput = document.getElementById('p-imagen');
let   archivoFinal = null; // guardamos el archivo comprimido aquí

fileInput.addEventListener('change', async () => {
  const file = fileInput.files[0];
  if (!file) return;
  archivoFinal = await procesarArchivo(file);
});

dropZone.addEventListener('dragover', e => {
  e.preventDefault();
  dropZone.classList.add('dragover');
});
dropZone.addEventListener('dragleave', () => {
  dropZone.classList.remove('dragover');
});
dropZone.addEventListener('drop', async e => {
  e.preventDefault();
  dropZone.classList.remove('dragover');
  const file = e.dataTransfer.files[0];
  if (!file) return;
  archivoFinal = await procesarArchivo(file);
});
// ── Guardar producto ──
document.getElementById('form-producto').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('btn-guardar');
  btn.disabled = true;
  btn.textContent = 'Guardando...';
  const formData = new FormData();
  formData.append('nombre',      document.getElementById('p-nombre').value);
  formData.append('descripcion', document.getElementById('p-descripcion').value);
  formData.append('precio',      document.getElementById('p-precio').value);
  formData.append('stock',       document.getElementById('p-stock').value);
  formData.append('categoria',   document.getElementById('p-categoria').value);
  if (archivoFinal) formData.append('imagen', archivoFinal);
  try {
    const res  = await fetch('/api/productos?accion=crear', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.ok) {
      cerrarModal();
      cargarProductos();
    } else {
      alert('Error: ' + data.error);
    }
  } catch (err) {
    alert('Error de red al guardar el producto.');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Guardar Producto';
  }
});
// ── Eliminar producto ──
async function eliminarProducto(id, btn) {
  if (!confirm('¿Eliminar este producto?')) return;
  btn.disabled = true;
  btn.textContent = '...';
  const res  = await fetch('/api/productos?accion=eliminar&id=' + id, { method: 'DELETE' });
  const data = await res.json();
  if (data.ok) cargarProductos();
  else { alert('Error al eliminar.'); btn.disabled = false; btn.textContent = '🗑 Eliminar'; }
}
// ── Sidebar ──
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('overlay').classList.toggle('show');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('overlay').classList.remove('show');
}
// ── Logout ──
function logout() {
  fetch('/api/logout').then(() => location.href = '/login');
}
// Cerrar modal al hacer clic afuera
document.getElementById('modal-overlay').addEventListener('click', function(e) {
  if (e.target === this) cerrarModal();
});
</script>
</body>
</html>
