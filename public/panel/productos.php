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
.btn-edit{background:#f0f4ff;color:#1a56db;border:1px solid #c7d7fa;font-size:12px;padding:5px 10px;border-radius:8px;cursor:pointer;font-weight:600;transition:.2s}
.btn-edit:hover{background:#1a56db;color:#fff}
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
/* Toast */
.toast-msg{position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:12px;font-size:13px;font-weight:600;z-index:9999;opacity:0;transform:translateY(10px);transition:all .3s;pointer-events:none;max-width:320px}
.toast-msg.show{opacity:1;transform:translateY(0)}
.toast-msg.success{background:#1a1a2e;color:#fff}
.toast-msg.error{background:#e03;color:#fff}
/* Imágenes existentes en modal edición */
.img-exist-grid{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
.img-exist-item{position:relative;width:80px;height:80px}
.img-exist-item img{width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid #eee}
.img-exist-del{position:absolute;top:-6px;right:-6px;background:#ff4444;color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1}
/* ── Reseñas ── */
.resenas-section{display:none;margin-top:24px;border-top:1px solid #eee;padding-top:20px}
.resenas-section.visible{display:block}
.resenas-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.resenas-header h3{font-size:15px;font-weight:700;color:#333}
.resena-card{border:1px solid #eee;border-radius:10px;padding:12px 14px;margin-bottom:10px;background:#fafafa;transition:.2s}
.resena-card.pendiente{border-left:3px solid #f59e0b;background:#fffbf0}
.resena-card.aprobada{border-left:3px solid #22c55e;background:#f0fff4}
.resena-top{display:flex;align-items:flex-start;gap:10px;margin-bottom:8px}
.resena-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0}
.resena-meta{flex:1;min-width:0}
.resena-autor{font-weight:600;font-size:13px;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.resena-fecha{font-size:11px;color:#aaa;margin-top:1px}
.resena-stars{color:#f59e0b;font-size:13px;letter-spacing:1px}
.resena-badge{display:inline-block;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;margin-left:6px}
.badge-pend{background:#fef3c7;color:#92400e}
.badge-apro{background:#dcfce7;color:#166534}
.resena-texto{font-size:13px;color:#555;line-height:1.55;margin-bottom:10px}
.resena-acciones{display:flex;gap:6px;flex-wrap:wrap}
.btn-aprobar{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;font-size:11px;padding:4px 10px;border-radius:6px;cursor:pointer;font-weight:600;transition:.2s}
.btn-aprobar:hover{background:#16a34a;color:#fff}
.btn-rechazar{background:#fff7ed;color:#ea580c;border:1px solid #fed7aa;font-size:11px;padding:4px 10px;border-radius:6px;cursor:pointer;font-weight:600;transition:.2s}
.btn-rechazar:hover{background:#ea580c;color:#fff}
.btn-edit-r{background:#f0f4ff;color:#1a56db;border:1px solid #c7d7fa;font-size:11px;padding:4px 10px;border-radius:6px;cursor:pointer;font-weight:600;transition:.2s}
.btn-edit-r:hover{background:#1a56db;color:#fff}
.btn-del-r{background:#fff5f5;color:#e03030;border:1px solid #fecaca;font-size:11px;padding:4px 10px;border-radius:6px;cursor:pointer;font-weight:600;transition:.2s}
.btn-del-r:hover{background:#e03030;color:#fff}
/* Form nueva reseña */
.resena-form{background:#f5f5f5;border-radius:10px;padding:14px;margin-top:12px}
.resena-form h4{font-size:13px;font-weight:700;margin-bottom:10px;color:#444}
.resena-form input,.resena-form textarea,.resena-form select{width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:7px;font-size:13px;outline:none;font-family:inherit;margin-bottom:8px}
.resena-form textarea{min-height:70px;resize:vertical}
.star-picker{display:flex;gap:4px;margin-bottom:10px}
.star-picker span{font-size:22px;cursor:pointer;color:#ddd;transition:color .15s}
.star-picker span.on{color:#f59e0b}
.resena-form .form-row-r{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.resenas-empty{text-align:center;padding:20px;color:#aaa;font-size:13px}
/* Modal confirmación */
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
<div class="toast-msg" id="toast-msg"></div>

<!-- MODAL AGREGAR / EDITAR PRODUCTO -->
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

      <!-- Imágenes existentes (solo en edición) -->
      <div class="form-group" id="grupo-img-exist" style="display:none">
        <label>📷 Imágenes actuales</label>
        <div class="img-exist-grid" id="img-exist-grid"></div>
      </div>
      <!-- Videos existentes (solo en edición) -->
      <div class="form-group" id="grupo-vid-exist" style="display:none">
        <label>🎥 Videos actuales</label>
        <div class="img-exist-grid" id="vid-exist-grid"></div>
      </div>

      <!-- Agregar imágenes nuevas -->
      <div class="form-group">
        <label>📷 Agregar imágenes <span style="color:#aaa;font-weight:400;font-size:12px">(puedes seleccionar varias)</span></label>
        <div class="drop-zone" id="dropZone">
          <input type="file" id="p-imagenes" accept="image/jpeg,image/png,image/webp" multiple>
          <div class="drop-icon">🖼️</div>
          <div class="drop-text">Arrastra imágenes aquí o <strong>haz clic para seleccionar</strong></div>
          <div class="drop-text" style="margin-top:4px;font-size:11px">JPG, PNG, WEBP · máx 5MB c/u</div>
        </div>
        <div id="preview-imagenes" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px"></div>
      </div>

      <!-- Agregar videos -->
      <div class="form-group">
        <label>🎥 Agregar video <span style="color:#aaa;font-weight:400;font-size:12px">(MP4, WEBM, MOV · máx 100MB)</span></label>
        <div class="drop-zone" id="dropZoneVideo" style="border-color:#7c3aed">
          <input type="file" id="p-videos" accept="video/mp4,video/webm,video/quicktime" multiple>
          <div class="drop-icon">🎬</div>
          <div class="drop-text">Arrastra tu video aquí o <strong>haz clic para seleccionar</strong></div>
        </div>
        <div id="preview-videos" style="margin-top:10px"></div>
      </div>

      <!-- Barra de progreso upload -->
      <div id="upload-progress" style="display:none;margin-bottom:12px">
        <div style="font-size:13px;color:#555;margin-bottom:6px" id="upload-label">Subiendo archivos...</div>
        <div style="background:#f0f0f0;border-radius:99px;height:8px;overflow:hidden">
          <div id="upload-bar" style="height:100%;background:#ff6a00;width:0%;transition:width .3s;border-radius:99px"></div>
        </div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="cerrarModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="btn-guardar">Guardar Producto</button>
      </div>
    </form>

    <!-- SECCIÓN RESEÑAS (solo en edición) -->
    <div class="resenas-section" id="resenas-section">
      <div class="resenas-header">
        <h3>⭐ Reseñas del producto <span id="resenas-count" style="font-weight:400;color:#aaa;font-size:13px"></span></h3>
        <button type="button" class="btn-edit" style="font-size:11px" onclick="toggleFormResena()">＋ Agregar reseña</button>
      </div>
      <div id="resenas-lista"></div>

      <!-- Formulario agregar/editar reseña -->
      <div class="resena-form" id="resena-form" style="display:none">
        <h4 id="resena-form-titulo">＋ Nueva reseña</h4>
        <input type="hidden" id="resena-id-edit" value="">
        <div class="form-row-r">
          <input type="text" id="r-autor" placeholder="Nombre del autor" maxlength="60">
          <select id="r-aprobada">
            <option value="true">✅ Aprobada</option>
            <option value="false">⏳ Pendiente</option>
          </select>
        </div>
        <div class="star-picker" id="star-picker">
          <span data-v="1">★</span><span data-v="2">★</span><span data-v="3">★</span><span data-v="4">★</span><span data-v="5">★</span>
        </div>
        <textarea id="r-texto" placeholder="Texto de la reseña..."></textarea>
        <div style="display:flex;gap:8px;justify-content:flex-end">
          <button type="button" class="btn btn-outline btn-sm" onclick="cancelarFormResena()">Cancelar</button>
          <button type="button" class="btn btn-primary btn-sm" onclick="guardarResena()">Guardar reseña</button>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
let nombreUsuario    = '';
let slugTienda       = '';
let productosMap     = {};
let imagenesEliminar  = [];
let imagenesExistentes= [];
let videosEliminar    = [];
let videosExistentes  = [];
let archivosImgPendientes = []; // File[] comprimidos listos para subir
let archivosVidPendientes = []; // File[] videos

// ── Sesión ──
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

// ── Cargar / Render productos ──
function cargarProductos() {
  document.getElementById('productos-container').innerHTML = '<div class="loading">Cargando...</div>';
  fetch('/api/productos?accion=listar')
    .then(r => r.json())
    .then(d => { if (!d.ok) { mostrarError(); return; } renderProductos(d.productos); })
    .catch(mostrarError);
}
function renderProductos(productos) {
  const container = document.getElementById('productos-container');
  productosMap = {};
  productos.forEach(p => { productosMap[p.id] = p; });
  if (!productos.length) {
    container.innerHTML = `<div class="empty-state"><div class="icon">📦</div><p>Aún no tienes productos.</p><p style="margin-top:8px;font-size:14px">Haz clic en <strong>Agregar Producto</strong> para comenzar.</p></div>`;
    return;
  }
  const html = '<div class="products-grid">' + productos.map(p => {
    const imgSrc = (p.imagenes && p.imagenes.length) ? p.imagenes[0] : (p.imagen || '');
    const hasVid = p.videos && p.videos.length;
    return `<div class="product-card">
      ${imgSrc ? `<img class="product-img" src="${imgSrc}" alt="${p.nombre}" loading="lazy">` : `<div class="product-img-placeholder">${hasVid ? '🎥' : '🖼️'}</div>`}
      <div class="product-info">
        ${p.categoria ? `<span class="product-cat">${p.categoria}</span>` : ''}
        <div class="product-name" title="${p.nombre}">${p.nombre}</div>
        <div class="product-price">S/. ${parseFloat(p.precio).toFixed(2)}</div>
        <div class="product-stock">Stock: ${p.stock} unid.${hasVid ? ' · 🎥 '+p.videos.length+' video(s)' : ''}${p.imagenes&&p.imagenes.length>1?' · 📷 '+p.imagenes.length+' fotos':''}</div>
        <div class="product-actions">
          <button class="btn btn-edit" onclick="editarProducto('${p.id}')">✏️ Editar</button>
          <button class="btn btn-danger" onclick="eliminarProducto('${p.id}', this)">🗑 Eliminar</button>
        </div>
      </div>
    </div>`;
  }).join('') + '</div>';
  container.innerHTML = html;
}
function mostrarError() {
  document.getElementById('productos-container').innerHTML = '<div class="empty-state"><p>Error cargando productos.</p></div>';
}

// ── Categoría rápida ──
function setCategoria(val) { document.getElementById('p-categoria').value = val; }

// ── Compresión de imágenes ──
const IMG_MAX_PX  = 1200;
const IMG_QUALITY = 0.85;
const IMG_FORMAT  = 'image/webp';

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
        const canvas = document.createElement('canvas');
        canvas.width = w; canvas.height = h;
        canvas.getContext('2d').drawImage(img, 0, 0, w, h);
        canvas.toBlob(blob => {
          if (!blob) { resolve(file); return; }
          resolve(new File([blob], file.name.replace(/\.[^.]+$/, '.webp'), { type: IMG_FORMAT }));
        }, IMG_FORMAT, IMG_QUALITY);
      };
      img.onerror = () => resolve(file);
      img.src = e.target.result;
    };
    reader.onerror = () => resolve(file);
    reader.readAsDataURL(file);
  });
}

// ── Previews de imágenes nuevas ──
async function procesarImagenes(files) {
  const previewBox = document.getElementById('preview-imagenes');
  const allowed = ['image/jpeg','image/png','image/webp'];
  for (const file of files) {
    if (!allowed.includes(file.type))   { showToast('Solo JPG/PNG/WEBP', 'error'); continue; }
    if (file.size > 15 * 1024 * 1024)  { showToast('Imagen muy grande (máx 15MB)', 'error'); continue; }
    const compressed = await comprimirImagen(file);
    archivosImgPendientes.push(compressed);
    const idx = archivosImgPendientes.length - 1;
    const div = document.createElement('div');
    div.style.cssText = 'position:relative;width:80px;height:80px';
    div.innerHTML = `<img src="${URL.createObjectURL(compressed)}" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid #eee">
      <button type="button" onclick="quitarImgNueva(${idx},this.parentNode)" style="position:absolute;top:-6px;right:-6px;background:#ff4444;color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:12px;cursor:pointer">×</button>
      <div style="font-size:9px;color:#22a862;text-align:center;margin-top:2px">${(compressed.size/1024).toFixed(0)}KB</div>`;
    previewBox.appendChild(div);
  }
}
function quitarImgNueva(idx, el) {
  archivosImgPendientes[idx] = null;
  el.remove();
}

// ── Previews de videos nuevos ──
function procesarVideos(files) {
  const previewBox = document.getElementById('preview-videos');
  const allowed = ['video/mp4','video/webm','video/quicktime'];
  for (const file of files) {
    if (!allowed.includes(file.type))   { showToast('Solo MP4/WEBM/MOV', 'error'); continue; }
    if (file.size > 100 * 1024 * 1024) { showToast('Video muy grande (máx 100MB)', 'error'); continue; }
    archivosVidPendientes.push(file);
    const idx = archivosVidPendientes.length - 1;
    const div = document.createElement('div');
    div.style.cssText = 'display:flex;align-items:center;gap:8px;padding:8px 10px;background:#f8f0ff;border-radius:8px;margin-bottom:6px';
    div.innerHTML = `<span style="font-size:20px">🎬</span>
      <div style="flex:1;min-width:0"><div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${file.name}</div>
      <div style="font-size:11px;color:#888">${(file.size/1024/1024).toFixed(1)} MB</div></div>
      <button type="button" onclick="quitarVidNuevo(${idx},this.parentNode)" style="background:#ff4444;color:#fff;border:none;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px">✕</button>`;
    previewBox.appendChild(div);
  }
}
function quitarVidNuevo(idx, el) {
  archivosVidPendientes[idx] = null;
  el.remove();
}

// ── Drag & drop imágenes ──
const dropZone  = document.getElementById('dropZone');
const fileInputImg = document.getElementById('p-imagenes');
fileInputImg.addEventListener('change', () => { procesarImagenes(Array.from(fileInputImg.files)); fileInputImg.value=''; });
dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => { e.preventDefault(); dropZone.classList.remove('dragover'); procesarImagenes(Array.from(e.dataTransfer.files)); });

// ── Drag & drop videos ──
const dropZoneVideo = document.getElementById('dropZoneVideo');
const fileInputVid  = document.getElementById('p-videos');
fileInputVid.addEventListener('change', () => { procesarVideos(Array.from(fileInputVid.files)); fileInputVid.value=''; });
dropZoneVideo.addEventListener('dragover',  e => { e.preventDefault(); dropZoneVideo.classList.add('dragover'); });
dropZoneVideo.addEventListener('dragleave', () => dropZoneVideo.classList.remove('dragover'));
dropZoneVideo.addEventListener('drop', e => { e.preventDefault(); dropZoneVideo.classList.remove('dragover'); procesarVideos(Array.from(e.dataTransfer.files)); });

// ── Modal ──
function abrirModal(producto = null) {
  document.getElementById('form-producto').reset();
  document.getElementById('dropZone').style.borderColor = '';
  document.getElementById('preview-imagenes').innerHTML = '';
  document.getElementById('preview-videos').innerHTML   = '';
  document.getElementById('img-exist-grid').innerHTML   = '';
  document.getElementById('vid-exist-grid').innerHTML   = '';
  document.getElementById('grupo-img-exist').style.display = 'none';
  document.getElementById('grupo-vid-exist').style.display = 'none';
  archivosImgPendientes = [];
  archivosVidPendientes = [];
  imagenesEliminar  = [];
  imagenesExistentes= [];
  videosEliminar    = [];
  videosExistentes  = [];

  // Ocultar sección reseñas por defecto
  document.getElementById('resenas-section').classList.remove('visible');
  document.getElementById('resenas-lista').innerHTML = '';
  document.getElementById('resena-form').style.display = 'none';
  document.getElementById('resenas-count').textContent = '';

  if (producto) {
    document.getElementById('producto-id').value       = producto.id;
    document.getElementById('modal-titulo').textContent = '✏️ Editar Producto';
    document.getElementById('btn-guardar').textContent  = 'Guardar Cambios';
    document.getElementById('p-nombre').value      = producto.nombre      || '';
    document.getElementById('p-descripcion').value = producto.descripcion || '';
    document.getElementById('p-precio').value      = producto.precio      || '';
    document.getElementById('p-stock').value       = producto.stock       ?? 0;
    document.getElementById('p-categoria').value   = producto.categoria   || '';
    // Imágenes existentes
    const imgs = (producto.imagenes && producto.imagenes.length) ? producto.imagenes : (producto.imagen ? [producto.imagen] : []);
    if (imgs.length) { imagenesExistentes = [...imgs]; renderMediaExist('img'); }
    // Videos existentes
    if (producto.videos && producto.videos.length) { videosExistentes = [...producto.videos]; renderMediaExist('vid'); }
    // Cargar reseñas del producto
    cargarResenas(producto.id);
  } else {
    document.getElementById('producto-id').value       = '';
    document.getElementById('modal-titulo').textContent = '＋ Nuevo Producto';
    document.getElementById('btn-guardar').textContent  = 'Guardar Producto';
  }
  document.getElementById('modal-overlay').classList.add('open');
}

function renderMediaExist(tipo) {
  const isImg  = tipo === 'img';
  const lista  = isImg ? imagenesExistentes : videosExistentes;
  const gridId = isImg ? 'img-exist-grid' : 'vid-exist-grid';
  const grpId  = isImg ? 'grupo-img-exist' : 'grupo-vid-exist';
  const grid   = document.getElementById(gridId);
  if (!lista.length) { document.getElementById(grpId).style.display='none'; grid.innerHTML=''; return; }
  document.getElementById(grpId).style.display = 'block';
  grid.innerHTML = lista.map((url, i) => isImg
    ? `<div class="img-exist-item"><img src="${url}" alt="img ${i+1}"><button class="img-exist-del" type="button" onclick="quitarMediaExist('${tipo}',${i})" title="Quitar">×</button></div>`
    : `<div class="img-exist-item" style="width:auto;height:auto;padding:6px 10px;background:#f8f0ff;border-radius:8px;display:flex;align-items:center;gap:8px;min-width:120px">
        <span>🎬</span><span style="font-size:12px;flex:1">Video ${i+1}</span>
        <button class="img-exist-del" type="button" onclick="quitarMediaExist('${tipo}',${i})" style="position:static;width:18px;height:18px;font-size:11px">×</button>
      </div>`
  ).join('');
}

function quitarMediaExist(tipo, i) {
  if (tipo === 'img') { imagenesEliminar.push(imagenesExistentes[i]); imagenesExistentes.splice(i, 1); renderMediaExist('img'); }
  else                { videosEliminar.push(videosExistentes[i]);     videosExistentes.splice(i, 1);   renderMediaExist('vid'); }
}

function editarProducto(id) { const p = productosMap[id]; if (p) abrirModal(p); }
function cerrarModal()      { document.getElementById('modal-overlay').classList.remove('open'); }

// ── Guardar producto ──
document.getElementById('form-producto').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn    = document.getElementById('btn-guardar');
  const pid    = document.getElementById('producto-id').value;
  const isEdit = !!pid;
  const imgsFinal = archivosImgPendientes.filter(Boolean);
  const vidsFinal = archivosVidPendientes.filter(Boolean);

  btn.disabled = true;
  btn.textContent = 'Guardando...';

  // Mostrar barra de progreso si hay archivos grandes
  const totalFiles = imgsFinal.length + vidsFinal.length;
  if (totalFiles) {
    document.getElementById('upload-progress').style.display = 'block';
    document.getElementById('upload-label').textContent = `Subiendo ${totalFiles} archivo(s) a Wasabi...`;
  }

  const formData = new FormData();
  formData.append('nombre',      document.getElementById('p-nombre').value);
  formData.append('descripcion', document.getElementById('p-descripcion').value);
  formData.append('precio',      document.getElementById('p-precio').value);
  formData.append('stock',       document.getElementById('p-stock').value);
  formData.append('categoria',   document.getElementById('p-categoria').value);
  if (isEdit) {
    formData.append('id',                   pid);
    formData.append('imagenes_existentes',  JSON.stringify(imagenesExistentes));
    formData.append('imagenes_eliminar',    JSON.stringify(imagenesEliminar));
    formData.append('videos_eliminar',      JSON.stringify(videosEliminar));
  }
  // PHP espera imagenes[] y videos[] para recibirlos como $_FILES array
  imgsFinal.forEach(f => formData.append('imagenes[]', f));
  vidsFinal.forEach(f => formData.append('videos[]', f));

  try {
    // Usar XMLHttpRequest para poder mostrar progreso real
    const result = await new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', '/api/productos?accion=' + (isEdit ? 'editar' : 'crear'));
      xhr.upload.addEventListener('progress', ev => {
        if (ev.lengthComputable) {
          const pct = Math.round(ev.loaded / ev.total * 100);
          document.getElementById('upload-bar').style.width = pct + '%';
          document.getElementById('upload-label').textContent = `Subiendo archivos... ${pct}%`;
        }
      });
      xhr.onload = () => {
        try { resolve(JSON.parse(xhr.responseText)); }
        catch { reject(new Error('Respuesta inválida del servidor: ' + xhr.responseText.substring(0,200))); }
      };
      xhr.onerror = () => reject(new Error('Error de conexión con el servidor'));
      xhr.send(formData);
    });

    if (result.ok) {
      showToast(isEdit ? '✅ Producto actualizado' : '✅ Producto creado', 'success');
      cerrarModal();
      cargarProductos();
    } else {
      showToast('Error: ' + (result.error || 'error desconocido'), 'error');
    }
  } catch (err) {
    showToast('⚠️ ' + err.message, 'error');
    console.error('Error al guardar:', err);
  } finally {
    btn.disabled = false;
    btn.textContent = isEdit ? 'Guardar Cambios' : 'Guardar Producto';
    document.getElementById('upload-progress').style.display = 'none';
    document.getElementById('upload-bar').style.width = '0%';
  }
});

// ── Eliminar producto ──
async function eliminarProducto(id, btn) {
  const ok = await modalConfirm({
    icon: '🗑️', tipo: 'danger',
    titulo: '¿Eliminar producto?',
    mensaje: 'Se eliminará el producto permanentemente. Esta acción no se puede deshacer.',
    btnTexto: 'Sí, eliminar'
  });
  if (!ok) return;
  btn.disabled = true; btn.textContent = '...';
  const res  = await fetch('/api/productos?accion=eliminar&id=' + id, { method: 'DELETE' });
  const data = await res.json();
  if (data.ok) cargarProductos();
  else { showToast('Error al eliminar.', 'error'); btn.disabled = false; btn.textContent = '🗑 Eliminar'; }
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
// ── Confirm modal ──
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
// ══════════════════════════════════════
// ── RESEÑAS ──
// ══════════════════════════════════════
let resenasProductoId = '';
let resenasCalificacion = 5;

// Star picker
document.querySelectorAll('#star-picker span').forEach(s => {
  s.addEventListener('click', () => {
    resenasCalificacion = +s.dataset.v;
    renderStarPicker();
  });
  s.addEventListener('mouseenter', () => {
    document.querySelectorAll('#star-picker span').forEach((x, i) => {
      x.classList.toggle('on', i < +s.dataset.v);
    });
  });
});
document.getElementById('star-picker').addEventListener('mouseleave', renderStarPicker);

function renderStarPicker() {
  document.querySelectorAll('#star-picker span').forEach((x, i) => {
    x.classList.toggle('on', i < resenasCalificacion);
  });
}

function cargarResenas(prodId) {
  resenasProductoId = prodId;
  const lista = document.getElementById('resenas-lista');
  lista.innerHTML = '<div class="resenas-empty">Cargando reseñas...</div>';
  document.getElementById('resenas-section').classList.add('visible');
  fetch(`/api/resenas?accion=listar&producto_id=${encodeURIComponent(prodId)}`)
    .then(r => r.json())
    .then(d => {
      if (!d.ok) { lista.innerHTML = '<div class="resenas-empty">Error cargando reseñas.</div>'; return; }
      renderResenas(d.resenas);
    })
    .catch(() => { lista.innerHTML = '<div class="resenas-empty">Error de conexión.</div>'; });
}

function renderResenas(resenas) {
  const lista = document.getElementById('resenas-lista');
  document.getElementById('resenas-count').textContent = `(${resenas.length})`;
  if (!resenas.length) {
    lista.innerHTML = '<div class="resenas-empty">Este producto aún no tiene reseñas.</div>';
    return;
  }
  lista.innerHTML = resenas.map(r => {
    const stars = '★'.repeat(r.calificacion) + '☆'.repeat(5 - r.calificacion);
    const inicial = (r.autor || '?').charAt(0).toUpperCase();
    const fecha   = r.creado_en ? new Date(r.creado_en).toLocaleDateString('es-PE', {day:'2-digit',month:'short',year:'numeric'}) : '';
    const badge   = r.aprobada
      ? `<span class="resena-badge badge-apro">✅ Aprobada</span>`
      : `<span class="resena-badge badge-pend">⏳ Pendiente</span>`;
    const btnEstado = r.aprobada
      ? `<button class="btn-rechazar" onclick="toggleAprobacion('${r.id}',false)">✕ Rechazar</button>`
      : `<button class="btn-aprobar" onclick="toggleAprobacion('${r.id}',true)">✔ Aprobar</button>`;
    return `<div class="resena-card ${r.aprobada ? 'aprobada' : 'pendiente'}" id="rc-${r.id}">
      <div class="resena-top">
        <div class="resena-avatar">${inicial}</div>
        <div class="resena-meta">
          <div class="resena-autor">${escHtml(r.autor)}${badge}</div>
          <div class="resena-fecha"><span class="resena-stars">${stars}</span> · ${fecha}</div>
        </div>
      </div>
      <div class="resena-texto">${escHtml(r.texto)}</div>
      <div class="resena-acciones">
        ${btnEstado}
        <button class="btn-edit-r" onclick="editarResena(${JSON.stringify(r).replace(/"/g,'&quot;')})">✏️ Editar</button>
        <button class="btn-del-r" onclick="eliminarResena('${r.id}')">🗑 Eliminar</button>
      </div>
    </div>`;
  }).join('');
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function toggleFormResena() {
  const form = document.getElementById('resena-form');
  const visible = form.style.display !== 'none';
  if (visible) { cancelarFormResena(); }
  else {
    document.getElementById('resena-id-edit').value = '';
    document.getElementById('r-autor').value    = '';
    document.getElementById('r-texto').value    = '';
    document.getElementById('r-aprobada').value = 'true';
    document.getElementById('resena-form-titulo').textContent = '＋ Nueva reseña';
    resenasCalificacion = 5; renderStarPicker();
    form.style.display = 'block';
    document.getElementById('r-autor').focus();
  }
}

function cancelarFormResena() {
  document.getElementById('resena-form').style.display = 'none';
  document.getElementById('resena-id-edit').value = '';
}

function editarResena(r) {
  document.getElementById('resena-id-edit').value = r.id;
  document.getElementById('r-autor').value    = r.autor || '';
  document.getElementById('r-texto').value    = r.texto || '';
  document.getElementById('r-aprobada').value = r.aprobada ? 'true' : 'false';
  document.getElementById('resena-form-titulo').textContent = '✏️ Editar reseña';
  resenasCalificacion = r.calificacion || 5; renderStarPicker();
  document.getElementById('resena-form').style.display = 'block';
  document.getElementById('r-texto').focus();
}

async function guardarResena() {
  const idEdit  = document.getElementById('resena-id-edit').value;
  const autor   = document.getElementById('r-autor').value.trim() || 'Anónimo';
  const texto   = document.getElementById('r-texto').value.trim();
  const aprobada= document.getElementById('r-aprobada').value;
  if (!texto) { showToast('El texto de la reseña es obligatorio.', 'error'); return; }

  const body = new URLSearchParams({
    producto_id: resenasProductoId,
    autor, texto,
    calificacion: resenasCalificacion,
    aprobada,
    ...(idEdit ? { id: idEdit } : {})
  });

  const accion = idEdit ? 'editar' : 'crear';
  try {
    const res  = await fetch(`/api/resenas?accion=${accion}`, { method: 'POST', body });
    const data = await res.json();
    if (data.ok) {
      showToast(idEdit ? '✅ Reseña actualizada' : '✅ Reseña agregada', 'success');
      cancelarFormResena();
      cargarResenas(resenasProductoId);
    } else {
      showToast('Error: ' + (data.error || 'error desconocido'), 'error');
    }
  } catch { showToast('Error de conexión.', 'error'); }
}

async function toggleAprobacion(resenaId, aprobada) {
  const body = new URLSearchParams({ id: resenaId, producto_id: resenasProductoId, aprobada: aprobada ? 'true' : 'false' });
  try {
    const res  = await fetch('/api/resenas?accion=aprobar', { method: 'POST', body });
    const data = await res.json();
    if (data.ok) { cargarResenas(resenasProductoId); }
    else showToast('Error al cambiar estado.', 'error');
  } catch { showToast('Error de conexión.', 'error'); }
}

async function eliminarResena(resenaId) {
  const ok = await modalConfirm({
    icon: '⭐', tipo: 'danger',
    titulo: '¿Eliminar reseña?',
    mensaje: 'La reseña se eliminará permanentemente.',
    btnTexto: 'Sí, eliminar'
  });
  if (!ok) return;
  try {
    const res  = await fetch(`/api/resenas?accion=eliminar&id=${resenaId}&producto_id=${resenasProductoId}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.ok) { showToast('Reseña eliminada.', 'success'); cargarResenas(resenasProductoId); }
    else showToast('Error al eliminar.', 'error');
  } catch { showToast('Error de conexión.', 'error'); }
}

// ── Toast ──
function showToast(msg, type='success') {
  const t = document.getElementById('toast-msg');
  t.textContent = msg;
  t.className = `toast-msg ${type} show`;
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('show'), 3500);
}
</script>
</body>
</html>
