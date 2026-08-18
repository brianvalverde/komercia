<?php
require_once '/var/www/komercia/config/firebase.php';

$slug = $_GET['slug'] ?? '';
if (!$slug || !preg_match('/^[a-z0-9-]+$/', $slug)) {
    http_response_code(404);
    exit('Tienda no encontrada');
}

$tiendaDoc = firestoreRequest('GET', "tiendas/{$slug}");
if (isset($tiendaDoc['error'])) {
    http_response_code(404);
    exit('Tienda no encontrada');
}

$f = $tiendaDoc['fields'] ?? [];
function fsStr(array $f, string $k, string $d = ''): string {
    return $f[$k]['stringValue'] ?? $f[$k]['integerValue'] ?? $d;
}

$nombreTienda   = fsStr($f, 'nombre') ?: ucfirst($slug);
$uid            = fsStr($f, 'uid');
$telefono       = preg_replace('/[^0-9]/', '', fsStr($f, 'telefono'));
$whatsapp       = preg_replace('/[^0-9]/', '', fsStr($f, 'whatsapp') ?: $telefono);
$descripcion    = fsStr($f, 'descripcion', 'Bienvenido a nuestra tienda');
$logoUrl        = fsStr($f, 'logo');
$colorPrimario  = fsStr($f, 'color_primario', '#ff6a00');
$metodoVentas   = fsStr($f, 'metodo_ventas', 'whatsapp');
$deliveryTipo   = fsStr($f, 'delivery_tipo', 'no_incluido');
$deliveryPrecio = (float)(fsStr($f, 'delivery_precio', '0'));

if (!preg_match('/^#[0-9a-fA-F]{6}$/', $colorPrimario)) $colorPrimario = '#ff6a00';

// Derivar colores
$r = hexdec(substr($colorPrimario,1,2));
$g = hexdec(substr($colorPrimario,3,2));
$b = hexdec(substr($colorPrimario,5,2));
$colorDark  = sprintf('#%02x%02x%02x', max(0,$r-30), max(0,$g-30), max(0,$b-30));
$colorLight = sprintf('rgba(%d,%d,%d,0.12)', $r, $g, $b);

// Cargar productos (solo activos)
$productos = [];
$categorias = [];
if ($uid) {
    $res = firestoreRequest('GET', "comerciantes/{$uid}/productos");
    if (!isset($res['error']) && isset($res['documents'])) {
        foreach ($res['documents'] as $doc) {
            $pf = $doc['fields'] ?? [];
            $activo = $pf['activo']['booleanValue'] ?? true;
            if (!$activo) continue;

            $imagenes = [];
            if (isset($pf['imagenes']['arrayValue']['values'])) {
                foreach ($pf['imagenes']['arrayValue']['values'] as $v) {
                    if (isset($v['stringValue'])) $imagenes[] = $v['stringValue'];
                }
            }
            if (empty($imagenes) && !empty($pf['imagen']['stringValue'])) {
                $imagenes[] = $pf['imagen']['stringValue'];
            }

            $videos = [];
            if (isset($pf['videos']['arrayValue']['values'])) {
                foreach ($pf['videos']['arrayValue']['values'] as $v) {
                    if (isset($v['stringValue'])) $videos[] = $v['stringValue'];
                }
            }

            $promociones = [];
            if (isset($pf['promociones']['arrayValue']['values'])) {
                foreach ($pf['promociones']['arrayValue']['values'] as $v) {
                    $mf = $v['mapValue']['fields'] ?? [];
                    $promociones[] = [
                        'nombre'  => $mf['nombre']['stringValue'] ?? '',
                        'precio'  => (float)($mf['precio']['doubleValue'] ?? $mf['precio']['integerValue'] ?? 0),
                        'detalle' => $mf['detalle']['stringValue'] ?? '',
                    ];
                }
            }

            $cat = $pf['categoria']['stringValue'] ?? '';
            if ($cat && !in_array($cat, $categorias)) $categorias[] = $cat;

            $productos[] = [
                'id'          => basename($doc['name']),
                'nombre'      => $pf['nombre']['stringValue'] ?? '',
                'descripcion' => $pf['descripcion']['stringValue'] ?? '',
                'precio'      => (float)($pf['precio']['doubleValue'] ?? $pf['precio']['integerValue'] ?? 0),
                'stock'       => (int)($pf['stock']['integerValue'] ?? 0),
                'imagen'      => $imagenes[0] ?? '',
                'imagenes'    => $imagenes,
                'videos'      => $videos,
                'categoria'   => $cat,
                'promociones' => $promociones,
            ];
        }
    }
}

