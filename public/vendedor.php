<?php
// /vendedor/{slug} — Vista del vendedor: login con código + pedidos asignados
session_start();
require_once '/var/www/komercia/config/firebase.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) { http_response_code(404); die('Tienda no encontrada'); }

function fsv($f,$k,$d=''){return $f[$k]['stringValue']??$f[$k]['booleanValue']??$f[$k]['integerValue']??$d;}

// Obtener datos de la tienda
$tiendaDoc = firestoreRequest('GET', "tiendas/{$slug}");
$tiendaFields = $tiendaDoc['fields'] ?? [];
if (empty($tiendaFields)) {
    http_response_code(404);
    die('Tienda no encontrada');
}
$uid_comerciante = fsv($tiendaFields, 'uid');
$tid_tienda      = fsv($tiendaFields, 'tienda_id', 'main');

// Obtener nombre de la tienda
$comDoc = firestoreRequest('GET', "comerciantes/{$uid_comerciante}");
$comFields = $comDoc['fields'] ?? [];
$nombreTienda = $comFields['nombreTienda']['stringValue']
             ?? $comFields['nombre_tienda']['stringValue']
             ?? $comFields['nombre']['stringValue']
             ?? 'Tienda';

// ¿Sesión de vendedor activa?
$vendedorLogueado = (
    isset($_SESSION['vendedor_uid']) &&
    $_SESSION['vendedor_uid'] === $uid_comerciante &&
    ($_SESSION['vendedor_tid'] === $tid_tienda || ($_SESSION['vendedor_tid']==='main' && $tid_tienda==='main'))
);

// Logout
if (isset($_GET['salir'])) {
    unset($_SESSION['vendedor_id'], $_SESSION['vendedor_uid'], $_SESSION['vendedor_tid'], $_SESSION['vendedor_nombre']);
    header('Location: /vendedor/'.$slug);
    exit;
}

