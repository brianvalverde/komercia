<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis Productos – Komercia</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f0f2f5;color:#1a1a2e}
.layout{display:flex;min-height:100vh}
.sidebar{width:220px;background:#fff;border-right:1px solid #e8eaf0;display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;padding:24px 0}
.sidebar-logo{padding:0 20px 24px;border-bottom:1px solid #e8eaf0;margin-bottom:16px;font-size:22px;font-weight:700;background:linear-gradient(135deg,#ff6a00,#ee0979);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.sidebar nav a{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#555;text-decoration:none;font-size:14px;border-radius:8px;margin:2px 8px;transition:all .2s}
.sidebar nav a:hover{background:#fff5f0;color:#ff6a00}
.sidebar nav a.active{background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;font-weight:600}
.sidebar nav a svg{width:18px;height:18px;flex-shrink:0}
.sidebar-footer{margin-top:auto;padding:16px 20px;border-top:1px solid #e8eaf0}
.sidebar-footer a{color:#aaa;text-decoration:none;font-size:13px}
.main{margin-left:220px;flex:1;display:flex;flex-direction:column;}
.topbar{background:#fff;padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #eee;position:sticky;top:0;z-index:50}
.topbar-left{display:flex;align-items:center;gap:12px}
.hamburger{display:none;background:none;border:none;font-size:22px;cursor:pointer}
.topbar h1{font-size:18px;font-weight:600}
.topbar-right{display:flex;align-items:center;gap:12px}
.btn{padding:8px 16px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:500;transition:.2s;font-family:inherit}
.btn-primary{background:#ff6a00;color:#fff}
.btn-primary:hover{background:#e55d00}
.btn-outline{background:#fff;color:#333;border:1px solid #ddd}
.btn-outline:hover{background:#f5f5f5}
.btn-danger{background:#fff0f0;color:#cc0000;border:1px solid #ffcccc;font-size:12px;padding:5px 10px}
.btn-danger:hover{background:#ffcccc}
.btn-sm{font-size:12px;padding:5px 12px}
.btn-edit{background:#f0f4ff;color:#3366cc;border:1px solid #ccd9ff;font-size:12px;padding:5px 10px}
.btn-edit:hover{background:#ccd9ff}
.btn-review{background:#fff8e1;color:#a16207;border:1px solid #fde68a;font-size:12px;padding:5px 10px}
.btn-review:hover{background:#fde68a}
.avatar{width:36px;height:36px;border-radius:50%;background:#ff6a00;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px}
.content{padding:24px}
/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:200;align-items:flex-start;justify-content:center;padding:20px;overflow-y:auto}
.modal-overlay.open{display:flex}
.modal{background:#fff;border-radius:14px;padding:28px;width:100%;max-width:640px;margin:auto}
.modal h2{font-size:18px;margin-bottom:20px;font-weight:700}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;font-weight:600;margin-bottom:6px;color:#444}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;outline:none;transition:.2s;font-family:inherit}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:#ff6a00}
.form-group textarea{resize:vertical;min-height:80px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:24px}
/* Productos */
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
/* Imágenes */
.imgs-grid{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.img-thumb{position:relative;width:80px;height:80px;border-radius:8px;overflow:hidden;border:2px solid #eee}
.img-thumb img{width:100%;height:100%;object-fit:cover}
.img-thumb .del-img{position:absolute;top:2px;right:2px;background:rgba(0,0,0,.65);color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:11px;cursor:pointer;display:flex;align-items:center;justify-content:center}
.add-img-btn{width:80px;height:80px;border:2px dashed #ddd;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:28px;color:#ccc;cursor:pointer;transition:.2s;background:#fafafa}
.add-img-btn:hover{border-color:#ff6a00;color:#ff6a00}
/* Videos */
.videos-list{display:flex;flex-direction:column;gap:8px;margin-top:8px}
.video-item{display:flex;align-items:center;gap:8px;background:#f5f5f5;padding:8px 10px;border-radius:8px;font-size:13px}
.video-item a{color:#3366cc;text-decoration:none;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.video-item button{background:#ffeeee;color:#cc0000;border:none;border-radius:6px;padding:3px 8px;cursor:pointer;font-size:12px}
/* Progress bar */
.upload-progress{margin-top:8px;display:none}
.progress-bar-wrap{background:#eee;border-radius:6px;height:8px;overflow:hidden;margin-bottom:4px}
.progress-bar-fill{height:8px;background:#ff6a00;border-radius:6px;transition:width .2s;width:0}
.progress-text{font-size:12px;color:#888}
/* Promociones */
.promo-table{width:100%;border-collapse:collapse;font-size:13px;margin-top:8px}
.promo-table th{background:#f5f5f5;padding:8px;text-align:left;font-weight:600;border-bottom:2px solid #eee}
.promo-table td{padding:8px;border-bottom:1px solid #f0f0f0}
.promo-table input{padding:5px 8px;border:1px solid #ddd;border-radius:6px;font-size:13px;width:100%}
.btn-add-promo{background:#f0fff0;color:#228b22;border:1px solid #c0e0c0;border-radius:8px;padding:6px 14px;font-size:13px;cursor:pointer;margin-top:8px;font-family:inherit}
.btn-add-promo:hover{background:#c0e0c0}
.btn-del-promo{background:none;border:none;color:#cc0000;cursor:pointer;font-size:16px}
/* Empty/Loading */
.empty-state{text-align:center;padding:60px 20px;color:#aaa}
.empty-state .icon{font-size:64px;margin-bottom:16px}
.loading{text-align:center;padding:60px;color:#aaa}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:90}
.cat-suggestions{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.cat-chip{background:#f0f0f0;border:none;border-radius:20px;padding:4px 12px;font-size:12px;cursor:pointer;transition:.2s;font-family:inherit}
.cat-chip:hover{background:#ff6a00;color:#fff}
/* RESEÑAS MODAL */
.resenas-modal{background:#fff;border-radius:14px;padding:28px;width:100%;max-width:680px;margin:auto;max-height:85vh;overflow-y:auto}
.resena-row{display:flex;align-items:flex-start;gap:12px;padding:14px 0;border-bottom:1px solid #f5f5f5}
.resena-row:last-child{border-bottom:none}
.resena-avatar{width:36px;height:36px;border-radius:50%;background:#ff6a00;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;flex-shrink:0}
.resena-body{flex:1}
.resena-nombre{font-weight:700;font-size:14px}
.resena-stars{color:#f59e0b;font-size:13px}
.resena-texto{font-size:13px;color:#555;margin-top:4px;line-height:1.5}
.resena-meta{font-size:11px;color:#aaa;margin-top:4px}
.resena-actions{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
.badge-aprobada{background:#d4edda;color:#155724;border-radius:12px;padding:2px 8px;font-size:11px;font-weight:700}
.badge-pendiente{background:#fff3cd;color:#856404;border-radius:12px;padding:2px 8px;font-size:11px;font-weight:700}
.badge-manual{background:#e8eaf6;color:#3949ab;border-radius:12px;padding:2px 8px;font-size:11px;font-weight:700}
.form-resena-manual{margin-top:20px;padding-top:16px;border-top:1px solid #eee}
.form-resena-manual h4{font-size:15px;font-weight:700;margin-bottom:14px}
.star-row{display:flex;gap:4px;margin-bottom:12px}
.star-row button{background:none;border:none;font-size:24px;cursor:pointer;padding:0;color:#ddd;transition:.2s}
.star-row button.on{color:#f59e0b}
@media(max-width:768px){
  .sidebar{transform:translateX(-220px)}
  .sidebar.open{transform:translateX(0)}
  .sidebar-overlay.show{display:block}
  .main{margin-left:0}
  .hamburger{display:block}
  .products-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr))}
  .form-row{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- Input de imágenes PERMANENTE (no se recrea) -->
<input type="file" id="img-file-input-global" accept="image/jpeg,image/png,image/webp" multiple style="display:none">

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
    </nav>
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

<!-- ── MODAL PRODUCTO ── -->
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
          <?php foreach (['Ropa','Calzado','Electrónica','Hogar','Belleza','Accesorios','Alimentos','Deportes'] as $cat): ?>
          <button type="button" class="cat-chip" onclick="setCategoria('<?= $cat ?>')"><?= $cat ?></button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Imágenes (hasta 5) -->
      <div class="form-group">
        <label>Imágenes <span style="color:#999;font-weight:400">(hasta 5 · JPG/PNG/WebP · máx 8MB c/u)</span></label>
        <div class="imgs-grid" id="imgs-grid"></div>
      </div>

      <!-- Videos (hasta 2) con progress bar -->
      <div class="form-group">
        <label>Videos <span style="color:#999;font-weight:400">(hasta 2 · MP4 · máx 50MB c/u)</span></label>
        <div id="videos-list" class="videos-list"></div>
        <div style="margin-top:8px">
          <label id="video-upload-label" style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;background:#f0f4ff;border:1px solid #ccd9ff;border-radius:8px;padding:6px 14px;font-size:13px;color:#3366cc;font-weight:600">
            <input type="file" id="video-file-input" accept="video/mp4,video/webm" multiple style="display:none"> 📹 Agregar video
          </label>
        </div>
        <div class="upload-progress" id="video-progress">
          <div class="progress-bar-wrap"><div class="progress-bar-fill" id="video-progress-bar"></div></div>
          <div class="progress-text" id="video-progress-text">Subiendo... 0%</div>
        </div>
      </div>

      <!-- Promociones / Variantes -->
      <div class="form-group">
        <label>Tabla de promociones / variantes <span style="color:#999;font-weight:400">(opcional)</span></label>
        <table class="promo-table">
          <thead><tr><th>Nombre</th><th>Precio</th><th>Detalle</th><th></th></tr></thead>
          <tbody id="promo-tbody"></tbody>
        </table>
        <button type="button" class="btn-add-promo" onclick="addPromoRow()">＋ Agregar fila</button>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="cerrarModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="btn-guardar">Guardar Producto</button>
      </div>
    </form>
  </div>
</div>

<!-- ── MODAL RESEÑAS ── -->
<div class="modal-overlay" id="resenas-overlay">
  <div class="resenas-modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h2 id="resenas-titulo" style="font-size:18px;font-weight:700">⭐ Reseñas</h2>
      <button onclick="cerrarResenas()" style="background:none;border:none;font-size:22px;cursor:pointer">✕</button>
    </div>

    <div id="resenas-lista"><div class="loading">Cargando...</div></div>

    <!-- Agregar reseña manual -->
    <div class="form-resena-manual">
      <h4>➕ Agregar reseña manual</h4>
      <div class="form-row">
        <div class="form-group">
          <label>Nombre</label>
          <input type="text" id="rm-nombre" placeholder="Nombre del cliente">
        </div>
        <div class="form-group">
          <label>País (opcional)</label>
          <input type="text" id="rm-pais" placeholder="Perú">
        </div>
      </div>
      <div class="form-group">
        <label>Puntuación</label>
        <div class="star-row" id="star-row">
          <button type="button" data-v="1" onclick="setStars(1)">★</button>
          <button type="button" data-v="2" onclick="setStars(2)">★</button>
          <button type="button" data-v="3" onclick="setStars(3)">★</button>
          <button type="button" data-v="4" onclick="setStars(4)">★</button>
          <button type="button" data-v="5" onclick="setStars(5)">★</button>
        </div>
      </div>
      <div class="form-group">
        <label>Comentario *</label>
        <textarea id="rm-comentario" placeholder="Escribe el comentario..."></textarea>
      </div>
      <div class="form-group">
        <label>Fecha</label>
        <input type="date" id="rm-fecha">
      </div>
      <button class="btn btn-primary" onclick="crearResenaManual()">Guardar reseña</button>
      <span id="rm-msg" style="margin-left:12px;font-size:13px;color:green"></span>
    </div>
  </div>
</div>

<script>
let slugTienda = '';
let imagenesExistentes = []; // [{url}]
let imagenesNuevas     = []; // File[]
let videosExistentes   = [];
let videosNuevos       = [];
let imagenesEliminar   = [];
let videosEliminar     = [];
let resenasProductoId  = null;
let starsVal           = 0;

const IMG_MAX_PX  = 1200;
const IMG_QUALITY = 0.85;

// ── INPUT DE IMAGEN PERMANENTE ────────────────────────────────
// Un solo listener, nunca recreado
document.getElementById('img-file-input-global').addEventListener('change', async function(e) {
  const files = Array.from(e.target.files);
  const total = imagenesExistentes.length + imagenesNuevas.length;
  const slots = 5 - total;
  for (const f of files.slice(0, slots)) {
    const compressed = await comprimirImagen(f);
    imagenesNuevas.push(compressed);
  }
  this.value = ''; // reset para poder volver a seleccionar el mismo archivo
  renderImgsGrid();
});

function triggerImgInput() {
  document.getElementById('img-file-input-global').click();
}

// ── COMPRESIÓN DE IMAGEN ──────────────────────────────────────
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

// ── RENDER IMÁGENES ───────────────────────────────────────────
function renderImgsGrid() {
  const grid  = document.getElementById('imgs-grid');
  const total = imagenesExistentes.length + imagenesNuevas.length;
  grid.innerHTML = '';

  imagenesExistentes.forEach((img, i) => {
    const d = document.createElement('div');
    d.className = 'img-thumb';
    d.innerHTML = `<img src="${img.url}" alt=""><button class="del-img" onclick="delImgExistente(${i})" type="button">✕</button>`;
    grid.appendChild(d);
  });
  imagenesNuevas.forEach((file, i) => {
    const d   = document.createElement('div');
    d.className = 'img-thumb';
    const url = URL.createObjectURL(file);
    d.innerHTML = `<img src="${url}" alt=""><button class="del-img" onclick="delImgNueva(${i})" type="button">✕</button>`;
    grid.appendChild(d);
  });

  if (total < 5) {
    const btn = document.createElement('div');
    btn.className = 'add-img-btn';
    btn.title     = 'Agregar imagen';
    btn.textContent = '＋';
    btn.onclick   = triggerImgInput;
    grid.appendChild(btn);
  }
}

function delImgExistente(i) { imagenesEliminar.push(imagenesExistentes[i].url); imagenesExistentes.splice(i, 1); renderImgsGrid(); }
function delImgNueva(i)     { imagenesNuevas.splice(i, 1); renderImgsGrid(); }

// ── RENDER VIDEOS ─────────────────────────────────────────────
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
    <td><input type="text" placeholder="Ej: Pack x3" value="${esc(nombre)}" class="promo-nombre"></td>
    <td><input type="number" placeholder="0.00" step="0.01" value="${precio}" class="promo-precio" style="width:90px"></td>
    <td><input type="text" placeholder="Descripción" value="${esc(detalle)}" class="promo-detalle"></td>
    <td><button type="button" class="btn-del-promo" onclick="this.closest('tr').remove()">🗑</button></td>`;
  document.getElementById('promo-tbody').appendChild(tr);
}

function getPromociones() {
  return Array.from(document.querySelectorAll('#promo-tbody tr')).map(tr => ({
    nombre:  tr.querySelector('.promo-nombre').value.trim(),
    precio:  parseFloat(tr.querySelector('.promo-precio').value) || 0,
    detalle: tr.querySelector('.promo-detalle').value.trim(),
  })).filter(p => p.nombre);
}

function setCategoria(val) { document.getElementById('p-categoria').value = val; }

// ── MODAL PRODUCTO ────────────────────────────────────────────
function abrirModal(producto = null) {
  document.getElementById('form-producto').reset();
  imagenesExistentes = []; imagenesNuevas = [];
  videosExistentes   = []; videosNuevos   = [];
  imagenesEliminar   = []; videosEliminar = [];
  document.getElementById('promo-tbody').innerHTML = '';
  document.getElementById('producto-id').value = '';
  document.getElementById('video-progress').style.display = 'none';

  if (producto) {
    document.getElementById('modal-titulo').textContent = '✏️ Editar Producto';
    document.getElementById('producto-id').value    = producto.id;
    document.getElementById('p-nombre').value       = producto.nombre;
    document.getElementById('p-descripcion').value  = producto.descripcion;
    document.getElementById('p-precio').value       = producto.precio;
    document.getElementById('p-stock').value        = producto.stock;
    document.getElementById('p-categoria').value    = producto.categoria;
    imagenesExistentes = (producto.imagenes || []).map(u => ({ url: u }));
    videosExistentes   = [...(producto.videos || [])];
    (producto.promociones || []).forEach(p => addPromoRow(p.nombre, p.precio, p.detalle));
  } else {
    document.getElementById('modal-titulo').textContent = '＋ Nuevo Producto';
  }

  renderImgsGrid();
  renderVideosList();
  document.getElementById('modal-overlay').classList.add('open');
}

function cerrarModal() { document.getElementById('modal-overlay').classList.remove('open'); }
document.getElementById('modal-overlay').addEventListener('click', function(e) { if (e.target === this) cerrarModal(); });

// ── GUARDAR CON XHR + PROGRESS ────────────────────────────────
document.getElementById('form-producto').addEventListener('submit', function(e) {
  e.preventDefault();
  const btn = document.getElementById('btn-guardar');
  btn.disabled = true; btn.textContent = 'Guardando...';

  const id     = document.getElementById('producto-id').value;
  const accion = id ? 'editar' : 'crear';
  const fd     = new FormData();

  fd.append('id',                   id);
  fd.append('nombre',               document.getElementById('p-nombre').value);
  fd.append('descripcion',          document.getElementById('p-descripcion').value);
  fd.append('precio',               document.getElementById('p-precio').value);
  fd.append('stock',                document.getElementById('p-stock').value);
  fd.append('categoria',            document.getElementById('p-categoria').value);
  fd.append('promociones',          JSON.stringify(getPromociones()));
  fd.append('imagenes_eliminar',    JSON.stringify(imagenesEliminar));
  fd.append('videos_eliminar',      JSON.stringify(videosEliminar));
  fd.append('imagenes_existentes',  JSON.stringify(imagenesExistentes.map(img => img.url || img)));
  imagenesNuevas.forEach(f => fd.append('imagenes[]', f));
  videosNuevos.forEach(f   => fd.append('videos[]',   f));

  const hasVideos = videosNuevos.length > 0;
  if (hasVideos) {
    const prog = document.getElementById('video-progress');
    const bar  = document.getElementById('video-progress-bar');
    const txt  = document.getElementById('video-progress-text');
    prog.style.display = 'block';

    const xhr = new XMLHttpRequest();
    xhr.upload.onprogress = function(ev) {
      if (ev.lengthComputable) {
        const pct = Math.round(ev.loaded / ev.total * 100);
        bar.style.width = pct + '%';
        txt.textContent = 'Subiendo... ' + pct + '%';
      }
    };
    xhr.onload = function() {
      prog.style.display = 'none';
      btn.disabled = false; btn.textContent = 'Guardar Producto';
      try {
        const data = JSON.parse(xhr.responseText);
        if (data.ok) { cerrarModal(); cargarProductos(); }
        else alert('Error: ' + data.error);
      } catch(err) { alert('Error al procesar respuesta'); }
    };
    xhr.onerror = function() {
      prog.style.display = 'none';
      btn.disabled = false; btn.textContent = 'Guardar Producto';
      alert('Error de red al subir el video');
    };
    xhr.open('POST', '/api/productos?accion=' + accion);
    xhr.send(fd);
  } else {
    fetch('/api/productos?accion=' + accion, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        btn.disabled = false; btn.textContent = 'Guardar Producto';
        if (data.ok) { cerrarModal(); cargarProductos(); }
        else alert('Error: ' + data.error);
      })
      .catch(() => { btn.disabled = false; btn.textContent = 'Guardar Producto'; alert('Error de red.'); });
  }
});

// ── TOGGLE ACTIVO ─────────────────────────────────────────────
async function toggleActivo(id, activo) {
  const fd = new FormData();
  fd.append('id', id); fd.append('activo', activo ? 'true' : 'false');
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

// ── CARGAR PRODUCTOS ──────────────────────────────────────────
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
  c.innerHTML = '<div class="products-grid">' + productos.map(p => `
    <div class="product-card ${!p.activo ? 'product-inactive' : ''}">
      <div class="toggle-wrap">
        <label class="toggle" title="${p.activo ? 'Activo' : 'Inactivo'}">
          <input type="checkbox" ${p.activo ? 'checked' : ''} onchange="toggleActivo('${p.id}', this.checked)">
          <span class="toggle-slider"></span>
        </label>
      </div>
      ${p.imagen ? `<img class="product-img" src="${p.imagen}" alt="${esc(p.nombre)}" loading="lazy">` : `<div class="product-img-placeholder">🖼️</div>`}
      <div class="product-info">
        ${p.categoria ? `<span class="product-cat">${esc(p.categoria)}</span>` : ''}
        <div class="product-name" title="${esc(p.nombre)}">${esc(p.nombre)}</div>
        <div class="product-price">S/. ${parseFloat(p.precio).toFixed(2)}</div>
        <div class="product-stock">Stock: ${p.stock} u${p.imagenes && p.imagenes.length > 1 ? ` · 📷 ${p.imagenes.length}` : ''}${p.videos && p.videos.length ? ` · 🎬 ${p.videos.length}` : ''}${p.promociones && p.promociones.length ? ` · 🏷️ ${p.promociones.length}` : ''}</div>
        <div class="product-actions">
          <button class="btn btn-edit" onclick='abrirModal(${JSON.stringify(p)})'>✏️ Editar</button>
          <button class="btn btn-review" onclick="abrirResenas('${p.id}', '${esc(p.nombre)}')">⭐ Reseñas</button>
          <button class="btn btn-danger" onclick="eliminarProducto('${p.id}', this)">🗑</button>
        </div>
      </div>
    </div>`).join('') + '</div>';
}

function mostrarError() {
  document.getElementById('productos-container').innerHTML = '<div class="empty-state"><p>Error cargando productos.</p></div>';
}

// ── RESEÑAS ───────────────────────────────────────────────────
function abrirResenas(prodId, prodNombre) {
  resenasProductoId = prodId;
  starsVal = 5;
  updateStarsUI();
  document.getElementById('resenas-titulo').textContent = '⭐ Reseñas — ' + prodNombre;
  document.getElementById('rm-nombre').value = '';
  document.getElementById('rm-pais').value   = '';
  document.getElementById('rm-comentario').value = '';
  document.getElementById('rm-fecha').value  = new Date().toISOString().split('T')[0];
  document.getElementById('rm-msg').textContent = '';
  document.getElementById('resenas-overlay').classList.add('open');
  cargarResenas();
}

function cerrarResenas() {
  document.getElementById('resenas-overlay').classList.remove('open');
  resenasProductoId = null;
}

document.getElementById('resenas-overlay').addEventListener('click', function(e) { if (e.target === this) cerrarResenas(); });

async function cargarResenas() {
  document.getElementById('resenas-lista').innerHTML = '<div class="loading">Cargando...</div>';
  try {
    const r = await fetch('/api/resenas?accion=listar_panel&producto_id=' + resenasProductoId);
    const d = await r.json();
    if (!d.ok) { document.getElementById('resenas-lista').innerHTML = '<p style="color:#888">Error cargando reseñas.</p>'; return; }
    renderResenas(d.resenas);
  } catch(e) { document.getElementById('resenas-lista').innerHTML = '<p style="color:#888">Error de red.</p>'; }
}

function renderResenas(resenas) {
  const el = document.getElementById('resenas-lista');
  if (!resenas.length) { el.innerHTML = '<p style="color:#aaa;text-align:center;padding:20px 0">No hay reseñas aún.</p>'; return; }
  el.innerHTML = resenas.map(r => `
    <div class="resena-row" id="resena-${r.id}">
      <div class="resena-avatar">${r.nombre.charAt(0).toUpperCase()}</div>
      <div class="resena-body">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <span class="resena-nombre">${esc(r.nombre)}</span>
          ${r.pais ? `<span style="font-size:11px;color:#aaa">${esc(r.pais)}</span>` : ''}
          <span class="${r.aprobada ? 'badge-aprobada' : 'badge-pendiente'}">${r.aprobada ? '✅ Aprobada' : '⏳ Pendiente'}</span>
          ${r.manual ? '<span class="badge-manual">✍️ Manual</span>' : ''}
        </div>
        <div class="resena-stars">${'★'.repeat(r.estrellas)}${'☆'.repeat(5-r.estrellas)}</div>
        <div class="resena-texto">${esc(r.comentario)}</div>
        <div class="resena-meta">${r.fecha ? new Date(r.fecha).toLocaleDateString('es-PE') : ''}</div>
        <div class="resena-actions">
          ${!r.aprobada ? `<button class="btn btn-sm btn-edit" onclick="moderarResena('${r.id}', true)">✅ Aprobar</button>` : `<button class="btn btn-sm btn-outline" onclick="moderarResena('${r.id}', false)">❌ Retirar aprobación</button>`}
          <button class="btn btn-sm btn-danger" onclick="eliminarResena('${r.id}')">🗑 Eliminar</button>
        </div>
      </div>
    </div>`).join('');
}

async function moderarResena(resenaId, aprobada) {
  const fd = new FormData();
  fd.append('producto_id', resenasProductoId);
  fd.append('resena_id', resenaId);
  fd.append('aprobada', aprobada ? 'true' : 'false');
  await fetch('/api/resenas?accion=moderar', { method: 'POST', body: fd });
  cargarResenas();
}

async function eliminarResena(resenaId) {
  if (!confirm('¿Eliminar esta reseña?')) return;
  await fetch('/api/resenas?accion=eliminar&producto_id=' + resenasProductoId + '&resena_id=' + resenaId, { method: 'DELETE' });
  cargarResenas();
}

function setStars(val) { starsVal = val; updateStarsUI(); }
function updateStarsUI() {
  document.querySelectorAll('#star-row button').forEach(b => b.classList.toggle('on', parseInt(b.dataset.v) <= starsVal));
}

async function crearResenaManual() {
  const nombre     = document.getElementById('rm-nombre').value.trim();
  const comentario = document.getElementById('rm-comentario').value.trim();
  if (!nombre || !comentario) return alert('Nombre y comentario son requeridos');
  if (!starsVal) return alert('Selecciona una puntuación');

  const fd = new FormData();
  fd.append('producto_id', resenasProductoId);
  fd.append('nombre',     nombre);
  fd.append('pais',       document.getElementById('rm-pais').value.trim());
  fd.append('estrellas',  starsVal);
  fd.append('comentario', comentario);
  fd.append('fecha',      document.getElementById('rm-fecha').value);

  const r = await fetch('/api/resenas?accion=crear_manual', { method: 'POST', body: fd });
  const d = await r.json();
  if (d.ok) {
    document.getElementById('rm-msg').textContent = '✅ Reseña guardada';
    document.getElementById('rm-nombre').value = '';
    document.getElementById('rm-comentario').value = '';
    starsVal = 5; updateStarsUI();
    cargarResenas();
    setTimeout(() => document.getElementById('rm-msg').textContent = '', 3000);
  } else {
    alert('Error: ' + d.error);
  }
}

// ── UTILS ─────────────────────────────────────────────────────
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

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

function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); document.getElementById('overlay').classList.toggle('show'); }
function closeSidebar()   { document.getElementById('sidebar').classList.remove('open'); document.getElementById('overlay').classList.remove('show'); }
function logout()         { fetch('/api/logout').then(() => location.href = '/login'); }
</script>
</body>
</html>