$tiendaJson   = json_encode(['nombre'=>$nombreTienda,'slug'=>$slug,'telefono'=>$whatsapp,'metodo'=>$metodoVentas,'delivery_tipo'=>$deliveryTipo,'delivery_precio'=>$deliveryPrecio]);
$productosJson = json_encode($productos);
$categoriasJson = json_encode(array_values($categorias));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($nombreTienda) ?></title>
<meta name="description" content="<?= htmlspecialchars($descripcion) ?>">
<meta property="og:title" content="<?= htmlspecialchars($nombreTienda) ?>">
<meta property="og:description" content="<?= htmlspecialchars($descripcion) ?>">
<?php if ($logoUrl): ?><meta property="og:image" content="<?= htmlspecialchars($logoUrl) ?>"><?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --c: <?= $colorPrimario ?>;
  --cd: <?= $colorDark ?>;
  --cl: <?= $colorLight ?>;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;background:#f7f7f7;color:#111;min-height:100vh}

/* ── TOPBAR ── */
.topbar{background:#fff;border-bottom:1px solid #eee;padding:0 16px;height:50px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:900}
.topbar-logo{display:flex;align-items:center;gap:10px;text-decoration:none;color:#111;font-weight:700;font-size:1rem}
.topbar-logo img{width:32px;height:32px;border-radius:6px;object-fit:contain}
.topbar-logo .logo-placeholder{width:32px;height:32px;border-radius:6px;background:var(--c);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.9rem}
.topbar-right{display:flex;align-items:center;gap:8px}
.cart-btn{position:relative;background:var(--c);color:#fff;border:none;border-radius:8px;padding:7px 14px;cursor:pointer;font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:6px;transition:.2s}
.cart-btn:hover{background:var(--cd)}
.cart-badge{background:#fff;color:var(--c);border-radius:50%;width:18px;height:18px;font-size:.7rem;font-weight:800;display:none;align-items:center;justify-content:center;position:absolute;top:-6px;right:-6px;border:2px solid var(--c)}

/* ── SEARCH BAR ── */
.search-bar{background:#fff;border-bottom:1px solid #eee;padding:10px 16px}
.search-input-wrap{display:flex;align-items:center;gap:8px;background:#f5f5f5;border-radius:10px;padding:8px 14px;max-width:600px;margin:0 auto}
.search-input-wrap svg{color:#999;flex-shrink:0}
.search-input{border:none;background:none;outline:none;font-size:.9rem;flex:1;font-family:'Inter',sans-serif;color:#111}
.search-input::placeholder{color:#aaa}

/* ── HERO ── */
.hero{background:linear-gradient(135deg,var(--c),var(--cd));color:#fff;padding:32px 16px;text-align:center}
.hero-logo{width:72px;height:72px;border-radius:14px;object-fit:contain;background:rgba(255,255,255,.2);padding:4px;margin:0 auto 14px;display:block}
.hero-logo-placeholder{width:72px;height:72px;border-radius:14px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 14px}
.hero h1{font-size:1.5rem;font-weight:800;margin-bottom:6px}
.hero p{font-size:.9rem;opacity:.85;max-width:500px;margin:0 auto}
.delivery-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.2);border-radius:20px;padding:5px 14px;font-size:.8rem;font-weight:600;margin-top:12px}

/* ── CATEGORÍAS ── */
.cats-bar{background:#fff;border-bottom:1px solid #eee;overflow-x:auto;scrollbar-width:none}
.cats-bar::-webkit-scrollbar{display:none}
.cats-inner{display:flex;gap:0;padding:0 12px;min-width:max-content}
.cat-btn{padding:12px 16px;border:none;background:none;cursor:pointer;font-size:.85rem;font-weight:500;color:#666;font-family:'Inter',sans-serif;white-space:nowrap;border-bottom:2px solid transparent;transition:.2s}
.cat-btn:hover{color:var(--c)}
.cat-btn.active{color:var(--c);border-bottom-color:var(--c);font-weight:700}

/* ── PRODUCTS ── */
.main-content{padding:16px;max-width:1200px;margin:0 auto}
.section-label{font-size:.8rem;font-weight:700;color:#999;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px}
.products-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
@media(min-width:600px){.products-grid{grid-template-columns:repeat(3,1fr)}}
@media(min-width:900px){.products-grid{grid-template-columns:repeat(4,1fr)}}

.product-card{background:#fff;border-radius:12px;overflow:hidden;cursor:pointer;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.07);display:flex;flex-direction:column}
.product-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.12);transform:translateY(-2px)}
.product-card-img{position:relative;width:100%;padding-bottom:100%;background:#f5f5f5;overflow:hidden}
.product-card-img img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.product-card-img .no-img{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:#ddd}
.product-card-img .has-gallery{position:absolute;bottom:6px;right:6px;background:rgba(0,0,0,.55);color:#fff;border-radius:6px;font-size:.7rem;padding:2px 6px}
.product-card-body{padding:10px;flex:1;display:flex;flex-direction:column;gap:4px}
.product-card-name{font-size:.9rem;font-weight:600;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.product-card-price{font-size:1rem;font-weight:800;color:var(--c)}
.product-card-cat{font-size:.7rem;color:#888;font-weight:500}
.product-card-add{margin-top:auto;background:var(--c);color:#fff;border:none;border-radius:8px;padding:7px;cursor:pointer;font-size:.8rem;font-weight:600;transition:.2s;font-family:'Inter',sans-serif}
.product-card-add:hover{background:var(--cd)}

/* ── EMPTY ── */
.no-products{text-align:center;padding:60px 20px;color:#aaa}
.no-products p{font-size:1.1rem;margin-top:12px}

/* ── PRODUCT MODAL ── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1000;align-items:flex-end;justify-content:center}
.modal-overlay.open{display:flex}
@media(min-width:600px){.modal-overlay{align-items:center}}
.product-modal{background:#fff;border-radius:20px 20px 0 0;width:100%;max-height:92vh;overflow-y:auto;display:flex;flex-direction:column}
@media(min-width:600px){.product-modal{border-radius:16px;max-width:680px;max-height:90vh}}
.modal-close{position:absolute;top:12px;right:12px;background:rgba(0,0,0,.4);color:#fff;border:none;border-radius:50%;width:34px;height:34px;cursor:pointer;font-size:1.2rem;display:flex;align-items:center;justify-content:center;z-index:2}
/* Gallery */
.gallery-wrap{position:relative;background:#f5f5f5}
.gallery-main{width:100%;aspect-ratio:1/1;object-fit:contain;background:#f5f5f5;cursor:zoom-in}
.gallery-video{width:100%;aspect-ratio:16/9;background:#000}
.gallery-thumbs{display:flex;gap:6px;padding:8px 12px;overflow-x:auto;scrollbar-width:none;background:#fff;border-bottom:1px solid #f0f0f0}
.gallery-thumbs::-webkit-scrollbar{display:none}
.gallery-thumb{width:56px;height:56px;border-radius:8px;object-fit:cover;cursor:pointer;border:2px solid transparent;flex-shrink:0;transition:.2s}
.gallery-thumb.active{border-color:var(--c)}
.gallery-thumb-video{width:56px;height:56px;border-radius:8px;background:#111;display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid transparent;flex-shrink:0;color:#fff;font-size:1.2rem;transition:.2s}
.gallery-thumb-video.active{border-color:var(--c)}
/* Product info in modal */
.modal-body{padding:16px 20px 24px}
.modal-nombre{font-size:1.2rem;font-weight:800;line-height:1.3;margin-bottom:6px}
.modal-precio{font-size:1.6rem;font-weight:800;color:var(--c);margin-bottom:12px}
.modal-desc{font-size:.88rem;color:#555;line-height:1.6;margin-bottom:16px}
/* Promociones */
.promo-section{margin-bottom:16px}
.promo-label{font-size:.8rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
.promo-table{width:100%;border-collapse:collapse;font-size:.85rem}
.promo-table th{background:#f5f5f5;padding:8px;text-align:left;font-weight:600;font-size:.78rem;color:#666}
.promo-table td{padding:8px;border-bottom:1px solid #f0f0f0}
.promo-row{cursor:pointer;transition:.15s}
.promo-row:hover,.promo-row.selected{background:var(--cl)}
.promo-row.selected td:first-child::before{content:'✓ ';color:var(--c);font-weight:800}
.promo-price{font-weight:700;color:var(--c)}
/* Qty + add */
.add-section{display:flex;gap:10px;align-items:center;margin-top:4px}
.qty-wrap{display:flex;align-items:center;gap:0;border:1.5px solid #ddd;border-radius:8px;overflow:hidden}
.qty-wrap button{background:none;border:none;width:36px;height:40px;cursor:pointer;font-size:1.1rem;font-weight:700;color:#333;display:flex;align-items:center;justify-content:center;transition:.15s}
.qty-wrap button:hover{background:#f5f5f5}
.qty-num{width:36px;text-align:center;font-weight:700;font-size:.95rem}
.btn-add-cart{flex:1;background:var(--c);color:#fff;border:none;border-radius:8px;padding:11px;cursor:pointer;font-size:.95rem;font-weight:700;transition:.2s;font-family:'Inter',sans-serif}
.btn-add-cart:hover{background:var(--cd)}

/* ── CART DRAWER ── */
.cart-drawer{position:fixed;top:0;right:-380px;width:380px;max-width:100vw;height:100%;background:#fff;box-shadow:-4px 0 24px rgba(0,0,0,.15);z-index:1100;display:flex;flex-direction:column;transition:right .3s cubic-bezier(.4,0,.2,1)}
.cart-drawer.open{right:0}
.cart-header{padding:18px 20px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between}
.cart-header h2{font-size:1rem;font-weight:700}
.cart-close{background:none;border:none;font-size:1.3rem;cursor:pointer;color:#555}
.cart-items{flex:1;overflow-y:auto;padding:12px}
.cart-item{display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #f5f5f5}
.cart-item-img{width:56px;height:56px;border-radius:8px;object-fit:cover;background:#f5f5f5;flex-shrink:0}
.cart-item-info{flex:1}
.cart-item-name{font-size:.85rem;font-weight:600;line-height:1.3;margin-bottom:2px}
.cart-item-variant{font-size:.75rem;color:#888;margin-bottom:4px}
.cart-item-row{display:flex;align-items:center;justify-content:space-between}
.cart-item-price{font-size:.9rem;font-weight:700;color:var(--c)}
.cart-item-del{background:none;border:none;color:#ccc;cursor:pointer;font-size:1rem}
.cart-item-del:hover{color:#cc0000}
.qty-mini{display:flex;align-items:center;gap:6px}
.qty-mini button{background:#f5f5f5;border:none;border-radius:4px;width:24px;height:24px;cursor:pointer;font-weight:700;font-size:.85rem}
.qty-mini span{font-size:.85rem;font-weight:600;width:20px;text-align:center}
.cart-empty{text-align:center;padding:60px 20px;color:#aaa}
.cart-footer{padding:16px 20px;border-top:1px solid #eee}
.cart-total{display:flex;justify-content:space-between;font-weight:700;font-size:1rem;margin-bottom:12px}
.btn-checkout{width:100%;padding:13px;background:var(--c);color:#fff;border:none;border-radius:10px;font-size:.95rem;font-weight:700;cursor:pointer;transition:.2s;font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;gap:8px}
.btn-checkout:hover{background:var(--cd)}

/* ── ORDER FORM MODAL ── */
.form-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1200;align-items:center;justify-content:center;padding:16px}
.form-modal.open{display:flex}
.form-modal-box{background:#fff;border-radius:16px;padding:24px;width:100%;max-width:440px;max-height:90vh;overflow-y:auto}
.form-modal-box h2{font-size:1.1rem;font-weight:700;margin-bottom:16px}
.fm-group{margin-bottom:14px}
.fm-group label{font-size:.8rem;font-weight:600;color:#666;margin-bottom:5px;display:block;text-transform:uppercase;letter-spacing:.4px}
.fm-group input,.fm-group textarea{width:100%;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:.9rem;font-family:'Inter',sans-serif;outline:none;transition:.2s}
.fm-group input:focus,.fm-group textarea:focus{border-color:var(--c)}
.fm-group textarea{min-height:70px;resize:vertical}
.fm-actions{display:flex;gap:10px;margin-top:20px}
.fm-cancel{flex:1;padding:11px;border:1.5px solid #e5e7eb;border-radius:8px;background:#fff;cursor:pointer;font-size:.9rem;font-weight:600;font-family:'Inter',sans-serif}
.fm-submit{flex:2;padding:11px;background:var(--c);color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:.9rem;font-weight:700;font-family:'Inter',sans-serif}
.fm-submit:hover{background:var(--cd)}

/* ── FOOTER ── */
.footer{text-align:center;padding:28px 16px;font-size:.78rem;color:#aaa;border-top:1px solid #eee;background:#fff;margin-top:20px}
.footer a{color:var(--c);text-decoration:none;font-weight:600}

/* ── CART OVERLAY ── */
.drawer-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:1050}
.drawer-overlay.show{display:block}
</style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
  <a class="topbar-logo" href="<?= '/tienda/' . htmlspecialchars($slug) ?>">
    <?php if ($logoUrl): ?>
      <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($nombreTienda) ?>">
    <?php else: ?>
      <div class="logo-placeholder"><?= strtoupper(substr($nombreTienda,0,1)) ?></div>
    <?php endif; ?>
    <span><?= htmlspecialchars($nombreTienda) ?></span>
  </a>
  <div class="topbar-right">
    <button class="cart-btn" onclick="abrirCarrito()">
      🛒 Carrito
      <span class="cart-badge" id="cart-badge">0</span>
    </button>
  </div>
</header>

<!-- SEARCH -->
<div class="search-bar">
  <div class="search-input-wrap">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input class="search-input" type="search" id="search-input" placeholder="Buscar productos..." oninput="filtrar()">
  </div>
</div>

<!-- HERO -->
<div class="hero">
  <?php if ($logoUrl): ?>
    <img class="hero-logo" src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($nombreTienda) ?>">
  <?php else: ?>
    <div class="hero-logo-placeholder">🏪</div>
  <?php endif; ?>
  <h1><?= htmlspecialchars($nombreTienda) ?></h1>
  <p><?= htmlspecialchars($descripcion) ?></p>
  <?php if ($deliveryTipo === 'gratis'): ?>
    <div class="delivery-badge">🎁 Envío gratis</div>
  <?php elseif ($deliveryTipo === 'costo_fijo' && $deliveryPrecio > 0): ?>
    <div class="delivery-badge">📦 Envío S/. <?= number_format($deliveryPrecio,2) ?></div>
  <?php endif; ?>
</div>

<!-- CATEGORÍAS -->
<?php if (!empty($categorias)): ?>
<div class="cats-bar" id="cats-bar">
  <div class="cats-inner">
    <button class="cat-btn active" onclick="filtrarCat('')" data-cat="">Todos</button>
    <?php foreach ($categorias as $cat): ?>
      <button class="cat-btn" onclick="filtrarCat('<?= htmlspecialchars($cat, ENT_QUOTES) ?>')" data-cat="<?= htmlspecialchars($cat, ENT_QUOTES) ?>"><?= htmlspecialchars($cat) ?></button>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- PRODUCTOS -->
<div class="main-content">
  <div class="section-label" id="result-label"><?= count($productos) ?> productos</div>
  <div class="products-grid" id="products-grid"></div>
  <div class="no-products" id="no-products" style="display:none">
    <div style="font-size:3rem">😕</div>
    <p>No encontramos productos.</p>
  </div>
</div>

<!-- FOOTER -->
<footer class="footer">
  Powered by <a href="https://komercia.online" target="_blank">Komercia</a>
</footer>

<!-- PRODUCT MODAL -->
<div class="modal-overlay" id="product-modal-overlay" onclick="handleModalOverlayClick(event)">
  <div class="product-modal" id="product-modal">
    <button class="modal-close" onclick="cerrarModal()">✕</button>
    <div class="gallery-wrap" id="gallery-wrap">
      <img class="gallery-main" id="gallery-main" src="" alt="">
    </div>
    <div class="gallery-thumbs" id="gallery-thumbs"></div>
    <div class="modal-body">
      <div class="modal-nombre" id="modal-nombre"></div>
      <div class="modal-precio" id="modal-precio"></div>
      <div class="modal-desc" id="modal-desc"></div>
      <div class="promo-section" id="promo-section" style="display:none">
        <div class="promo-label">Variantes / Promociones</div>
        <table class="promo-table">
          <thead><tr><th>Opción</th><th>Precio</th><th>Detalle</th></tr></thead>
          <tbody id="promo-tbody"></tbody>
        </table>
      </div>
      <div class="add-section">
        <div class="qty-wrap">
          <button onclick="changeQty(-1)">−</button>
          <span class="qty-num" id="qty-num">1</span>
          <button onclick="changeQty(1)">+</button>
        </div>
        <button class="btn-add-cart" onclick="addToCart()">🛒 Agregar al carrito</button>
      </div>
    </div>
  </div>
</div>

<!-- CART DRAWER -->
<div class="drawer-overlay" id="drawer-overlay" onclick="cerrarCarrito()"></div>
<div class="cart-drawer" id="cart-drawer">
  <div class="cart-header">
    <h2>🛒 Mi carrito</h2>
    <button class="cart-close" onclick="cerrarCarrito()">✕</button>
  </div>
  <div class="cart-items" id="cart-items"></div>
  <div class="cart-footer" id="cart-footer" style="display:none">
    <div class="cart-total"><span>Total</span><span id="cart-total-price">S/. 0.00</span></div>
    <button class="btn-checkout" onclick="checkout()">
      <?php if ($metodoVentas === 'whatsapp'): ?>
        💬 Pedir por WhatsApp
      <?php else: ?>
        📋 Completar pedido
      <?php endif; ?>
    </button>
  </div>
</div>

<!-- ORDER FORM MODAL -->
<div class="form-modal" id="form-modal">
  <div class="form-modal-box">
    <h2>📋 Completa tu pedido</h2>
    <div class="fm-group"><label>Nombre completo *</label><input type="text" id="fm-nombre" placeholder="Juan Pérez"></div>
    <div class="fm-group"><label>Teléfono *</label><input type="tel" id="fm-tel" placeholder="+51 999 999 999"></div>
    <div class="fm-group"><label>Dirección de entrega</label><input type="text" id="fm-dir" placeholder="Av. Principal 123, Lima"></div>
    <div class="fm-group"><label>Notas adicionales</label><textarea id="fm-notas" placeholder="Horario preferido, referencias..."></textarea></div>
    <div class="fm-actions">
      <button class="fm-cancel" onclick="cerrarFormModal()">Cancelar</button>
      <button class="fm-submit" onclick="enviarFormulario()">Enviar pedido ✓</button>
    </div>
  </div>
</div>

<script>
const tienda   = <?= $tiendaJson ?>;
const productos = <?= $productosJson ?>;
const SLUG     = '<?= $slug ?>';
const CART_KEY = 'cart_' + SLUG;

let cart = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
let currentProduct = null;
let currentPromo   = null;
let qty = 1;
let catActual = '';
let searchTerm = '';

// ── RENDER PRODUCTS ───────────────────────────────────────────
function getFiltered() {
  return productos.filter(p => {
    const matchCat  = !catActual || p.categoria === catActual;
    const matchText = !searchTerm || p.nombre.toLowerCase().includes(searchTerm) || (p.descripcion||'').toLowerCase().includes(searchTerm);
    return matchCat && matchText;
  });
}

function renderProductos() {
  const filtered = getFiltered();
  const grid = document.getElementById('products-grid');
  const noP  = document.getElementById('no-products');
  const label = document.getElementById('result-label');
  label.textContent = filtered.length + (filtered.length === 1 ? ' producto' : ' productos');

  if (!filtered.length) {
    grid.innerHTML = ''; noP.style.display = 'block'; return;
  }
  noP.style.display = 'none';
  grid.innerHTML = filtered.map((p, i) => `
    <div class="product-card" onclick="abrirProducto(${i})">
      <div class="product-card-img">
        ${p.imagen
          ? `<img src="${p.imagen}" alt="${esc(p.nombre)}" loading="lazy">`
          : '<div class="no-img">🖼️</div>'}
        ${(p.imagenes && p.imagenes.length > 1) || (p.videos && p.videos.length)
          ? `<div class="has-gallery">📷 ${(p.imagenes||[]).length}${p.videos&&p.videos.length?` 🎬${p.videos.length}`:''}</div>`
          : ''}
      </div>
      <div class="product-card-body">
        ${p.categoria ? `<div class="product-card-cat">${esc(p.categoria)}</div>` : ''}
        <div class="product-card-name">${esc(p.nombre)}</div>
        <div class="product-card-price">S/. ${fmt(p.precio)}</div>
        <button class="product-card-add" onclick="event.stopPropagation();abrirProducto(${i});addToCart(true)">+ Agregar</button>
      </div>
    </div>`).join('');
}

function filtrar() {
  searchTerm = document.getElementById('search-input').value.toLowerCase().trim();
  renderProductos();
}

function filtrarCat(cat) {
  catActual = cat;
  document.querySelectorAll('.cat-btn').forEach(b => b.classList.toggle('active', b.dataset.cat === cat));
  renderProductos();
}

// ── PRODUCT MODAL ─────────────────────────────────────────────
function abrirProducto(i) {
  const filtered = getFiltered();
  currentProduct = filtered[i] || productos[i];
  currentPromo   = null;
  qty = 1;
  document.getElementById('qty-num').textContent = qty;

  // Gallery
  const allMedia = [
    ...(currentProduct.imagenes || (currentProduct.imagen ? [currentProduct.imagen] : [])).map(u => ({type:'img',url:u})),
    ...(currentProduct.videos || []).map(u => ({type:'video',url:u})),
  ];

  const wrap   = document.getElementById('gallery-wrap');
  const main   = document.getElementById('gallery-main');
  const thumbs = document.getElementById('gallery-thumbs');

  if (allMedia.length) {
    showMedia(allMedia[0], wrap, main);
    thumbs.innerHTML = allMedia.map((m, idx) => {
      if (m.type === 'img') {
        return `<img class="gallery-thumb ${idx===0?'active':''}" src="${m.url}" onclick="switchMedia(${idx})" loading="lazy">`;
      } else {
        return `<div class="gallery-thumb-video ${idx===0?'active':''}" onclick="switchMedia(${idx})">▶</div>`;
      }
    }).join('');
    thumbs.style.display = allMedia.length > 1 ? 'flex' : 'none';
    // Store media list
    wrap._media = allMedia;
    wrap._idx   = 0;
  } else {
    wrap.innerHTML = '<div style="width:100%;aspect-ratio:1/1;background:#f5f5f5;display:flex;align-items:center;justify-content:center;font-size:4rem;color:#ddd">🖼️</div>';
    thumbs.style.display = 'none';
  }

  document.getElementById('modal-nombre').textContent = currentProduct.nombre;
  document.getElementById('modal-precio').textContent  = 'S/. ' + fmt(currentProduct.precio);
  document.getElementById('modal-desc').textContent    = currentProduct.descripcion || '';

  // Promociones
  const promos = currentProduct.promociones || [];
  const ps = document.getElementById('promo-section');
  if (promos.length) {
    ps.style.display = 'block';
    document.getElementById('promo-tbody').innerHTML = promos.map((p,i) => `
      <tr class="promo-row" onclick="selectPromo(${i})" data-i="${i}">
        <td>${esc(p.nombre)}</td>
        <td class="promo-price">S/. ${fmt(p.precio)}</td>
        <td style="color:#888;font-size:.8rem">${esc(p.detalle)}</td>
      </tr>`).join('');
  } else {
    ps.style.display = 'none';
  }

  document.getElementById('product-modal-overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function showMedia(m, wrap, main) {
  if (m.type === 'img') {
    if (!main || main.tagName !== 'IMG') {
      const img = document.createElement('img');
      img.className = 'gallery-main'; img.id = 'gallery-main';
      wrap.innerHTML = ''; wrap.appendChild(img); main = img;
    }
    main.src = m.url;
    main.style.display = 'block';
  } else {
    const vid = document.createElement('video');
    vid.className = 'gallery-video'; vid.controls = true; vid.src = m.url; vid.autoplay = false;
    wrap.innerHTML = ''; wrap.appendChild(vid);
  }
}

function switchMedia(idx) {
  const wrap  = document.getElementById('gallery-wrap');
  const main  = wrap.querySelector('.gallery-main') || wrap.querySelector('video');
  const media = wrap._media || [];
  wrap._idx   = idx;
  showMedia(media[idx], wrap, main);
  document.querySelectorAll('.gallery-thumb,.gallery-thumb-video').forEach((t,i) => t.classList.toggle('active', i===idx));
}

function selectPromo(i) {
  const promos = currentProduct.promociones || [];
  currentPromo = promos[i];
  document.querySelectorAll('.promo-row').forEach((r,ri) => r.classList.toggle('selected', ri===i));
  document.getElementById('modal-precio').textContent = 'S/. ' + fmt(currentPromo.precio);
}

function changeQty(d) {
  qty = Math.max(1, qty + d);
  document.getElementById('qty-num').textContent = qty;
}

function cerrarModal() {
  document.getElementById('product-modal-overlay').classList.remove('open');
  document.body.style.overflow = '';
  currentProduct = null; currentPromo = null;
}

function handleModalOverlayClick(e) {
  if (e.target === document.getElementById('product-modal-overlay')) cerrarModal();
}

// ── CART ──────────────────────────────────────────────────────
function addToCart(quick = false) {
  if (!currentProduct) return;
  const nombre = currentPromo
    ? currentProduct.nombre + ' – ' + currentPromo.nombre
    : currentProduct.nombre;
  const precio = currentPromo ? currentPromo.precio : currentProduct.precio;
  const imagen = currentProduct.imagen || '';
  const prodId = currentProduct.id;

  const key = prodId + (currentPromo ? '_' + currentPromo.nombre : '');
  const idx  = cart.findIndex(c => c.key === key);
  if (idx >= 0) {
    cart[idx].qty += qty;
  } else {
    cart.push({ key, prodId, nombre, precio, imagen, qty });
  }
  saveCart();
  if (!quick) cerrarModal();
  abrirCarrito();
}

function saveCart() {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
  updateBadge();
}

function updateBadge() {
  const total = cart.reduce((s,c) => s + c.qty, 0);
  const badge = document.getElementById('cart-badge');
  badge.textContent = total;
  badge.style.display = total > 0 ? 'flex' : 'none';
}

function abrirCarrito() {
  renderCarrito();
  document.getElementById('cart-drawer').classList.add('open');
  document.getElementById('drawer-overlay').classList.add('show');
  document.body.style.overflow = 'hidden';
}

function cerrarCarrito() {
  document.getElementById('cart-drawer').classList.remove('open');
  document.getElementById('drawer-overlay').classList.remove('show');
  document.body.style.overflow = '';
}

function renderCarrito() {
  const container = document.getElementById('cart-items');
  const footer    = document.getElementById('cart-footer');
  if (!cart.length) {
    container.innerHTML = '<div class="cart-empty"><div style="font-size:2.5rem">🛒</div><p style="margin-top:10px">Tu carrito está vacío</p></div>';
    footer.style.display = 'none'; return;
  }
  footer.style.display = 'block';
  container.innerHTML = cart.map((item, i) => `
    <div class="cart-item">
      ${item.imagen ? `<img class="cart-item-img" src="${item.imagen}" alt="">` : '<div class="cart-item-img" style="display:flex;align-items:center;justify-content:center;font-size:1.4rem">🛒</div>'}
      <div class="cart-item-info">
        <div class="cart-item-name">${esc(item.nombre)}</div>
        <div class="cart-item-row">
          <div class="qty-mini">
            <button onclick="updateQtyCart(${i},-1)">−</button>
            <span>${item.qty}</span>
            <button onclick="updateQtyCart(${i},1)">+</button>
          </div>
          <div class="cart-item-price">S/. ${fmt(item.precio * item.qty)}</div>
          <button class="cart-item-del" onclick="removeFromCart(${i})">🗑</button>
        </div>
      </div>
    </div>`).join('');

  const total = cart.reduce((s,c) => s + c.precio * c.qty, 0);
  let totalText = 'S/. ' + fmt(total);
  if (tienda.delivery_tipo === 'costo_fijo' && tienda.delivery_precio > 0) {
    totalText += ' + S/. ' + fmt(tienda.delivery_precio) + ' envío';
  }
  document.getElementById('cart-total-price').textContent = totalText;
}

function updateQtyCart(i, d) {
  cart[i].qty = Math.max(1, cart[i].qty + d);
  saveCart(); renderCarrito();
}
function removeFromCart(i) {
  cart.splice(i, 1); saveCart(); renderCarrito();
}

// ── CHECKOUT ──────────────────────────────────────────────────
function checkout() {
  if (!cart.length) return;
  if (tienda.metodo === 'formulario') {
    cerrarCarrito();
    document.getElementById('form-modal').classList.add('open');
  } else {
    enviarWhatsApp();
  }
}

function enviarWhatsApp() {
  const lines = cart.map(c => `• ${c.nombre} x${c.qty} = S/. ${fmt(c.precio * c.qty)}`);
  const total = cart.reduce((s,c) => s + c.precio * c.qty, 0);
  let msg = `Hola, quiero hacer un pedido en *${tienda.nombre}*:\n\n${lines.join('\n')}\n\n*Total: S/. ${fmt(total)}*`;
  if (tienda.delivery_tipo === 'gratis') msg += '\n\n🎁 Envío gratis';
  else if (tienda.delivery_tipo === 'costo_fijo' && tienda.delivery_precio > 0) msg += `\n📦 Envío: S/. ${fmt(tienda.delivery_precio)}`;
  const wa = 'https://wa.me/' + tienda.telefono + '?text=' + encodeURIComponent(msg);
  window.open(wa, '_blank');
}

function cerrarFormModal() {
  document.getElementById('form-modal').classList.remove('open');
}

async function enviarFormulario() {
  const nombre = document.getElementById('fm-nombre').value.trim();
  const tel    = document.getElementById('fm-tel').value.trim();
  if (!nombre || !tel) { alert('Por favor completa nombre y teléfono.'); return; }

  const items  = cart.map(c => ({ nombre: c.nombre, qty: c.qty, precio: c.precio }));
  const total  = cart.reduce((s,c) => s + c.precio * c.qty, 0);
  const fd     = new FormData();
  fd.append('slug',       SLUG);
  fd.append('nombre',     nombre);
  fd.append('telefono',   tel);
  fd.append('direccion',  document.getElementById('fm-dir').value.trim());
  fd.append('notas',      document.getElementById('fm-notas').value.trim());
  fd.append('items',      JSON.stringify(items));
  fd.append('total',      total);

  try {
    const res  = await fetch('/api/pedidos?accion=crear', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      cart = []; saveCart(); cerrarFormModal(); cerrarCarrito();
      alert('✅ ¡Pedido enviado! Nos pondremos en contacto pronto.');
    } else {
      alert('Error: ' + (data.error || 'No se pudo enviar'));
    }
  } catch(e) {
    alert('Error de conexión.');
  }
}

// ── UTILS ─────────────────────────────────────────────────────
function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmt(n) { return parseFloat(n||0).toFixed(2); }

// ── INIT ──────────────────────────────────────────────────────
updateBadge();
renderProductos();
</script>
</body>
</html>
