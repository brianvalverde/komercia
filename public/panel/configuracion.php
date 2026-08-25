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
<title>Configuración de Tienda — Komercia</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
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

/* ── Main ────────────────────────────────────────── */
.main{margin-left:220px;flex:1;padding:32px}
.page-header{margin-bottom:28px}
.page-header h1{font-size:22px;font-weight:700;color:#1a1a2e}
.page-header p{color:#777;font-size:14px;margin-top:4px}

/* ── Card ────────────────────────────────────────── */
.card{background:#fff;border-radius:16px;padding:28px;margin-bottom:24px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.card-title{font-size:15px;font-weight:700;color:#1a1a2e;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.card-title svg{width:18px;height:18px;color:#ff6a00}

/* ── Form grid ───────────────────────────────────── */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-grid.single{grid-template-columns:1fr}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group label{font-size:13px;font-weight:600;color:#444}
.form-group input,.form-group textarea,.form-group select{border:1.5px solid #e0e0e0;border-radius:10px;padding:10px 14px;font-size:14px;font-family:inherit;outline:none;transition:border-color .2s;background:#fafafa;color:#1a1a2e}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:#ff6a00;background:#fff}
.form-group textarea{resize:vertical;min-height:80px}
.form-group .hint{font-size:12px;color:#aaa}
.color-wrap{display:flex;align-items:center;gap:10px}
.color-wrap input[type=color]{width:44px;height:44px;border:none;padding:2px;border-radius:8px;cursor:pointer;background:none}

/* ── Logo preview ────────────────────────────────── */
.logo-area{display:flex;align-items:center;gap:20px;flex-wrap:wrap}
.logo-preview{width:90px;height:90px;border-radius:12px;border:2px dashed #e0e0e0;object-fit:contain;background:#f5f5f5;cursor:pointer;transition:border-color .2s}
.logo-preview:hover{border-color:#ff6a00}
.logo-upload-btn{display:inline-flex;align-items:center;gap:8px;padding:9px 16px;background:#f5f5f5;border:1.5px solid #e0e0e0;border-radius:10px;cursor:pointer;font-size:13px;font-weight:600;color:#444;transition:all .2s}
.logo-upload-btn:hover{border-color:#ff6a00;color:#ff6a00}

/* ── Banner section ──────────────────────────────── */
.banners-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-bottom:16px}
.banner-item{position:relative;border-radius:12px;overflow:hidden;background:#f5f5f5;aspect-ratio:16/7;cursor:grab}
.banner-item img{width:100%;height:100%;object-fit:cover;pointer-events:none}
.banner-item .banner-del{position:absolute;top:6px;right:6px;background:rgba(0,0,0,.55);color:#fff;border:none;border-radius:6px;padding:4px 8px;font-size:12px;cursor:pointer;transition:background .2s}
.banner-item .banner-del:hover{background:#ee0979}
.banner-item .drag-handle{position:absolute;top:6px;left:6px;background:rgba(0,0,0,.45);color:#fff;border-radius:6px;padding:4px 6px;font-size:12px;cursor:grab}
.banner-upload-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;border:none;border-radius:10px;cursor:pointer;font-size:14px;font-weight:600;transition:opacity .2s}
.banner-upload-btn:hover{opacity:.88}
.banner-upload-btn:disabled{opacity:.5;cursor:not-allowed}
.banner-count{font-size:13px;color:#888;margin-left:10px}
.banner-hint{font-size:12px;color:#aaa;margin-top:8px}

/* ── Social media ────────────────────────────────── */
.social-row{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.social-input-wrap{position:relative}
.social-input-wrap .social-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:18px;height:18px}
.social-input-wrap input{padding-left:38px}

/* ── Delivery section ────────────────────────────── */
.delivery-options{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.delivery-opt{display:flex;align-items:center;gap:8px;padding:10px 16px;border:1.5px solid #e0e0e0;border-radius:10px;cursor:pointer;transition:all .2s;user-select:none}
.delivery-opt.active{border-color:#ff6a00;background:#fff5f0;color:#ff6a00;font-weight:600}
.delivery-opt input{display:none}
#precio-delivery-wrap{margin-top:4px}

/* ── Save btn ────────────────────────────────────── */
.btn-save{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;transition:opacity .2s;margin-top:8px}
.btn-save:hover{opacity:.88}
.btn-save:disabled{opacity:.5;cursor:not-allowed}

/* ── Toast ───────────────────────────────────────── */
#toast{position:fixed;bottom:28px;right:28px;background:#1a1a2e;color:#fff;padding:12px 22px;border-radius:12px;font-size:14px;font-weight:500;opacity:0;transform:translateY(12px);transition:all .3s;z-index:999;pointer-events:none}
#toast.show{opacity:1;transform:translateY(0)}
#toast.error{background:#ee0979}

/* ── Progress ────────────────────────────────────── */
.upload-progress{display:none;margin-top:10px}
.progress-bar-wrap{background:#f0f0f0;border-radius:99px;height:8px;overflow:hidden}
.progress-bar-fill{height:100%;background:linear-gradient(90deg,#ff6a00,#ee0979);border-radius:99px;width:0%;transition:width .2s}
.progress-text{font-size:12px;color:#888;margin-top:4px}

/* ── Section divider ─────────────────────────────── */
.section-sep{border:none;border-top:1px solid #f0f0f0;margin:8px 0 20px}

@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .main{margin-left:0;padding:20px}
  .form-grid{grid-template-columns:1fr}
  .social-row{grid-template-columns:1fr}
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
    <a href="/panel/pedidos">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Pedidos
    </a>
    <a href="/panel/configuracion" class="active">
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

<!-- Main -->
<main class="main">
  <div class="page-header">
    <h1>⚙️ Configuración de tu tienda</h1>
    <p>Personaliza la información, apariencia y opciones de venta.</p>
  </div>

  <!-- Información general -->
  <div class="card">
    <div class="card-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      Información general
    </div>
    <div class="form-grid" style="margin-bottom:16px">
      <div class="form-group">
        <label>Nombre de la tienda *</label>
        <input type="text" id="nombre" placeholder="Mi tienda genial" maxlength="80">
      </div>
      <div class="form-group">
        <label>Correo de contacto</label>
        <input type="email" id="email" placeholder="hola@mitienda.com">
      </div>
    </div>
    <div class="form-grid single" style="margin-bottom:16px">
      <div class="form-group">
        <label>Descripción</label>
        <textarea id="descripcion" placeholder="Describe tu tienda en pocas palabras..."></textarea>
      </div>
    </div>
    <div class="form-grid" style="margin-bottom:16px">
      <div class="form-group">
        <label>Teléfono / WhatsApp</label>
        <input type="text" id="telefono" placeholder="+51 999 999 999">
      </div>
      <div class="form-group">
        <label>Dirección</label>
        <input type="text" id="direccion" placeholder="Jr. Ejemplo 123, Lima">
      </div>
    </div>
    <div class="form-grid" style="grid-template-columns:auto 1fr;align-items:end">
      <div class="form-group">
        <label>Color principal</label>
        <div class="color-wrap">
          <input type="color" id="color_primario" value="#ff6a00">
          <input type="text" id="color_hex" style="width:110px" placeholder="#ff6a00" maxlength="7">
        </div>
      </div>
      <div class="form-group">
        <label>Método de ventas</label>
        <select id="metodo_ventas">
          <option value="whatsapp">WhatsApp (enlace directo)</option>
          <option value="formulario">Formulario de pedido</option>
        </select>
      </div>
    </div>
  </div>

  <!-- Logo -->
  <div class="card">
    <div class="card-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
      Logo de la tienda
    </div>
    <div class="logo-area">
      <img id="logo-preview" class="logo-preview" src="/assets/img/placeholder.png" alt="Logo" title="Clic para cambiar">
      <div>
        <label class="logo-upload-btn" for="logo-input">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          Subir logo
        </label>
        <input type="file" id="logo-input" accept="image/*" style="display:none">
        <p class="hint" style="margin-top:8px;font-size:12px;color:#aaa">JPG, PNG o WebP · Máx. 5 MB</p>
      </div>
    </div>
  </div>

  <!-- Banners -->
  <div class="card">
    <div class="card-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="10" rx="2"/><path d="M17 7V5a2 2 0 00-2-2H9a2 2 0 00-2 2v2"/></svg>
      Banners del carrusel
    </div>
    <p style="font-size:13px;color:#888;margin-bottom:16px">Hasta 5 banners. Arrastra para reordenar. Recomendado: 1200×450px.</p>

    <div id="banners-grid" class="banners-grid"></div>

    <div style="display:flex;align-items:center;flex-wrap:wrap;gap:10px">
      <label class="banner-upload-btn" id="banner-upload-label" for="banner-input">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Subir banner
      </label>
      <input type="file" id="banner-input" accept="image/jpeg,image/png,image/webp" style="display:none">
      <span class="banner-count" id="banner-count">0 / 5</span>
    </div>
    <div class="upload-progress" id="banner-progress">
      <div class="progress-bar-wrap"><div class="progress-bar-fill" id="banner-bar"></div></div>
      <div class="progress-text" id="banner-progress-text">Subiendo...</div>
    </div>
    <p class="banner-hint">JPG, PNG o WebP · Máx. 5 MB por imagen</p>
  </div>

  <!-- Redes sociales -->
  <div class="card">
    <div class="card-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
      Redes sociales
    </div>
    <div class="social-row">
      <div class="form-group">
        <label>Facebook</label>
        <div class="social-input-wrap">
          <svg class="social-icon" viewBox="0 0 24 24" fill="#1877f2"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
          <input type="url" id="facebook" placeholder="https://facebook.com/tutienda">
        </div>
      </div>
      <div class="form-group">
        <label>Instagram</label>
        <div class="social-input-wrap">
          <svg class="social-icon" viewBox="0 0 24 24" fill="none" stroke="url(#ig-grad)" stroke-width="2">
            <defs><linearGradient id="ig-grad" x1="0%" y1="100%" x2="100%" y2="0%"><stop offset="0%" stop-color="#f09433"/><stop offset="50%" stop-color="#e6683c"/><stop offset="100%" stop-color="#bc1888"/></linearGradient></defs>
            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="#bc1888" stroke="none"/>
          </svg>
          <input type="url" id="instagram" placeholder="https://instagram.com/tutienda">
        </div>
      </div>
      <div class="form-group">
        <label>TikTok</label>
        <div class="social-input-wrap">
          <svg class="social-icon" viewBox="0 0 24 24" fill="#000"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.18 8.18 0 004.78 1.52V6.75a4.85 4.85 0 01-1.01-.06z"/></svg>
          <input type="url" id="tiktok" placeholder="https://tiktok.com/@tutienda">
        </div>
      </div>
    </div>
  </div>

  <!-- WhatsApp -->
  <div class="card">
    <div class="card-title">
      <svg viewBox="0 0 24 24" fill="#25D366" width="18" height="18"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12.002 2a9.998 9.998 0 00-8.637 14.998L2 22l5.138-1.349A9.998 9.998 0 1012.002 2zm0 18.18a8.18 8.18 0 01-4.17-1.143l-.3-.178-3.048.8.815-2.98-.196-.307a8.18 8.18 0 1110.63 1.214 8.147 8.147 0 01-3.731.594z"/></svg>
      WhatsApp de contacto
    </div>
    <div class="form-grid" style="grid-template-columns:1fr 2fr">
      <div class="form-group">
        <label>Número de WhatsApp</label>
        <input type="text" id="whatsapp" placeholder="+51999999999">
        <span class="hint">Con código de país, sin espacios</span>
      </div>
    </div>
  </div>

  <!-- Delivery -->
  <div class="card">
    <div class="card-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h11a2 2 0 012 2v3m0 0h3l3 3v4h-3m-3 0H9m3 0a2 2 0 11-4 0 2 2 0 014 0zm8 0a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      Delivery / Envío
    </div>
    <div class="delivery-options">
      <label class="delivery-opt" id="opt-no_incluido">
        <input type="radio" name="delivery" value="no_incluido"> 📦 No incluido
      </label>
      <label class="delivery-opt" id="opt-gratis">
        <input type="radio" name="delivery" value="gratis"> 🆓 Delivery gratis
      </label>
      <label class="delivery-opt" id="opt-costo_fijo">
        <input type="radio" name="delivery" value="costo_fijo"> 💰 Costo fijo
      </label>
    </div>
    <div id="precio-delivery-wrap" style="display:none" class="form-grid" style="grid-template-columns:200px 1fr">
      <div class="form-group">
        <label>Precio de delivery</label>
        <input type="number" id="delivery_precio" placeholder="5.00" min="0" step="0.50">
      </div>
    </div>
  </div>

  <!-- Save -->
  <div>
    <button class="btn-save" id="btn-save" onclick="guardarTienda()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
      Guardar cambios
    </button>
  </div>
</main>

<div id="toast"></div>

<script>
const slug = '<?= htmlspecialchars($slug) ?>';

// ── Toast ─────────────────────────────────────────────────────
function toast(msg, isError = false) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'show' + (isError ? ' error' : '');
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.className = '', 3500);
}

// ── Color sync ────────────────────────────────────────────────
const colorPicker = document.getElementById('color_primario');
const colorHex    = document.getElementById('color_hex');
colorPicker.addEventListener('input', () => { colorHex.value = colorPicker.value; });
colorHex.addEventListener('input', () => {
  if (/^#[0-9a-fA-F]{6}$/.test(colorHex.value)) colorPicker.value = colorHex.value;
});

// ── Delivery toggle ───────────────────────────────────────────
document.querySelectorAll('input[name=delivery]').forEach(r => {
  r.addEventListener('change', () => {
    document.querySelectorAll('.delivery-opt').forEach(o => o.classList.remove('active'));
    r.closest('.delivery-opt').classList.add('active');
    document.getElementById('precio-delivery-wrap').style.display = r.value === 'costo_fijo' ? 'block' : 'none';
  });
});
function setDelivery(val) {
  const r = document.querySelector(`input[name=delivery][value="${val}"]`);
  if (r) { r.checked = true; r.dispatchEvent(new Event('change')); }
}

// ── Logo preview ──────────────────────────────────────────────
document.getElementById('logo-input').addEventListener('change', function() {
  const f = this.files[0]; if (!f) return;
  const reader = new FileReader();
  reader.onload = e => document.getElementById('logo-preview').src = e.target.result;
  reader.readAsDataURL(f);
});
document.getElementById('logo-preview').addEventListener('click', () => document.getElementById('logo-input').click());

// ── Banners ───────────────────────────────────────────────────
let banners = []; // array of URLs

function renderBannersGrid() {
  const grid  = document.getElementById('banners-grid');
  const count = document.getElementById('banner-count');
  const label = document.getElementById('banner-upload-label');
  count.textContent = banners.length + ' / 5';
  label.style.pointerEvents = banners.length >= 5 ? 'none' : '';
  label.style.opacity = banners.length >= 5 ? '.5' : '1';

  grid.innerHTML = '';
  banners.forEach((url, i) => {
    const div = document.createElement('div');
    div.className = 'banner-item';
    div.draggable = true;
    div.dataset.idx = i;
    div.innerHTML = `
      <span class="drag-handle" title="Arrastrar">⠿</span>
      <img src="${url}" alt="Banner ${i+1}" loading="lazy">
      <button class="banner-del" onclick="eliminarBanner('${url}',this)">✕</button>
    `;
    // Drag & drop
    div.addEventListener('dragstart', e => e.dataTransfer.setData('text/plain', i));
    div.addEventListener('dragover', e => e.preventDefault());
    div.addEventListener('drop', e => {
      e.preventDefault();
      const fromIdx = parseInt(e.dataTransfer.getData('text/plain'));
      const toIdx   = parseInt(div.dataset.idx);
      if (fromIdx === toIdx) return;
      const moved = banners.splice(fromIdx, 1)[0];
      banners.splice(toIdx, 0, moved);
      renderBannersGrid();
      reordenarBanners();
    });
    grid.appendChild(div);
  });
}

// Upload banner via XHR
document.getElementById('banner-input').addEventListener('change', async function() {
  const f = this.files[0]; if (!f) return;
  this.value = '';
  if (banners.length >= 5) { toast('Máximo 5 banners', true); return; }
  if (f.size > 5*1024*1024) { toast('El banner supera los 5 MB', true); return; }

  const prog = document.getElementById('banner-progress');
  const bar  = document.getElementById('banner-bar');
  const txt  = document.getElementById('banner-progress-text');
  prog.style.display = 'block';
  bar.style.width = '0%';
  txt.textContent  = 'Subiendo...';

  const fd = new FormData();
  fd.append('banner', f);

  const xhr = new XMLHttpRequest();
  xhr.upload.onprogress = ev => {
    if (ev.lengthComputable) {
      const pct = Math.round(ev.loaded / ev.total * 100);
      bar.style.width = pct + '%';
      txt.textContent = 'Subiendo... ' + pct + '%';
    }
  };
  xhr.onload = () => {
    prog.style.display = 'none';
    if (xhr.status === 200) {
      try {
        const res = JSON.parse(xhr.responseText);
        if (res.ok) {
          banners = res.banners;
          renderBannersGrid();
          toast('✅ Banner subido');
        } else {
          toast(res.error || 'Error al subir banner', true);
        }
      } catch { toast('Error inesperado', true); }
    } else {
      toast('Error al subir banner', true);
    }
  };
  xhr.onerror = () => { prog.style.display = 'none'; toast('Error de red', true); };
  xhr.open('POST', `/api/tienda?accion=subir_banner`);
  xhr.send(fd);
});

function eliminarBanner(url, btn) {
  if (btn && btn.dataset.confirm !== '1') {
    btn.dataset.confirm = '1';
    const orig = btn.textContent;
    btn.textContent = '¿Confirmar?';
    btn.style.background = '#e74c3c';
    setTimeout(() => { btn.dataset.confirm='0'; btn.textContent=orig; btn.style.background=''; }, 3000);
    return;
  }
  if (btn) { btn.dataset.confirm='0'; btn.style.background=''; }
  const fd = new FormData();
  fd.append('url', url);
  fetch('/api/tienda?accion=eliminar_banner', {method:'POST', body:fd})
    .then(r => r.json())
    .then(data => {
      if (data.ok) { banners = data.banners; renderBannersGrid(); toast('Banner eliminado'); }
      else toast(data.error || 'Error al eliminar', true);
    });
}

async function reordenarBanners() {
  const fd = new FormData();
  fd.append('banners', JSON.stringify(banners));
  await fetch('/api/tienda?accion=reordenar_banners', {method:'POST', body:fd});
}

// ── Load store data ───────────────────────────────────────────
async function cargarTienda() {
  try {
    const res  = await fetch('/api/tienda?accion=obtener');
    const data = await res.json();

    document.getElementById('nombre').value         = data.nombre || '';
    document.getElementById('descripcion').value    = data.descripcion || '';
    document.getElementById('email').value          = data.email || '';
    document.getElementById('telefono').value       = data.telefono || '';
    document.getElementById('direccion').value      = data.direccion || '';
    document.getElementById('whatsapp').value       = data.whatsapp || '';
    document.getElementById('facebook').value       = data.facebook || '';
    document.getElementById('instagram').value      = data.instagram || '';
    document.getElementById('tiktok').value         = data.tiktok || '';
    colorPicker.value = data.color_primario || '#ff6a00';
    colorHex.value    = data.color_primario || '#ff6a00';
    document.getElementById('metodo_ventas').value  = data.metodo_ventas || 'whatsapp';
    setDelivery(data.delivery_tipo || 'no_incluido');
    document.getElementById('delivery_precio').value = data.delivery_precio || '0';

    if (data.logo) document.getElementById('logo-preview').src = data.logo;
    banners = Array.isArray(data.banners) ? data.banners : [];
    renderBannersGrid();
  } catch(e) { toast('Error al cargar datos de la tienda', true); }
}
cargarTienda();

// ── Save ──────────────────────────────────────────────────────
async function guardarTienda() {
  const nombre = document.getElementById('nombre').value.trim();
  if (!nombre) { toast('El nombre de la tienda es obligatorio', true); return; }

  const btn = document.getElementById('btn-save');
  btn.disabled = true;
  btn.textContent = 'Guardando...';

  const fd = new FormData();
  fd.append('nombre',          nombre);
  fd.append('descripcion',     document.getElementById('descripcion').value.trim());
  fd.append('email',           document.getElementById('email').value.trim());
  fd.append('telefono',        document.getElementById('telefono').value.trim());
  fd.append('direccion',       document.getElementById('direccion').value.trim());
  fd.append('whatsapp',        document.getElementById('whatsapp').value.trim());
  fd.append('facebook',        document.getElementById('facebook').value.trim());
  fd.append('instagram',       document.getElementById('instagram').value.trim());
  fd.append('tiktok',          document.getElementById('tiktok').value.trim());
  fd.append('color_primario',  colorPicker.value);
  fd.append('metodo_ventas',   document.getElementById('metodo_ventas').value);
  fd.append('delivery_tipo',   document.querySelector('input[name=delivery]:checked')?.value || 'no_incluido');
  fd.append('delivery_precio', document.getElementById('delivery_precio').value || '0');

  const logoInput = document.getElementById('logo-input');
  if (logoInput.files[0]) fd.append('logo', logoInput.files[0]);

  try {
    const res  = await fetch('/api/tienda?accion=guardar', {method:'POST', body:fd});
    const data = await res.json();
    if (data.ok) {
      if (data.logo) document.getElementById('logo-preview').src = data.logo;
      toast('✅ Tienda guardada correctamente');
    } else {
      toast(data.error || 'Error al guardar', true);
    }
  } catch { toast('Error de red al guardar', true); }

  btn.disabled = false;
  btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardar cambios`;
}
</script>
</body>
</html>
