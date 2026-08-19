<?php
// ──────────────────────────────────────────────────────────
// producto.php — Página de detalle de producto (SEO-friendly)
// URL: /tienda/{slug}/producto/{id}
// ──────────────────────────────────────────────────────────
require_once '/var/www/komercia/config/firebase.php';

$slug  = $_GET['slug']  ?? '';
$prodId = $_GET['id']   ?? '';

if (!$slug || !$prodId) {
    http_response_code(404);
    die('Página no encontrada');
}

// ── Obtener datos de la tienda ──────────────────────────────
$tiendaDoc = firestoreRequest('GET', "tiendas/{$slug}");
if (!$tiendaDoc || isset($tiendaDoc['error'])) {
    http_response_code(404);
    die('Tienda no encontrada');
}
$tf = $tiendaDoc['fields'] ?? [];
function fsv(array $f, string $k, string $d = ''): string {
    return (string)($f[$k]['stringValue'] ?? $f[$k]['integerValue'] ?? $f[$k]['doubleValue'] ?? $d);
}
function fsArr(array $f, string $k): array {
    $out = [];
    foreach (($f[$k]['arrayValue']['values'] ?? []) as $v) {
        if (isset($v['stringValue'])) $out[] = $v['stringValue'];
    }
    return $out;
}

$uid            = fsv($tf, 'uid');
$tiendaNombre   = fsv($tf, 'nombre', 'Tienda');
$tiendaLogo     = fsv($tf, 'logo');
$colorPrimario  = fsv($tf, 'color_primario', '#ff6a00');
$metodoVentas   = fsv($tf, 'metodo_ventas', 'whatsapp');
$whatsapp       = fsv($tf, 'whatsapp');
$deliveryTipo   = fsv($tf, 'delivery_tipo', 'no_incluido');
$deliveryPrecio = fsv($tf, 'delivery_precio', '0');
$facebook       = fsv($tf, 'facebook');
$instagram      = fsv($tf, 'instagram');
$tiktok         = fsv($tf, 'tiktok');

if (!$uid) { http_response_code(404); die('Tienda no encontrada'); }

// ── Obtener producto ────────────────────────────────────────
$prodDoc = firestoreRequest('GET', "comerciantes/{$uid}/productos/{$prodId}");
if (!$prodDoc || isset($prodDoc['error'])) {
    http_response_code(404);
    die('Producto no encontrado');
}
$pf = $prodDoc['fields'] ?? [];

$pNombre      = fsv($pf, 'nombre', 'Producto');
$pPrecio      = (float)($pf['precio']['doubleValue'] ?? $pf['precio']['integerValue'] ?? 0);
$pDescripcion = fsv($pf, 'descripcion');
$pCategoria   = fsv($pf, 'categoria');
$pStock       = (int)($pf['stock']['integerValue'] ?? 99);
$pActivo      = $pf['activo']['booleanValue'] ?? true;
$pImagenes    = fsArr($pf, 'imagenes');
$pVideos      = fsArr($pf, 'videos');
$pImagenPrinc = $pImagenes[0] ?? fsv($pf, 'imagen');

// Variantes
$pVariantes = [];
foreach (($pf['variantes']['arrayValue']['values'] ?? []) as $v) {
    $mf = $v['mapValue']['fields'] ?? [];
    if (!empty($mf['nombre']['stringValue'])) {
        $pVariantes[] = fsv($mf, 'nombre');
    }
}

// Promociones
$pPromociones = [];
foreach (($pf['promociones']['arrayValue']['values'] ?? []) as $v) {
    $mf = $v['mapValue']['fields'] ?? [];
    if (!empty($mf['cantidad']['integerValue']) && !empty($mf['precio']['doubleValue'])) {
        $pPromociones[] = [
            'cantidad' => (int)$mf['cantidad']['integerValue'],
            'precio'   => (float)$mf['precio']['doubleValue'],
        ];
    }
}

// Reseñas aprobadas
$resenasDoc = firestoreRequest('GET', "comerciantes/{$uid}/productos/{$prodId}/resenas");
$resenasArr = [];
foreach (($resenasDoc['documents'] ?? []) as $doc) {
    $rf = $doc['fields'] ?? [];
    if (!($rf['aprobada']['booleanValue'] ?? false)) continue;
    $resenasArr[] = [
        'id'         => basename($doc['name']),
        'nombre'     => fsv($rf, 'nombre', 'Anónimo'),
        'pais'       => fsv($rf, 'pais'),
        'estrellas'  => (int)($rf['estrellas']['integerValue'] ?? 5),
        'comentario' => fsv($rf, 'comentario'),
        'fecha'      => fsv($rf, 'fecha'),
    ];
}
usort($resenasArr, fn($a,$b) => strcmp($b['fecha'], $a['fecha']));
$avgEstrellas = count($resenasArr) ? array_sum(array_column($resenasArr, 'estrellas')) / count($resenasArr) : 0;

// Productos relacionados (misma categoría, activos, excluyendo este)
$todosProds = firestoreRequest('GET', "comerciantes/{$uid}/productos");
$relacionados = [];
foreach (($todosProds['documents'] ?? []) as $doc) {
    $rf = $doc['fields'] ?? [];
    $rid = basename($doc['name']);
    if ($rid === $prodId) continue;
    if (!($rf['activo']['booleanValue'] ?? true)) continue;
    $rCat = fsv($rf, 'categoria');
    if ($pCategoria && $rCat !== $pCategoria) continue;
    $rImgs = [];
    foreach (($rf['imagenes']['arrayValue']['values'] ?? []) as $v) {
        if (isset($v['stringValue'])) $rImgs[] = $v['stringValue'];
    }
    $relacionados[] = [
        'id'     => $rid,
        'nombre' => fsv($rf, 'nombre', 'Producto'),
        'precio' => (float)($rf['precio']['doubleValue'] ?? $rf['precio']['integerValue'] ?? 0),
        'imagen' => $rImgs[0] ?? fsv($rf, 'imagen'),
    ];
    if (count($relacionados) >= 8) break;
}