$vendedorNombre = $_SESSION['vendedor_nombre'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vendedor — <?= htmlspecialchars($nombreTienda) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#f0f2f5;color:#1a1a2e;min-height:100vh}

/* ── LOGIN ── */
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:linear-gradient(135deg,#ff6a00 0%,#ee0979 100%)}
.login-card{background:#fff;border-radius:24px;padding:40px;width:100%;max-width:380px;box-shadow:0 20px 60px rgba(0,0,0,.2)}
.login-logo{font-size:24px;font-weight:800;background:linear-gradient(135deg,#ff6a00,#ee0979);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:4px}
.login-tienda{font-size:14px;color:#888;margin-bottom:28px}
.login-card h2{font-size:20px;font-weight:700;margin-bottom:6px}
.login-card p{font-size:13px;color:#888;margin-bottom:24px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;font-weight:600;color:#444;margin-bottom:6px}
.form-group input{width:100%;padding:12px 16px;border:1.5px solid #e0e0e0;border-radius:12px;font-size:18px;font-weight:700;letter-spacing:6px;text-transform:uppercase;text-align:center;font-family:monospace;outline:none;transition:border-color .2s}
.form-group input:focus{border-color:#ff6a00}
.btn-login{width:100%;padding:13px;background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;margin-top:8px;transition:opacity .2s}
.btn-login:hover{opacity:.88}
.login-error{background:#fff0f0;border:1.5px solid #ffc0c0;border-radius:10px;padding:10px 14px;font-size:13px;color:#cc0033;margin-bottom:16px;display:none}
.login-error.show{display:block}

/* ── PANEL VENDEDOR ── */
.vend-topbar{background:#fff;border-bottom:1px solid #e8eaf0;padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.vend-topbar-left{display:flex;align-items:center;gap:12px}
.vend-topbar-logo{font-size:18px;font-weight:800;background:linear-gradient(135deg,#ff6a00,#ee0979);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.vend-topbar-sep{color:#ddd}
.vend-topbar-name{font-size:14px;font-weight:600;color:#444}
.vend-topbar-right{display:flex;align-items:center;gap:10px}
.btn-salir{display:flex;align-items:center;gap:6px;padding:7px 14px;background:#fff5f0;color:#ff6a00;border:1.5px solid #ffd0b0;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600}
.vend-content{max-width:900px;margin:0 auto;padding:28px 20px}
.vend-header{margin-bottom:24px}
.vend-header h1{font-size:20px;font-weight:700}
.vend-header p{font-size:13px;color:#888;margin-top:3px}

/* Pedidos */
.pedido-card{background:#fff;border-radius:16px;border:1.5px solid #e8eaf0;padding:20px;margin-bottom:16px;transition:box-shadow .2s}
.pedido-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.08)}
.pedido-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px}
.pedido-id{font-size:12px;color:#aaa;font-family:monospace}
.pedido-fecha{font-size:12px;color:#aaa}
.pedido-cliente{font-size:15px;font-weight:700;margin-bottom:2px}
.pedido-contacto{font-size:13px;color:#666;margin-bottom:12px}
.pedido-items{background:#f8f9fb;border-radius:10px;padding:12px;margin-bottom:12px}
.pedido-item{display:flex;justify-content:space-between;font-size:13px;padding:3px 0}
.pedido-item-nombre{color:#444}
.pedido-item-precio{font-weight:600;color:#1a1a2e}
.pedido-total{display:flex;justify-content:space-between;font-size:15px;font-weight:700;border-top:1.5px solid #e8eaf0;padding-top:10px;margin-top:6px}
.pedido-actions{display:flex;gap:8px;flex-wrap:wrap}
.badge{display:inline-flex;align-items:center;padding:4px 10px;border-radius:99px;font-size:11px;font-weight:700}
.badge-pendiente{background:#fff8e0;color:#b87800}
.badge-confirmado{background:#e0f0ff;color:#0066cc}
.badge-entregado{background:#e8f7ef;color:#1a8a55}
.badge-cancelado{background:#fff0f0;color:#cc0033}
.btn-wa-pedido{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#e8f7ef;color:#25d366;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none}
.btn-wa-pedido:hover{background:#d0f0de}
.btn-wa-pedido svg{width:16px;height:16px}

.empty-state{text-align:center;padding:60px 20px;color:#aaa}
.empty-state svg{width:56px;height:56px;margin-bottom:16px;opacity:.3}
.empty-state h3{font-size:18px;color:#555;margin-bottom:8px}

.skeleton{background:linear-gradient(90deg,#f0f0f0 25%,#e8e8e8 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;border-radius:8px}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
</style>
</head>
<body>

<?php if (!$vendedorLogueado): ?>
<!-- ── PANTALLA LOGIN ── -->
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">Komercia</div>
    <div class="login-tienda"><?= htmlspecialchars($nombreTienda) ?></div>
    <h2>Acceso vendedor</h2>
    <p>Ingresa el código que te dio tu administrador</p>
    <div class="login-error" id="login-error"></div>
    <div class="form-group">
      <label>Código de acceso</label>
      <input type="text" id="codigo-input" maxlength="6" placeholder="XXXXXX"
             oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')"
             onkeydown="if(event.key==='Enter')loginVendedor()">
    </div>
    <button class="btn-login" onclick="loginVendedor()" id="btn-login">Ingresar</button>
  </div>
</div>

<script>
async function loginVendedor() {
  const codigo = document.getElementById('codigo-input').value.trim();
  const errEl  = document.getElementById('login-error');
  const btn    = document.getElementById('btn-login');

  if (codigo.length < 6) {
    errEl.textContent = 'El código debe tener 6 caracteres';
    errEl.classList.add('show');
    return;
  }

  btn.textContent = 'Verificando...';
  btn.disabled = true;
  errEl.classList.remove('show');

  try {
    const r = await fetch('/api/vendedores', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        accion: 'login_vendedor',
        codigo,
        uid: '<?= htmlspecialchars($uid_comerciante) ?>',
        tienda_id: '<?= htmlspecialchars($tid_tienda) ?>'
      })
    });
    const d = await r.json();
    if (d.ok) {
      window.location.reload();
    } else {
      errEl.textContent = d.error || 'Código incorrecto';
      errEl.classList.add('show');
      btn.textContent = 'Ingresar';
      btn.disabled = false;
    }
  } catch(e) {
    errEl.textContent = 'Error de conexión';
    errEl.classList.add('show');
    btn.textContent = 'Ingresar';
    btn.disabled = false;
  }
}
</script>

<?php else: ?>
<!-- ── PANEL DEL VENDEDOR ── -->
<div class="vend-topbar">
  <div class="vend-topbar-left">
    <div class="vend-topbar-logo">Komercia</div>
    <span class="vend-topbar-sep">|</span>
    <div class="vend-topbar-name">👤 <?= htmlspecialchars($vendedorNombre) ?></div>
  </div>
  <div class="vend-topbar-right">
    <a href="?salir=1" class="btn-salir">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Salir
    </a>
  </div>
</div>

<div class="vend-content">
  <div class="vend-header">
    <h1>Mis pedidos</h1>
    <p><?= htmlspecialchars($nombreTienda) ?> · <?= htmlspecialchars($vendedorNombre) ?></p>
  </div>
  <div id="pedidos-container">
    <!-- Skeleton -->
    <?php for($i=0;$i<3;$i++): ?>
    <div class="pedido-card">
      <div class="skeleton" style="height:14px;width:30%;margin-bottom:10px"></div>
      <div class="skeleton" style="height:18px;width:55%;margin-bottom:6px"></div>
      <div class="skeleton" style="height:13px;width:45%;margin-bottom:14px"></div>
      <div class="skeleton" style="height:80px;border-radius:10px;margin-bottom:12px"></div>
      <div style="display:flex;gap:8px">
        <div class="skeleton" style="height:32px;width:100px;border-radius:8px"></div>
      </div>
    </div>
    <?php endfor; ?>
  </div>
</div>

<script>
const SLUG       = '<?= htmlspecialchars($slug) ?>';
const VND_NOMBRE = '<?= htmlspecialchars($vendedorNombre) ?>';

async function cargarPedidos() {
  try {
    // Pedidos de la tienda filtrados por vendedor_id en la sesión (server-side filter)
    const r = await fetch('/api/pedidos?accion=lista_vendedor');
    const d = await r.json();
    renderPedidos(d.pedidos || []);
  } catch(e) {
    document.getElementById('pedidos-container').innerHTML =
      '<p style="color:#e03;text-align:center;padding:40px">Error cargando pedidos</p>';
  }
}

function renderPedidos(pedidos) {
  const c = document.getElementById('pedidos-container');
  if (!pedidos.length) {
    c.innerHTML = `
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <h3>Sin pedidos asignados</h3>
        <p>Los pedidos que te asignen aparecerán aquí</p>
      </div>`;
    return;
  }

  const badges = {
    pendiente:  'badge-pendiente',
    confirmado: 'badge-confirmado',
    entregado:  'badge-entregado',
    cancelado:  'badge-cancelado',
  };

  c.innerHTML = pedidos.map(p => {
    const items = (p.items || []).map(i =>
      `<div class="pedido-item">
        <span class="pedido-item-nombre">${esc(i.nombre)} x${i.cantidad||1}</span>
        <span class="pedido-item-precio">S/ ${Number(i.precio||0).toFixed(2)}</span>
      </div>`
    ).join('');

    const waMsg = encodeURIComponent(
      `Hola ${p.cliente_nombre || ''}! 👋 Soy ${VND_NOMBRE} de la tienda ${SLUG}.\n` +
      `Te escribo sobre tu pedido #${p.id.slice(-6).toUpperCase()}.\n¿Cómo te podemos ayudar?`
    );
    const waLink = p.cliente_telefono
      ? `<a href="https://wa.me/${p.cliente_telefono}?text=${waMsg}" target="_blank" class="btn-wa-pedido">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.117.554 4.103 1.523 5.824L0 24l6.35-1.494A11.934 11.934 0 0012 24c6.627 0 12-5.373 12-12S18.626 0 11.999 0zm.001 21.818a9.82 9.82 0 01-5.013-1.375l-.36-.213-3.767.887.948-3.667-.233-.374A9.816 9.816 0 012.182 12c0-5.42 4.4-9.818 9.818-9.818 5.42 0 9.818 4.399 9.818 9.818 0 5.42-4.399 9.818-9.818 9.818z"/></svg>
          WhatsApp cliente
        </a>` : '';

    return `
      <div class="pedido-card">
        <div class="pedido-top">
          <div>
            <div class="pedido-id">#${p.id.slice(-8).toUpperCase()}</div>
            <div class="pedido-cliente">${esc(p.cliente_nombre || 'Sin nombre')}</div>
            <div class="pedido-contacto">${p.cliente_telefono ? '📞 '+esc(p.cliente_telefono) : ''} ${p.cliente_email ? '✉ '+esc(p.cliente_email) : ''}</div>
          </div>
          <div>
            <span class="badge ${badges[p.estado]||'badge-pendiente'}">${p.estado||'pendiente'}</span>
            <div class="pedido-fecha" style="margin-top:4px">${p.fecha ? new Date(p.fecha).toLocaleDateString('es-PE') : ''}</div>
          </div>
        </div>
        <div class="pedido-items">
          ${items}
          <div class="pedido-total"><span>Total</span><span>S/ ${Number(p.total||0).toFixed(2)}</span></div>
        </div>
        ${p.nota ? `<div style="font-size:12px;color:#888;margin-bottom:12px">📝 ${esc(p.nota)}</div>` : ''}
        <div class="pedido-actions">
          ${waLink}
        </div>
      </div>`;
  }).join('');
}

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

cargarPedidos();
</script>

<?php endif; ?>
</body>
</html>
