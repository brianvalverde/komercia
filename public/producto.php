<?php
// ──────────────────────────────────────────────────────────
// producto.php — Página de detalle de producto (SEO-friendly)
// URL: /tienda/{slug}/producto/{id}
// ──────────────────────────────────────────────────────────
require_once '/var/www/komercia/config/firebase.php';

$slug   = $_GET['slug']  ?? '';
$prodId = $_GET['id']    ?? '';

if (!$slug || !$prodId) { http_response_code(404); die('Página no encontrada'); }

// ── Tienda ──────────────────────────────────────────────────
$tiendaDoc = firestoreRequest('GET', "tiendas/{$slug}");
if (!$tiendaDoc || isset($tiendaDoc['error'])) { http_response_code(404); die('Tienda no encontrada'); }
$tf = $tiendaDoc['fields'] ?? [];

function fsv(array $f, string $k, string $d = ''): string {
    return (string)($f[$k]['stringValue'] ?? $f[$k]['integerValue'] ?? $f[$k]['doubleValue'] ?? $d);
}
function fsArr(array $f, string $k): array {
    $out = [];
    foreach (($f[$k]['arrayValue']['values'] ?? []) as $v)
        if (isset($v['stringValue'])) $out[] = $v['stringValue'];
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
$anuncio        = fsv($tf, 'anuncio');
$envios         = (bool)($tf['envios']['booleanValue'] ?? false);
$verificada     = (bool)($tf['verificada']['booleanValue'] ?? false);

if (!$uid) { http_response_code(404); die('Tienda no encontrada'); }

// RGB para glow
$hex = ltrim($colorPrimario, '#');
$r = hexdec(substr($hex,0,2));
$g = hexdec(substr($hex,2,2));
$b = hexdec(substr($hex,4,2));

// ── Producto ────────────────────────────────────────────────
$prodDoc = firestoreRequest('GET', "comerciantes/{$uid}/productos/{$prodId}");
if (!$prodDoc || isset($prodDoc['error'])) { http_response_code(404); die('Producto no encontrado'); }
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

$pVariantes = [];
foreach (($pf['variantes']['arrayValue']['values'] ?? []) as $v) {
    $mf = $v['mapValue']['fields'] ?? [];
    if (!empty($mf['nombre']['stringValue'])) $pVariantes[] = fsv($mf, 'nombre');
}
$pPromociones = [];
foreach (($pf['promociones']['arrayValue']['values'] ?? []) as $v) {
    $mf = $v['mapValue']['fields'] ?? [];
    if (!empty($mf['cantidad']['integerValue']) && !empty($mf['precio']['doubleValue']))
        $pPromociones[] = ['cantidad'=>(int)$mf['cantidad']['integerValue'],'precio'=>(float)$mf['precio']['doubleValue']];
}

// ── Reseñas ─────────────────────────────────────────────────
$resenasDoc = firestoreRequest('GET', "comerciantes/{$uid}/productos/{$prodId}/resenas");
$resenasArr = [];
foreach (($resenasDoc['documents'] ?? []) as $doc) {
    $rf = $doc['fields'] ?? [];
    if (!($rf['aprobada']['booleanValue'] ?? false)) continue;
    $resenasArr[] = [
        'nombre'     => fsv($rf,'nombre','Anónimo'),
        'pais'       => fsv($rf,'pais'),
        'estrellas'  => (int)($rf['estrellas']['integerValue'] ?? 5),
        'comentario' => fsv($rf,'comentario'),
        'fecha'      => fsv($rf,'fecha'),
    ];
}
usort($resenasArr, fn($a,$b) => strcmp($b['fecha'],$a['fecha']));
$avgEstrellas = count($resenasArr) ? array_sum(array_column($resenasArr,'estrellas'))/count($resenasArr) : 0;

// ── Relacionados ────────────────────────────────────────────
$todosProds = firestoreRequest('GET', "comerciantes/{$uid}/productos");
$relacionados = [];
foreach (($todosProds['documents'] ?? []) as $doc) {
    $rf  = $doc['fields'] ?? [];
    $rid = basename($doc['name']);
    if ($rid === $prodId || !($rf['activo']['booleanValue'] ?? true)) continue;
    if ($pCategoria && fsv($rf,'categoria') !== $pCategoria) continue;
    $rImgs = [];
    foreach (($rf['imagenes']['arrayValue']['values'] ?? []) as $v)
        if (isset($v['stringValue'])) $rImgs[] = $v['stringValue'];
    $relacionados[] = ['id'=>$rid,'nombre'=>fsv($rf,'nombre','Producto'),'precio'=>(float)($rf['precio']['doubleValue']??$rf['precio']['integerValue']??0),'imagen'=>$rImgs[0]??fsv($rf,'imagen')];
    if (count($relacionados)>=8) break;
}

// ── SEO ─────────────────────────────────────────────────────
$metaTitle    = htmlspecialchars("{$pNombre} - {$tiendaNombre}");
$metaDesc     = htmlspecialchars($pDescripcion ?: "{$pNombre} disponible en {$tiendaNombre}. Precio: S/. ".number_format($pPrecio,2));
$canonicalUrl = "https://{$_SERVER['HTTP_HOST']}/tienda/{$slug}/producto/{$prodId}";
$tiendaUrl    = "https://{$_SERVER['HTTP_HOST']}/tienda/{$slug}";

// Categorías de tienda para la nav
$catsRaw = fsArr($tf,'categorias');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= $metaTitle ?></title>
<meta name="description" content="<?= $metaDesc ?>">
<link rel="canonical" href="<?= $canonicalUrl ?>">
<meta property="og:title"       content="<?= $metaTitle ?>">
<meta property="og:description" content="<?= $metaDesc ?>">
<meta property="og:image"       content="<?= htmlspecialchars($pImagenPrinc) ?>">
<meta property="og:url"         content="<?= $canonicalUrl ?>">
<meta property="og:type"        content="product">
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="<?= $metaTitle ?>">
<meta name="twitter:description" content="<?= $metaDesc ?>">
<meta name="twitter:image"       content="<?= htmlspecialchars($pImagenPrinc) ?>">
<script type="application/ld+json">
{
  "@context":"https://schema.org/","@type":"Product",
  "name":<?= json_encode($pNombre) ?>,"description":<?= json_encode($pDescripcion) ?>,
  "image":<?= json_encode($pImagenPrinc) ?>,
  "offers":{"@type":"Offer","priceCurrency":"PEN","price":<?= json_encode($pPrecio) ?>,"availability":"<?= $pStock>0?'https://schema.org/InStock':'https://schema.org/OutOfStock' ?>"}
  <?php if(count($resenasArr)>0): ?>,
  "aggregateRating":{"@type":"AggregateRating","ratingValue":<?= round($avgEstrellas,1) ?>,"reviewCount":<?= count($resenasArr) ?>}
  <?php endif; ?>
}
</script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{
  --c:<?= $colorPrimario ?>;
  --r:<?= $r ?>;--g2:<?= $g ?>;--b2:<?= $b ?>;
  --cd:color-mix(in srgb,<?= $colorPrimario ?> 80%,#000);
  --cl:color-mix(in srgb,<?= $colorPrimario ?> 20%,#fff);
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;background:#0d0d0d;color:#e5e5e5;min-height:100vh;-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}
img{max-width:100%}

/* ── ANNOUNCE BAR ── */
.announce-bar{background:var(--c);color:#fff;text-align:center;padding:7px 16px;font-size:12px;font-weight:600;letter-spacing:.3px;display:flex;align-items:center;justify-content:center;gap:16px;flex-wrap:wrap}
.ab-item{display:flex;align-items:center;gap:5px;opacity:.95}

/* ── TOPBAR ── */
.topbar{background:rgba(13,13,13,.92);backdrop-filter:blur(16px);border-bottom:1px solid rgba(255,255,255,.07);padding:0 24px;height:62px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:200;gap:16px}
.tb-left{display:flex;align-items:center;gap:12px;flex-shrink:0}
.tb-logo{height:38px;width:38px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.1)}
.tb-name{font-weight:800;font-size:15px;color:#fff}
.tb-sub{font-size:10px;color:var(--c);font-weight:700;letter-spacing:.5px;text-transform:uppercase;display:block;margin-top:-2px}
.tb-center{flex:1;display:flex;align-items:center;gap:10px;max-width:500px;margin:0 auto}
.tb-right{display:flex;align-items:center;gap:10px;flex-shrink:0}
.tb-cart-btn{display:flex;align-items:center;gap:7px;background:#1a1a1a;border:1.5px solid rgba(255,255,255,.1);border-radius:24px;padding:8px 16px;cursor:pointer;font-size:13px;font-weight:600;color:#e5e5e5;transition:.2s;position:relative}
.tb-cart-btn:hover{border-color:var(--c);color:var(--c)}
.cart-badge{position:absolute;top:-5px;right:-5px;background:var(--c);color:#fff;font-size:10px;font-weight:800;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center}
.tb-contact-btn{background:var(--c);color:#fff;border:none;border-radius:24px;padding:9px 18px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;transition:.2s}
.tb-contact-btn:hover{filter:brightness(1.1)}

/* ── CATS NAV ── */
.cats-nav{background:#111;border-bottom:1px solid rgba(255,255,255,.05);padding:0 24px;display:flex;align-items:center;gap:6px;overflow-x:auto;height:44px;scrollbar-width:none}
.cats-nav::-webkit-scrollbar{display:none}
.cat-chip{padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;color:#aaa;cursor:pointer;white-space:nowrap;transition:.2s;border:1.5px solid transparent;flex-shrink:0}
.cat-chip:hover{color:#fff;border-color:rgba(255,255,255,.15)}

/* ── BREADCRUMB ── */
.breadcrumb{padding:14px 24px;font-size:12px;color:#666;display:flex;align-items:center;gap:6px;flex-wrap:wrap;max-width:1200px;margin:0 auto}
.breadcrumb a{color:#666;transition:.2s}
.breadcrumb a:hover{color:var(--c)}
.breadcrumb .sep{color:#333}
.breadcrumb .cur{color:#aaa}

/* ── CONTAINER ── */
.container{max-width:1200px;margin:0 auto;padding:0 24px 60px}

/* ── PRODUCT GRID ── */
.prod-grid{display:grid;grid-template-columns:80px 1fr 420px;gap:24px;margin-bottom:40px}

/* ── GALLERY THUMBS ── */
.gallery-thumbs{display:flex;flex-direction:column;gap:8px;padding:4px 0}
.gthumb{width:72px;height:72px;border-radius:10px;object-fit:cover;border:2px solid #222;cursor:pointer;transition:.2s;background:#1a1a1a;flex-shrink:0}
.gthumb:hover,.gthumb.active{border-color:var(--c);box-shadow:0 0 0 2px rgba(var(--r),var(--g2),var(--b2),.25)}
.gthumb-vid{width:72px;height:72px;border-radius:10px;background:#1a1a1a;border:2px solid #222;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:20px;color:#aaa;transition:.2s;flex-shrink:0}
.gthumb-vid:hover,.gthumb-vid.active{border-color:var(--c);color:var(--c)}

/* ── GALLERY MAIN ── */
.gallery-main{position:relative;border-radius:18px;overflow:hidden;background:#111;border:1px solid #1e1e1e;aspect-ratio:4/3}
.main-img{width:100%;height:100%;object-fit:contain;display:block;background:#111;cursor:crosshair;transition:transform .1s ease;transform-origin:50% 50%;will-change:transform}
.main-video{width:100%;height:100%;display:none;background:#000}
.g-arrows{position:absolute;top:50%;transform:translateY(-50%);width:100%;display:flex;justify-content:space-between;pointer-events:none;padding:0 12px}
.g-arrow{width:36px;height:36px;background:rgba(0,0,0,.6);border:1px solid rgba(255,255,255,.12);border-radius:50%;cursor:pointer;font-size:16px;pointer-events:all;transition:.2s;display:flex;align-items:center;justify-content:center;color:#fff}
.g-arrow:hover{background:var(--c);border-color:var(--c)}

/* ── PRODUCT INFO ── */
.prod-info{display:flex;flex-direction:column;gap:0}
.prod-cat{font-size:11px;color:var(--c);font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-bottom:8px}
.prod-nombre{font-size:24px;font-weight:900;line-height:1.2;color:#fff;margin-bottom:10px}
.rating-row{display:flex;align-items:center;gap:8px;margin-bottom:14px}
.stars-row{color:#f59e0b;font-size:15px;letter-spacing:1px}
.rating-count{font-size:12px;color:#666}
.prod-precio{font-size:36px;font-weight:900;color:var(--c);margin-bottom:16px;font-variant-numeric:tabular-nums}
.stock-ok{display:inline-flex;align-items:center;gap:5px;background:rgba(34,197,94,.1);color:#4ade80;font-size:12px;font-weight:700;padding:5px 12px;border-radius:99px;margin-bottom:16px;border:1px solid rgba(34,197,94,.2)}
.stock-low{display:inline-flex;align-items:center;gap:5px;background:rgba(251,191,36,.08);color:#fbbf24;font-size:12px;font-weight:700;padding:5px 12px;border-radius:99px;margin-bottom:16px;border:1px solid rgba(251,191,36,.2)}
.stock-out{display:inline-flex;align-items:center;gap:5px;background:rgba(239,68,68,.08);color:#f87171;font-size:12px;font-weight:700;padding:5px 12px;border-radius:99px;margin-bottom:16px;border:1px solid rgba(239,68,68,.2)}

/* Sección label */
.sec-label{font-size:12px;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px}

/* Promociones */
.promo-wrap{margin-bottom:18px}
.promo-pills{display:flex;gap:8px;flex-wrap:wrap}
.promo-pill{padding:7px 14px;border-radius:10px;border:1.5px solid #2a2a2a;font-size:13px;cursor:pointer;transition:.2s;font-weight:600;background:#161616;color:#aaa}
.promo-pill:hover{border-color:var(--c);color:var(--c)}
.promo-pill.active{border-color:var(--c);background:rgba(var(--r),var(--g2),var(--b2),.12);color:var(--c)}

/* Variantes */
.var-wrap{margin-bottom:18px}
.var-pills{display:flex;gap:8px;flex-wrap:wrap}
.var-btn{padding:8px 16px;border:1.5px solid #2a2a2a;border-radius:10px;cursor:pointer;font-size:13px;font-weight:600;background:#161616;color:#ccc;transition:.2s}
.var-btn:hover{border-color:var(--c);color:var(--c)}
.var-btn.active{border-color:var(--c);background:rgba(var(--r),var(--g2),var(--b2),.12);color:var(--c)}

/* Cantidad */
.qty-wrap{display:flex;align-items:center;gap:14px;margin-bottom:20px}
.qty-ctrl{display:flex;align-items:center;background:#161616;border:1.5px solid #2a2a2a;border-radius:10px;overflow:hidden}
.qty-btn{width:38px;height:38px;border:none;background:transparent;color:#ccc;font-size:20px;cursor:pointer;transition:.15s;display:flex;align-items:center;justify-content:center}
.qty-btn:hover{background:#222;color:#fff}
.qty-val{width:44px;text-align:center;font-weight:700;font-size:15px;border:none;outline:none;background:transparent;color:#fff}

/* CTA */
.cta-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px}
.btn-add{background:var(--c);color:#fff;border:none;border-radius:12px;padding:13px 10px;font-size:14px;font-weight:700;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:6px}
.btn-add:hover{filter:brightness(1.1)}
.btn-wsp{background:#25d366;color:#fff;border:none;border-radius:12px;padding:13px 10px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:.2s}
.btn-wsp:hover{background:#1db954}
.btn-pedido{grid-column:1/-1;background:#1a1a1a;color:#ccc;border:1.5px solid #2a2a2a;border-radius:12px;padding:12px;font-size:13px;font-weight:600;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:6px}
.btn-pedido:hover{border-color:var(--c);color:var(--c)}

/* Descripción */
.desc-box{background:#111;border:1px solid #1e1e1e;border-radius:14px;padding:16px 18px}
.desc-text{font-size:14px;color:#aaa;line-height:1.7}

/* Delivery badge */
.delivery-badge{display:inline-flex;align-items:center;gap:6px;background:#111;border:1px solid #1e1e1e;border-radius:99px;padding:5px 12px;font-size:12px;color:#888;margin-bottom:16px}

/* ── REVIEWS ── */
.section{margin-bottom:36px}
.section-head{font-size:20px;font-weight:800;color:#fff;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.reviews-box{background:#111;border:1px solid #1a1a1a;border-radius:18px;overflow:hidden}
.reviews-summary{display:flex;align-items:center;gap:24px;padding:24px;border-bottom:1px solid #1a1a1a}
.avg-big{font-size:56px;font-weight:900;color:var(--c);line-height:1}
.avg-stars{font-size:26px;color:#f59e0b;letter-spacing:2px}
.avg-label{font-size:12px;color:#666;margin-top:4px}
.review-card{padding:18px 24px;border-bottom:1px solid #161616}
.review-card:last-of-type{border-bottom:none}
.rev-header{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.rev-avatar{width:36px;height:36px;border-radius:50%;background:var(--c);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;flex-shrink:0}
.rev-name{font-weight:700;font-size:14px;color:#e5e5e5}
.rev-pais{font-size:11px;color:#555}
.rev-stars{color:#f59e0b;font-size:13px}
.rev-fecha{font-size:11px;color:#444;margin-left:auto}
.rev-text{font-size:14px;color:#888;line-height:1.6}
.no-reviews{text-align:center;padding:40px;color:#444;font-size:14px}

/* Form reseña */
.form-review{padding:24px;border-top:1px solid #1a1a1a}
.form-review h4{font-size:15px;font-weight:700;color:#e5e5e5;margin-bottom:16px}
.form-row2{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px}
.fc{width:100%;padding:10px 14px;background:#161616;border:1.5px solid #2a2a2a;border-radius:10px;font-size:14px;font-family:inherit;outline:none;transition:.2s;color:#e5e5e5}
.fc:focus{border-color:var(--c)}
.fc::placeholder{color:#444}
textarea.fc{resize:vertical;min-height:80px}
.star-picker{display:flex;gap:6px;margin-bottom:14px}
.sp-star{font-size:28px;cursor:pointer;color:#2a2a2a;transition:color .15s,transform .1s;user-select:none;line-height:1}
.sp-star:hover,.sp-star.lit{color:#f59e0b}
.sp-star:active{transform:scale(.85)}
.star-label{font-size:12px;color:#555;margin-bottom:8px}
.btn-submit{background:var(--c);color:#fff;border:none;border-radius:10px;padding:11px 24px;font-size:14px;font-weight:700;cursor:pointer;transition:.2s}
.btn-submit:hover{filter:brightness(1.1)}
.btn-submit:disabled{opacity:.5;cursor:not-allowed}
.review-sent{display:none;padding:12px 16px;background:rgba(34,197,94,.1);color:#4ade80;border-radius:10px;font-size:14px;margin-top:10px;border:1px solid rgba(34,197,94,.2)}

/* ── RELACIONADOS ── */
.rel-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px}
.rel-card{border-radius:14px;overflow:hidden;border:1px solid #1a1a1a;background:#111;transition:.25s;cursor:pointer}
.rel-card:hover{border-color:rgba(var(--r),var(--g2),var(--b2),.4);transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.4)}
.rel-img{width:100%;aspect-ratio:1;object-fit:cover;background:#161616;display:block}
.rel-info{padding:10px 12px}
.rel-nombre{font-size:13px;font-weight:600;line-height:1.3;margin-bottom:4px;color:#ccc;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.rel-precio{font-size:14px;font-weight:800;color:var(--c)}

/* ── CART DRAWER ── */
.drawer-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:300;display:none;opacity:0;transition:.3s;backdrop-filter:blur(4px)}
.drawer-overlay.open{display:block;opacity:1}
.drawer{position:fixed;right:0;top:0;height:100%;width:min(400px,100vw);background:#111;border-left:1px solid #1e1e1e;z-index:301;transform:translateX(100%);transition:.3s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column}
.drawer.open{transform:translateX(0)}
.drawer-head{padding:18px 20px;border-bottom:1px solid #1e1e1e;display:flex;align-items:center;justify-content:space-between}
.drawer-head h3{font-size:17px;font-weight:700;color:#fff}
.drawer-close{background:#1a1a1a;border:none;color:#aaa;width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;transition:.2s}
.drawer-close:hover{color:#fff;background:#222}
.drawer-items{flex:1;overflow-y:auto;padding:14px 16px;display:flex;flex-direction:column;gap:10px;scrollbar-width:thin;scrollbar-color:var(--c) #1a1a1a}
.cart-item{display:flex;gap:12px;align-items:center;background:#161616;border:1px solid #1e1e1e;border-radius:12px;padding:10px}
.ci-img{width:54px;height:54px;border-radius:8px;object-fit:cover;background:#111;flex-shrink:0}
.ci-info{flex:1;min-width:0}
.ci-name{font-size:13px;font-weight:600;color:#e5e5e5;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ci-var{font-size:11px;color:#555}
.ci-price{font-size:13px;font-weight:800;color:var(--c)}
.ci-del{background:none;border:none;color:#444;cursor:pointer;padding:4px 6px;border-radius:6px;transition:.2s;font-size:16px}
.ci-del:hover{color:#ef4444;background:rgba(239,68,68,.1)}
.drawer-footer{padding:16px 20px;border-top:1px solid #1e1e1e}
.cart-total-row{display:flex;justify-content:space-between;font-size:15px;font-weight:700;color:#e5e5e5;margin-bottom:14px}
.cart-total-row span:last-child{color:var(--c)}
.btn-checkout{width:100%;background:var(--c);color:#fff;border:none;border-radius:12px;padding:14px;font-size:15px;font-weight:700;cursor:pointer;transition:.2s}
.btn-checkout:hover{filter:brightness(1.1)}
.cart-empty{text-align:center;color:#444;padding:40px 0;font-size:14px}

/* ── MODAL PEDIDO ── */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:400;display:none;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(6px)}
.modal-overlay.open{display:flex}
.modal-box{background:#111;border:1px solid #1e1e1e;border-radius:20px;width:100%;max-width:440px;max-height:90vh;overflow-y:auto}
.modal-head2{padding:20px;border-bottom:1px solid #1e1e1e;display:flex;align-items:center;justify-content:space-between}
.modal-head2 h3{font-size:17px;font-weight:700;color:#fff}
.modal-x{background:#1a1a1a;border:none;color:#aaa;width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;transition:.2s}
.modal-x:hover{color:#fff}
.modal-body2{padding:20px;display:flex;flex-direction:column;gap:12px}
.ig label{font-size:13px;font-weight:600;color:#888;display:block;margin-bottom:6px}
.modal-foot2{padding:16px 20px;border-top:1px solid #1e1e1e}
.btn-confirmar{width:100%;background:var(--c);color:#fff;border:none;border-radius:12px;padding:14px;font-size:15px;font-weight:700;cursor:pointer;transition:.2s}
.btn-confirmar:hover{filter:brightness(1.1)}
.btn-confirmar:disabled{opacity:.5;cursor:not-allowed}

/* WhatsApp float */
.wa-float{position:fixed;bottom:26px;right:26px;width:54px;height:54px;background:#25d366;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 20px rgba(37,211,102,.35);z-index:250;transition:.3s}
.wa-float:hover{transform:scale(1.08);box-shadow:0 6px 28px rgba(37,211,102,.5)}

/* Toast */
#toast{position:fixed;bottom:80px;left:50%;transform:translateX(-50%) translateY(10px);background:#1e1e1e;border:1px solid #2a2a2a;color:#e5e5e5;padding:11px 22px;border-radius:12px;font-size:13px;font-weight:600;z-index:999;opacity:0;pointer-events:none;transition:.3s;white-space:nowrap}
#toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
#toast.ok{background:#14532d;border-color:#166534;color:#4ade80}

/* ── FOOTER ── */
.site-footer{background:#0a0a0a;border-top:1px solid #1a1a1a;margin-top:20px}
.sf-inner{max-width:1200px;margin:0 auto;padding:48px 24px 32px;display:grid;grid-template-columns:260px 1fr 1fr 1fr;gap:40px}
.sf-brand{}
.sf-logo-row{display:flex;align-items:center;gap:12px;margin-bottom:20px}
.sf-logo-img{width:44px;height:44px;border-radius:10px;object-fit:cover;border:1px solid #222}
.sf-store-name{font-size:15px;font-weight:800;color:#fff}
.sf-store-sub{font-size:10px;color:var(--c);font-weight:700;letter-spacing:.8px;margin-top:1px}
.sf-wsp-btn{display:inline-flex;align-items:center;gap:7px;background:#25d366;color:#fff;border-radius:24px;padding:9px 18px;font-size:13px;font-weight:700;transition:.2s;text-decoration:none}
.sf-wsp-btn:hover{background:#1db954}
.sf-col{}
.sf-col-title{font-size:11px;font-weight:700;color:#555;letter-spacing:1px;text-transform:uppercase;margin-bottom:14px}
.sf-col a,.sf-col span{display:block;font-size:13px;color:#666;margin-bottom:10px;text-decoration:none;transition:.2s;cursor:pointer}
.sf-col a:hover{color:#aaa}
.sf-col a[style*="color"]{color:var(--c) !important}
.sf-bottom{max-width:1200px;margin:0 auto;padding:16px 24px;border-top:1px solid #161616;display:flex;align-items:center;justify-content:space-between;font-size:12px;color:#444;flex-wrap:wrap;gap:8px}
.sf-bottom a{color:var(--c);text-decoration:none;font-weight:600}

/* Responsive */
@media(max-width:900px){
  .prod-grid{grid-template-columns:70px 1fr;grid-template-rows:auto auto}
  .gallery-thumbs{grid-column:1;grid-row:1;order:1}
  .gallery-main{grid-column:2;grid-row:1;aspect-ratio:4/3}
  .prod-info{grid-column:1/-1;grid-row:2;order:3}
  .sf-inner{grid-template-columns:1fr 1fr;gap:28px}
  .sf-brand{grid-column:1/-1}
}
@media(max-width:640px){
  /* Full-width single column: thumbs strip → main image → info */
  .prod-grid{
    grid-template-columns:1fr;
    grid-template-rows:auto auto auto;
    gap:0;
  }
  .gallery-thumbs{
    order:1;
    grid-column:1;grid-row:1;
    flex-direction:row;
    overflow-x:auto;
    padding:10px 0 10px;
    gap:8px;
    margin:0 -14px;
    padding-left:14px;
    padding-right:14px;
    /* hide scrollbar but keep functionality */
    scrollbar-width:none;
  }
  .gallery-thumbs::-webkit-scrollbar{display:none}
  .gthumb,.gthumb-vid{
    width:68px;height:68px;
    flex-shrink:0;
    border-radius:10px;
  }
  .gallery-main{
    order:2;
    grid-column:1;grid-row:2;
    border-radius:0;
    border-left:none;border-right:none;
    margin:0 -14px;
    aspect-ratio:1/1;
  }
  .prod-info{
    order:3;
    grid-column:1;grid-row:3;
    padding-top:20px;
  }
  .container{padding:0 14px 48px}
  .cta-row{grid-template-columns:1fr}
  .form-row2{grid-template-columns:1fr}
  .topbar{padding:0 14px}
  .sf-inner{grid-template-columns:1fr;padding:28px 16px}
  .sf-bottom{flex-direction:column;text-align:center;padding:14px 16px}
}
</style>
</head>
<body>

<?php if($anuncio||$envios||$verificada): ?>
<div class="announce-bar">
  <?php if($envios): ?>
  <div class="ab-item">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
    Envíos disponibles
  </div>
  <?php endif; ?>
  <?php if($verificada): ?>
  <div class="ab-item">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
    Productos verificados
  </div>
  <?php endif; ?>
  <?php if($anuncio): ?>
  <div class="ab-item"><?= htmlspecialchars($anuncio) ?></div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- TOPBAR -->
<div class="topbar">
  <div class="tb-left">
    <?php if($tiendaLogo): ?>
    <img src="<?= htmlspecialchars($tiendaLogo) ?>" class="tb-logo" alt="Logo">
    <?php endif; ?>
    <a href="<?= $tiendaUrl ?>" style="display:flex;flex-direction:column">
      <span class="tb-name"><?= htmlspecialchars($tiendaNombre) ?></span>
      <span class="tb-sub">TIENDA ONLINE</span>
    </a>
  </div>

  <div class="tb-right">
    <button class="tb-cart-btn" onclick="openDrawer()">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      Carrito
      <span class="cart-badge" id="cart-count">0</span>
    </button>
    <?php if($whatsapp): ?>
    <button class="tb-contact-btn" onclick="irWsp()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.127.557 4.126 1.533 5.862L.054 23.447a.5.5 0 0 0 .631.631l5.801-1.57A11.939 11.939 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
      Contactar
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- CATS NAV -->
<?php if(!empty($catsRaw)): ?>
<div class="cats-nav">
  <a href="<?= $tiendaUrl ?>" class="cat-chip">Todos los productos</a>
  <?php foreach($catsRaw as $cat): ?>
  <a href="<?= $tiendaUrl ?>?cat=<?= urlencode($cat) ?>" class="cat-chip"><?= htmlspecialchars($cat) ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- BREADCRUMB -->
<div class="breadcrumb">
  <a href="<?= $tiendaUrl ?>">🏠 <?= htmlspecialchars($tiendaNombre) ?></a>
  <span class="sep">›</span>
  <?php if($pCategoria): ?>
  <a href="<?= $tiendaUrl ?>?cat=<?= urlencode($pCategoria) ?>"><?= htmlspecialchars($pCategoria) ?></a>
  <span class="sep">›</span>
  <?php endif; ?>
  <span class="cur"><?= htmlspecialchars($pNombre) ?></span>
</div>

<div class="container">

  <!-- PRODUCT GRID -->
  <div class="prod-grid">

    <!-- Thumbnails -->
    <div class="gallery-thumbs" id="thumbs-col">
      <?php
      $mi = 0;
      foreach ($pImagenes as $i => $img): ?>
        <img src="<?= htmlspecialchars($img) ?>" class="gthumb<?= $i===0?' active':'' ?>"
             data-idx="<?= $mi++ ?>" onclick="selMedia(this,'img','<?= htmlspecialchars($img) ?>')"
             alt="Imagen <?= $i+1 ?>">
      <?php endforeach;
      foreach ($pVideos as $j => $vid): ?>
        <div class="gthumb-vid" data-idx="<?= $mi++ ?>" onclick="selMedia(this,'vid','<?= htmlspecialchars($vid) ?>')">▶</div>
      <?php endforeach; ?>
    </div>

    <!-- Imagen principal -->
    <div class="gallery-main">
      <img id="main-img" src="<?= htmlspecialchars($pImagenPrinc) ?>" class="main-img" alt="<?= htmlspecialchars($pNombre) ?>">
      <video id="main-video" class="main-video" controls></video>
      <?php if(count($pImagenes)+count($pVideos)>1): ?>
      <div class="g-arrows">
        <button class="g-arrow" onclick="navMedia(-1)">‹</button>
        <button class="g-arrow" onclick="navMedia(1)">›</button>
      </div>
      <?php endif; ?>
    </div>

    <!-- Info -->
    <div class="prod-info">
      <?php if($pCategoria): ?><div class="prod-cat"><?= htmlspecialchars($pCategoria) ?></div><?php endif; ?>
      <div class="prod-nombre"><?= htmlspecialchars($pNombre) ?></div>

      <?php if(count($resenasArr)>0): ?>
      <div class="rating-row">
        <span class="stars-row"><?php for($s=1;$s<=5;$s++) echo $s<=round($avgEstrellas)?'★':'☆'; ?></span>
        <span class="rating-count"><?= number_format($avgEstrellas,1) ?> (<?= count($resenasArr) ?> reseña<?= count($resenasArr)!==1?'s':'' ?>)</span>
      </div>
      <?php endif; ?>

      <div class="prod-precio" id="precio-display">S/. <?= number_format($pPrecio,2) ?></div>

      <?php if($pStock<=0): ?>
        <div class="stock-out">✕ Sin stock disponible</div>
      <?php elseif($pStock<=5): ?>
        <div class="stock-low">⚡ Solo quedan <?= $pStock ?> unidades</div>
      <?php else: ?>
        <div class="stock-ok">✓ En stock</div>
      <?php endif; ?>

      <?php if($deliveryTipo==='gratis'): ?>
      <div class="delivery-badge">🚚 Envío gratis</div>
      <?php elseif($deliveryTipo==='costo_fijo'): ?>
      <div class="delivery-badge">🚚 Envío: S/. <?= number_format((float)$deliveryPrecio,2) ?></div>
      <?php endif; ?>

      <?php if(!empty($pPromociones)): ?>
      <div class="promo-wrap">
        <div class="sec-label">🏷️ Promociones</div>
        <div class="promo-pills">
          <?php foreach($pPromociones as $pr): ?>
          <div class="promo-pill" onclick="selPromo(this,<?= $pr['precio'] ?>,<?= $pr['cantidad'] ?>)">
            <?= $pr['cantidad'] ?>+ → S/. <?= number_format($pr['precio'],2) ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if(!empty($pVariantes)): ?>
      <div class="var-wrap">
        <div class="sec-label">Variante</div>
        <div class="var-pills">
          <?php foreach($pVariantes as $v): ?>
          <button class="var-btn" onclick="togVar(this)"><?= htmlspecialchars($v) ?></button>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if($pStock>0): ?>
      <div class="qty-wrap">
        <div class="sec-label" style="margin-bottom:0">Cantidad</div>
        <div class="qty-ctrl">
          <button class="qty-btn" onclick="chQty(-1)">−</button>
          <input type="number" id="qty" class="qty-val" value="1" min="1" max="<?= $pStock ?>">
          <button class="qty-btn" onclick="chQty(1)">+</button>
        </div>
      </div>
      <div class="cta-row">
        <button class="btn-add" onclick="addToCart()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
          Agregar
        </button>
        <?php if($whatsapp): ?>
        <button class="btn-wsp" onclick="comprarWsp()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.127.557 4.126 1.533 5.862L.054 23.447a.5.5 0 0 0 .631.631l5.801-1.57A11.939 11.939 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
          WhatsApp
        </button>
        <?php endif; ?>
        <button class="btn-pedido" onclick="openDrawer()">📋 Ver mi pedido</button>
      </div>
      <?php else: ?>
      <div class="stock-out" style="font-size:15px;padding:12px 18px;width:100%">Sin stock disponible</div>
      <?php endif; ?>

      <?php if($pDescripcion): ?>
      <div class="sec-label" style="margin-top:8px">Descripción</div>
      <div class="desc-box">
        <div class="desc-text"><?= nl2br(htmlspecialchars($pDescripcion)) ?></div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- RESEÑAS -->
  <div class="section">
    <div class="section-head">⭐ Reseñas del producto</div>
    <div class="reviews-box">
      <?php if(count($resenasArr)>0): ?>
      <div class="reviews-summary">
        <div class="avg-big"><?= number_format($avgEstrellas,1) ?></div>
        <div>
          <div class="avg-stars"><?php for($s=1;$s<=5;$s++) echo $s<=round($avgEstrellas)?'★':'☆'; ?></div>
          <div class="avg-label"><?= count($resenasArr) ?> reseña<?= count($resenasArr)!==1?'s':'' ?></div>
        </div>
      </div>
      <?php foreach($resenasArr as $rv): ?>
      <div class="review-card">
        <div class="rev-header">
          <div class="rev-avatar"><?= mb_strtoupper(mb_substr($rv['nombre'],0,1)) ?></div>
          <div>
            <div class="rev-name"><?= htmlspecialchars($rv['nombre']) ?><?= $rv['pais']?' <span style="font-size:11px;color:#555">— '.htmlspecialchars($rv['pais']).'</span>':'' ?></div>
            <div class="rev-stars"><?php for($s=1;$s<=5;$s++) echo $s<=$rv['estrellas']?'★':'☆'; ?></div>
          </div>
          <div class="rev-fecha"><?= $rv['fecha']?date('d M Y',strtotime($rv['fecha'])):'' ?></div>
        </div>
        <div class="rev-text"><?= htmlspecialchars($rv['comentario']) ?></div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <div class="no-reviews">Sin reseñas aún. ¡Sé el primero en opinar!</div>
      <?php endif; ?>

      <div class="form-review">
        <h4>Deja tu reseña</h4>
        <div class="star-label">Puntuación *</div>
        <div class="star-picker" id="star-picker">
          <span class="sp-star" data-v="1">★</span>
          <span class="sp-star" data-v="2">★</span>
          <span class="sp-star" data-v="3">★</span>
          <span class="sp-star" data-v="4">★</span>
          <span class="sp-star" data-v="5">★</span>
        </div>
        <input type="hidden" id="star-val" value="0">
        <div class="form-row2">
          <input class="fc" id="res-nombre" placeholder="Tu nombre *">
          <input class="fc" id="res-pais" placeholder="País (opcional)">
        </div>
        <textarea class="fc" id="res-comentario" placeholder="Tu comentario *" rows="3" style="width:100%;margin-bottom:12px"></textarea>
        <button class="btn-submit" id="btn-resena" onclick="enviarResena()">Enviar reseña</button>
        <div class="review-sent" id="review-sent">✅ ¡Gracias! Tu reseña está pendiente de aprobación.</div>
      </div>
    </div>
  </div>

  <!-- RELACIONADOS -->
  <?php if(!empty($relacionados)): ?>
  <div class="section">
    <div class="section-head">🛍️ También te puede interesar</div>
    <div class="rel-grid">
      <?php foreach($relacionados as $r): ?>
      <div class="rel-card" onclick="location.href='/tienda/<?= urlencode($slug) ?>/producto/<?= urlencode($r['id']) ?>'">
        <?php if($r['imagen']): ?>
        <img src="<?= htmlspecialchars($r['imagen']) ?>" class="rel-img" alt="<?= htmlspecialchars($r['nombre']) ?>" loading="lazy">
        <?php else: ?>
        <div class="rel-img" style="display:flex;align-items:center;justify-content:center;color:#333;font-size:32px">📦</div>
        <?php endif; ?>
        <div class="rel-info">
          <div class="rel-nombre"><?= htmlspecialchars($r['nombre']) ?></div>
          <div class="rel-precio">S/. <?= number_format($r['precio'],2) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /container -->

<!-- FOOTER -->
<footer class="site-footer">
  <div class="sf-inner">
    <div class="sf-brand">
      <div class="sf-logo-row">
        <?php if($tiendaLogo): ?>
        <img src="<?= htmlspecialchars($tiendaLogo) ?>" class="sf-logo-img" alt="Logo">
        <?php endif; ?>
        <div>
          <div class="sf-store-name"><?= htmlspecialchars($tiendaNombre) ?></div>
          <div class="sf-store-sub">TIENDA ONLINE</div>
        </div>
      </div>
      <?php if($whatsapp): ?>
      <a href="https://wa.me/<?= preg_replace('/\D/','',$whatsapp) ?>" target="_blank" class="sf-wsp-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.127.557 4.126 1.533 5.862L.054 23.447a.5.5 0 0 0 .631.631l5.801-1.57A11.939 11.939 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
        WhatsApp
      </a>
      <?php endif; ?>
    </div>

    <div class="sf-col">
      <div class="sf-col-title">TIENDA</div>
      <a href="<?= $tiendaUrl ?>">Todos los productos</a>
      <?php foreach(array_slice($catsRaw,0,4) as $cat): ?>
      <a href="<?= $tiendaUrl ?>?cat=<?= urlencode($cat) ?>"><?= htmlspecialchars($cat) ?></a>
      <?php endforeach; ?>
    </div>

    <div class="sf-col">
      <div class="sf-col-title">AYUDA</div>
      <?php if($whatsapp): ?>
      <a href="https://wa.me/<?= preg_replace('/\D/','',$whatsapp) ?>" target="_blank">Contactar vendedor</a>
      <?php endif; ?>
      <span>Preguntas frecuentes</span>
      <span>Política de devoluciones</span>
    </div>

    <div class="sf-col">
      <div class="sf-col-title">PLATAFORMA</div>
      <a href="https://komercia.online" target="_blank">Komercia</a>
      <a href="https://komercia.online/registro" target="_blank">Crear mi tienda</a>
      <a href="/panel" target="_blank">Panel de vendedor</a>
    </div>
  </div>

  <div class="sf-bottom">
    <span>© <?= date('Y') ?> <?= htmlspecialchars($tiendaNombre) ?>. Todos los derechos reservados.</span>
    <span>Tienda creada con <a href="https://komercia.online" target="_blank">Komercia</a> ✏️</span>
  </div>
</footer>

<!-- WhatsApp float -->
<?php if($whatsapp): ?>
<a href="https://wa.me/<?= preg_replace('/\D/','',$whatsapp) ?>" target="_blank" class="wa-float" title="Contactar por WhatsApp">
  <svg width="28" height="28" viewBox="0 0 32 32" fill="none">
    <path fill="#fff" d="M16 1C7.716 1 1 7.716 1 16c0 2.628.672 5.1 1.848 7.258L1 31l7.98-1.824A14.937 14.937 0 0 0 16 31c8.284 0 15-6.716 15-15S24.284 1 16 1z"/>
    <path fill="#25D366" d="M16 3.5c-6.904 0-12.5 5.596-12.5 12.5 0 2.31.63 4.47 1.726 6.326L3.5 28.5l6.35-1.664A12.46 12.46 0 0 0 16 28.5c6.904 0 12.5-5.596 12.5-12.5S22.904 3.5 16 3.5z"/>
    <path fill="#fff" d="M21.98 19.44c-.3-.15-1.77-.873-2.044-.972-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.133-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.67-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.172-.008-.37-.01-.569-.01s-.52.074-.792.372c-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.71.306 1.263.489 1.694.625.712.227 1.36.195 1.872.118.57-.085 1.757-.719 2.005-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
  </svg>
</a>
<?php endif; ?>

<!-- CART DRAWER -->
<div class="drawer-overlay" id="drawer-overlay" onclick="closeDrawer()"></div>
<div class="drawer" id="drawer">
  <div class="drawer-head">
    <h3>🛒 Tu carrito</h3>
    <button class="drawer-close" onclick="closeDrawer()">✕</button>
  </div>
  <div class="drawer-items" id="drawer-items"></div>
  <div class="drawer-footer">
    <div class="cart-total-row"><span>Total</span><span id="cart-total-val">S/. 0.00</span></div>
    <button class="btn-checkout" onclick="checkout()">Finalizar pedido</button>
  </div>
</div>

<!-- MODAL PEDIDO -->
<div class="modal-overlay" id="modal-pedido">
  <div class="modal-box">
    <div class="modal-head2">
      <h3>📋 Datos del pedido</h3>
      <button class="modal-x" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body2">
      <div class="ig"><label>Nombre completo *</label><input class="fc" id="ped-nombre" placeholder="Ej: Juan Pérez"></div>
      <div class="ig"><label>Teléfono *</label><input class="fc" id="ped-tel" type="tel" placeholder="+51 999 999 999"></div>
      <div class="ig"><label>Dirección de entrega</label><input class="fc" id="ped-dir" placeholder="Calle, número, distrito"></div>
      <div class="ig"><label>Notas (opcional)</label><textarea class="fc" id="ped-notas" placeholder="Color, talla, instrucciones..."></textarea></div>
      <?php if($deliveryTipo==='costo_fijo'): ?>
      <div style="background:#161616;border:1px solid #2a2a2a;border-radius:10px;padding:12px;font-size:14px;color:#aaa">🚚 Costo de envío: <strong style="color:#e5e5e5">S/. <?= number_format((float)$deliveryPrecio,2) ?></strong></div>
      <?php elseif($deliveryTipo==='gratis'): ?>
      <div style="background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:12px;font-size:14px;color:#4ade80">✅ Envío gratis</div>
      <?php endif; ?>
    </div>
    <div class="modal-foot2">
      <button class="btn-confirmar" id="btn-confirmar" onclick="confirmarPedido()">Confirmar pedido</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
const SLUG    = <?= json_encode($slug) ?>;
const PROD_ID = <?= json_encode($prodId) ?>;
const PROD    = {
  id: PROD_ID,
  nombre: <?= json_encode($pNombre) ?>,
  precio: <?= json_encode($pPrecio) ?>,
  imagen: <?= json_encode($pImagenPrinc) ?>,
  stock:  <?= json_encode($pStock) ?>,
};
const METODO_VENTAS   = <?= json_encode($metodoVentas) ?>;
const WHATSAPP        = <?= json_encode($whatsapp) ?>;
const TIENDA_NOMBRE   = <?= json_encode($tiendaNombre) ?>;
const DELIVERY_TIPO   = <?= json_encode($deliveryTipo) ?>;
const DELIVERY_PRECIO = <?= json_encode((float)$deliveryPrecio) ?>;

let cart = [];
try { cart = JSON.parse(localStorage.getItem('cart_'+SLUG)||'[]'); } catch(e){}

let curIdx = 0;
const allMedia = [
  <?php foreach($pImagenes as $img): ?>{'type':'img','src':<?= json_encode($img) ?>},<?php endforeach; ?>
  <?php foreach($pVideos as $vid): ?>{'type':'vid','src':<?= json_encode($vid) ?>},<?php endforeach; ?>
];
let selVar = null, selPromoPrice = null, selPromoQty = 0;

// ── GALLERY ──────────────────────────────────────────────────
function selMedia(el, type, src) {
  document.querySelectorAll('.gthumb,.gthumb-vid').forEach(t=>t.classList.remove('active'));
  el.classList.add('active');
  curIdx = parseInt(el.dataset.idx);
  showMedia(type, src);
}
function showMedia(type, src) {
  const img = document.getElementById('main-img');
  const vid = document.getElementById('main-video');
  if (type==='vid') {
    img.style.display='none'; vid.style.display='block';
    vid.src=src; vid.play().catch(()=>{});
  } else {
    vid.pause(); vid.style.display='none';
    img.style.display='block'; img.src=src;
  }
}
function navMedia(d) {
  if (!allMedia.length) return;
  curIdx = (curIdx+d+allMedia.length)%allMedia.length;
  const m = allMedia[curIdx];
  document.querySelectorAll('.gthumb,.gthumb-vid').forEach(t=>t.classList.toggle('active',parseInt(t.dataset.idx)===curIdx));
  showMedia(m.type, m.src);
}

// ── VARIANTES ────────────────────────────────────────────────
function togVar(btn) {
  const was = btn.classList.contains('active');
  document.querySelectorAll('.var-btn').forEach(b=>b.classList.remove('active'));
  if (!was) { btn.classList.add('active'); selVar=btn.textContent; }
  else selVar=null;
}

// ── PROMOS ───────────────────────────────────────────────────
function selPromo(pill, precio, qty) {
  const was = pill.classList.contains('active');
  document.querySelectorAll('.promo-pill').forEach(p=>p.classList.remove('active'));
  if (!was) {
    pill.classList.add('active');
    selPromoPrice=precio; selPromoQty=qty;
    document.getElementById('precio-display').textContent='S/. '+precio.toFixed(2);
    document.getElementById('qty').value=qty;
  } else {
    selPromoPrice=null; selPromoQty=0;
    document.getElementById('precio-display').textContent='S/. '+PROD.precio.toFixed(2);
  }
}

// ── CANTIDAD ─────────────────────────────────────────────────
function chQty(d) {
  const inp=document.getElementById('qty');
  inp.value=Math.max(1,Math.min(PROD.stock,parseInt(inp.value)+d));
}

// ── CART ─────────────────────────────────────────────────────
function saveCart(){ localStorage.setItem('cart_'+SLUG,JSON.stringify(cart)); }

function addToCart() {
  const qty   = parseInt(document.getElementById('qty').value)||1;
  const precio= selPromoPrice??PROD.precio;
  const key   = PROD.id+'|'+(selVar||'');
  const exist = cart.find(x=>x.key===key);
  if (exist) exist.qty+=qty;
  else cart.push({key,id:PROD.id,nombre:PROD.nombre,imagen:PROD.imagen,precio,variante:selVar,qty});
  saveCart(); updateCartUI(); openDrawer(); showToast('✅ Agregado al carrito','ok');
}

function updateCartUI() {
  const count = cart.reduce((a,c)=>a+c.qty,0);
  document.getElementById('cart-count').textContent=count;
  const total = cart.reduce((a,c)=>a+c.precio*c.qty,0);
  document.getElementById('cart-total-val').textContent='S/. '+total.toFixed(2);
  const el=document.getElementById('drawer-items');
  if (!cart.length) { el.innerHTML='<div class="cart-empty">Tu carrito está vacío</div>'; return; }
  el.innerHTML=cart.map((item,i)=>`
    <div class="cart-item">
      <img src="${esc(item.imagen)}" class="ci-img" alt="">
      <div class="ci-info">
        <div class="ci-name">${esc(item.nombre)}</div>
        ${item.variante?`<div class="ci-var">${esc(item.variante)}</div>`:''}
        <div class="ci-price">S/. ${(item.precio*item.qty).toFixed(2)} × ${item.qty}</div>
      </div>
      <button class="ci-del" onclick="removeItem(${i})">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
      </button>
    </div>`).join('');
}

function removeItem(i){ cart.splice(i,1); saveCart(); updateCartUI(); }
function openDrawer(){ updateCartUI(); document.getElementById('drawer').classList.add('open'); document.getElementById('drawer-overlay').classList.add('open'); }
function closeDrawer(){ document.getElementById('drawer').classList.remove('open'); document.getElementById('drawer-overlay').classList.remove('open'); }
function closeModal(){ document.getElementById('modal-pedido').classList.remove('open'); }

function irWsp(){
  const num=(WHATSAPP||'').replace(/\D/g,'');
  window.open('https://wa.me/'+num,'_blank');
}

function comprarWsp() {
  const qty   = parseInt(document.getElementById('qty').value)||1;
  const precio= selPromoPrice??PROD.precio;
  let msg=`*${TIENDA_NOMBRE}*\n\nHola, quiero comprar:\n• ${PROD.nombre}${selVar?' ('+selVar+')':''} x${qty} — S/. ${(precio*qty).toFixed(2)}`;
  if (DELIVERY_TIPO==='costo_fijo') msg+=`\nEnvío: S/. ${DELIVERY_PRECIO.toFixed(2)}`;
  msg+=`\n\n*Total: S/. ${(precio*qty+(DELIVERY_TIPO==='costo_fijo'?DELIVERY_PRECIO:0)).toFixed(2)}*`;
  const num=(WHATSAPP||'').replace(/\D/g,'');
  window.open('https://wa.me/'+num+'?text='+encodeURIComponent(msg),'_blank');
}

function checkout() {
  if (!cart.length) return showToast('Tu carrito está vacío');
  if (METODO_VENTAS==='whatsapp') {
    let msg=`*Pedido en ${TIENDA_NOMBRE}*\n\n`;
    cart.forEach(c=>{ msg+=`• ${c.nombre}${c.variante?' ('+c.variante+')':''} x${c.qty} — S/. ${(c.precio*c.qty).toFixed(2)}\n`; });
    const total=cart.reduce((a,c)=>a+c.precio*c.qty,0);
    if (DELIVERY_TIPO==='costo_fijo') msg+=`\nEnvío: S/. ${DELIVERY_PRECIO.toFixed(2)}`;
    msg+=`\n*Total: S/. ${(total+(DELIVERY_TIPO==='costo_fijo'?DELIVERY_PRECIO:0)).toFixed(2)}*`;
    const num=(WHATSAPP||'').replace(/\D/g,'');
    window.open('https://wa.me/'+num+'?text='+encodeURIComponent(msg),'_blank');
  } else {
    closeDrawer();
    document.getElementById('modal-pedido').classList.add('open');
  }
}

async function confirmarPedido() {
  const nombre=document.getElementById('ped-nombre').value.trim();
  const tel=document.getElementById('ped-tel').value.trim();
  if (!nombre||!tel) return showToast('Nombre y teléfono son requeridos');
  const btn=document.getElementById('btn-confirmar');
  btn.disabled=true; btn.textContent='Enviando...';
  const total=cart.reduce((a,c)=>a+c.precio*c.qty,0)+(DELIVERY_TIPO==='costo_fijo'?DELIVERY_PRECIO:0);
  const fd=new FormData();
  fd.append('slug',SLUG); fd.append('nombre',nombre); fd.append('telefono',tel);
  fd.append('direccion',document.getElementById('ped-dir').value.trim());
  fd.append('notas',document.getElementById('ped-notas').value.trim());
  fd.append('items',JSON.stringify(cart.map(c=>({nombre:c.nombre,qty:c.qty,precio:c.precio}))));
  fd.append('total',total.toFixed(2));
  try {
    const r=await fetch('/api/pedidos?accion=crear',{method:'POST',body:fd});
    const d=await r.json();
    if (d.ok) { cart=[]; saveCart(); updateCartUI(); closeModal(); showToast('🎉 ¡Pedido enviado!','ok'); }
    else showToast('Error: '+(d.error||'Error'));
  } catch(e) { showToast('Error de red'); }
  btn.disabled=false; btn.textContent='Confirmar pedido';
}

// ── RESEÑAS — star picker ─────────────────────────────────────
(function(){
  const stars=document.querySelectorAll('#star-picker .sp-star');
  const inp=document.getElementById('star-val');
  function paint(n){ stars.forEach(s=>s.classList.toggle('lit',parseInt(s.dataset.v)<=n)); }
  stars.forEach(s=>{
    s.addEventListener('mouseenter',()=>paint(parseInt(s.dataset.v)));
    s.addEventListener('mouseleave',()=>paint(parseInt(inp.value)));
    s.addEventListener('click',()=>{ inp.value=s.dataset.v; paint(parseInt(s.dataset.v)); });
  });
})();

async function enviarResena() {
  const nombre=document.getElementById('res-nombre').value.trim();
  const comentario=document.getElementById('res-comentario').value.trim();
  const estrellas=parseInt(document.getElementById('star-val').value)||0;
  if (!nombre||!comentario) return showToast('Nombre y comentario son requeridos');
  if (!estrellas) return showToast('Selecciona una puntuación');
  const btn=document.getElementById('btn-resena');
  btn.disabled=true; btn.textContent='Enviando...';
  const fd=new FormData();
  fd.append('slug',SLUG); fd.append('producto_id',PROD_ID);
  fd.append('nombre',nombre); fd.append('pais',document.getElementById('res-pais').value.trim());
  fd.append('estrellas',estrellas); fd.append('comentario',comentario);
  try {
    const r=await fetch('/api/resenas?accion=crear',{method:'POST',body:fd});
    const d=await r.json();
    if (d.ok) {
      document.getElementById('review-sent').style.display='block';
      ['res-nombre','res-pais','res-comentario'].forEach(id=>document.getElementById(id).value='');
      document.getElementById('star-val').value='0';
      document.querySelectorAll('#star-picker .sp-star').forEach(s=>s.classList.remove('lit'));
    } else showToast('Error: '+(d.error||'Error'));
  } catch(e){ showToast('Error de red'); }
  btn.disabled=false; btn.textContent='Enviar reseña';
}

// ── UTILS ────────────────────────────────────────────────────
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
let _tt;
function showToast(msg, type='') {
  const t=document.getElementById('toast');
  t.textContent=msg; t.className='show'+(type==='ok'?' ok':'');
  clearTimeout(_tt); _tt=setTimeout(()=>t.className='',3000);
}

updateCartUI();

// ── ZOOM ─────────────────────────────────────────────────────
(function(){
  const gallery = document.querySelector('.gallery-main');
  const img     = document.getElementById('main-img');
  const SCALE   = 2.5;

  gallery.addEventListener('mousemove', function(e) {
    if (img.style.display === 'none') return;
    const r  = gallery.getBoundingClientRect();
    const px = ((e.clientX - r.left)  / r.width)  * 100;
    const py = ((e.clientY - r.top)   / r.height) * 100;
    img.style.transformOrigin = px + '% ' + py + '%';
    img.style.transform       = 'scale(' + SCALE + ')';
  });

  gallery.addEventListener('mouseleave', function() {
    img.style.transform       = 'scale(1)';
    img.style.transformOrigin = '50% 50%';
  });
})();
</script>
</body>
</html>