// SEO
$metaTitle       = htmlspecialchars("{$pNombre} - {$tiendaNombre}");
$metaDesc        = htmlspecialchars($pDescripcion ?: "{$pNombre} disponible en {$tiendaNombre}. Precio: S/. " . number_format($pPrecio, 2));
$metaImg         = $pImagenPrinc;
$canonicalUrl    = "https://{$_SERVER['HTTP_HOST']}/tienda/{$slug}/producto/{$prodId}";
$tiendaUrl       = "https://{$_SERVER['HTTP_HOST']}/tienda/{$slug}";
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= $metaTitle ?></title>
<meta name="description" content="<?= $metaDesc ?>">
<link rel="canonical" href="<?= $canonicalUrl ?>">
<!-- Open Graph -->
<meta property="og:title"       content="<?= $metaTitle ?>">
<meta property="og:description" content="<?= $metaDesc ?>">
<meta property="og:image"       content="<?= htmlspecialchars($metaImg) ?>">
<meta property="og:url"         content="<?= $canonicalUrl ?>">
<meta property="og:type"        content="product">
<!-- Twitter Card -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="<?= $metaTitle ?>">
<meta name="twitter:description" content="<?= $metaDesc ?>">
<meta name="twitter:image"       content="<?= htmlspecialchars($metaImg) ?>">
<!-- Schema.org Product -->
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": <?= json_encode($pNombre) ?>,
  "description": <?= json_encode($pDescripcion) ?>,
  "image": <?= json_encode($pImagenPrinc) ?>,
  "offers": {
    "@type": "Offer",
    "priceCurrency": "PEN",
    "price": <?= json_encode($pPrecio) ?>,
    "availability": "<?= $pStock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' ?>"
  }
  <?php if (count($resenasArr) > 0): ?>
  , "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": <?= round($avgEstrellas, 1) ?>,
    "reviewCount": <?= count($resenasArr) ?>
  }
  <?php endif; ?>
}
</script>
<style>
:root{--primary:<?= $colorPrimario ?>}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#f5f5f5;color:#333}
a{text-decoration:none;color:inherit}

