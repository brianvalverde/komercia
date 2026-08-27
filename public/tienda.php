<?php
require_once '/var/www/komercia/config/firebase.php';
$slug = $_GET['slug'] ?? '';
if (!$slug || !preg_match('/^[a-z0-9-]+$/', $slug)) { http_response_code(404); exit('Tienda no encontrada'); }
$tiendaDoc = firestoreRequest('GET', "tiendas/{$slug}");
if (isset($tiendaDoc['error']) || !$tiendaDoc) { http_response_code(404); exit('Tienda no encontrada'); }
$f = $tiendaDoc['fields'] ?? [];
function fsStr(array $f, string $k, string $d = ''): string {
    return (string)($f[$k]['stringValue'] ?? $f[$k]['integerValue'] ?? $f[$k]['doubleValue'] ?? $d);
}
function fsStrArray(array $f, string $k): array {
    $out = [];
    foreach ($f[$k]['arrayValue']['values'] ?? [] as $v) { if (isset($v['stringValue'])) $out[] = $v['stringValue']; }
    return $out;
}
$nombreTienda   = fsStr($f,'nombre') ?: ucfirst($slug);
$uid            = fsStr($f,'uid');
$telefono       = preg_replace('/[^0-9]/','',fsStr($f,'telefono'));
$whatsapp       = preg_replace('/[^0-9]/','',fsStr($f,'whatsapp') ?: $telefono);
$descripcion    = fsStr($f,'descripcion','Bienvenido a nuestra tienda');
$logoUrl        = fsStr($f,'logo');
$colorPrimario  = fsStr($f,'color_primario','#ff6a00');
$metodoVentas   = fsStr($f,'metodo_ventas','whatsapp');
$deliveryTipo   = fsStr($f,'delivery_tipo','no_incluido');
$deliveryPrecio = (float)fsStr($f,'delivery_precio','0');
$facebook       = fsStr($f,'facebook');
$instagram      = fsStr($f,'instagram');
$tiktok         = fsStr($f,'tiktok');
$banners        = fsStrArray($f,'banners');
if (!preg_match('/^#[0-9a-fA-F]{6}$/',$colorPrimario)) $colorPrimario = '#ff6a00';
$r = hexdec(substr($colorPrimario,1,2));
$g = hexdec(substr($colorPrimario,3,2));
$b = hexdec(substr($colorPrimario,5,2));
$colorDark  = sprintf('#%02x%02x%02x',max(0,$r-30),max(0,$g-30),max(0,$b-30));
$colorLight = sprintf('rgba(%d,%d,%d,0.15)',$r,$g,$b);
// ── PLAN CHECK ────────────────────────────────────────────────
$tienda_id = '';
if ($uid) {
    $cf = firestoreRequest('GET',"comerciantes/{$uid}");
    $cff = $cf['fields'] ?? [];
    $planActivo  = $cff['plan_activo']['booleanValue'] ?? true;
    $planExpira  = $cff['plan_expira']['stringValue']  ?? '';
    $plan        = $cff['plan']['stringValue']         ?? 'trial';
    $trialInicio = $cff['trial_inicio']['stringValue'] ?? '';
    $tiendaBloqueada = false;
    if ($planActivo === false) $tiendaBloqueada = true;
    elseif ($planExpira && strtotime($planExpira) < time()) $tiendaBloqueada = true;
    elseif ($plan === 'trial' && $trialInicio && (strtotime($trialInicio)+7*86400) < time()) $tiendaBloqueada = true;
    if ($tiendaBloqueada) { ?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=htmlspecialchars($nombreTienda)?> — No disponible</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:#0d0d0d;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}.card{background:#1a1a1a;border-radius:24px;padding:48px 40px;max-width:460px;width:100%;text-align:center}.icon{font-size:52px;display:block;margin-bottom:20px}.store-name{font-size:18px;font-weight:700;color:#fff;margin-bottom:24px}h1{font-size:21px;font-weight:800;color:#fff;margin-bottom:12px}p{font-size:14px;color:#888;line-height:1.6;margin-bottom:28px}.btn{display:inline-block;background:<?=$colorPrimario?>;color:#fff;border-radius:12px;padding:13px 28px;font-size:14px;font-weight:700;text-decoration:none}</style>
</head><body><div class="card"><?php if($logoUrl):?><img src="<?=htmlspecialchars($logoUrl)?>" style="width:60px;height:60px;border-radius:50%;margin:0 auto 20px;display:block" alt=""><?php endif;?>
<div class="store-name"><?=htmlspecialchars($nombreTienda)?></div><span class="icon">🔒</span>
<h1>Esta tienda no está disponible</h1><p>El propietario necesita renovar su plan para mostrar sus productos.</p>
<a href="/login" class="btn">Soy el dueño — Ingresar</a></div></body></html><?php exit; }
    $tiendaRef = firestoreRequest('GET',"tiendas/{$slug}");
    $tienda_id = $tiendaRef['fields']['tienda_id']['stringValue'] ?? '';
}
// ── PRODUCTOS ─────────────────────────────────────────────────
$productos = []; $categorias = [];
if ($uid) {
    $productosPath = ($tienda_id && $tienda_id !== 'main')
        ? "comerciantes/{$uid}/tiendas/{$tienda_id}/productos"
        : "comerciantes/{$uid}/productos";
    $res = firestoreRequest('GET',$productosPath);
    if (!isset($res['error']) && isset($res['documents'])) {
        foreach ($res['documents'] as $doc) {
            $pf = $doc['fields'] ?? [];
            if (!($pf['activo']['booleanValue'] ?? true)) continue;
            $imagenes = [];
            foreach ($pf['imagenes']['arrayValue']['values'] ?? [] as $v) { if (isset($v['stringValue'])) $imagenes[] = $v['stringValue']; }
            if (empty($imagenes) && !empty($pf['imagen']['stringValue'])) $imagenes[] = $pf['imagen']['stringValue'];
            $videos = [];
            foreach ($pf['videos']['arrayValue']['values'] ?? [] as $v) { if (isset($v['stringValue'])) $videos[] = $v['stringValue']; }
            $promociones = [];
            foreach ($pf['promociones']['arrayValue']['values'] ?? [] as $v) {
                $mf = $v['mapValue']['fields'] ?? [];
                $promociones[] = ['nombre'=>$mf['nombre']['stringValue']??'','precio'=>(float)($mf['precio']['doubleValue']??$mf['precio']['integerValue']??0),'detalle'=>$mf['detalle']['stringValue']??''];
            }
            $cat = fsStr($pf,'categoria');
            if ($cat && !in_array($cat,$categorias)) $categorias[] = $cat;
            $productos[] = [
                'id'         => basename($doc['name']),
                'nombre'     => fsStr($pf,'nombre'),
                'descripcion'=> fsStr($pf,'descripcion'),
                'precio'     => (float)($pf['precio']['doubleValue']??$pf['precio']['integerValue']??0),
                'stock'      => (int)($pf['stock']['integerValue']??99),
                'imagen'     => $imagenes[0] ?? '',
                'imagenes'   => $imagenes,
                'videos'     => $videos,
                'categoria'  => $cat,
                'promociones'=> $promociones,
            ];
        }
    }
}
$numProductos   = count($productos);
$tiendaJson     = json_encode(['nombre'=>$nombreTienda,'slug'=>$slug,'telefono'=>$whatsapp,'metodo'=>$metodoVentas,'delivery_tipo'=>$deliveryTipo,'delivery_precio'=>$deliveryPrecio]);
$productosJson  = json_encode($productos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=htmlspecialchars($nombreTienda)?></title>
<meta name="description" content="<?=htmlspecialchars($descripcion)?>">
<meta property="og:title" content="<?=htmlspecialchars($nombreTienda)?>">
<?php if($logoUrl):?><meta property="og:image" content="<?=htmlspecialchars($logoUrl)?>"><?php endif;?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--c:<?=$colorPrimario?>;--cd:<?=$colorDark?>;--cl:<?=$colorLight?>;--r:<?=$r?>;--g:<?=$g?>;--b:<?=$b?>}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;background:#0a0a0a;color:#e5e5e5;min-height:100vh}
a{text-decoration:none;color:inherit}

