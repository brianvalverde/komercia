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
<title>Planes — Komercia</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

/* ── Topbar ──────────────────────────────────────── */
.topbar{position:fixed;top:0;left:220px;right:0;height:60px;background:#fff;border-bottom:1px solid #e8eaf0;display:flex;align-items:center;justify-content:space-between;padding:0 28px;z-index:90}
.topbar-left h2{font-size:16px;font-weight:700;color:#1a1a2e}
.topbar-left p{font-size:12px;color:#888;margin-top:2px}

/* ── Main ────────────────────────────────────────── */
.main{margin-left:220px;padding-top:60px;flex:1}
.content{padding:28px;max-width:1100px;margin:0 auto}

/* ── Status banner ───────────────────────────────── */
#plan-status-banner{border-radius:14px;padding:18px 24px;margin-bottom:32px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
#plan-status-banner.trial{background:#fffbea;border:1.5px solid #f5c842}
#plan-status-banner.pro{background:#edfff5;border:1.5px solid #2ecc71}
#plan-status-banner.empresarial{background:#f3eeff;border:1.5px solid #9b59b6}
#plan-status-banner.vencido{background:#fff0f0;border:1.5px solid #e74c3c}
.banner-left{display:flex;align-items:center;gap:14px}
.banner-icon{font-size:28px}
.banner-title{font-size:15px;font-weight:700;color:#1a1a2e}
.banner-sub{font-size:13px;color:#666;margin-top:3px}

/* ── Section heading ─────────────────────────────── */
.section-head{text-align:center;margin-bottom:36px}
.section-head h1{font-size:28px;font-weight:800;color:#1a1a2e;margin-bottom:8px}
.section-head p{font-size:15px;color:#666;max-width:500px;margin:0 auto}

/* ── Plans grid ──────────────────────────────────── */
.plans-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:40px}
@media(max-width:820px){.plans-grid{grid-template-columns:1fr}}

.plan-card{background:#fff;border-radius:18px;padding:32px 28px;border:2px solid #e8eaf0;position:relative;transition:transform .2s,box-shadow .2s}
.plan-card:hover{transform:translateY(-4px);box-shadow:0 12px 40px rgba(0,0,0,.10)}
.plan-card.featured{border-color:#ff6a00;box-shadow:0 8px 32px rgba(255,106,0,.18)}

.plan-badge-top{position:absolute;top:-14px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;font-size:11px;font-weight:700;padding:4px 16px;border-radius:99px;white-space:nowrap;letter-spacing:.5px}

.plan-name{font-size:13px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px}
.plan-price{font-size:42px;font-weight:800;color:#1a1a2e;line-height:1;margin-bottom:4px}
.plan-price span{font-size:16px;font-weight:600;color:#888}
.plan-period{font-size:12px;color:#aaa;margin-bottom:24px}

.plan-divider{border:none;border-top:1px solid #f0f2f5;margin:0 0 24px}

.plan-features{list-style:none;display:flex;flex-direction:column;gap:10px;margin-bottom:32px}
.plan-features li{display:flex;align-items:flex-start;gap:10px;font-size:14px;color:#444}
.plan-features li .chk{width:20px;height:20px;flex-shrink:0;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;margin-top:1px}
.plan-features li .chk.ok{background:#e8fff0;color:#27ae60}
.plan-features li .chk.no{background:#f5f5f5;color:#bbb}
.plan-features li.dim{color:#bbb}

.plan-cta{width:100%;padding:14px;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;border:none;transition:all .2s}
.plan-cta.outline{background:#fff;border:2px solid #e0e0e0;color:#555}
.plan-cta.outline:hover{border-color:#ff6a00;color:#ff6a00}
.plan-cta.primary{background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;box-shadow:0 4px 16px rgba(255,106,0,.35)}
.plan-cta.primary:hover{opacity:.9}
.plan-cta.purple{background:linear-gradient(135deg,#9b59b6,#6c3483);color:#fff;box-shadow:0 4px 16px rgba(155,89,182,.35)}
.plan-cta.purple:hover{opacity:.9}
.plan-cta:disabled{opacity:.5;cursor:not-allowed}

/* Current plan tag */
.current-tag{display:inline-block;background:#edfff5;color:#27ae60;font-size:11px;font-weight:700;padding:3px 10px;border-radius:99px;border:1px solid #2ecc71;margin-bottom:12px}

/* ── FAQ ─────────────────────────────────────────── */
.faq-section{background:#fff;border-radius:18px;padding:32px;margin-bottom:32px}
.faq-section h2{font-size:18px;font-weight:700;margin-bottom:24px;color:#1a1a2e}
.faq-item{border-bottom:1px solid #f0f2f5;padding:16px 0}
.faq-item:last-child{border-bottom:none;padding-bottom:0}
.faq-q{font-size:14px;font-weight:600;color:#1a1a2e;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:12px}
.faq-q .arr{font-size:18px;color:#aaa;transition:transform .2s;flex-shrink:0}
.faq-q.open .arr{transform:rotate(180deg)}
.faq-a{font-size:13px;color:#666;line-height:1.7;margin-top:10px;display:none}
.faq-a.open{display:block}

/* ── Contact strip ───────────────────────────────── */
.contact-strip{background:linear-gradient(135deg,#ff6a00,#ee0979);border-radius:18px;padding:28px 32px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:28px}
.contact-strip h3{font-size:18px;font-weight:700;color:#fff}
.contact-strip p{font-size:13px;color:rgba(255,255,255,.85);margin-top:4px}
.contact-strip a{display:inline-flex;align-items:center;gap:8px;background:#fff;color:#ff6a00;font-weight:700;font-size:14px;padding:12px 22px;border-radius:12px;text-decoration:none;white-space:nowrap;transition:opacity .2s}
.contact-strip a:hover{opacity:.9}
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
    <a href="/panel/planes" class="active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      Planes
    </a>
  </nav>
</aside>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left">
    <h2>Planes y precios</h2>
    <p>Elige el plan que mejor se adapta a tu negocio</p>
  </div>
</div>

<!-- Main -->
<div class="main">
<div class="content">

  <!-- Status banner (loaded via JS) -->
  <div id="plan-status-banner" style="display:none"></div>

  <!-- Heading -->
  <div class="section-head">
    <h1>Impulsa tu tienda</h1>
    <p>Sin comisiones por venta. Pagas solo por el plan mensual.</p>
  </div>

  <!-- Plans grid -->
  <div class="plans-grid">

    <!-- TRIAL -->
    <div class="plan-card" id="card-trial">
      <div class="plan-name">Trial</div>
      <div class="plan-price">S/ 0 <span>/mes</span></div>
      <div class="plan-period">7 días gratis · sin tarjeta</div>
      <hr class="plan-divider">
      <ul class="plan-features">
        <li><span class="chk ok">✓</span> Hasta 10 productos</li>
        <li><span class="chk ok">✓</span> Tienda pública completa</li>
        <li><span class="chk ok">✓</span> Pedidos por WhatsApp</li>
        <li><span class="chk ok">✓</span> Carrito de compras</li>
        <li class="dim"><span class="chk no">✗</span> <span>Productos ilimitados</span></li>
        <li class="dim"><span class="chk no">✗</span> <span>Estadísticas avanzadas</span></li>
        <li class="dim"><span class="chk no">✗</span> <span>Soporte prioritario</span></li>
      </ul>
      <button class="plan-cta outline" disabled id="cta-trial">Plan actual</button>
    </div>

    <!-- PRO (featured) -->
    <div class="plan-card featured" id="card-pro">
      <div class="plan-badge-top">⚡ MÁS POPULAR</div>
      <div class="plan-name">Pro</div>
      <div class="plan-price">S/ 49 <span>/mes</span></div>
      <div class="plan-period">Facturado mensualmente</div>
      <hr class="plan-divider">
      <ul class="plan-features">
        <li><span class="chk ok">✓</span> Hasta 500 productos</li>
        <li><span class="chk ok">✓</span> Tienda pública completa</li>
        <li><span class="chk ok">✓</span> Pedidos por WhatsApp</li>
        <li><span class="chk ok">✓</span> Carrito de compras</li>
        <li><span class="chk ok">✓</span> Galería de imágenes y video</li>
        <li><span class="chk ok">✓</span> Banners y redes sociales</li>
        <li class="dim"><span class="chk no">✗</span> <span>Soporte prioritario</span></li>
      </ul>
      <button class="plan-cta primary" id="cta-pro" onclick="contactarPlan('Pro - S/ 49/mes')">Activar Pro</button>
    </div>

    <!-- EMPRESARIAL -->
    <div class="plan-card" id="card-emp">
      <div class="plan-name">Empresarial</div>
      <div class="plan-price">S/ 99 <span>/mes</span></div>
      <div class="plan-period">Facturado mensualmente</div>
      <hr class="plan-divider">
      <ul class="plan-features">
        <li><span class="chk ok">✓</span> Productos ilimitados</li>
        <li><span class="chk ok">✓</span> Tienda pública completa</li>
        <li><span class="chk ok">✓</span> Pedidos por WhatsApp</li>
        <li><span class="chk ok">✓</span> Carrito de compras</li>
        <li><span class="chk ok">✓</span> Galería de imágenes y video</li>
        <li><span class="chk ok">✓</span> Banners y redes sociales</li>
        <li><span class="chk ok">✓</span> Soporte prioritario</li>
      </ul>
      <button class="plan-cta purple" id="cta-emp" onclick="contactarPlan('Empresarial - S/ 99/mes')">Activar Empresarial</button>
    </div>

  </div>

  <!-- Contact strip -->
  <div class="contact-strip">
    <div>
      <h3>¿Tienes dudas o quieres factura?</h3>
      <p>Escríbenos por WhatsApp y te ayudamos a elegir el plan ideal.</p>
    </div>
    <a href="https://wa.me/51933989236?text=Hola%2C%20quiero%20información%20sobre%20los%20planes%20de%20Komercia" target="_blank">
      <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
      Hablar por WhatsApp
    </a>
  </div>

  <!-- FAQ -->
  <div class="faq-section">
    <h2>Preguntas frecuentes</h2>

    <div class="faq-item">
      <div class="faq-q" onclick="toggleFaq(this)">¿Cuándo se cobra el plan? <span class="arr">▾</span></div>
      <div class="faq-a">El cobro es mensual desde el día que activas el plan. Te avisamos por correo 3 días antes del vencimiento.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q" onclick="toggleFaq(this)">¿Puedo cambiar de plan en cualquier momento? <span class="arr">▾</span></div>
      <div class="faq-a">Sí, puedes subir o bajar de plan cuando quieras. Si subes, el cambio es inmediato. Si bajas, aplica al siguiente ciclo.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q" onclick="toggleFaq(this)">¿Komercia cobra comisión por cada venta? <span class="arr">▾</span></div>
      <div class="faq-a">No. Komercia cobra solo la cuota mensual del plan. Las ventas son 100% tuyas, sin comisiones.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q" onclick="toggleFaq(this)">¿Qué pasa cuando vence el trial? <span class="arr">▾</span></div>
      <div class="faq-a">Tu tienda se pausa automáticamente y muestra un aviso a tus clientes. Tus datos y productos quedan guardados. Al activar un plan, la tienda vuelve a funcionar de inmediato.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q" onclick="toggleFaq(this)">¿Emiten factura o boleta? <span class="arr">▾</span></div>
      <div class="faq-a">Sí, emitimos boleta electrónica automáticamente. Si necesitas factura, escríbenos por WhatsApp antes del pago.</div>
    </div>
  </div>

</div>
</div>

<script>
// ── Cargar plan actual ─────────────────────────────────────────
async function cargarPlan() {
  try {
    const r = await fetch('/api/plan?accion=info');
    const p = await r.json();
    if (!p.ok) return;

    const banner = document.getElementById('plan-status-banner');

    // Highlight current plan card
    ['trial','pro','empresarial'].forEach(k => {
      const card = document.getElementById('card-' + (k==='pro'?'pro':k==='empresarial'?'emp':'trial'));
      const cta  = document.getElementById('cta-' + (k==='pro'?'pro':k==='empresarial'?'emp':'trial'));
      if (card && p.plan === k) {
        // Add "current" label
        const tag = document.createElement('div');
        tag.className = 'current-tag';
        tag.textContent = '✓ Tu plan actual';
        card.insertBefore(tag, card.querySelector('.plan-name'));
        if (cta) { cta.textContent = 'Plan actual'; cta.disabled = true; cta.className = 'plan-cta outline'; }
      }
    });

    // Status banner
    let cls, icon, title, sub;
    if (p.vencido) {
      cls  = 'vencido';
      icon = '⚠️';
      title = 'Tu plan ha vencido';
      sub   = 'Tu tienda está en pausa. Activa un plan para volver a recibir clientes.';
    } else if (p.plan === 'trial') {
      cls  = 'trial';
      icon = '⏳';
      title = `Plan Trial · ${p.dias_restantes} día${p.dias_restantes !== 1 ? 's' : ''} restante${p.dias_restantes !== 1 ? 's' : ''}`;
      sub   = 'Límite de 10 productos. Actualiza para crecer sin límites.';
    } else if (p.plan === 'pro') {
      cls  = 'pro';
      icon = '⚡';
      title = 'Plan Pro activo';
      sub   = p.dias_restantes !== null ? `Vence en ${p.dias_restantes} días · hasta 500 productos` : 'Hasta 500 productos';
    } else if (p.plan === 'empresarial') {
      cls  = 'empresarial';
      icon = '👑';
      title = 'Plan Empresarial activo';
      sub   = p.dias_restantes !== null ? `Vence en ${p.dias_restantes} días · productos ilimitados` : 'Productos ilimitados';
    }

    if (cls) {
      banner.className = cls;
      banner.innerHTML = `
        <div class="banner-left">
          <div class="banner-icon">${icon}</div>
          <div>
            <div class="banner-title">${title}</div>
            <div class="banner-sub">${sub}</div>
          </div>
        </div>`;
      banner.style.display = 'flex';
    }
  } catch(e) { console.error('Error al cargar plan:', e); }
}

// ── Contactar por WhatsApp con el plan pre-seleccionado ────────
function contactarPlan(planLabel) {
  const msg = encodeURIComponent(`Hola, quiero activar el plan ${planLabel} en Komercia para mi tienda komercia.online/tienda/<?= htmlspecialchars($slug) ?>`);
  window.open(`https://wa.me/51933989236?text=${msg}`, '_blank');
}

// ── FAQ toggle ─────────────────────────────────────────────────
function toggleFaq(el) {
  el.classList.toggle('open');
  const ans = el.nextElementSibling;
  ans.classList.toggle('open');
}

// Init
cargarPlan();
</script>
</body>
</html>