/* ── TOPBAR ── */
.topbar{background:#fff;border-bottom:1px solid #eee;padding:0 24px;height:56px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.topbar-left{display:flex;align-items:center;gap:14px}
.topbar-logo{height:36px;width:36px;border-radius:50%;object-fit:cover}
.topbar-name{font-weight:700;font-size:15px}
.topbar-right{display:flex;align-items:center;gap:14px}
.social-link{font-size:20px;color:#555;transition:.2s}
.social-link:hover{color:var(--primary)}
.btn-cart{background:var(--primary);color:#fff;border:none;border-radius:20px;padding:8px 18px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px}

/* ── BREADCRUMB ── */
.breadcrumb{padding:12px 24px;font-size:13px;color:#888;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.breadcrumb a{color:#888;transition:.2s}
.breadcrumb a:hover{color:var(--primary)}
.breadcrumb span{color:var(--primary)}

/* ── MAIN LAYOUT ── */
.container{max-width:1140px;margin:0 auto;padding:20px 24px}

/* ── PRODUCT DETAIL ── */
.prod-detail{display:grid;grid-template-columns:80px 1fr 420px;gap:24px;background:#fff;border-radius:16px;padding:28px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:32px}
.gallery-thumbs{display:flex;flex-direction:column;gap:10px}
.thumb{width:72px;height:72px;border-radius:10px;object-fit:cover;border:2px solid transparent;cursor:pointer;transition:.2s}
.thumb:hover,.thumb.active{border-color:var(--primary)}
.video-thumb{width:72px;height:72px;border-radius:10px;background:#111;display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid transparent;font-size:22px;color:#fff;transition:.2s}
.video-thumb:hover,.video-thumb.active{border-color:var(--primary)}
.gallery-main{position:relative}
.zoom-lens{position:absolute;width:110px;height:110px;border:2px solid #ff6a00;border-radius:6px;pointer-events:none;display:none;z-index:10;box-sizing:border-box}.zoom-result{display:none;position:absolute;left:calc(100% + 12px);top:0;width:360px;height:360px;border:1px solid #e0e0e0;border-radius:14px;overflow:hidden;background:#fff;z-index:20;box-shadow:0 8px 32px rgba(0,0,0,.14)}@media(max-width:900px){.zoom-result{display:none!important}}.main-img{width:100%;aspect-ratio:1;object-fit:contain;background:#fff;border-radius:14px;display:block}
.main-video{width:100%;aspect-ratio:1;border-radius:14px;display:none;background:#000}
.gallery-arrows{position:absolute;top:50%;transform:translateY(-50%);width:100%;display:flex;justify-content:space-between;pointer-events:none;padding:0 10px}
.gallery-arrow{width:36px;height:36px;background:rgba(255,255,255,.85);border:none;border-radius:50%;cursor:pointer;font-size:18px;pointer-events:all;transition:.2s;display:flex;align-items:center;justify-content:center}
.gallery-arrow:hover{background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.2)}

/* ── PRODUCT INFO ── */
.prod-info{}
.prod-categoria{font-size:12px;color:#888;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px}
.prod-nombre{font-size:26px;font-weight:800;line-height:1.2;margin-bottom:10px}
.rating-row{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.stars{color:#f59e0b;font-size:16px}
.rating-count{font-size:13px;color:#888}
.prod-precio{font-size:32px;font-weight:900;color:var(--primary);margin-bottom:16px}
.low-stock{display:inline-block;background:#fff3cd;color:#856404;font-size:12px;font-weight:700;padding:4px 10px;border-radius:6px;margin-bottom:12px}
.out-stock{display:inline-block;background:#f8d7da;color:#721c24;font-size:12px;font-weight:700;padding:4px 10px;border-radius:6px;margin-bottom:12px}

/* Promociones */
.promo-title{font-size:13px;font-weight:700;color:#555;margin-bottom:8px}
.promo-table{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.promo-pill{padding:6px 14px;border-radius:20px;border:2px solid #eee;font-size:13px;cursor:pointer;transition:.2s;font-weight:600;background:#fff}
.promo-pill:hover,.promo-pill.active{border-color:var(--primary);background:var(--primary);color:#fff}

/* Variantes */
.var-title{font-size:13px;font-weight:700;color:#555;margin-bottom:8px}
.var-grid{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.var-btn{padding:7px 16px;border:2px solid #eee;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;background:#fff;transition:.2s}
.var-btn:hover{border-color:var(--primary)}
.var-btn.active{border-color:var(--primary);background:var(--primary);color:#fff}

/* Cantidad */
.qty-row{display:flex;align-items:center;gap:12px;margin-bottom:20px}
.qty-label{font-size:13px;font-weight:700;color:#555}
.qty-ctrl{display:flex;align-items:center;gap:0;border:1px solid #ddd;border-radius:8px;overflow:hidden}
.qty-btn{width:36px;height:36px;border:none;background:#f5f5f5;font-size:18px;cursor:pointer;transition:.2s}
.qty-btn:hover{background:#eee}
.qty-val{width:44px;text-align:center;font-weight:700;font-size:15px;border:none;outline:none}

/* CTA */
.cta-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px}
.btn-add{flex:1;min-width:160px;background:var(--primary);color:#fff;border:none;border-radius:10px;padding:13px;font-size:15px;font-weight:700;cursor:pointer;transition:.2s}
.btn-add:hover{filter:brightness(1.1)}
.btn-wsp{flex:1;min-width:160px;background:#25d366;color:#fff;border:none;border-radius:10px;padding:13px;font-size:15px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:.2s}
.btn-wsp:hover{background:#1db954}
.btn-wsp svg{width:20px;height:20px;fill:currentColor}

/* Descripción */
.prod-desc-title{font-size:14px;font-weight:700;color:#555;margin-bottom:6px;margin-top:4px}
.prod-desc{font-size:14px;color:#666;line-height:1.6}

/* ── RESEÑAS ── */
.section-title{font-size:20px;font-weight:800;margin-bottom:20px}
.resenas-wrap{background:#fff;border-radius:16px;padding:28px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:32px}
.resenas-summary{display:flex;align-items:center;gap:24px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid #f0f0f0}
.avg-num{font-size:56px;font-weight:900;color:var(--primary);line-height:1}
.avg-stars{font-size:28px;color:#f59e0b}
.avg-count{font-size:13px;color:#888}
.resena-card{padding:16px 0;border-bottom:1px solid #f5f5f5}
.resena-card:last-child{border-bottom:none}
.resena-header{display:flex;align-items:center;gap:10px;margin-bottom:6px}
.resena-avatar{width:36px;height:36px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;flex-shrink:0}
.resena-nombre{font-weight:700;font-size:14px}
.resena-pais{font-size:12px;color:#aaa}
.resena-stars{color:#f59e0b;font-size:14px}
.resena-fecha{font-size:11px;color:#bbb;margin-left:auto}
.resena-text{font-size:14px;color:#555;line-height:1.5}
.no-resenas{text-align:center;padding:32px 0;color:#aaa;font-size:14px}
/* Formulario reseña */
.form-resena{margin-top:24px;padding-top:20px;border-top:1px solid #f0f0f0}
.form-resena h4{font-size:16px;font-weight:700;margin-bottom:16px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px}
.form-row.single{grid-template-columns:1fr}
.form-control{padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px;font-family:inherit;width:100%;outline:none;transition:.2s}
.form-control:focus{border-color:var(--primary)}
.star-input{display:flex;gap:4px;margin-bottom:12px}
.star-input label{font-size:28px;cursor:pointer;color:#ddd;transition:.2s}
.star-input input{display:none}
.star-input label:hover,.star-input label.on{color:#f59e0b}
.btn-submit-resena{background:var(--primary);color:#fff;border:none;border-radius:8px;padding:11px 24px;font-size:14px;font-weight:700;cursor:pointer;transition:.2s}
.btn-submit-resena:hover{filter:brightness(1.1)}
.btn-submit-resena:disabled{opacity:.6;cursor:not-allowed}
.resena-ok{display:none;padding:12px 16px;background:#d4edda;color:#155724;border-radius:8px;font-size:14px;margin-top:10px}

/* ── RELACIONADOS ── */
.rel-wrap{background:#fff;border-radius:16px;padding:28px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:32px}
.rel-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px}
.rel-card{border-radius:12px;overflow:hidden;border:1px solid #f0f0f0;background:#fff;transition:.2s;cursor:pointer}
.rel-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.1);transform:translateY(-2px)}
.rel-img{width:100%;aspect-ratio:1;object-fit:cover;display:block;background:#f5f5f5}
.rel-info{padding:10px}
.rel-nombre{font-size:13px;font-weight:600;line-height:1.3;margin-bottom:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.rel-precio{font-size:14px;font-weight:800;color:var(--primary)}

/* ── CART DRAWER ── */
.drawer-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:200;display:none;opacity:0;transition:.3s}
.drawer-overlay.open{display:block;opacity:1}
.drawer{position:fixed;right:0;top:0;height:100%;width:min(400px,100vw);background:#fff;z-index:201;transform:translateX(100%);transition:.3s;display:flex;flex-direction:column}
.drawer.open{transform:translateX(0)}
.drawer-header{padding:20px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between}
.drawer-header h3{font-size:18px;font-weight:700}
.drawer-close{background:none;border:none;font-size:22px;cursor:pointer;padding:4px}
.drawer-items{flex:1;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:12px}
.cart-item{display:flex;gap:12px;align-items:center;padding:12px;background:#f9f9f9;border-radius:10px}
.cart-item-img{width:56px;height:56px;border-radius:8px;object-fit:cover;flex-shrink:0;background:#eee}
.cart-item-info{flex:1;min-width:0}
.cart-item-name{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cart-item-var{font-size:11px;color:#888}
.cart-item-price{font-size:14px;font-weight:800;color:var(--primary)}
.cart-item-remove{background:none;border:none;color:#ccc;cursor:pointer;font-size:18px;padding:4px;transition:.2s}
.cart-item-remove:hover{color:#e74c3c}
.drawer-footer{padding:20px;border-top:1px solid #eee}
.cart-total{display:flex;justify-content:space-between;font-size:16px;font-weight:700;margin-bottom:16px}
.btn-checkout{width:100%;background:var(--primary);color:#fff;border:none;border-radius:10px;padding:14px;font-size:15px;font-weight:700;cursor:pointer;transition:.2s}
.btn-checkout:hover{filter:brightness(1.1)}
.cart-empty-msg{text-align:center;color:#aaa;padding:40px 0;font-size:14px}

/* ── MODAL FORMULARIO PEDIDO ── */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:300;display:flex;align-items:center;justify-content:center;padding:20px;display:none}
.modal-overlay.open{display:flex}
.modal{background:#fff;border-radius:16px;width:100%;max-width:440px;max-height:90vh;overflow-y:auto}
.modal-header{padding:20px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between}
.modal-header h3{font-size:18px;font-weight:700}
.modal-close{background:none;border:none;font-size:22px;cursor:pointer}
.modal-body{padding:20px;display:flex;flex-direction:column;gap:12px}
.input-group{display:flex;flex-direction:column;gap:6px}
.input-group label{font-size:13px;font-weight:600;color:#555}
.input-group input,.input-group textarea{padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px;font-family:inherit;outline:none;transition:.2s}
.input-group input:focus,.input-group textarea:focus{border-color:var(--primary)}
.input-group textarea{resize:vertical;min-height:70px}
.modal-footer{padding:20px;border-top:1px solid #eee}
.btn-confirmar{width:100%;background:var(--primary);color:#fff;border:none;border-radius:10px;padding:14px;font-size:15px;font-weight:700;cursor:pointer;transition:.2s}
.btn-confirmar:hover{filter:brightness(1.1)}
.btn-confirmar:disabled{opacity:.6;cursor:not-allowed}

/* BACK TO TOP */
.back-top{position:fixed;bottom:24px;right:24px;width:44px;height:44px;background:var(--primary);color:#fff;border:none;border-radius:50%;font-size:20px;cursor:pointer;display:none;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,.2);z-index:150;transition:.3s}
.back-top.show{display:flex}

/* TOAST */
.toast{position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.85);color:#fff;padding:10px 22px;border-radius:24px;font-size:14px;font-weight:600;z-index:999;opacity:0;pointer-events:none;transition:.3s}
.toast.show{opacity:1}

@media(max-width:900px){
  .prod-detail{grid-template-columns:60px 1fr;grid-template-rows:auto auto}
  .prod-info{grid-column:2;grid-row:1}
  .prod-detail>.gallery-thumbs{grid-column:1;grid-row:1/3}
}
@media(max-width:640px){
  .prod-detail{grid-template-columns:1fr;padding:16px}
  .gallery-thumbs{flex-direction:row;overflow-x:auto}
  .form-row{grid-template-columns:1fr}
  .container{padding:12px 16px}
  .rel-grid{grid-template-columns:repeat(2,1fr)}
}
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
  <div class="topbar-left">
    <?php if ($tiendaLogo): ?>
    <img src="<?= htmlspecialchars($tiendaLogo) ?>" class="topbar-logo" alt="Logo">
    <?php endif; ?>
    <a href="<?= $tiendaUrl ?>" class="topbar-name"><?= htmlspecialchars($tiendaNombre) ?></a>
  </div>
  <div class="topbar-right">
    <?php if ($facebook): ?>
    <a href="<?= htmlspecialchars($facebook) ?>" target="_blank" class="social-link" title="Facebook">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
    </a>
    <?php endif; ?>
    <?php if ($instagram): ?>
    <a href="<?= htmlspecialchars($instagram) ?>" target="_blank" class="social-link" title="Instagram">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><circle cx="12" cy="12" r="3"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
    </a>
    <?php endif; ?>
    <?php if ($tiktok): ?>
    <a href="<?= htmlspecialchars($tiktok) ?>" target="_blank" class="social-link" title="TikTok">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg>
    </a>
    <?php endif; ?>
    <button class="btn-cart" onclick="openDrawer()">
      🛒 <span id="cart-count">0</span>
    </button>
  </div>
</div>

<!-- BREADCRUMB -->
<div class="breadcrumb">
  <a href="<?= $tiendaUrl ?>">🏠 <?= htmlspecialchars($tiendaNombre) ?></a>
  <span>›</span>
  <?php if ($pCategoria): ?>
  <a href="<?= $tiendaUrl ?>?cat=<?= urlencode($pCategoria) ?>"><?= htmlspecialchars($pCategoria) ?></a>
  <span>›</span>
  <?php endif; ?>
  <span><?= htmlspecialchars($pNombre) ?></span>
</div>

<div class="container">

  <!-- PRODUCT DETAIL -->
  <div class="prod-detail">
    <!-- Thumbnails izquierda -->
    <div class="gallery-thumbs" id="thumbs-col">
      <?php
      $mediaIdx = 0;
      foreach ($pImagenes as $i => $img): ?>
        <img src="<?= htmlspecialchars($img) ?>" class="thumb<?= $i === 0 ? ' active' : '' ?>"
             data-idx="<?= $mediaIdx++ ?>" onclick="selectMedia(this, 'img', '<?= htmlspecialchars($img) ?>')" alt="<?= htmlspecialchars($pNombre) ?> imagen <?= $i+1 ?>">
      <?php endforeach; ?>
      <?php foreach ($pVideos as $j => $vid): ?>
        <div class="video-thumb" data-idx="<?= $mediaIdx++ ?>" onclick="selectMedia(this, 'vid', '<?= htmlspecialchars($vid) ?>')">▶</div>
      <?php endforeach; ?>
    </div>

    <!-- Imagen principal -->
    <div class="gallery-main">
      <img id="main-img" src="<?= htmlspecialchars($pImagenPrinc) ?>" class="main-img" alt="<?= htmlspecialchars($pNombre) ?>">
      <div class="zoom-lens" id="zoom-lens"></div><div class="zoom-result" id="zoom-result"><img id="zoom-img" style="position:absolute;pointer-events:none;max-width:none" alt=""></div><video id="main-video" class="main-video" controls></video>
      <?php if (count($pImagenes) + count($pVideos) > 1): ?>
      <div class="gallery-arrows">
        <button class="gallery-arrow" onclick="navMedia(-1)">‹</button>
        <button class="gallery-arrow" onclick="navMedia(1)">›</button>
      </div>
      <?php endif; ?>
    </div>

    <!-- Info -->
    <div class="prod-info">
      <?php if ($pCategoria): ?>
      <div class="prod-categoria"><?= htmlspecialchars($pCategoria) ?></div>
      <?php endif; ?>
      <div class="prod-nombre"><?= htmlspecialchars($pNombre) ?></div>

      <!-- Rating -->
      <?php if (count($resenasArr) > 0): ?>
      <div class="rating-row">
        <span class="stars">
          <?php for ($s=1;$s<=5;$s++): ?>
            <?= $s <= round($avgEstrellas) ? '★' : '☆' ?>
          <?php endfor; ?>
        </span>
        <span class="rating-count"><?= number_format($avgEstrellas,1) ?> (<?= count($resenasArr) ?> reseña<?= count($resenasArr)!==1?'s':'' ?>)</span>
      </div>
      <?php endif; ?>

      <div class="prod-precio" id="precio-display">S/. <?= number_format($pPrecio, 2) ?></div>

      <?php if ($pStock <= 0): ?>
        <div class="out-stock">😔 Sin stock</div>
      <?php elseif ($pStock <= 5): ?>
        <div class="low-stock">⚠️ Solo quedan <?= $pStock ?> unidades</div>
      <?php endif; ?>

      <!-- Promociones -->
      <?php if (!empty($pPromociones)): ?>
      <div class="promo-title">🏷️ Promociones por cantidad</div>
      <div class="promo-table">
        <?php foreach ($pPromociones as $prom): ?>
        <div class="promo-pill" onclick="selectPromo(this, <?= $prom['precio'] ?>, <?= $prom['cantidad'] ?>)">
          <?= $prom['cantidad'] ?>+ unidades → S/. <?= number_format($prom['precio'], 2) ?> c/u
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Variantes -->
      <?php if (!empty($pVariantes)): ?>
      <div class="var-title">Variante</div>
      <div class="var-grid" id="variantes-wrap">
        <?php foreach ($pVariantes as $var): ?>
        <button class="var-btn" onclick="toggleVariante(this)"><?= htmlspecialchars($var) ?></button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Cantidad -->
      <?php if ($pStock > 0): ?>
      <div class="qty-row">
        <span class="qty-label">Cantidad</span>
        <div class="qty-ctrl">
          <button class="qty-btn" onclick="changeQty(-1)">−</button>
          <input type="number" id="qty" class="qty-val" value="1" min="1" max="<?= $pStock ?>">
          <button class="qty-btn" onclick="changeQty(1)">+</button>
        </div>
      </div>
      <div class="cta-row">
        <?php if ($metodoVentas === 'formulario'): ?>
          <button class="btn-add" onclick="addToCart()">🛒 Agregar al carrito</button>
          <button class="btn-wsp" onclick="openCartDrawerWsp()">
            <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.127.557 4.126 1.533 5.862L.054 23.447a.5.5 0 0 0 .499.553.502.502 0 0 0 .132-.018l5.801-1.57A11.939 11.939 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
            Comprar por WhatsApp
          </button>
        <?php else: ?>
          <button class="btn-add" onclick="addToCart()">🛒 Agregar al carrito</button>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="out-stock" style="font-size:15px;padding:10px 16px">Sin stock disponible</div>
      <?php endif; ?>

      <!-- Descripción -->
      <?php if ($pDescripcion): ?>
      <div class="prod-desc-title">Descripción</div>
      <div class="prod-desc"><?= nl2br(htmlspecialchars($pDescripcion)) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- RESEÑAS -->
  <div class="resenas-wrap">
    <div class="section-title">⭐ Reseñas</div>
    <?php if (count($resenasArr) > 0): ?>
    <div class="resenas-summary">
      <div>
        <div class="avg-num"><?= number_format($avgEstrellas, 1) ?></div>
        <div class="avg-stars">
          <?php for ($s=1;$s<=5;$s++) echo $s<=round($avgEstrellas)?'★':'☆'; ?>
        </div>
        <div class="avg-count"><?= count($resenasArr) ?> reseña<?= count($resenasArr)!==1?'s':'' ?></div>
      </div>
    </div>
    <?php foreach ($resenasArr as $r): ?>
    <div class="resena-card">
      <div class="resena-header">
        <div class="resena-avatar"><?= mb_strtoupper(mb_substr($r['nombre'],0,1)) ?></div>
        <div>
          <div class="resena-nombre"><?= htmlspecialchars($r['nombre']) ?><?= $r['pais'] ? ' <span style="font-size:11px;color:#aaa">— '.htmlspecialchars($r['pais']).'</span>' : '' ?></div>
          <div class="resena-stars"><?php for($s=1;$s<=5;$s++) echo $s<=$r['estrellas']?'★':'☆'; ?></div>
        </div>
        <div class="resena-fecha"><?= $r['fecha'] ? date('d M Y', strtotime($r['fecha'])) : '' ?></div>
      </div>
      <div class="resena-text"><?= htmlspecialchars($r['comentario']) ?></div>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="no-resenas">No hay reseñas aún. ¡Sé el primero en opinar!</div>
    <?php endif; ?>

    <!-- Formulario nueva reseña -->
    <div class="form-resena">
      <h4>Deja tu reseña</h4>
      <div class="star-input" id="star-input">
        <?php for($s=5;$s>=1;$s--): ?>
        <input type="radio" name="estrellas" id="s<?=$s?>" value="<?=$s?>">
        <label for="s<?=$s?>" title="<?=$s?> estrellas">★</label>
        <?php endfor; ?>
      </div>
      <div class="form-row">
        <input class="form-control" id="res-nombre" placeholder="Tu nombre *" required>
        <input class="form-control" id="res-pais" placeholder="País (opcional)">
      </div>
      <div class="form-row single">
        <textarea class="form-control" id="res-comentario" placeholder="Tu comentario *" rows="3"></textarea>
      </div>
      <button class="btn-submit-resena" id="btn-resena" onclick="enviarResena()">Enviar reseña</button>
      <div class="resena-ok" id="resena-ok">✅ ¡Gracias! Tu reseña está pendiente de aprobación.</div>
    </div>
  </div>

  <!-- PRODUCTOS RELACIONADOS -->
  <?php if (!empty($relacionados)): ?>
  <div class="rel-wrap">
    <div class="section-title">🛍️ Productos relacionados</div>
    <div class="rel-grid">
      <?php foreach ($relacionados as $r): ?>
      <div class="rel-card" onclick="location.href='/tienda/<?= urlencode($slug) ?>/producto/<?= urlencode($r['id']) ?>'">
        <?php if ($r['imagen']): ?>
        <img src="<?= htmlspecialchars($r['imagen']) ?>" class="rel-img" alt="<?= htmlspecialchars($r['nombre']) ?>" loading="lazy">
        <?php else: ?>
        <div class="rel-img" style="display:flex;align-items:center;justify-content:center;color:#ccc;font-size:32px">📦</div>
        <?php endif; ?>
        <div class="rel-info">
          <div class="rel-nombre"><?= htmlspecialchars($r['nombre']) ?></div>
          <div class="rel-precio">S/. <?= number_format($r['precio'], 2) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /container -->

<!-- CART DRAWER -->
<div class="drawer-overlay" id="drawer-overlay" onclick="closeDrawer()"></div>
<div class="drawer" id="drawer">
  <div class="drawer-header">
    <h3>🛒 Tu carrito</h3>
    <button class="drawer-close" onclick="closeDrawer()">✕</button>
  </div>
  <div class="drawer-items" id="drawer-items"></div>
  <div class="drawer-footer">
    <div class="cart-total"><span>Total</span><span id="cart-total-val">S/. 0.00</span></div>
    <button class="btn-checkout" onclick="checkout()">Finalizar pedido</button>
  </div>
</div>

<!-- MODAL PEDIDO -->
<div class="modal-overlay" id="modal-pedido">
  <div class="modal">
    <div class="modal-header">
      <h3>📋 Datos del pedido</h3>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <div class="input-group"><label>Nombre completo *</label><input id="ped-nombre" placeholder="Ej: Juan Pérez"></div>
      <div class="input-group"><label>Teléfono *</label><input id="ped-tel" type="tel" placeholder="+51 999 999 999"></div>
      <div class="input-group"><label>Dirección de entrega</label><input id="ped-dir" placeholder="Calle, número, distrito"></div>
      <div class="input-group"><label>Notas (opcional)</label><textarea id="ped-notas" placeholder="Color, talla, instrucciones..."></textarea></div>
      <?php if ($deliveryTipo === 'costo_fijo'): ?>
      <div style="background:#f9f9f9;border-radius:8px;padding:12px;font-size:14px">🚚 Costo de envío: <strong>S/. <?= number_format((float)$deliveryPrecio, 2) ?></strong></div>
      <?php elseif ($deliveryTipo === 'gratis'): ?>
      <div style="background:#d4edda;border-radius:8px;padding:12px;font-size:14px;color:#155724">✅ Envío gratis</div>
      <?php endif; ?>
    </div>
    <div class="modal-footer">
      <button class="btn-confirmar" id="btn-confirmar" onclick="confirmarPedido()">Confirmar pedido</button>
    </div>
  </div>
</div>

<!-- BACK TO TOP -->
<button class="back-top" id="back-top" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>
<div class="toast" id="toast"></div>

<script>
const SLUG    = <?= json_encode($slug) ?>;
const PROD_ID = <?= json_encode($prodId) ?>;
const PROD    = {
  id:     PROD_ID,
  nombre: <?= json_encode($pNombre) ?>,
  precio: <?= json_encode($pPrecio) ?>,
  imagen: <?= json_encode($pImagenPrinc) ?>,
  stock:  <?= json_encode($pStock) ?>,
};
const METODO_VENTAS   = <?= json_encode($metodoVentas) ?>;
const WHATSAPP        = <?= json_encode($whatsapp) ?>;
const DELIVERY_TIPO   = <?= json_encode($deliveryTipo) ?>;
const DELIVERY_PRECIO = <?= json_encode((float)$deliveryPrecio) ?>;

let cart = [];
try { cart = JSON.parse(localStorage.getItem('cart_' + SLUG) || '[]'); } catch(e){}

let currentMediaIdx = 0;
let totalMedia = <?= count($pImagenes) + count($pVideos) ?>;
let allMedia = [
  <?php foreach ($pImagenes as $img): ?>{'type':'img','src':<?= json_encode($img) ?>},<?php endforeach; ?>
  <?php foreach ($pVideos as $vid): ?>{'type':'vid','src':<?= json_encode($vid) ?>},<?php endforeach; ?>
];
let selectedVariante = null;
let selectedPromoPrice = null;
let selectedPromoQty   = 0;

// ── GALLERY ──────────────────────────────────────────────────
function selectMedia(el, type, src) {
  document.querySelectorAll('.thumb,.video-thumb').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  currentMediaIdx = parseInt(el.dataset.idx);
  showMedia(type, src);
}

function showMedia(type, src) {
  const img = document.getElementById('main-img');
  const vid = document.getElementById('main-video');
  if (type === 'vid') {
    img.style.display = 'none';
    vid.style.display = 'block';
    vid.src = src;
    vid.play().catch(()=>{});
  } else {
    vid.pause(); vid.style.display = 'none';
    img.style.display = 'block';
    img.src = src;
  }
}

function navMedia(dir) {
  if (totalMedia === 0) return;
  currentMediaIdx = (currentMediaIdx + dir + totalMedia) % totalMedia;
  const m = allMedia[currentMediaIdx];
  const thumbs = document.querySelectorAll('.thumb,.video-thumb');
  thumbs.forEach(t => t.classList.toggle('active', parseInt(t.dataset.idx) === currentMediaIdx));
  showMedia(m.type, m.src);
}

// ── VARIANTES ────────────────────────────────────────────────
function toggleVariante(btn) {
  const wasActive = btn.classList.contains('active');
  document.querySelectorAll('.var-btn').forEach(b => b.classList.remove('active'));
  if (!wasActive) {
    btn.classList.add('active');
    selectedVariante = btn.textContent;
  } else {
    selectedVariante = null;
  }
}

// ── PROMOCIONES ──────────────────────────────────────────────
function selectPromo(pill, precio, qty) {
  const wasActive = pill.classList.contains('active');
  document.querySelectorAll('.promo-pill').forEach(p => p.classList.remove('active'));
  if (!wasActive) {
    pill.classList.add('active');
    selectedPromoPrice = precio;
    selectedPromoQty   = qty;
    document.getElementById('precio-display').textContent = 'S/. ' + precio.toFixed(2);
    document.getElementById('qty').value = qty;
  } else {
    selectedPromoPrice = null;
    selectedPromoQty   = 0;
    document.getElementById('precio-display').textContent = 'S/. ' + PROD.precio.toFixed(2);
  }
}

// ── CANTIDAD ─────────────────────────────────────────────────
function changeQty(d) {
  const inp = document.getElementById('qty');
  let v = parseInt(inp.value) + d;
  v = Math.max(1, Math.min(PROD.stock, v));
  inp.value = v;
}

// ── CART ─────────────────────────────────────────────────────
function saveCart() { localStorage.setItem('cart_' + SLUG, JSON.stringify(cart)); }

function addToCart() {
  const qty    = parseInt(document.getElementById('qty').value) || 1;
  const precio = selectedPromoPrice ?? PROD.precio;
  const key    = PROD.id + '|' + (selectedVariante || '');
  const exist  = cart.find(x => x.key === key);
  if (exist) exist.qty += qty;
  else cart.push({ key, id: PROD.id, nombre: PROD.nombre, imagen: PROD.imagen, precio, variante: selectedVariante, qty });
  saveCart();
  updateCartUI();
  openDrawer();
  showToast('✅ Agregado al carrito');
}

function updateCartUI() {
  const count = cart.reduce((a,c) => a+c.qty, 0);
  document.getElementById('cart-count').textContent = count;
  const total = cart.reduce((a,c) => a + c.precio*c.qty, 0);
  document.getElementById('cart-total-val').textContent = 'S/. ' + total.toFixed(2);
  const el = document.getElementById('drawer-items');
  if (!cart.length) {
    el.innerHTML = '<div class="cart-empty-msg">Tu carrito está vacío</div>';
    return;
  }
  el.innerHTML = cart.map((item,i) => `
    <div class="cart-item">
      <img src="${esc(item.imagen)}" class="cart-item-img" alt="">
      <div class="cart-item-info">
        <div class="cart-item-name">${esc(item.nombre)}</div>
        ${item.variante?`<div class="cart-item-var">${esc(item.variante)}</div>`:''}
        <div class="cart-item-price">S/. ${(item.precio*item.qty).toFixed(2)} × ${item.qty}</div>
      </div>
      <button class="cart-item-remove" onclick="removeItem(${i})">✕</button>
    </div>
  `).join('');
}

function removeItem(i) { cart.splice(i,1); saveCart(); updateCartUI(); }
function openDrawer() {
  updateCartUI();
  document.getElementById('drawer').classList.add('open');
  document.getElementById('drawer-overlay').classList.add('open');
}
function closeDrawer() {
  document.getElementById('drawer').classList.remove('open');
  document.getElementById('drawer-overlay').classList.remove('open');
}

function checkout() {
  if (!cart.length) return showToast('Tu carrito está vacío');
  if (METODO_VENTAS === 'whatsapp') {
    openCartDrawerWsp();
  } else {
    closeDrawer();
    document.getElementById('modal-pedido').classList.add('open');
  }
}

function openCartDrawerWsp() {
  if (!cart.length) { addToCart(); return; }
  let msg = `*Pedido en ${<?= json_encode($tiendaNombre) ?>}*\n\n`;
  cart.forEach(c => { msg += `• ${c.nombre}${c.variante?' ('+c.variante+')':''} x${c.qty} — S/. ${(c.precio*c.qty).toFixed(2)}\n`; });
  const total = cart.reduce((a,c)=>a+c.precio*c.qty,0);
  if (DELIVERY_TIPO==='costo_fijo') msg += `\nEnvío: S/. ${DELIVERY_PRECIO.toFixed(2)}`;
  msg += `\n*Total: S/. ${(total + (DELIVERY_TIPO==='costo_fijo'?DELIVERY_PRECIO:0)).toFixed(2)}*`;
  const num = (WHATSAPP||'').replace(/\D/g,'');
  window.open('https://wa.me/'+num+'?text='+encodeURIComponent(msg),'_blank');
}

function closeModal() { document.getElementById('modal-pedido').classList.remove('open'); }

async function confirmarPedido() {
  const nombre = document.getElementById('ped-nombre').value.trim();
  const tel    = document.getElementById('ped-tel').value.trim();
  if (!nombre || !tel) return showToast('Nombre y teléfono son requeridos');
  const btn = document.getElementById('btn-confirmar');
  btn.disabled = true; btn.textContent = 'Enviando...';
  const total = cart.reduce((a,c)=>a+c.precio*c.qty,0) + (DELIVERY_TIPO==='costo_fijo'?DELIVERY_PRECIO:0);
  const fd = new FormData();
  fd.append('slug', SLUG);
  fd.append('nombre', nombre);
  fd.append('telefono', tel);
  fd.append('direccion', document.getElementById('ped-dir').value.trim());
  fd.append('notas', document.getElementById('ped-notas').value.trim());
  fd.append('items', JSON.stringify(cart.map(c=>({nombre:c.nombre,qty:c.qty,precio:c.precio}))));
  fd.append('total', total.toFixed(2));
  try {
    const r = await fetch('/api/pedidos?accion=crear', {method:'POST',body:fd});
    const d = await r.json();
    if (d.ok) {
      cart = []; saveCart(); updateCartUI();
      closeModal();
      showToast('🎉 ¡Pedido enviado! Te contactaremos pronto.');
    } else showToast('Error: ' + d.error);
  } catch(e) { showToast('Error de red'); }
  btn.disabled = false; btn.textContent = 'Confirmar pedido';
}

// ── RESEÑAS ──────────────────────────────────────────────────
// Star input CSS hack
const starLabels = document.querySelectorAll('#star-input label');
function updateStars() {
  const checked = document.querySelector('#star-input input:checked');
  const val = checked ? parseInt(checked.value) : 0;
  starLabels.forEach(l => {
    const v = parseInt(l.getAttribute('for').replace('s',''));
    l.classList.toggle('on', v >= val); // reversed because CSS displays RTL
  });
  // Actually just use direct coloring
  document.querySelectorAll('#star-input input').forEach(inp => {
    inp.addEventListener('change', () => {
      const v = parseInt(inp.value);
      starLabels.forEach(l => {
        const lv = parseInt(l.getAttribute('for').replace('s',''));
        l.style.color = lv <= v ? '#f59e0b' : '#ddd';
      });
    });
  });
}
// init star colors on page load
document.addEventListener('DOMContentLoaded', () => {
  // Default all gray
  starLabels.forEach(l => l.style.color = '#ddd');
});

async function enviarResena() {
  const nombre     = document.getElementById('res-nombre').value.trim();
  const comentario = document.getElementById('res-comentario').value.trim();
  const checked    = document.querySelector('#star-input input:checked');
  const estrellas  = checked ? parseInt(checked.value) : 0;
  if (!nombre || !comentario) return showToast('Nombre y comentario son requeridos');
  if (!estrellas) return showToast('Selecciona una puntuación');
  const btn = document.getElementById('btn-resena');
  btn.disabled = true; btn.textContent = 'Enviando...';
  const fd = new FormData();
  fd.append('slug', SLUG);
  fd.append('producto_id', PROD_ID);
  fd.append('nombre', nombre);
  fd.append('pais', document.getElementById('res-pais').value.trim());
  fd.append('estrellas', estrellas);
  fd.append('comentario', comentario);
  try {
    const r = await fetch('/api/resenas?accion=crear', {method:'POST',body:fd});
    const d = await r.json();
    if (d.ok) {
      document.getElementById('resena-ok').style.display = 'block';
      document.getElementById('res-nombre').value = '';
      document.getElementById('res-pais').value = '';
      document.getElementById('res-comentario').value = '';
      document.querySelectorAll('#star-input input').forEach(i=>i.checked=false);
      starLabels.forEach(l=>l.style.color='#ddd');
    } else showToast('Error: ' + d.error);
  } catch(e) { showToast('Error de red'); }
  btn.disabled = false; btn.textContent = 'Enviar reseña';
}

// ── UTILS ────────────────────────────────────────────────────
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg; t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

// Back to top
window.addEventListener('scroll', () => {
  document.getElementById('back-top').classList.toggle('show', window.scrollY > 400);
});

// Init cart
updateCartUI();
;(function(){var wrap=document.querySelector(".gallery-main"),lens=document.getElementById("zoom-lens"),result=document.getElementById("zoom-result"),zi=document.getElementById("zoom-img"),on=false;function init(){var img=document.getElementById("main-img");if(!img||img.style.display==="none")return;zi.src=img.src;}wrap.addEventListener("mouseenter",function(){var img=document.getElementById("main-img");if(!img||img.style.display==="none")return;init();on=true;lens.style.display="block";result.style.display="block";});wrap.addEventListener("mouseleave",function(){on=false;lens.style.display="none";result.style.display="none";});wrap.addEventListener("mousemove",function(e){if(!on)return;var img=document.getElementById("main-img");if(!img||img.style.display==="none")return;var b=img.getBoundingClientRect(),rx=result.offsetWidth/lens.offsetWidth,ry=result.offsetHeight/lens.offsetHeight,lx=Math.max(0,Math.min(e.clientX-b.left-lens.offsetWidth/2,b.width-lens.offsetWidth)),ly=Math.max(0,Math.min(e.clientY-b.top-lens.offsetHeight/2,b.height-lens.offsetHeight));lens.style.left=lx+"px";lens.style.top=ly+"px";zi.style.left=-(lx*rx)+"px";zi.style.top=-(ly*ry)+"px";zi.style.width=(b.width*rx)+"px";zi.style.height=(b.height*ry)+"px";});var orig=window.showMedia;if(orig)window.showMedia=function(t,s){orig(t,s);if(t!=="vid")setTimeout(init,60);else{lens.style.display="none";result.style.display="none";on=false;}};})();</script>
</body>
</html>