/* ━━ ANNOUNCE BAR ━━ */
.announce-bar{
  background:#111;
  border-bottom:1px solid rgba(255,255,255,.06);
  padding:8px 24px;
  display:flex;align-items:center;justify-content:space-between;
  font-size:.73rem;color:#777;
}
.announce-left{display:flex;align-items:center;gap:20px}
.announce-item{display:flex;align-items:center;gap:6px;color:#888}
.announce-item svg{color:var(--c)}
.announce-right{font-weight:700;letter-spacing:.3px}
.announce-right span{color:var(--c)}

/* ━━ TOPBAR ━━ */
.topbar{
  background:#111;
  border-bottom:1px solid rgba(255,255,255,.06);
  padding:0 24px;height:66px;
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:900;gap:16px;
}
.topbar-logo{display:flex;align-items:center;gap:12px;flex-shrink:0}
.logo-img{width:42px;height:42px;border-radius:10px;object-fit:contain;background:#1e1e1e}
.logo-placeholder{width:42px;height:42px;border-radius:10px;background:var(--c);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:1.1rem;flex-shrink:0}
.logo-text-wrap{display:flex;flex-direction:column;gap:3px}
.logo-store-name{font-size:.95rem;font-weight:800;color:#fff;letter-spacing:-.2px;line-height:1}
.logo-sub{font-size:.62rem;font-weight:700;color:var(--c);text-transform:uppercase;letter-spacing:.8px;line-height:1}
.topbar-search{flex:1;max-width:500px;position:relative}
.topbar-search input{
  width:100%;background:#1c1c1c;border:1px solid rgba(255,255,255,.08);
  border-radius:12px;padding:10px 44px 10px 16px;
  font-size:.88rem;font-family:'Inter',sans-serif;color:#e5e5e5;outline:none;transition:.2s;
}
.topbar-search input::placeholder{color:#444}
.topbar-search input:focus{border-color:var(--c);background:#1e1e1e}
.topbar-search-btn{
  position:absolute;right:6px;top:50%;transform:translateY(-50%);
  background:var(--c);border:none;border-radius:8px;
  width:32px;height:32px;display:flex;align-items:center;justify-content:center;
  cursor:pointer;color:#fff;transition:.2s;
}
.topbar-search-btn:hover{background:var(--cd)}
/* ── AUTOCOMPLETE DROPDOWN ── */
.search-dropdown{
  position:absolute;top:calc(100% + 6px);left:0;right:0;
  background:#1a1a1a;border:1px solid rgba(255,255,255,.10);
  border-radius:12px;overflow:hidden;
  box-shadow:0 12px 40px rgba(0,0,0,.7);
  z-index:1200;display:none;
}
.search-dropdown.open{display:block}
.search-drop-item{
  display:flex;align-items:center;gap:12px;
  padding:10px 14px;cursor:pointer;transition:.15s;border-bottom:1px solid rgba(255,255,255,.04);
}
.search-drop-item:last-child{border-bottom:none}
.search-drop-item:hover{background:rgba(255,255,255,.06)}
.search-drop-img{
  width:40px;height:40px;border-radius:8px;object-fit:cover;
  background:#222;flex-shrink:0;
}
.search-drop-info{flex:1;min-width:0}
.search-drop-name{font-size:.85rem;font-weight:600;color:#e5e5e5;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.search-drop-price{font-size:.78rem;font-weight:700;color:var(--c)}
.search-drop-empty{padding:14px 16px;font-size:.84rem;color:#555;text-align:center}
.topbar-right{display:flex;align-items:center;gap:10px;flex-shrink:0}
.cart-btn{
  position:relative;background:#1c1c1c;border:1px solid rgba(255,255,255,.08);
  color:#ccc;border-radius:10px;padding:9px 16px;cursor:pointer;
  font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:7px;
  transition:.2s;font-family:'Inter',sans-serif;
}
.cart-btn:hover{border-color:var(--c);color:var(--c)}
.cart-badge{
  background:var(--c);color:#fff;border-radius:50%;
  width:18px;height:18px;font-size:.68rem;font-weight:800;
  display:none;align-items:center;justify-content:center;
  position:absolute;top:-7px;right:-7px;
}
.contact-btn{
  background:#25d366;color:#fff;border:none;border-radius:10px;
  padding:10px 18px;cursor:pointer;font-size:.85rem;font-weight:700;
  display:flex;align-items:center;gap:7px;transition:.2s;font-family:'Inter',sans-serif;
}
.contact-btn:hover{background:#1db954}

/* ━━ CATEGORY BAR ━━ */
.cats-bar{
  background:#111;border-bottom:1px solid rgba(255,255,255,.06);
  overflow-x:auto;scrollbar-width:none;
  position:sticky;top:66px;z-index:800;
}
.cats-bar::-webkit-scrollbar{display:none}
.cats-inner{display:flex;gap:4px;padding:10px 24px;min-width:max-content}
.cat-btn{
  padding:8px 20px;border:none;background:none;cursor:pointer;
  font-size:.83rem;font-weight:600;color:#555;
  font-family:'Inter',sans-serif;white-space:nowrap;border-radius:22px;transition:.2s;
}
.cat-btn:hover{color:#fff;background:rgba(255,255,255,.06)}
.cat-btn.active{background:var(--c);color:#fff;font-weight:700}

/* ━━ BANNER CAROUSEL ━━ */
.carousel{position:relative;overflow:hidden;background:#000;max-height:380px}
.carousel-track{display:flex;transition:transform .5s cubic-bezier(.4,0,.2,1)}
.carousel-slide{flex-shrink:0;width:100%}
.carousel-slide img{width:100%;max-height:380px;object-fit:cover;display:block}
.carousel-prev,.carousel-next{position:absolute;top:50%;transform:translateY(-50%);background:rgba(0,0,0,.5);color:#fff;border:none;border-radius:50%;width:42px;height:42px;cursor:pointer;font-size:22px;display:flex;align-items:center;justify-content:center;z-index:2;transition:.2s}
.carousel-prev{left:14px}.carousel-next{right:14px}
.carousel-prev:hover,.carousel-next:hover{background:rgba(0,0,0,.8)}
.carousel-dots{position:absolute;bottom:12px;left:50%;transform:translateX(-50%);display:flex;gap:6px}
.carousel-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.35);border:none;cursor:pointer;transition:.2s;padding:0}
.carousel-dot.active{background:var(--c);transform:scale(1.3)}

/* ━━ HERO ━━ */
.hero{
  position:relative;background:#0a0a0a;overflow:hidden;
  padding:70px 24px 60px;
}
/* Glow naranja dramático — cubre casi todo el banner */
.hero::before{
  content:'';position:absolute;
  top:0;left:0;right:0;bottom:0;width:100%;height:100%;
  background:
    radial-gradient(ellipse at 75% 50%,rgba(<?=$r?>,<?=$g?>,<?=$b?>,.50) 0%,rgba(<?=$r?>,<?=$g?>,<?=$b?>,.22) 40%,transparent 65%),
    radial-gradient(ellipse at 40% 80%,rgba(<?=$r?>,<?=$g?>,<?=$b?>,.18) 0%,transparent 50%);
  pointer-events:none;
}
/* Patrón de puntos sutil */
.hero::after{
  content:'';position:absolute;inset:0;
  background-image:radial-gradient(rgba(255,255,255,.03) 1px,transparent 1px);
  background-size:28px 28px;pointer-events:none;
}
.hero-inner{
  position:relative;z-index:1;
  max-width:1100px;margin:0 auto;
  display:flex;align-items:center;gap:40px;justify-content:space-between;
}
/* Texto izquierdo */
.hero-text{flex:1;max-width:580px}
.hero-badge{
  display:inline-flex;align-items:center;gap:7px;
  background:rgba(<?=$r?>,<?=$g?>,<?=$b?>,.18);
  color:var(--c);border:1px solid rgba(<?=$r?>,<?=$g?>,<?=$b?>,.4);
  border-radius:22px;padding:7px 18px;
  font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.8px;
  margin-bottom:22px;
}
.hero-title{
  font-size:clamp(2rem,4vw,3.2rem);font-weight:900;
  color:#fff;line-height:1.1;margin-bottom:16px;letter-spacing:-.5px;
}
.hero-title-name{color:var(--c);display:block}
.hero-desc{font-size:.95rem;color:#777;line-height:1.65;margin-bottom:32px;max-width:420px}
.hero-btns{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:40px}
.btn-hero-primary{
  background:var(--c);color:#fff;border:none;border-radius:12px;
  padding:14px 28px;font-size:.92rem;font-weight:800;cursor:pointer;
  display:inline-flex;align-items:center;gap:8px;transition:.2s;
  font-family:'Inter',sans-serif;letter-spacing:-.1px;
  box-shadow:0 8px 24px rgba(<?=$r?>,<?=$g?>,<?=$b?>,.4);
}
.btn-hero-primary:hover{background:var(--cd);transform:translateY(-1px);box-shadow:0 12px 32px rgba(<?=$r?>,<?=$g?>,<?=$b?>,.5)}
.btn-hero-secondary{
  background:transparent;color:#ccc;
  border:1.5px solid #2a2a2a;border-radius:12px;
  padding:14px 28px;font-size:.92rem;font-weight:600;cursor:pointer;
  display:inline-flex;align-items:center;gap:8px;transition:.2s;
  font-family:'Inter',sans-serif;
}
.btn-hero-secondary:hover{border-color:#444;color:#fff}
/* Stats */
.hero-stats{display:flex;gap:0;align-items:stretch}
.hero-stat{display:flex;flex-direction:column;align-items:flex-start;padding:0 28px 0 0;margin:0 28px 0 0;border-right:1px solid #1e1e1e}
.hero-stat:first-child{padding-left:0}
.hero-stat:last-child{border-right:none;margin-right:0;padding-right:0}
.hero-stat-num{font-size:1.5rem;font-weight:900;color:#fff;line-height:1}
.hero-stat-label{font-size:.62rem;color:#555;font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-top:3px}
.hero-stat-row{display:flex;align-items:center;gap:5px}

/* ━━ HERO CARDS STACK (3 tarjetas apiladas) ━━ */
.hero-cards-stack{
  position:relative;flex-shrink:0;
  width:210px;height:310px;
  display:none;
}
@media(min-width:860px){.hero-cards-stack{display:block}}
/* Tarjeta 3 — más atrás, más a la derecha */
.hero-card-ghost-2{
  position:absolute;top:24px;right:-44px;
  width:190px;height:268px;background:#1a1a1a;
  border:1px solid rgba(255,255,255,.05);border-radius:16px;
  opacity:.35;transform:rotate(5deg);
}
/* Tarjeta 2 — intermedia */
.hero-card-ghost-1{
  position:absolute;top:12px;right:-22px;
  width:200px;height:280px;background:#1e1e1e;
  border:1px solid rgba(255,255,255,.08);border-radius:16px;
  opacity:.6;transform:rotate(2.5deg);
}
/* Tarjeta 1 — al frente */
.hero-card{
  position:absolute;top:0;left:0;
  width:210px;background:#1c1c1c;
  border:1px solid rgba(255,255,255,.1);border-radius:16px;
  overflow:hidden;z-index:3;
  box-shadow:0 24px 64px rgba(0,0,0,.7);
}
.hero-card-img{
  width:100%;aspect-ratio:1/1;background:#141414;
  display:flex;align-items:center;justify-content:center;overflow:hidden;
}
.hero-card-img img{width:100%;height:100%;object-fit:cover}
.hero-card-img .no-img-hero{font-size:3.5rem;color:#2a2a2a}
.hero-card-body{padding:12px 14px 14px}
.hero-card-price{font-size:1.1rem;font-weight:900;color:var(--c);margin-bottom:3px}
.hero-card-name{font-size:.78rem;color:#888;line-height:1.3}
.hero-card-wa{
  width:100%;background:#25d366;color:#fff;border:none;
  padding:10px;font-size:.8rem;font-weight:700;cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:6px;
  font-family:'Inter',sans-serif;transition:.2s;
}
.hero-card-wa:hover{background:#1db954}

/* ━━ TRUST BAR ━━ */
.trust-bar{
  background:#111;
  border-top:1px solid rgba(255,255,255,.05);
  border-bottom:1px solid rgba(255,255,255,.05);
  padding:28px 24px;
}
.trust-inner{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:repeat(2,1fr);gap:24px}
@media(min-width:700px){.trust-inner{grid-template-columns:repeat(4,1fr)}}
.trust-item{display:flex;align-items:center;gap:14px}
.trust-icon{
  width:46px;height:46px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.trust-icon.orange{background:rgba(<?=$r?>,<?=$g?>,<?=$b?>,.15);color:var(--c)}
.trust-icon.blue{background:rgba(59,130,246,.15);color:#3b82f6}
.trust-icon.teal{background:rgba(20,184,166,.15);color:#14b8a6}
.trust-icon.green{background:rgba(34,197,94,.15);color:#22c55e}
.trust-text strong{display:block;font-size:.85rem;font-weight:700;color:#e5e5e5;margin-bottom:2px}
.trust-text span{font-size:.73rem;color:#555}

/* ━━ PRODUCTS SECTION ━━ */
.products-section{padding:48px 24px 72px;max-width:1148px;margin:0 auto}
.products-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}
.products-title{
  font-size:1.4rem;font-weight:900;color:#fff;
  display:flex;align-items:center;gap:12px;letter-spacing:-.3px;
}
.products-title::before{content:'';display:inline-block;width:4px;height:24px;background:var(--c);border-radius:2px}
.products-count-label{font-size:.85rem;font-weight:600;color:var(--c)}

/* Skeleton */
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
.skeleton-card{background:#151515;border-radius:14px;overflow:hidden;border:1px solid #1a1a1a}
.skeleton-img{width:100%;padding-bottom:85%;background:linear-gradient(90deg,#161616 25%,#1e1e1e 50%,#161616 75%);background-size:200% 100%;animation:shimmer 1.5s infinite}
.skeleton-body{padding:12px;display:flex;flex-direction:column;gap:9px}
.skeleton-line{height:11px;border-radius:4px;background:linear-gradient(90deg,#161616 25%,#1e1e1e 50%,#161616 75%);background-size:200% 100%;animation:shimmer 1.5s infinite}

/* Product grid */
.products-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
@media(min-width:580px){.products-grid{grid-template-columns:repeat(3,1fr)}}
@media(min-width:900px){.products-grid{grid-template-columns:repeat(4,1fr)}}

/* Product card */
.product-card{
  background:#141414;border-radius:16px;overflow:hidden;
  cursor:pointer;transition:.25s;display:flex;flex-direction:column;
  border:1px solid rgba(255,255,255,.06);
}
.product-card:hover{
  border-color:rgba(<?=$r?>,<?=$g?>,<?=$b?>,.4);
  transform:translateY(-3px);
  box-shadow:0 12px 40px rgba(0,0,0,.5);
}
.product-card-img{position:relative;width:100%;padding-bottom:88%;background:#111;overflow:hidden}
.product-card-img img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transition:.35s}
.product-card:hover .product-card-img img{transform:scale(1.06)}
.product-card-img .no-img{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:#222}
.stock-badge{position:absolute;top:8px;left:8px;padding:3px 9px;border-radius:6px;font-size:.68rem;font-weight:700}
.stock-low{background:rgba(245,158,11,.2);color:#f59e0b}
.stock-out{background:rgba(239,68,68,.2);color:#ef4444}
.product-card-body{padding:13px 13px 12px;flex:1;display:flex;flex-direction:column;gap:5px}
.product-card-cat{font-size:.67rem;color:#555;font-weight:600;text-transform:uppercase;letter-spacing:.4px}
.product-card-name{font-size:.88rem;font-weight:700;color:#e5e5e5;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.product-card-stars{display:flex;align-items:center;gap:3px}
.product-card-stars svg{fill:#f59e0b}
.product-card-stars span{font-size:.72rem;color:#555;margin-left:2px}
.product-card-price{font-size:1.05rem;font-weight:900;color:var(--c);letter-spacing:-.2px}
.product-card-footer{display:flex;align-items:center;justify-content:space-between;margin-top:8px;gap:8px}
.product-card-stock{font-size:.72rem;color:#444}
.product-card-cart-btn{
  flex:1;height:36px;border-radius:10px;background:var(--c);
  color:#fff;border:none;cursor:pointer;font-size:.82rem;font-weight:700;
  letter-spacing:.3px;transition:.2s;
}
.product-card-cart-btn:hover{background:var(--cd);transform:scale(1.03)}
.product-card-cart-btn:disabled{background:#1e1e1e;cursor:not-allowed;color:#333}
.no-products{text-align:center;padding:70px 20px;color:#333}
.no-products div{font-size:3.5rem;margin-bottom:14px}
.no-products p{font-size:1rem;color:#444}

/* ━━ MODALES ━━ */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:1000;align-items:flex-end;justify-content:center}
.modal-overlay.open{display:flex}
@media(min-width:600px){.modal-overlay{align-items:center}}
.product-modal{background:#161616;border-radius:20px 20px 0 0;width:100%;max-height:92vh;overflow-y:auto;display:flex;flex-direction:column;scrollbar-width:thin;scrollbar-color:var(--c) #1a1a1a}
.product-modal::-webkit-scrollbar{width:5px}
.product-modal::-webkit-scrollbar-track{background:#1a1a1a;border-radius:10px}
.product-modal::-webkit-scrollbar-thumb{background:var(--c);border-radius:10px}
.product-modal::-webkit-scrollbar-thumb:hover{background:var(--cd)}
@media(min-width:600px){.product-modal{border-radius:18px;max-width:680px;max-height:90vh}}
.modal-close{position:absolute;top:12px;right:12px;background:rgba(255,255,255,.1);color:#fff;border:none;border-radius:50%;width:34px;height:34px;cursor:pointer;font-size:1.2rem;display:flex;align-items:center;justify-content:center;z-index:2;transition:.2s}
.modal-close:hover{background:rgba(255,255,255,.2)}
.gallery-wrap{position:relative;background:#111}
.gallery-main{width:100%;aspect-ratio:4/3;object-fit:contain;background:#111;max-height:320px}
.gallery-video{width:100%;aspect-ratio:16/9;background:#000}
.gallery-thumbs{display:flex;gap:8px;padding:10px 14px 12px;overflow-x:auto;background:#161616;border-bottom:1px solid #1e1e1e;scrollbar-width:thin;scrollbar-color:var(--c) #1a1a1a}
.gallery-thumbs::-webkit-scrollbar{height:3px}
.gallery-thumbs::-webkit-scrollbar-track{background:#1a1a1a;border-radius:10px}
.gallery-thumbs::-webkit-scrollbar-thumb{background:var(--c);border-radius:10px}
.gallery-thumb{width:68px;height:68px;border-radius:10px;object-fit:cover;cursor:pointer;border:2px solid #2a2a2a;flex-shrink:0;transition:.2s;background:#111}
.gallery-thumb.active{border-color:var(--c);box-shadow:0 0 0 2px var(--c)}
.gallery-thumb-video{width:56px;height:56px;border-radius:8px;background:#111;display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid #2a2a2a;flex-shrink:0;color:#fff;font-size:1.2rem;transition:.2s}
.gallery-thumb-video.active{border-color:var(--c)}
.modal-body{padding:18px 22px 26px}
.modal-nombre{font-size:1.2rem;font-weight:800;color:#fff;margin-bottom:4px}
.modal-precio{font-size:1.6rem;font-weight:900;color:var(--c);margin-bottom:4px}
.modal-stock{font-size:.8rem;margin-bottom:12px}
.modal-desc{font-size:.87rem;color:#666;line-height:1.65;margin-bottom:16px}
.promo-section{margin-bottom:16px}
.promo-label{font-size:.75rem;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
.promo-table{width:100%;border-collapse:collapse;font-size:.85rem}
.promo-table th{background:#1e1e1e;padding:8px;text-align:left;font-weight:600;font-size:.76rem;color:#555}
.promo-table td{padding:8px;border-bottom:1px solid #1e1e1e;color:#bbb}
.promo-row{cursor:pointer;transition:.15s}
.promo-row:hover,.promo-row.selected{background:#1e1e1e}
.promo-row.selected td:first-child::before{content:'✓ ';color:var(--c);font-weight:800}
.promo-price{font-weight:700;color:var(--c)}
.add-section{display:flex;gap:10px;align-items:center;margin-top:4px;flex-wrap:wrap}
.qty-wrap{display:flex;align-items:center;border:1.5px solid #2a2a2a;border-radius:10px;overflow:hidden}
.qty-wrap button{background:none;border:none;width:38px;height:42px;cursor:pointer;font-size:1.2rem;font-weight:700;color:#aaa;display:flex;align-items:center;justify-content:center;transition:.15s}
.qty-wrap button:hover{background:#1e1e1e;color:#fff}
.qty-num{width:38px;text-align:center;font-weight:800;font-size:.95rem;color:#fff}
.btn-add-cart{flex:1;background:var(--c);color:#fff;border:none;border-radius:10px;padding:12px;cursor:pointer;font-size:.92rem;font-weight:700;transition:.2s;font-family:'Inter',sans-serif}
.btn-add-cart:hover{background:var(--cd)}
.btn-ver-detalle{flex:1;background:#1e1e1e;color:var(--c);border:1.5px solid #2a2a2a;border-radius:10px;padding:12px;cursor:pointer;font-size:.92rem;font-weight:700;transition:.2s;font-family:'Inter',sans-serif;text-align:center;display:flex;align-items:center;justify-content:center;gap:6px}
.btn-ver-detalle:hover{background:#222}

/* ━━ CART DRAWER ━━ */
.cart-drawer{position:fixed;top:0;right:-390px;width:390px;max-width:100vw;height:100%;background:#141414;box-shadow:-4px 0 32px rgba(0,0,0,.6);z-index:1100;display:flex;flex-direction:column;transition:right .3s cubic-bezier(.4,0,.2,1)}
.cart-drawer.open{right:0}
.cart-header{padding:18px 22px;border-bottom:1px solid #1e1e1e;display:flex;align-items:center;justify-content:space-between}
.cart-header h2{font-size:1rem;font-weight:800;color:#fff}
.cart-close{background:none;border:none;font-size:1.3rem;cursor:pointer;color:#555}
.cart-close:hover{color:#fff}
.cart-items{flex:1;overflow-y:auto;padding:12px}
.cart-item{display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #1a1a1a}
.cart-item-img{width:56px;height:56px;border-radius:10px;object-fit:cover;background:#1e1e1e;flex-shrink:0}
.cart-item-info{flex:1}
.cart-item-name{font-size:.83rem;font-weight:600;color:#e5e5e5;line-height:1.3;margin-bottom:4px}
.cart-item-row{display:flex;align-items:center;justify-content:space-between}
.cart-item-price{font-size:.9rem;font-weight:700;color:var(--c)}
.cart-item-del{background:none;border:none;color:#666;cursor:pointer;font-size:1.1rem;padding:4px 6px;border-radius:6px;transition:.2s;line-height:1}
.cart-item-del:hover{color:#ef4444;background:rgba(239,68,68,.1)}
.qty-mini{display:flex;align-items:center;gap:5px}
.qty-mini button{background:#1e1e1e;border:none;border-radius:5px;width:24px;height:24px;cursor:pointer;font-weight:700;font-size:.85rem;color:#bbb}
.qty-mini span{font-size:.85rem;font-weight:700;width:20px;text-align:center;color:#fff}
.cart-empty{text-align:center;padding:60px 20px;color:#333}
.cart-footer{padding:16px 20px;border-top:1px solid #1e1e1e}
.cart-total{display:flex;justify-content:space-between;font-weight:800;font-size:1rem;margin-bottom:14px;color:#fff}
.checkout-opts{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px}
.co-btn{border:none;border-radius:10px;padding:12px 6px;font-size:.76rem;font-weight:700;cursor:pointer;transition:.2s;font-family:'Inter',sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;position:relative}
.co-wsp{background:#25d366;color:#fff}.co-wsp:hover{background:#1db954}
.co-form{background:var(--c);color:#fff}.co-form:hover{background:var(--cd)}
.co-pay{background:#1a1a1a;color:#333;cursor:not-allowed}
.co-soon{font-size:.62rem;font-weight:700;background:var(--c);color:#fff;border-radius:8px;padding:1px 6px;position:absolute;top:-7px;right:-4px}

/* ━━ FORM MODAL ━━ */
.form-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:1200;align-items:center;justify-content:center;padding:16px}
.form-modal.open{display:flex}
.form-modal-box{background:#161616;border-radius:18px;padding:26px;width:100%;max-width:440px;max-height:90vh;overflow-y:auto;border:1px solid #1e1e1e}
.form-modal-box h2{font-size:1.1rem;font-weight:800;color:#fff;margin-bottom:18px}
.fm-group{margin-bottom:14px}
.fm-group label{font-size:.75rem;font-weight:700;color:#555;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:.5px}
.fm-group input,.fm-group textarea{width:100%;padding:11px 14px;border:1.5px solid #222;border-radius:10px;font-size:.9rem;font-family:'Inter',sans-serif;outline:none;transition:.2s;background:#0d0d0d;color:#e5e5e5}
.fm-group input:focus,.fm-group textarea:focus{border-color:var(--c)}
.fm-group textarea{min-height:70px;resize:vertical}
.fm-actions{display:flex;gap:10px;margin-top:20px}
.fm-cancel{flex:1;padding:12px;border:1.5px solid #222;border-radius:10px;background:#1a1a1a;cursor:pointer;font-size:.9rem;font-weight:600;font-family:'Inter',sans-serif;color:#888}
.fm-submit{flex:2;padding:12px;background:var(--c);color:#fff;border:none;border-radius:10px;cursor:pointer;font-size:.9rem;font-weight:700;font-family:'Inter',sans-serif}
.fm-submit:hover{background:var(--cd)}
.fm-submit:disabled{opacity:.6;cursor:not-allowed}

/* ━━ FOOTER ━━ */
.footer{background:#0d0d0d;border-top:1px solid rgba(255,255,255,.05);padding:56px 24px 28px}
.footer-top{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1fr;gap:40px}
@media(min-width:680px){.footer-top{grid-template-columns:1.6fr 1fr 1fr 1fr}}
.footer-logo{display:flex;align-items:center;gap:12px;margin-bottom:20px}
.footer-logo-icon{width:46px;height:46px;border-radius:12px;background:var(--c);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:1.15rem;flex-shrink:0}
.footer-logo img{width:46px;height:46px;border-radius:12px;object-fit:contain;background:#1a1a1a}
.footer-logo-text strong{display:block;font-size:.95rem;font-weight:800;color:#fff}
.footer-logo-text span{font-size:.64rem;font-weight:700;color:var(--c);text-transform:uppercase;letter-spacing:.8px}
.footer-wa-btn{display:inline-flex;align-items:center;gap:8px;background:#25d366;color:#fff;border-radius:12px;padding:11px 20px;font-size:.85rem;font-weight:700;transition:.2s}
.footer-wa-btn:hover{background:#1db954}
.footer-col h4{font-size:.68rem;font-weight:800;color:#444;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px}
.footer-col a{display:block;font-size:.85rem;color:#555;margin-bottom:11px;transition:.2s}
.footer-col a:hover{color:var(--c)}
.footer-bottom{max-width:1100px;margin:40px auto 0;padding-top:22px;border-top:1px solid rgba(255,255,255,.05);display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;font-size:.76rem;color:#333}
.footer-bottom a{color:var(--c);font-weight:600}

/* ━━ FLOATING WHATSAPP ━━ */
.wa-float{
  position:fixed;bottom:28px;right:28px;
  width:60px;height:60px;background:#25d366;color:#fff;
  border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 6px 28px rgba(37,211,102,.5);z-index:850;transition:.3s;
  text-decoration:none;
}
.wa-float:hover{background:#1db954;transform:scale(1.1);box-shadow:0 8px 32px rgba(37,211,102,.5)}

/* ━━ BACK TO TOP ━━ */
.back-top{position:fixed;bottom:96px;right:28px;width:44px;height:44px;background:#1c1c1c;border:1px solid #2a2a2a;color:#888;border-radius:50%;font-size:18px;cursor:pointer;display:none;align-items:center;justify-content:center;z-index:800;transition:.3s}
.back-top.show{display:flex}
.back-top:hover{background:var(--c);color:#fff;border-color:var(--c)}

/* ━━ TOAST ━━ */
.toast{position:fixed;bottom:100px;left:50%;transform:translateX(-50%);background:#1a1a1a;border:1px solid #2a2a2a;color:#e5e5e5;padding:11px 24px;border-radius:28px;font-size:.85rem;font-weight:600;z-index:9999;opacity:0;pointer-events:none;transition:.3s;white-space:nowrap;box-shadow:0 8px 24px rgba(0,0,0,.4)}
.toast.show{opacity:1}

/* ━━ OVERLAY ━━ */
.drawer-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1050}
.drawer-overlay.show{display:block}

/* ━━ RESPONSIVE ━━ */
@media(max-width:580px){
  .hero-title{font-size:2rem}
  .hero-stats{gap:14px}
  .hero-stat{padding-right:14px;margin-right:14px}
  .announce-bar{font-size:.68rem;padding:7px 14px}
  .topbar-search{display:none}
  .contact-btn span{display:none}
  .topbar{padding:0 14px}
  .cats-inner{padding:9px 14px}
  .products-section{padding:36px 14px 60px}
  .mobile-search{display:block}
}
/* Barra de búsqueda mobile */
.mobile-search{
  display:none;background:#111;padding:8px 14px;border-bottom:1px solid #1a1a1a;position:relative;
}
.mobile-search input{
  width:100%;background:#1c1c1c;border:1px solid rgba(255,255,255,.08);
  border-radius:10px;padding:9px 40px 9px 14px;
  font-size:.88rem;font-family:'Inter',sans-serif;color:#e5e5e5;outline:none;
}
.mobile-search input::placeholder{color:#444}
.mobile-search-btn{
  position:absolute;right:22px;top:50%;transform:translateY(-50%);
  background:var(--c);border:none;border-radius:7px;width:28px;height:28px;
  display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;
}
</style>
</head>
<body>

<!-- ANNOUNCE BAR -->
<div class="announce-bar">
  <div class="announce-left">
    <span class="announce-item">
      <!-- Truck SVG -->
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      Envíos disponibles
    </span>
    <span class="announce-item">
      <!-- Star SVG -->
      <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      Productos verificados
    </span>
  </div>
  <div class="announce-right">Powered by <span>Komercia</span></div>
</div>

<!-- TOPBAR -->
<header class="topbar">
  <a class="topbar-logo" href="/tienda/<?=htmlspecialchars($slug)?>">
    <?php if($logoUrl):?>
      <img class="logo-img" src="<?=htmlspecialchars($logoUrl)?>" alt="<?=htmlspecialchars($nombreTienda)?>">
    <?php else:?>
      <div class="logo-placeholder"><?=strtoupper(substr($nombreTienda,0,1))?></div>
    <?php endif;?>
    <div class="logo-text-wrap">
      <span class="logo-store-name"><?=htmlspecialchars($nombreTienda)?></span>
      <span class="logo-sub">Tienda Online</span>
    </div>
  </a>
  <div class="topbar-search">
    <input type="search" id="search-input" placeholder="Buscar productos..." autocomplete="off"
      oninput="buscarAuto(this.value)" onkeydown="navDrop(event)" onfocus="buscarAuto(this.value)" onblur="setTimeout(cerrarDrop,200)">
    <button class="topbar-search-btn" onclick="buscarAuto(document.getElementById('search-input').value)">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    </button>
    <div class="search-dropdown" id="search-dropdown"></div>
  </div>
  <div class="topbar-right">
    <?php if($facebook):?><a href="<?=htmlspecialchars($facebook)?>" target="_blank" rel="noopener" style="color:#444;display:flex" title="Facebook"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a><?php endif;?>
    <?php if($instagram):?><a href="<?=htmlspecialchars($instagram)?>" target="_blank" rel="noopener" style="color:#444;display:flex" title="Instagram"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><circle cx="12" cy="12" r="3"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a><?php endif;?>
    <button class="cart-btn" onclick="abrirCarrito()">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      Carrito
      <span class="cart-badge" id="cart-badge">0</span>
    </button>
    <?php if($whatsapp):?>
    <a href="https://wa.me/<?=$whatsapp?>" target="_blank" class="contact-btn">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a6.27 6.27 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479s1.065 2.875 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.557 4.126 1.533 5.862L.054 23.447a.5.5 0 0 0 .631.553l5.801-1.57A11.939 11.939 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
      <span>Contactar</span>
    </a>
    <?php endif;?>
  </div>
</header>

<!-- MOBILE SEARCH -->
<div class="mobile-search">
  <input type="search" id="search-input-mobile" placeholder="Buscar productos..." autocomplete="off"
    oninput="filtrarMobile(this.value)" onblur="setTimeout(cerrarDrop,200)">
  <button class="mobile-search-btn" onclick="filtrarMobile(document.getElementById('search-input-mobile').value)">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
  </button>
  <div class="search-dropdown" id="search-dropdown-mobile"></div>
</div>

<!-- CATEGORY BAR -->
<?php if(!empty($categorias)):?>
<div class="cats-bar">
  <div class="cats-inner">
    <button class="cat-btn active" onclick="filtrarCat('')" data-cat="">Todos los productos</button>
    <?php foreach($categorias as $cat):?>
    <button class="cat-btn" onclick="filtrarCat('<?=htmlspecialchars($cat,ENT_QUOTES)?>')" data-cat="<?=htmlspecialchars($cat,ENT_QUOTES)?>"><?=htmlspecialchars($cat)?></button>
    <?php endforeach;?>
  </div>
</div>
<?php endif;?>

<!-- BANNER CAROUSEL -->
<?php if(!empty($banners)):?>
<div class="carousel" id="carousel">
  <div class="carousel-track" id="carousel-track">
    <?php foreach($banners as $banner):?>
    <div class="carousel-slide"><img src="<?=htmlspecialchars($banner)?>" alt="Banner" loading="lazy"></div>
    <?php endforeach;?>
  </div>
  <?php if(count($banners)>1):?>
  <button class="carousel-prev" onclick="carouselNav(-1)">&#8249;</button>
  <button class="carousel-next" onclick="carouselNav(1)">&#8250;</button>
  <div class="carousel-dots" id="carousel-dots">
    <?php foreach($banners as $i=>$burl):?>
    <button class="carousel-dot<?=$i===0?' active':''?>" onclick="carouselGoTo(<?=$i?>)"></button>
    <?php endforeach;?>
  </div>
  <?php endif;?>
</div>
<?php endif;?>

<!-- HERO -->
<section class="hero">
  <div class="hero-inner">
    <!-- Texto izquierdo -->
    <div class="hero-text">
      <div class="hero-badge">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        TIENDA OFICIAL
      </div>
      <h1 class="hero-title">
        Bienvenido a
        <span class="hero-title-name"><?=htmlspecialchars($nombreTienda)?></span>
      </h1>
      <p class="hero-desc"><?=htmlspecialchars($descripcion)?></p>
      <div class="hero-btns">
        <a href="#productos" class="btn-hero-primary">Ver productos &nbsp;›</a>
        <?php if($whatsapp):?>
        <a href="https://wa.me/<?=$whatsapp?>" target="_blank" class="btn-hero-secondary">Escribir al vendedor</a>
        <?php endif;?>
      </div>
      <!-- Stats -->
      <div class="hero-stats">
        <div class="hero-stat">
          <span class="hero-stat-num"><?=$numProductos?></span>
          <span class="hero-stat-label">Productos</span>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-row">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <span class="hero-stat-num">5.0</span>
          </div>
          <span class="hero-stat-label">Calificación</span>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-row">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <span class="hero-stat-label">Verificado</span>
        </div>
      </div>
    </div>
    <!-- 3 tarjetas apiladas derecha -->
    <div class="hero-cards-stack">
      <div class="hero-card-ghost-2"></div>
      <div class="hero-card-ghost-1"></div>
      <div class="hero-card" id="hero-card-main">
        <div class="hero-card-img" id="hero-card-img"><div class="no-img-hero">🏪</div></div>
        <div class="hero-card-body">
          <div class="hero-card-price" id="hero-card-price">—</div>
          <div class="hero-card-name" id="hero-card-name">—</div>
        </div>
        <?php if($whatsapp):?>
        <button class="hero-card-wa" onclick="window.open('https://wa.me/<?=$whatsapp?>','_blank')">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.557 4.126 1.533 5.862L.054 23.447a.5.5 0 0 0 .631.553l5.801-1.57A11.939 11.939 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
          WhatsApp
        </button>
        <?php endif;?>
      </div>
    </div>
  </div>
</section>

<!-- TRUST BAR -->
<div class="trust-bar">
  <div class="trust-inner">
    <div class="trust-item">
      <div class="trust-icon orange">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      </div>
      <div class="trust-text"><strong>Envío Rápido</strong><span>Coordinamos contigo</span></div>
    </div>
    <div class="trust-item">
      <div class="trust-icon blue">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      </div>
      <div class="trust-text"><strong>Compra Segura</strong><span>Vendedor verificado</span></div>
    </div>
    <div class="trust-item">
      <div class="trust-icon teal">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      </div>
      <div class="trust-text"><strong>Soporte Directo</strong><span>Respuesta por WhatsApp</span></div>
    </div>
    <div class="trust-item">
      <div class="trust-icon green">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <div class="trust-text"><strong>Calidad Garantizada</strong><span>Productos seleccionados</span></div>
    </div>
  </div>
</div>

<!-- PRODUCTS -->
<div class="products-section" id="productos">
  <div class="products-header">
    <div class="products-title">Nuestros Productos</div>
    <span class="products-count-label" id="result-label"></span>
  </div>
  <div class="products-grid" id="skeleton-grid">
    <?php for($sk=0;$sk<8;$sk++):?>
    <div class="skeleton-card"><div class="skeleton-img"></div><div class="skeleton-body"><div class="skeleton-line" style="width:80%"></div><div class="skeleton-line" style="width:50%"></div><div class="skeleton-line" style="width:62%;height:14px"></div></div></div>
    <?php endfor;?>
  </div>
  <div class="products-grid" id="products-grid" style="display:none"></div>
  <div class="no-products" id="no-products" style="display:none">
    <div>😕</div><p>No encontramos productos.</p>
  </div>
</div>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-top">
    <div>
      <div class="footer-logo">
        <?php if($logoUrl):?><img src="<?=htmlspecialchars($logoUrl)?>" alt=""><?php else:?><div class="footer-logo-icon"><?=strtoupper(substr($nombreTienda,0,1))?></div><?php endif;?>
        <div class="footer-logo-text">
          <strong><?=htmlspecialchars($nombreTienda)?></strong>
          <span>Tienda Online</span>
        </div>
      </div>
      <?php if($whatsapp):?>
      <a href="https://wa.me/<?=$whatsapp?>" target="_blank" class="footer-wa-btn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.557 4.126 1.533 5.862L.054 23.447a.5.5 0 0 0 .631.553l5.801-1.57A11.939 11.939 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
        WhatsApp
      </a>
      <?php endif;?>
    </div>
    <div class="footer-col">
      <h4>Tienda</h4>
      <a href="#productos" onclick="filtrarCat('')">Todos los productos</a>
      <?php foreach(array_slice($categorias,0,4) as $cat):?>
      <a href="#productos" onclick="filtrarCat('<?=htmlspecialchars($cat,ENT_QUOTES)?>')"><?=htmlspecialchars($cat)?></a>
      <?php endforeach;?>
    </div>
    <div class="footer-col">
      <h4>Ayuda</h4>
      <?php if($whatsapp):?><a href="https://wa.me/<?=$whatsapp?>" target="_blank">Contactar vendedor</a><?php endif;?>
      <a href="#">Preguntas frecuentes</a>
      <a href="#">Política de devoluciones</a>
    </div>
    <div class="footer-col">
      <h4>Plataforma</h4>
      <a href="https://komercia.online" target="_blank">Komercia</a>
      <a href="/registro" target="_blank">Crear mi tienda</a>
      <a href="/login" target="_blank">Panel de vendedor</a>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© <?=date('Y')?> <?=htmlspecialchars($nombreTienda)?>. Todos los derechos reservados.</span>
    <span>Tienda creada con <a href="https://komercia.online" target="_blank">Komercia</a> ✏️</span>
  </div>
</footer>

<!-- PRODUCT MODAL -->
<div class="modal-overlay" id="product-modal-overlay" onclick="handleModalOverlayClick(event)">
  <div class="product-modal" id="product-modal">
    <button class="modal-close" onclick="cerrarModal()">✕</button>
    <div class="gallery-wrap" id="gallery-wrap"><img class="gallery-main" id="gallery-main" src="" alt=""></div>
    <div class="gallery-thumbs" id="gallery-thumbs"></div>
    <div class="modal-body">
      <div class="modal-nombre" id="modal-nombre"></div>
      <div class="modal-precio" id="modal-precio"></div>
      <div class="modal-stock" id="modal-stock"></div>
      <div class="modal-desc" id="modal-desc"></div>
      <div class="promo-section" id="promo-section" style="display:none">
        <div class="promo-label">Variantes / Promociones</div>
        <table class="promo-table"><thead><tr><th>Opción</th><th>Precio</th><th>Detalle</th></tr></thead><tbody id="promo-tbody"></tbody></table>
      </div>
      <div class="add-section">
        <div class="qty-wrap">
          <button onclick="changeQty(-1)">−</button>
          <span class="qty-num" id="qty-num">1</span>
          <button onclick="changeQty(1)">+</button>
        </div>
        <button class="btn-add-cart" id="btn-add-cart" onclick="addToCart()">🛒 Agregar al carrito</button>
        <a class="btn-ver-detalle" id="btn-ver-detalle" href="#">🔍 Ver detalle</a>
      </div>
    </div>
  </div>
</div>

<!-- CART DRAWER -->
<div class="drawer-overlay" id="drawer-overlay" onclick="cerrarCarrito()"></div>
<div class="cart-drawer" id="cart-drawer">
  <div class="cart-header"><h2>🛒 Mi carrito</h2><button class="cart-close" onclick="cerrarCarrito()">✕</button></div>
  <div class="cart-items" id="cart-items"></div>
  <div class="cart-footer" id="cart-footer" style="display:none">
    <div class="cart-total"><span>Total</span><span id="cart-total-price">S/. 0.00</span></div>
    <div class="checkout-opts">
      <?php if($whatsapp):?>
      <button class="co-btn co-wsp" onclick="enviarWhatsApp()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.557 4.126 1.533 5.862L.054 23.447a.5.5 0 0 0 .631.553l5.801-1.57A11.939 11.939 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
        WhatsApp
      </button>
      <?php endif;?>
      <button class="co-btn co-form" onclick="abrirFormModal()">📋 Pedido</button>
      <button class="co-btn co-pay" disabled>💳 Pagar<span class="co-soon">pronto</span></button>
    </div>
  </div>
</div>

<!-- FORM MODAL -->
<div class="form-modal" id="form-modal">
  <div class="form-modal-box">
    <h2>📋 Completa tu pedido</h2>
    <div class="fm-group"><label>Nombre completo *</label><input type="text" id="fm-nombre" placeholder="Juan Pérez"></div>
    <div class="fm-group"><label>Teléfono *</label><input type="tel" id="fm-tel" placeholder="+51 999 999 999"></div>
    <div class="fm-group"><label>Dirección de entrega</label><input type="text" id="fm-dir" placeholder="Av. Principal 123, Lima"></div>
    <div class="fm-group"><label>Notas adicionales</label><textarea id="fm-notas" placeholder="Horario preferido, referencias..."></textarea></div>
    <?php if($deliveryTipo==='costo_fijo'&&$deliveryPrecio>0):?>
    <div style="background:#1a1a1a;border-radius:10px;padding:11px;font-size:.85rem;color:#888">🚚 Costo de envío: <strong style="color:#fff">S/. <?=number_format($deliveryPrecio,2)?></strong></div>
    <?php elseif($deliveryTipo==='gratis'):?>
    <div style="background:rgba(34,197,94,.1);border-radius:10px;padding:11px;font-size:.85rem;color:#22c55e">✅ Envío gratis incluido</div>
    <?php endif;?>
    <div class="fm-actions">
      <button class="fm-cancel" onclick="cerrarFormModal()">Cancelar</button>
      <button class="fm-submit" id="fm-submit-btn" onclick="enviarFormulario()">Enviar pedido ✓</button>
    </div>
  </div>
</div>

<!-- FLOATING WhatsApp -->
<?php if($whatsapp):?>
<a href="https://wa.me/<?=$whatsapp?>" target="_blank" class="wa-float" title="Soporte por WhatsApp">
  <svg width="30" height="30" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path fill="#fff" d="M16 1C7.716 1 1 7.716 1 16c0 2.628.672 5.1 1.848 7.258L1 31l7.98-1.824A14.937 14.937 0 0 0 16 31c8.284 0 15-6.716 15-15S24.284 1 16 1z"/>
    <path fill="#25D366" d="M16 3.5c-6.904 0-12.5 5.596-12.5 12.5 0 2.31.63 4.47 1.726 6.326L3.5 28.5l6.35-1.664A12.46 12.46 0 0 0 16 28.5c6.904 0 12.5-5.596 12.5-12.5S22.904 3.5 16 3.5z"/>
    <path fill="#fff" d="M21.98 19.44c-.3-.15-1.77-.873-2.044-.972-.274-.1-.473-.15-.672.15-.2.298-.772.972-.946 1.172-.174.2-.348.224-.648.075-.3-.15-1.265-.466-2.41-1.485-.89-.793-1.49-1.772-1.665-2.072-.174-.3-.018-.462.13-.61.135-.135.3-.35.45-.524.15-.175.2-.3.3-.5.1-.2.05-.374-.025-.524-.075-.15-.672-1.62-.92-2.22-.243-.583-.49-.504-.673-.513a12.72 12.72 0 0 0-.573-.01c-.2 0-.523.074-.797.374-.274.3-1.047 1.022-1.047 2.492s1.072 2.892 1.22 3.092c.15.2 2.108 3.22 5.107 4.514.714.308 1.27.492 1.705.63.716.228 1.368.195 1.883.118.574-.085 1.77-.724 2.02-1.423.25-.7.25-1.3.175-1.423-.075-.125-.274-.2-.573-.35z"/>
  </svg>
</a>
<?php endif;?>

<button class="back-top" id="back-top" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>
<div class="toast" id="toast"></div>

<script>
const tienda   = <?=$tiendaJson?>;
const productos = <?=$productosJson?>;
const SLUG      = <?=json_encode($slug)?>;
const CART_KEY  = 'cart_' + SLUG;
let cart = [];
try { cart = JSON.parse(localStorage.getItem(CART_KEY)||'[]'); } catch(e){ cart=[]; }
let currentProduct=null, currentPromo=null, qty=1, catActual='', searchTerm='';

// ── HERO CARD ──────────────────────────────────────────────────
function renderHeroCard(){
  if(!productos.length) return;
  const p = productos[0];
  document.getElementById('hero-card-price').textContent = 'S/. '+fmt(p.precio);
  document.getElementById('hero-card-name').textContent  = p.nombre;
  if(p.imagen){
    document.getElementById('hero-card-img').innerHTML = `<img src="${esc(p.imagen)}" alt="${esc(p.nombre)}">`;
  }
}

// ── CAROUSEL ──────────────────────────────────────────────────
let carouselIdx=0;
const carouselTotal=<?=count($banners)?>;
let carouselTimer=null;
function carouselGoTo(idx){
  carouselIdx=idx;
  const t=document.getElementById('carousel-track');
  if(t) t.style.transform=`translateX(-${idx*100}%)`;
  document.querySelectorAll('.carousel-dot').forEach((d,i)=>d.classList.toggle('active',i===idx));
}
function carouselNav(dir){ carouselGoTo((carouselIdx+dir+carouselTotal)%carouselTotal); resetCarouselTimer(); }
function resetCarouselTimer(){ clearInterval(carouselTimer); if(carouselTotal>1) carouselTimer=setInterval(()=>carouselNav(1),4500); }
if(carouselTotal>1) resetCarouselTimer();

// ── RENDER PRODUCTS ───────────────────────────────────────────
function norm(s){ return (s||'').toLowerCase().normalize('NFD').replace(/\p{Mn}/gu,''); }
function getFiltered(){
  const st=norm(searchTerm);
  return productos.filter(p=>{
    const mc=!catActual||p.categoria===catActual;
    const mt=!st||norm(p.nombre).includes(st)||norm(p.descripcion||'').includes(st);
    return mc&&mt;
  });
}
const STARS_SVG='<svg width="11" height="11" viewBox="0 0 24 24" fill="#f59e0b" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
const CART_SVG='🛒 Comprar';
function renderProductos(){
  const filtered=getFiltered();
  const grid=document.getElementById('products-grid');
  const noP=document.getElementById('no-products');
  const label=document.getElementById('result-label');
  const skel=document.getElementById('skeleton-grid');
  skel.style.display='none';
  label.textContent=filtered.length+(filtered.length===1?' producto':' productos');
  if(!filtered.length){ grid.style.display='none'; noP.style.display='block'; return; }
  noP.style.display='none'; grid.style.display='grid';
  grid.innerHTML=filtered.map((p,i)=>{
    const ob=p.stock===0;
    const stockBadge=ob?'<span class="stock-badge stock-out">Sin stock</span>':p.stock>0&&p.stock<=5?`<span class="stock-badge stock-low">Solo ${p.stock}</span>`:'';
    const stockText=ob?'Sin stock':`En stock (${p.stock})`;
    const detailUrl=`/tienda/${SLUG}/producto/${p.id}`;
    return `<div class="product-card" onclick="window.location.href='${detailUrl}'">
      <div class="product-card-img">
        ${p.imagen?`<img src="${esc(p.imagen)}" alt="${esc(p.nombre)}" loading="lazy">`:'<div class="no-img">🖼️</div>'}
        ${stockBadge}
      </div>
      <div class="product-card-body">
        ${p.categoria?`<div class="product-card-cat">${esc(p.categoria)}</div>`:''}
        <div class="product-card-name">${esc(p.nombre)}</div>
        <div class="product-card-stars">${STARS_SVG.repeat(5)}<span>(nuevo)</span></div>
        <div class="product-card-price">S/. ${fmt(p.precio)}</div>
        <div class="product-card-footer">
          <span class="product-card-stock">${stockText}</span>
          <button class="product-card-cart-btn" ${ob?'disabled':''} onclick="event.stopPropagation();addToCartById('${p.id}')">${CART_SVG}</button>
        </div>
      </div>
    </div>`;
  }).join('');
}
let dropIdx=-1;
function norm(s){ return (s||'').toLowerCase().normalize('NFD').replace(/\p{Mn}/gu,''); }
function buscarAuto(v){
  const dd=document.getElementById('search-dropdown');
  const q=norm(v.trim());
  if(!q){ dd.classList.remove('open'); dd.innerHTML=''; dropIdx=-1; return; }
  const matches=productos.filter(p=>norm(p.nombre).includes(q)).slice(0,8);
  if(!matches.length){
    dd.innerHTML='<div class="search-drop-empty">Sin resultados para "'+v+'"</div>';
    dd.classList.add('open'); return;
  }
  dd.innerHTML=matches.map((p,i)=>`
    <div class="search-drop-item" data-id="${p.id}" onmousedown="seleccionarProducto('${p.id}')">
      ${p.imagen?`<img class="search-drop-img" src="${esc(p.imagen)}" alt="">`:'<div class="search-drop-img"></div>'}
      <div class="search-drop-info">
        <div class="search-drop-name">${esc(p.nombre)}</div>
        <div class="search-drop-price">S/. ${fmt(p.precio)}</div>
      </div>
    </div>`).join('');
  dd.classList.add('open'); dropIdx=-1;
}
function cerrarDrop(){
  const dd=document.getElementById('search-dropdown');
  dd.classList.remove('open'); dropIdx=-1;
}
function navDrop(e){
  const items=document.querySelectorAll('.search-drop-item');
  if(!items.length) return;
  if(e.key==='ArrowDown'){ e.preventDefault(); dropIdx=Math.min(dropIdx+1,items.length-1); items.forEach((el,i)=>el.style.background=i===dropIdx?'rgba(255,255,255,.08)':''); }
  else if(e.key==='ArrowUp'){ e.preventDefault(); dropIdx=Math.max(dropIdx-1,0); items.forEach((el,i)=>el.style.background=i===dropIdx?'rgba(255,255,255,.08)':''); }
  else if(e.key==='Enter' && dropIdx>=0){ items[dropIdx].dispatchEvent(new Event('mousedown')); }
  else if(e.key==='Escape'){ cerrarDrop(); }
}
function seleccionarProducto(id){
  cerrarDrop();
  document.getElementById('search-input').value='';
  // Abrir modal del producto
  const p=productos.find(x=>x.id===id); if(!p) return;
  const i=productos.indexOf(p);
  abrirProducto(i);
}
function filtrar(){ /* legacy */ }
function filtrarMobile(v){
  const dd=document.getElementById('search-dropdown-mobile');
  const q=norm((v||'').trim());
  if(!dd) return;
  if(!q){ dd.classList.remove('open'); dd.innerHTML=''; return; }
  const matches=productos.filter(p=>norm(p.nombre).includes(q)).slice(0,6);
  if(!matches.length){ dd.innerHTML='<div class="search-drop-empty">Sin resultados</div>'; dd.classList.add('open'); return; }
  dd.innerHTML=matches.map(p=>`
    <div class="search-drop-item" onmousedown="seleccionarProducto('${p.id}')">
      ${p.imagen?`<img class="search-drop-img" src="${esc(p.imagen)}" alt="">`:'<div class="search-drop-img"></div>'}
      <div class="search-drop-info">
        <div class="search-drop-name">${esc(p.nombre)}</div>
        <div class="search-drop-price">S/. ${fmt(p.precio)}</div>
      </div>
    </div>`).join('');
  dd.classList.add('open');
}
function filtrarCat(cat){
  catActual=cat;
  document.querySelectorAll('.cat-btn').forEach(b=>b.classList.toggle('active',b.dataset.cat===cat));
  renderProductos();
}

// ── PRODUCT MODAL ─────────────────────────────────────────────
function abrirProducto(i){
  const filtered=getFiltered();
  currentProduct=filtered[i]!==undefined?filtered[i]:productos[i];
  currentPromo=null; qty=1;
  document.getElementById('qty-num').textContent=qty;
  const allMedia=[
    ...(currentProduct.imagenes||(currentProduct.imagen?[currentProduct.imagen]:[])).map(u=>({type:'img',url:u})),
    ...(currentProduct.videos||[]).map(u=>({type:'video',url:u})),
  ];
  const wrap=document.getElementById('gallery-wrap'),main=document.getElementById('gallery-main'),thumbs=document.getElementById('gallery-thumbs');
  if(allMedia.length){
    showMedia(allMedia[0],wrap,main);
    thumbs.innerHTML=allMedia.map((m,idx)=>m.type==='img'
      ?`<img class="gallery-thumb ${idx===0?'active':''}" src="${m.url}" onclick="switchMedia(${idx})" loading="lazy">`
      :`<div class="gallery-thumb-video ${idx===0?'active':''}" onclick="switchMedia(${idx})">▶</div>`
    ).join('');
    thumbs.style.display=allMedia.length>1?'flex':'none';
    wrap._media=allMedia; wrap._idx=0;
  } else {
    wrap.innerHTML='<div style="width:100%;aspect-ratio:1/1;background:#111;display:flex;align-items:center;justify-content:center;font-size:4rem;color:#222">🖼️</div>';
    thumbs.style.display='none';
  }
  document.getElementById('modal-nombre').textContent=currentProduct.nombre;
  document.getElementById('modal-precio').textContent='S/. '+fmt(currentProduct.precio);
  document.getElementById('modal-desc').textContent=currentProduct.descripcion||'';
  const stockEl=document.getElementById('modal-stock');
  if(currentProduct.stock===0){
    stockEl.innerHTML='<span style="background:rgba(239,68,68,.15);color:#ef4444;padding:3px 10px;border-radius:6px;font-size:.78rem;font-weight:700">😔 Sin stock</span>';
    document.getElementById('btn-add-cart').disabled=true;
  } else if(currentProduct.stock>0&&currentProduct.stock<=5){
    stockEl.innerHTML=`<span style="background:rgba(245,158,11,.15);color:#f59e0b;padding:3px 10px;border-radius:6px;font-size:.78rem;font-weight:700">⚠️ Solo ${currentProduct.stock} disponibles</span>`;
    document.getElementById('btn-add-cart').disabled=false;
  } else { stockEl.innerHTML=''; document.getElementById('btn-add-cart').disabled=false; }
  document.getElementById('btn-ver-detalle').href='/tienda/'+SLUG+'/producto/'+currentProduct.id;
  const promos=currentProduct.promociones||[];
  const ps=document.getElementById('promo-section');
  if(promos.length){
    ps.style.display='block';
    document.getElementById('promo-tbody').innerHTML=promos.map((p,i)=>`<tr class="promo-row" onclick="selectPromo(${i})"><td>${esc(p.nombre)}</td><td class="promo-price">S/. ${fmt(p.precio)}</td><td style="color:#555;font-size:.78rem">${esc(p.detalle)}</td></tr>`).join('');
  } else ps.style.display='none';
  document.getElementById('product-modal-overlay').classList.add('open');
  document.body.style.overflow='hidden';
}
function showMedia(m,wrap,main){
  if(m.type==='img'){
    let img=wrap.querySelector('img.gallery-main');
    if(!img){wrap.innerHTML='';img=document.createElement('img');img.className='gallery-main';img.id='gallery-main';wrap.appendChild(img);}
    img.src=m.url;
  } else {
    const vid=document.createElement('video');
    vid.className='gallery-video';vid.controls=true;vid.src=m.url;
    wrap.innerHTML='';wrap.appendChild(vid);
  }
}
function switchMedia(idx){
  const wrap=document.getElementById('gallery-wrap'),media=wrap._media||[];
  wrap._idx=idx; showMedia(media[idx],wrap,null);
  document.querySelectorAll('.gallery-thumb,.gallery-thumb-video').forEach((t,i)=>t.classList.toggle('active',i===idx));
}
function selectPromo(i){
  currentPromo=(currentProduct.promociones||[])[i];
  document.querySelectorAll('.promo-row').forEach((r,ri)=>r.classList.toggle('selected',ri===i));
  document.getElementById('modal-precio').textContent='S/. '+fmt(currentPromo.precio);
}
function changeQty(d){ qty=Math.max(1,Math.min(currentProduct?.stock||99,qty+d)); document.getElementById('qty-num').textContent=qty; }
function cerrarModal(){ document.getElementById('product-modal-overlay').classList.remove('open'); document.body.style.overflow=''; currentProduct=null; currentPromo=null; }
function handleModalOverlayClick(e){ if(e.target===document.getElementById('product-modal-overlay')) cerrarModal(); }

// ── CART ──────────────────────────────────────────────────────
function addToCart(){
  if(!currentProduct||currentProduct.stock===0) return showToast('Sin stock disponible');
  const nombre=currentPromo?currentProduct.nombre+' – '+currentPromo.nombre:currentProduct.nombre;
  const precio=currentPromo?currentPromo.precio:currentProduct.precio;
  const key=currentProduct.id+(currentPromo?'_'+currentPromo.nombre:'');
  const idx=cart.findIndex(c=>c.key===key);
  if(idx>=0) cart[idx].qty+=qty;
  else cart.push({key,prodId:currentProduct.id,nombre,precio,imagen:currentProduct.imagen||'',qty});
  saveCart(); cerrarModal(); abrirCarrito(); showToast('✅ Agregado al carrito');
}
function addToCartById(id){
  const p=productos.find(x=>x.id===id); if(!p||p.stock===0) return;
  const idx=cart.findIndex(c=>c.key===p.id);
  if(idx>=0) cart[idx].qty+=1;
  else cart.push({key:p.id,prodId:p.id,nombre:p.nombre,precio:p.precio,imagen:p.imagen||'',qty:1});
  saveCart(); abrirCarrito(); showToast('✅ Agregado al carrito');
}
function addToCartDirect(i){
  const p=productos[i]; if(!p||p.stock===0) return;
  addToCartById(p.id);
}
function saveCart(){ localStorage.setItem(CART_KEY,JSON.stringify(cart)); updateBadge(); }
function updateBadge(){
  const total=cart.reduce((s,c)=>s+c.qty,0);
  const b=document.getElementById('cart-badge');
  b.textContent=total; b.style.display=total>0?'flex':'none';
}
function abrirCarrito(){ renderCarrito(); document.getElementById('cart-drawer').classList.add('open'); document.getElementById('drawer-overlay').classList.add('show'); document.body.style.overflow='hidden'; }
function cerrarCarrito(){ document.getElementById('cart-drawer').classList.remove('open'); document.getElementById('drawer-overlay').classList.remove('show'); document.body.style.overflow=''; }
function renderCarrito(){
  const container=document.getElementById('cart-items'),footer=document.getElementById('cart-footer');
  if(!cart.length){
    container.innerHTML='<div class="cart-empty"><div style="font-size:2.5rem">🛒</div><p style="margin-top:10px;color:#444">Tu carrito está vacío</p></div>';
    footer.style.display='none'; return;
  }
  footer.style.display='block';
  container.innerHTML=cart.map((item,i)=>`<div class="cart-item">
    ${item.imagen?`<img class="cart-item-img" src="${esc(item.imagen)}" alt="">`:'<div class="cart-item-img" style="display:flex;align-items:center;justify-content:center;font-size:1.4rem">🛒</div>'}
    <div class="cart-item-info">
      <div class="cart-item-name">${esc(item.nombre)}</div>
      <div class="cart-item-row">
        <div class="qty-mini"><button onclick="updateQtyCart(${i},-1)">−</button><span>${item.qty}</span><button onclick="updateQtyCart(${i},1)">+</button></div>
        <div class="cart-item-price">S/. ${fmt(item.precio*item.qty)}</div>
        <button class="cart-item-del" onclick="removeFromCart(${i})" title="Eliminar">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        </button>
      </div>
    </div>
  </div>`).join('');
  const total=cart.reduce((s,c)=>s+c.precio*c.qty,0);
  let txt='S/. '+fmt(total);
  if(tienda.delivery_tipo==='costo_fijo'&&tienda.delivery_precio>0) txt+=' + S/. '+fmt(tienda.delivery_precio)+' envío';
  document.getElementById('cart-total-price').textContent=txt;
}
function updateQtyCart(i,d){ cart[i].qty=Math.max(1,cart[i].qty+d); saveCart(); renderCarrito(); }
function removeFromCart(i){ cart.splice(i,1); saveCart(); renderCarrito(); }

// ── CHECKOUT ──────────────────────────────────────────────────
function enviarWhatsApp(){
  const lines=cart.map(c=>`• ${c.nombre} x${c.qty} = S/. ${fmt(c.precio*c.qty)}`);
  const total=cart.reduce((s,c)=>s+c.precio*c.qty,0);
  let msg=`Hola, quiero hacer un pedido en *${tienda.nombre}*:\n\n${lines.join('\n')}\n\n*Total: S/. ${fmt(total)}*`;
  if(tienda.delivery_tipo==='gratis') msg+='\n\n🎁 Envío gratis';
  else if(tienda.delivery_tipo==='costo_fijo'&&tienda.delivery_precio>0) msg+=`\n📦 Envío: S/. ${fmt(tienda.delivery_precio)}`;
  window.open('https://wa.me/'+tienda.telefono+'?text='+encodeURIComponent(msg),'_blank');
}
function abrirFormModal(){ if(!cart.length) return; cerrarCarrito(); document.getElementById('form-modal').classList.add('open'); }
function cerrarFormModal(){ document.getElementById('form-modal').classList.remove('open'); }
async function enviarFormulario(){
  const nombre=document.getElementById('fm-nombre').value.trim();
  const tel=document.getElementById('fm-tel').value.trim();
  if(!nombre||!tel) return showToast('Por favor completa nombre y teléfono.');
  const btn=document.getElementById('fm-submit-btn');
  btn.disabled=true; btn.textContent='Enviando...';
  const items=cart.map(c=>({nombre:c.nombre,qty:c.qty,precio:c.precio}));
  const total=cart.reduce((s,c)=>s+c.precio*c.qty,0)+(tienda.delivery_tipo==='costo_fijo'?tienda.delivery_precio:0);
  const fd=new FormData();
  fd.append('slug',SLUG);fd.append('nombre',nombre);fd.append('telefono',tel);
  fd.append('direccion',document.getElementById('fm-dir').value.trim());
  fd.append('notas',document.getElementById('fm-notas').value.trim());
  fd.append('items',JSON.stringify(items));fd.append('total',total.toFixed(2));
  try{
    const res=await fetch('/api/pedidos?accion=crear',{method:'POST',body:fd});
    const data=await res.json();
    if(data.ok){ cart=[];saveCart();cerrarFormModal();cerrarCarrito();showToast('🎉 ¡Pedido enviado!'); }
    else showToast('Error: '+(data.error||'No se pudo enviar'));
  }catch(e){showToast('Error de conexión.');}
  btn.disabled=false;btn.textContent='Enviar pedido ✓';
}

// ── UTILS ─────────────────────────────────────────────────────
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;')}
function fmt(n){return parseFloat(n||0).toFixed(2)}
function showToast(msg){
  const t=document.getElementById('toast');
  t.textContent=msg;t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'),3000);
}
window.addEventListener('scroll',()=>{
  document.getElementById('back-top').classList.toggle('show',window.scrollY>400);
});

// ── INIT ──────────────────────────────────────────────────────
updateBadge();
renderHeroCard();
renderProductos();
</script>
</body>
</html>
