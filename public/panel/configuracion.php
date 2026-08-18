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
<title>Configuración — Komercia</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--orange:#ff6a00;--orange-dark:#e05a00;--sidebar-bg:#111;--sidebar-width:240px;--body-bg:#f5f5f5;--card-bg:#fff;--text:#1a1a1a;--text-muted:#6b7280;--border:#e5e7eb;--radius:12px;--transition:.2s ease}
body{font-family:'Inter',sans-serif;background:var(--body-bg);color:var(--text);min-height:100vh;display:flex}
.sidebar{width:var(--sidebar-width);background:var(--sidebar-bg);min-height:100vh;display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:200;transition:transform var(--transition)}
.sidebar-logo{padding:24px 20px 20px;border-bottom:1px solid #222}
.sidebar-logo span{font-size:1.3rem;font-weight:700;color:var(--orange);letter-spacing:-.5px}
.sidebar-nav{flex:1;padding:16px 12px;display:flex;flex-direction:column;gap:4px}
.sidebar-nav a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;color:#aaa;text-decoration:none;font-size:.875rem;font-weight:500;transition:background var(--transition),color var(--transition)}
.sidebar-nav a:hover{background:#1e1e1e;color:#fff}
.sidebar-nav a.active{background:var(--orange);color:#fff}
.sidebar-footer{padding:16px 20px;border-top:1px solid #222}
.sidebar-footer a{color:#aaa;text-decoration:none;font-size:.8rem;display:flex;align-items:center;gap:8px}
.sidebar-footer a:hover{color:#fff}
.main{margin-left:var(--sidebar-width);flex:1;display:flex;flex-direction:column}
.topbar{background:var(--card-bg);padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100}
.topbar-left{display:flex;align-items:center;gap:12px}
.hamburger{display:none;background:none;border:none;font-size:22px;cursor:pointer;color:var(--text)}
.topbar h1{font-size:1.1rem;font-weight:600}
.topbar-right{display:flex;align-items:center;gap:12px}
.btn{padding:8px 16px;border-radius:8px;border:none;cursor:pointer;font-size:.875rem;font-weight:500;transition:.2s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn-outline{background:var(--card-bg);color:var(--text);border:1px solid var(--border)}
.btn-outline:hover{background:var(--body-bg)}
.btn-sm{font-size:.75rem;padding:5px 12px}
.avatar{width:36px;height:36px;border-radius:50%;background:var(--orange);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem}
.content{padding:28px;max-width:780px}
.section-card{background:var(--card-bg);border-radius:var(--radius);border:1px solid var(--border);padding:24px;margin-bottom:20px}
.section-title{font-size:1rem;font-weight:700;margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px}
.form-group{margin-bottom:18px}
.form-group label{display:block;font-size:.8rem;font-weight:600;margin-bottom:6px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:.9rem;font-family:'Inter',sans-serif;color:var(--text);background:#fff;outline:none;transition:border-color var(--transition)}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:var(--orange)}
.form-group textarea{resize:vertical;min-height:90px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
/* Logo upload */
.logo-wrap{display:flex;align-items:flex-start;gap:20px;flex-wrap:wrap}
.logo-preview-box{width:100px;height:100px;border-radius:10px;border:2px dashed var(--border);overflow:hidden;display:flex;align-items:center;justify-content:center;background:#fafafa;flex-shrink:0}
.logo-preview-box img{width:100%;height:100%;object-fit:contain}
.logo-preview-box .placeholder{font-size:2rem;color:#ddd}
.logo-drop{flex:1;border:2px dashed var(--border);border-radius:10px;padding:20px;text-align:center;cursor:pointer;background:#fafafa;position:relative;transition:.2s}
.logo-drop:hover,.logo-drop.over{border-color:var(--orange);background:#fff8f4}
.logo-drop input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.logo-drop .drop-icon{font-size:1.8rem;margin-bottom:6px}
.logo-drop p{font-size:.8rem;color:var(--text-muted)}
.logo-drop p strong{color:var(--orange)}
/* Color picker */
.color-row{display:flex;align-items:center;gap:12px}
.color-row input[type=color]{width:48px;height:40px;border:none;background:none;cursor:pointer;padding:0;border-radius:8px;overflow:hidden}
.color-row input[type=text]{flex:1}
.color-preview{width:40px;height:40px;border-radius:8px;border:1px solid var(--border);flex-shrink:0}
/* Radio options */
.radio-group{display:flex;gap:12px;flex-wrap:wrap}
.radio-option{flex:1;min-width:140px;border:2px solid var(--border);border-radius:10px;padding:14px;cursor:pointer;transition:.2s;display:flex;align-items:center;gap:10px}
.radio-option:hover{border-color:var(--orange)}
.radio-option.selected{border-color:var(--orange);background:#fff8f4}
.radio-option input[type=radio]{accent-color:var(--orange)}
.radio-option .ro-icon{font-size:1.4rem}
.radio-option .ro-text strong{display:block;font-size:.875rem;font-weight:600}
.radio-option .ro-text span{font-size:.78rem;color:var(--text-muted)}
/* Delivery precio */
#delivery-precio-wrap{display:none}
/* Save bar */
.save-bar{background:var(--card-bg);border-top:1px solid var(--border);padding:16px 28px;display:flex;align-items:center;justify-content:space-between;gap:16px;position:sticky;bottom:0}
.btn-save{background:var(--orange);color:#fff;padding:10px 28px;border-radius:8px;border:none;cursor:pointer;font-size:.9rem;font-weight:600;transition:.2s;font-family:'Inter',sans-serif}
.btn-save:hover{background:var(--orange-dark)}
.btn-save:disabled{opacity:.6;cursor:not-allowed}
.save-msg{font-size:.85rem;color:var(--text-muted)}
.save-ok{color:#16a34a;font-weight:600}
.save-err{color:#dc2626;font-weight:600}
/* Overlay sidebar */
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:190}
@media(max-width:768px){
  .sidebar{transform:translateX(calc(-1 * var(--sidebar-width)))}
  .sidebar.open{transform:none}
  .sidebar-overlay.show{display:block}
  .main{margin-left:0}
  .hamburger{display:block}
  .form-row{grid-template-columns:1fr}
  .radio-group{flex-direction:column}
}
</style>
</head>
<body>
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo"><span>⚡ Komercia</span></div>
  <nav class="sidebar-nav">
    <a href="/panel"><span>📊</span> Dashboard</a>
    <a href="/panel/productos"><span>📦</span> Productos</a>
    <a href="/panel/pedidos"><span>🛒</span> Pedidos</a>
    <a href="#"><span>🏪</span> Mi Tienda</a>
    <a href="#"><span>💎</span> Mi Plan</a>
    <a href="/panel/configuracion" class="active"><span>⚙️</span> Configuración</a>
  </nav>
  <div class="sidebar-footer">
    <a href="#" onclick="logout()">🚪 Cerrar sesión</a>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div class="topbar-left">
      <button class="hamburger" onclick="toggleSidebar()">☰</button>
      <h1>⚙️ Configuración</h1>
    </div>
    <div class="topbar-right">
      <a id="link-tienda" href="#" target="_blank" class="btn btn-outline btn-sm">🏪 Ver mi tienda</a>
      <div class="avatar" id="avatar-inicial">?</div>
    </div>
  </div>

  <div class="content">

    <!-- SECCIÓN 1: Información de la tienda -->
    <div class="section-card">
      <div class="section-title">🏪 Información de la tienda</div>

      <div class="form-group">
        <label>Nombre de la tienda *</label>
        <input type="text" id="nombre" placeholder="Ej: Mi Tienda Online">
      </div>

      <div class="form-group">
        <label>Descripción</label>
        <textarea id="descripcion" placeholder="Cuéntale a tus clientes de qué trata tu tienda..."></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Correo electrónico</label>
          <input type="email" id="email" placeholder="tu@email.com">
        </div>
        <div class="form-group">
          <label>Teléfono</label>
          <input type="tel" id="telefono" placeholder="+51 999 999 999">
        </div>
      </div>

      <div class="form-group">
        <label>Dirección</label>
        <input type="text" id="direccion" placeholder="Av. Principal 123, Lima">
      </div>
    </div>

    <!-- SECCIÓN 2: Marca -->
    <div class="section-card">
      <div class="section-title">🎨 Marca y apariencia</div>

      <div class="form-group">
        <label>Logo de la tienda</label>
        <div class="logo-wrap">
          <div class="logo-preview-box" id="logo-preview-box">
            <img id="logo-img" src="" alt="Logo" style="display:none">
            <span class="placeholder" id="logo-placeholder">🏪</span>
          </div>
          <div class="logo-drop" id="logo-drop">
            <input type="file" id="logo-file" accept="image/jpeg,image/png,image/webp,image/gif">
            <div class="drop-icon">🖼️</div>
            <p>Arrastra tu logo aquí o <strong>haz clic</strong></p>
            <p>JPG, PNG, WEBP · máx 5 MB</p>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label>Color principal</label>
        <div class="color-row">
          <input type="color" id="color-picker" value="#ff6a00" oninput="syncColor(this.value)">
          <input type="text" id="color-hex" value="#ff6a00" placeholder="#ff6a00" oninput="syncColorFromText(this.value)">
          <div class="color-preview" id="color-preview" style="background:#ff6a00"></div>
        </div>
      </div>
    </div>

    <!-- SECCIÓN 3: Ventas y envío -->
    <div class="section-card">
      <div class="section-title">🛒 Ventas y envío</div>

      <div class="form-group">
        <label>Número de WhatsApp</label>
        <input type="tel" id="whatsapp" placeholder="51999999999 (sin + ni espacios)">
        <small style="color:#888;font-size:12px;margin-top:4px;display:block">Incluye el código de país. Ej: 51999999999</small>
      </div>

      <div class="form-group">
        <label>Método de ventas</label>
        <div class="radio-group">
          <label class="radio-option selected" id="ro-whatsapp">
            <input type="radio" name="metodo_ventas" value="whatsapp" checked onchange="selectMetodo('whatsapp')">
            <span class="ro-icon">💬</span>
            <div class="ro-text">
              <strong>WhatsApp</strong>
              <span>El pedido se envía por WhatsApp</span>
            </div>
          </label>
          <label class="radio-option" id="ro-formulario">
            <input type="radio" name="metodo_ventas" value="formulario" onchange="selectMetodo('formulario')">
            <span class="ro-icon">📋</span>
            <div class="ro-text">
              <strong>Formulario</strong>
              <span>El cliente llena un formulario</span>
            </div>
          </label>
        </div>
      </div>

      <div class="form-group">
        <label>Delivery / Envío</label>
        <div class="radio-group">
          <label class="radio-option selected" id="ro-no_incluido">
            <input type="radio" name="delivery_tipo" value="no_incluido" checked onchange="selectDelivery('no_incluido')">
            <span class="ro-icon">📦</span>
            <div class="ro-text">
              <strong>No incluido</strong>
              <span>A coordinar con el cliente</span>
            </div>
          </label>
          <label class="radio-option" id="ro-gratis">
            <input type="radio" name="delivery_tipo" value="gratis" onchange="selectDelivery('gratis')">
            <span class="ro-icon">🎁</span>
            <div class="ro-text">
              <strong>Gratis</strong>
              <span>Envío gratuito</span>
            </div>
          </label>
          <label class="radio-option" id="ro-costo_fijo">
            <input type="radio" name="delivery_tipo" value="costo_fijo" onchange="selectDelivery('costo_fijo')">
            <span class="ro-icon">💰</span>
            <div class="ro-text">
              <strong>Costo fijo</strong>
              <span>Precio de envío definido</span>
            </div>
          </label>
        </div>
        <div id="delivery-precio-wrap" style="margin-top:12px">
          <label style="font-size:.8rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px">Costo de envío (S/.)</label>
          <input type="number" id="delivery-precio" placeholder="0.00" step="0.01" min="0" style="margin-top:6px;width:160px">
        </div>
      </div>
    </div>

  </div><!-- /content -->

  <div class="save-bar">
    <span class="save-msg" id="save-msg"></span>
    <button class="btn-save" id="btn-save" onclick="guardar()">Guardar cambios</button>
  </div>
</div><!-- /main -->

<script>
let logoFile = null;

// ── Sesión ────────────────────────────────────────────────────
fetch('/api/sesion')
  .then(r => r.json())
  .then(d => {
    if (!d.ok) { location.href = '/login'; return; }
    document.getElementById('avatar-inicial').textContent = d.nombre.charAt(0).toUpperCase();
    document.getElementById('link-tienda').href = '/tienda/' + d.slug;
    cargarDatos();
  });

function cargarDatos() {
  fetch('/api/tienda?accion=obtener')
    .then(r => r.json())
    .then(d => {
      document.getElementById('nombre').value      = d.nombre      || '';
      document.getElementById('descripcion').value = d.descripcion || '';
      document.getElementById('email').value       = d.email       || '';
      document.getElementById('telefono').value    = d.telefono    || '';
      document.getElementById('direccion').value   = d.direccion   || '';
      document.getElementById('whatsapp').value    = d.whatsapp    || '';

      const color = d.color_primario || '#ff6a00';
      document.getElementById('color-picker').value = color;
      document.getElementById('color-hex').value    = color;
      document.getElementById('color-preview').style.background = color;

      if (d.logo) {
        document.getElementById('logo-img').src = d.logo;
        document.getElementById('logo-img').style.display = 'block';
        document.getElementById('logo-placeholder').style.display = 'none';
      }

      selectMetodo(d.metodo_ventas || 'whatsapp');
      selectDelivery(d.delivery_tipo || 'no_incluido');
      if (d.delivery_precio) document.getElementById('delivery-precio').value = d.delivery_precio;
    });
}

// ── Color ─────────────────────────────────────────────────────
function syncColor(val) {
  document.getElementById('color-hex').value = val;
  document.getElementById('color-preview').style.background = val;
}
function syncColorFromText(val) {
  if (/^#[0-9a-fA-F]{6}$/.test(val)) {
    document.getElementById('color-picker').value = val;
    document.getElementById('color-preview').style.background = val;
  }
}

// ── Logo ──────────────────────────────────────────────────────
const logoDrop = document.getElementById('logo-drop');
const logoFileInput = document.getElementById('logo-file');

async function procesarLogo(file) {
  if (file.size > 5 * 1024 * 1024) { alert('El logo supera 5MB'); return; }
  const allowed = ['image/jpeg','image/png','image/webp','image/gif'];
  if (!allowed.includes(file.type)) { alert('Formato no permitido'); return; }

  if (file.type !== 'image/gif') {
    const reader = new FileReader();
    reader.onload = e => {
      const img = new Image();
      img.onload = () => {
        let w = img.width, h = img.height;
        if (w > 400 || h > 400) {
          if (w >= h) { h = Math.round(h * 400 / w); w = 400; }
          else        { w = Math.round(w * 400 / h); h = 400; }
        }
        const c = document.createElement('canvas');
        c.width = w; c.height = h;
        c.getContext('2d').drawImage(img, 0, 0, w, h);
        c.toBlob(blob => {
          logoFile = new File([blob], 'logo.webp', { type: 'image/webp' });
          showLogoPreview(URL.createObjectURL(logoFile));
        }, 'image/webp', 0.88);
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  } else {
    logoFile = file;
    showLogoPreview(URL.createObjectURL(file));
  }
}

function showLogoPreview(url) {
  const img = document.getElementById('logo-img');
  img.src = url;
  img.style.display = 'block';
  document.getElementById('logo-placeholder').style.display = 'none';
}

logoFileInput.addEventListener('change', e => { if (e.target.files[0]) procesarLogo(e.target.files[0]); });
logoDrop.addEventListener('dragover', e => { e.preventDefault(); logoDrop.classList.add('over'); });
logoDrop.addEventListener('dragleave', () => logoDrop.classList.remove('over'));
logoDrop.addEventListener('drop', e => { e.preventDefault(); logoDrop.classList.remove('over'); if (e.dataTransfer.files[0]) procesarLogo(e.dataTransfer.files[0]); });

// ── Radio helpers ─────────────────────────────────────────────
function selectMetodo(val) {
  ['whatsapp','formulario'].forEach(v => {
    const el = document.getElementById('ro-' + v);
    if (el) el.classList.toggle('selected', v === val);
    const radio = el ? el.querySelector('input') : null;
    if (radio) radio.checked = v === val;
  });
}
function selectDelivery(val) {
  ['no_incluido','gratis','costo_fijo'].forEach(v => {
    const el = document.getElementById('ro-' + v);
    if (el) el.classList.toggle('selected', v === val);
    const radio = el ? el.querySelector('input') : null;
    if (radio) radio.checked = v === val;
  });
  document.getElementById('delivery-precio-wrap').style.display = val === 'costo_fijo' ? 'block' : 'none';
}

// ── Guardar ───────────────────────────────────────────────────
async function guardar() {
  const btn = document.getElementById('btn-save');
  const msg = document.getElementById('save-msg');
  btn.disabled = true; btn.textContent = 'Guardando...'; msg.textContent = '';

  const color = document.getElementById('color-hex').value.trim() || '#ff6a00';
  const metodo = document.querySelector('input[name="metodo_ventas"]:checked')?.value || 'whatsapp';
  const delivery = document.querySelector('input[name="delivery_tipo"]:checked')?.value || 'no_incluido';

  const fd = new FormData();
  fd.append('nombre',         document.getElementById('nombre').value);
  fd.append('descripcion',    document.getElementById('descripcion').value);
  fd.append('email',          document.getElementById('email').value);
  fd.append('telefono',       document.getElementById('telefono').value);
  fd.append('direccion',      document.getElementById('direccion').value);
  fd.append('color_primario', color);
  fd.append('metodo_ventas',  metodo);
  fd.append('delivery_tipo',  delivery);
  fd.append('delivery_precio',document.getElementById('delivery-precio').value || '0');
  fd.append('whatsapp',       document.getElementById('whatsapp').value);
  if (logoFile) fd.append('logo', logoFile);

  try {
    const res  = await fetch('/api/tienda?accion=guardar', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      msg.className = 'save-msg save-ok'; msg.textContent = '✅ Cambios guardados';
      if (data.logo) showLogoPreview(data.logo);
      logoFile = null;
    } else {
      msg.className = 'save-msg save-err'; msg.textContent = '❌ ' + (data.error || 'Error al guardar');
    }
  } catch(e) {
    msg.className = 'save-msg save-err'; msg.textContent = '❌ Error de conexión';
  } finally {
    btn.disabled = false; btn.textContent = 'Guardar cambios';
    setTimeout(() => { msg.textContent = ''; }, 4000);
  }
}

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
