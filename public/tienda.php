<?php
require_once '/var/www/komercia/config/firebase.php';

$slug = $_GET['slug'] ?? '';

if (!$slug || !preg_match('/^[a-z0-9-]+$/', $slug)) {
    http_response_code(404);
    exit('Tienda no encontrada');
}

// Cargar datos de la tienda
$tiendaDoc = firestoreRequest('GET', "tiendas/{$slug}");

if (isset($tiendaDoc['error'])) {
    http_response_code(404);
    exit('Tienda no encontrada');
}

$f = $tiendaDoc['fields'] ?? [];
$nombreTienda   = $f['nombreTienda']['stringValue'] ?? $f['nombre']['stringValue'] ?? ucfirst($slug);
$uid            = $f['uid']['stringValue'] ?? '';
$telefono       = preg_replace('/[^0-9]/', '', $f['telefono']['stringValue'] ?? '');
$descripcion    = $f['descripcion']['stringValue'] ?? 'Bienvenido a nuestra tienda';
$logoUrl        = $f['logo']['stringValue'] ?? '';
$colorPrimario  = $f['color_primario']['stringValue'] ?? '#ff6a00';
// Seguridad: solo aceptar colores hex válidos
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $colorPrimario)) $colorPrimario = '#ff6a00';
// Calcular versión más oscura para hover
$r = hexdec(substr($colorPrimario,1,2));
$g = hexdec(substr($colorPrimario,3,2));
$b = hexdec(substr($colorPrimario,5,2));
$colorHover = sprintf('#%02x%02x%02x', max(0,$r-30), max(0,$g-30), max(0,$b-30));

// Cargar productos
$productos = [];
if ($uid) {
    $productosRes = firestoreRequest('GET', "comerciantes/{$uid}/productos");
    if (!isset($productosRes['error']) && isset($productosRes['documents'])) {
        foreach ($productosRes['documents'] as $doc) {
            $pf = $doc['fields'] ?? [];
            $productos[] = [
                'nombre'      => $pf['nombre']['stringValue'] ?? '',
                'precio'      => $pf['precio']['doubleValue'] ?? $pf['precio']['integerValue'] ?? 0,
                'descripcion' => $pf['descripcion']['stringValue'] ?? '',
                'imagen'      => $pf['imagen']['stringValue'] ?? '',
                'stock'       => $pf['stock']['integerValue'] ?? 0,
                'categoria'   => $pf['categoria']['stringValue'] ?? '',
            ];
        }
    }
}

$tiendaJson = json_encode([
    'nombre'   => $nombreTienda,
    'slug'     => $slug,
    'telefono' => $telefono,
]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($nombreTienda) ?> | Komercia</title>
<meta name="description" content="<?= htmlspecialchars($descripcion) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  /* ===== RESET & BASE ===== */
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --orange: <?= $colorPrimario ?>;
    --orange-hover: <?= $colorHover ?>;
    --orange-light: <?= 'rgba(' . $r . ',' . $g . ',' . $b . ',0.12)' ?>;
    --dark: #0a0a0a;
    --dark2: #111111;
    --dark3: #1a1a1a;
    --dark4: #222222;
    --dark5: #2a2a2a;
    --border: rgba(255,255,255,0.08);
    --text: #f0f0f0;
    --text2: #aaaaaa;
    --text3: #666666;
    --white: #ffffff;
    --radius: 12px;
    --shadow: 0 4px 24px rgba(0,0,0,0.4);
  }
  html { scroll-behavior: smooth; }
  body {
    font-family: 'Inter', system-ui, sans-serif;
    background: var(--dark);
    color: var(--text);
    min-height: 100vh;
    line-height: 1.5;
  }
  a { color: inherit; text-decoration: none; }
  img { max-width: 100%; display: block; }

  /* ===== TOP BAR ===== */
  .topbar {
    background: var(--dark2);
    border-bottom: 1px solid var(--border);
    padding: 8px 0;
    font-size: 12px;
    color: var(--text2);
  }
  .topbar-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .topbar-left { display: flex; gap: 16px; align-items: center; }
  .topbar-left span { display: flex; align-items: center; gap: 5px; }
  .topbar-right a {
    color: var(--orange);
    font-weight: 500;
    transition: opacity .2s;
  }
  .topbar-right a:hover { opacity: .8; }

  /* ===== NAVBAR ===== */
  .navbar {
    background: var(--dark2);
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 20px rgba(0,0,0,0.5);
  }
  .navbar-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    align-items: center;
    gap: 24px;
    height: 68px;
  }
  .brand {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
  }
  .brand-icon {
    width: 40px;
    height: 40px;
    background: var(--orange);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -1px;
  }
  .brand-name {
    font-size: 18px;
    font-weight: 700;
    color: var(--white);
    line-height: 1.1;
  }
  .brand-name small {
    display: block;
    font-size: 10px;
    color: var(--orange);
    font-weight: 500;
    letter-spacing: .5px;
    text-transform: uppercase;
  }
  .search-bar {
    flex: 1;
    position: relative;
    max-width: 560px;
  }
  .search-bar input {
    width: 100%;
    background: var(--dark4);
    border: 1.5px solid var(--border);
    border-radius: 50px;
    padding: 11px 48px 11px 20px;
    color: var(--text);
    font-size: 14px;
    font-family: inherit;
    transition: border-color .2s;
    outline: none;
  }
  .search-bar input::placeholder { color: var(--text3); }
  .search-bar input:focus { border-color: var(--orange); }
  .search-btn {
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
    background: var(--orange);
    border: none;
    border-radius: 50%;
    width: 34px;
    height: 34px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .2s;
  }
  .search-btn:hover { background: var(--orange-hover); }
  .search-btn svg { width: 15px; height: 15px; fill: none; stroke: #fff; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
  .nav-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
  }
  .nav-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    padding: 6px 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: background .2s;
    border: none;
    background: transparent;
    color: var(--text2);
    font-size: 11px;
    font-family: inherit;
  }
  .nav-btn:hover { background: var(--dark4); color: var(--white); }
  .nav-btn svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
  .whatsapp-nav {
    background: #25d366;
    color: #fff;
    border-radius: 8px;
    padding: 9px 16px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
    border: none;
    font-family: inherit;
    transition: background .2s;
  }
  .whatsapp-nav:hover { background: #1fb855; }
  .whatsapp-nav svg { width: 18px; height: 18px; fill: #fff; }

  /* ===== SECONDARY NAV ===== */
  .subnav {
    background: var(--dark3);
    border-bottom: 1px solid var(--border);
  }
  .subnav-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    align-items: center;
    gap: 4px;
    height: 44px;
    overflow-x: auto;
    scrollbar-width: none;
  }
  .subnav-inner::-webkit-scrollbar { display: none; }
  .subnav-item {
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    color: var(--text2);
    cursor: pointer;
    white-space: nowrap;
    transition: all .2s;
    border: 1px solid transparent;
  }
  .subnav-item:hover { color: var(--white); background: var(--dark5); }
  .subnav-item.active { color: var(--orange); border-color: var(--orange-light); background: var(--orange-light); }

  /* ===== HERO ===== */
  .hero {
    background: linear-gradient(135deg, #0f0f0f 0%, #1a0a00 50%, #0f0f0f 100%);
    padding: 60px 20px;
    position: relative;
    overflow: hidden;
  }
  .hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 60% 80% at 70% 50%, rgba(255,106,0,0.15) 0%, transparent 70%);
  }
  .hero-inner {
    max-width: 1280px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    align-items: center;
    position: relative;
    z-index: 1;
  }
  .hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--orange-light);
    border: 1px solid rgba(255,106,0,0.3);
    color: var(--orange);
    font-size: 12px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 20px;
    margin-bottom: 16px;
    letter-spacing: .3px;
    text-transform: uppercase;
  }
  .hero-title {
    font-size: clamp(28px, 4vw, 52px);
    font-weight: 800;
    line-height: 1.1;
    color: var(--white);
    margin-bottom: 16px;
  }
  .hero-title span { color: var(--orange); }
  .hero-sub {
    color: var(--text2);
    font-size: 15px;
    line-height: 1.7;
    margin-bottom: 28px;
    max-width: 420px;
  }
  .hero-ctas { display: flex; gap: 12px; flex-wrap: wrap; }
  .btn-orange {
    background: var(--orange);
    color: #fff;
    border: none;
    padding: 13px 28px;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: all .2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 20px rgba(255,106,0,0.35);
  }
  .btn-orange:hover { background: var(--orange-hover); transform: translateY(-1px); box-shadow: 0 6px 24px rgba(255,106,0,0.45); }
  .btn-ghost {
    background: transparent;
    color: var(--text);
    border: 1.5px solid var(--border);
    padding: 12px 24px;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: all .2s;
  }
  .btn-ghost:hover { border-color: var(--orange); color: var(--orange); }
  .hero-stats {
    display: flex;
    gap: 24px;
    margin-top: 32px;
  }
  .stat { text-align: center; }
  .stat-num { font-size: 22px; font-weight: 800; color: var(--orange); }
  .stat-label { font-size: 11px; color: var(--text3); text-transform: uppercase; letter-spacing: .5px; }
  .hero-visual {
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .hero-cards-stack {
    position: relative;
    width: 300px;
    height: 300px;
  }
  .hero-card-bg {
    position: absolute;
    background: var(--dark3);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
  }
  .hero-card-bg:nth-child(1) { width: 200px; height: 220px; top: 20px; right: 0; transform: rotate(6deg); }
  .hero-card-bg:nth-child(2) { width: 200px; height: 220px; top: 40px; left: 0; transform: rotate(-4deg); opacity: .5; }
  .hero-card-main {
    position: absolute;
    width: 210px;
    background: var(--dark3);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    top: 0; left: 50%; transform: translateX(-50%);
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
  }
  .hero-card-img {
    width: 100%;
    height: 140px;
    background: linear-gradient(135deg, var(--dark4), var(--dark5));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 52px;
  }
  .hero-card-body { padding: 12px; }
  .hero-card-body .price { color: var(--orange); font-size: 18px; font-weight: 800; }
  .hero-card-body .name { font-size: 12px; color: var(--text2); margin-top: 2px; }
  .hero-float-badge {
    position: absolute;
    background: #25d366;
    color: #fff;
    border-radius: 50px;
    padding: 5px 12px;
    font-size: 11px;
    font-weight: 700;
    bottom: 60px;
    right: 10px;
    box-shadow: 0 4px 16px rgba(37,211,102,.4);
  }

  /* ===== TRUST BADGES ===== */
  .trust {
    background: var(--dark3);
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    padding: 20px;
  }
  .trust-inner {
    max-width: 1280px;
    margin: 0 auto;
    display: flex;
    justify-content: space-around;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
  }
  .trust-item {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .trust-icon {
    width: 44px;
    height: 44px;
    background: var(--orange-light);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
  }
  .trust-text strong { display: block; font-size: 13px; font-weight: 700; color: var(--white); }
  .trust-text span { font-size: 11px; color: var(--text3); }

  /* ===== SECTION HEADER ===== */
  .section { padding: 48px 20px; }
  .section-header {
    max-width: 1280px;
    margin: 0 auto 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
  }
  .section-title {
    font-size: 22px;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .section-title::before {
    content: '';
    display: block;
    width: 4px;
    height: 22px;
    background: var(--orange);
    border-radius: 2px;
  }
  .section-link {
    color: var(--orange);
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
  }

  /* ===== PRODUCT GRID ===== */
  .products-grid {
    max-width: 1280px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
  }
  .product-card {
    background: var(--dark3);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    cursor: pointer;
    transition: all .25s;
    position: relative;
    group: true;
  }
  .product-card:hover {
    transform: translateY(-4px);
    border-color: rgba(255,106,0,0.3);
    box-shadow: 0 12px 40px rgba(0,0,0,0.4);
  }
  .product-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: var(--orange);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 4px;
    z-index: 2;
    text-transform: uppercase;
    letter-spacing: .5px;
  }
  .product-img {
    width: 100%;
    aspect-ratio: 1/1;
    object-fit: cover;
    background: var(--dark4);
  }
  .product-img-placeholder {
    width: 100%;
    aspect-ratio: 1/1;
    background: linear-gradient(135deg, var(--dark4) 0%, var(--dark5) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 52px;
    color: var(--text3);
  }
  .product-body { padding: 14px; }
  .product-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 4px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    line-height: 1.4;
    min-height: 36px;
  }
  .product-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 8px;
  }
  .stars { color: #f5c518; font-size: 11px; letter-spacing: 1px; }
  .rating-count { font-size: 11px; color: var(--text3); }
  .product-price { font-size: 20px; font-weight: 800; color: var(--orange); }
  .product-price-old { font-size: 12px; color: var(--text3); text-decoration: line-through; margin-left: 6px; }
  .product-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 10px;
  }
  .product-stock { font-size: 11px; color: var(--text3); }
  .product-stock.low { color: #ff9f43; }
  .add-cart-btn {
    width: 34px;
    height: 34px;
    background: var(--orange);
    border: none;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .2s;
    flex-shrink: 0;
  }
  .add-cart-btn:hover { background: var(--orange-hover); }
  .add-cart-btn svg { width: 16px; height: 16px; fill: none; stroke: #fff; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

  /* ===== EMPTY STATE ===== */
  .empty-state {
    max-width: 1280px;
    margin: 0 auto;
    text-align: center;
    padding: 60px 20px;
    color: var(--text2);
  }
  .empty-icon { font-size: 64px; margin-bottom: 16px; }
  .empty-state h3 { font-size: 18px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
  .empty-state p { font-size: 14px; }

  /* ===== WHATSAPP FLOATING ===== */
  .whatsapp-float {
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 200;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
  }
  .wa-bubble {
    background: var(--dark3);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 10px 14px;
    font-size: 12px;
    color: var(--text2);
    max-width: 200px;
    text-align: right;
    box-shadow: var(--shadow);
    animation: fadeInUp .4s ease;
  }
  .wa-button {
    width: 58px;
    height: 58px;
    background: #25d366;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 6px 24px rgba(37,211,102,0.4);
    transition: transform .2s;
    border: none;
  }
  .wa-button:hover { transform: scale(1.08); }
  .wa-button svg { width: 28px; height: 28px; fill: #fff; }
  @keyframes fadeInUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

  /* ===== PRODUCT MODAL ===== */
  .modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.85);
    z-index: 300;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    backdrop-filter: blur(4px);
    opacity: 0;
    pointer-events: none;
    transition: opacity .25s;
  }
  .modal-overlay.active { opacity: 1; pointer-events: all; }
  .modal {
    background: var(--dark3);
    border: 1px solid var(--border);
    border-radius: 20px;
    max-width: 560px;
    width: 100%;
    overflow: hidden;
    transform: scale(.95);
    transition: transform .25s;
    max-height: 90vh;
    overflow-y: auto;
  }
  .modal-overlay.active .modal { transform: scale(1); }
  .modal-img {
    width: 100%;
    height: 280px;
    object-fit: cover;
    background: var(--dark4);
  }
  .modal-img-placeholder {
    width: 100%;
    height: 280px;
    background: linear-gradient(135deg, var(--dark4), var(--dark5));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 80px;
  }
  .modal-body { padding: 24px; }
  .modal-title { font-size: 22px; font-weight: 800; margin-bottom: 6px; }
  .modal-price { font-size: 28px; font-weight: 800; color: var(--orange); margin-bottom: 14px; }
  .modal-desc { font-size: 14px; color: var(--text2); line-height: 1.7; margin-bottom: 20px; }
  .modal-stock-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.05);
    border-radius: 6px;
    padding: 5px 12px;
    font-size: 12px;
    color: var(--text2);
    margin-bottom: 20px;
  }
  .modal-actions { display: flex; gap: 12px; }
  .modal-close {
    position: absolute;
    top: 14px; right: 14px;
    width: 36px; height: 36px;
    background: rgba(0,0,0,0.5);
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 18px;
    z-index: 10;
  }
  .modal-wrapper { position: relative; }

  /* ===== FOOTER ===== */
  footer {
    background: var(--dark2);
    border-top: 1px solid var(--border);
    padding: 48px 20px 20px;
    margin-top: 48px;
  }
  .footer-inner {
    max-width: 1280px;
    margin: 0 auto;
  }
  .footer-top {
    display: grid;
    grid-template-columns: 1.5fr repeat(3, 1fr);
    gap: 40px;
    margin-bottom: 40px;
  }
  .footer-brand p { color: var(--text3); font-size: 13px; margin-top: 12px; max-width: 240px; line-height: 1.7; }
  .footer-col h4 { font-size: 13px; font-weight: 700; color: var(--white); margin-bottom: 14px; text-transform: uppercase; letter-spacing: .5px; }
  .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 8px; }
  .footer-col ul li a { font-size: 13px; color: var(--text3); transition: color .2s; }
  .footer-col ul li a:hover { color: var(--orange); }
  .footer-bottom {
    border-top: 1px solid var(--border);
    padding-top: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
  }
  .footer-bottom p { font-size: 12px; color: var(--text3); }
  .footer-powered {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--text3);
  }
  .footer-powered a { color: var(--orange); font-weight: 600; }

  /* ===== SEARCH OVERLAY ===== */
  .search-results {
    position: absolute;
    top: calc(100% + 8px);
    left: 0; right: 0;
    background: var(--dark3);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    z-index: 200;
    box-shadow: var(--shadow);
    display: none;
  }
  .search-results.show { display: block; }
  .search-result-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    cursor: pointer;
    transition: background .15s;
    border-bottom: 1px solid var(--border);
  }
  .search-result-item:last-child { border-bottom: none; }
  .search-result-item:hover { background: var(--dark4); }
  .search-result-img {
    width: 40px; height: 40px;
    object-fit: cover;
    border-radius: 6px;
    background: var(--dark5);
    flex-shrink: 0;
  }
  .search-result-name { font-size: 13px; font-weight: 500; }
  .search-result-price { font-size: 13px; color: var(--orange); font-weight: 700; }
  .search-no-results { padding: 20px; text-align: center; color: var(--text3); font-size: 13px; }

  /* ===== CART SIDEBAR ===== */
  .cart-sidebar {
    position: fixed;
    top: 0; right: 0;
    width: 360px;
    height: 100%;
    background: var(--dark3);
    border-left: 1px solid var(--border);
    z-index: 400;
    transform: translateX(100%);
    transition: transform .3s;
    display: flex;
    flex-direction: column;
    box-shadow: -10px 0 40px rgba(0,0,0,0.5);
  }
  .cart-sidebar.open { transform: translateX(0); }
  .cart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    border-bottom: 1px solid var(--border);
  }
  .cart-header h3 { font-size: 17px; font-weight: 700; }
  .cart-close { background: none; border: none; color: var(--text2); cursor: pointer; font-size: 22px; }
  .cart-items { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
  .cart-item {
    display: flex;
    gap: 12px;
    background: var(--dark4);
    border-radius: 10px;
    padding: 10px;
    align-items: center;
  }
  .cart-item-img {
    width: 56px; height: 56px;
    object-fit: cover;
    border-radius: 8px;
    background: var(--dark5);
    flex-shrink: 0;
    font-size: 28px;
    display: flex; align-items: center; justify-content: center;
  }
  .cart-item-info { flex: 1; min-width: 0; }
  .cart-item-name { font-size: 13px; font-weight: 600; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
  .cart-item-price { color: var(--orange); font-size: 14px; font-weight: 700; margin-top: 2px; }
  .cart-qty {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--dark5);
    border-radius: 6px;
    padding: 4px 8px;
  }
  .cart-qty button { background: none; border: none; color: var(--text); font-size: 16px; cursor: pointer; width: 20px; text-align: center; }
  .cart-qty span { font-size: 14px; font-weight: 600; min-width: 20px; text-align: center; }
  .cart-footer {
    padding: 20px;
    border-top: 1px solid var(--border);
  }
  .cart-total { display: flex; justify-content: space-between; font-size: 16px; font-weight: 700; margin-bottom: 16px; }
  .cart-total span:last-child { color: var(--orange); }
  .cart-empty { text-align: center; padding: 60px 20px; color: var(--text3); }
  .cart-empty .icon { font-size: 48px; margin-bottom: 12px; }

  /* ===== RESPONSIVE ===== */
  @media (max-width: 900px) {
    .hero-inner { grid-template-columns: 1fr; }
    .hero-visual { display: none; }
    .footer-top { grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 600px) {
    .topbar { display: none; }
    .navbar-inner { gap: 12px; height: 60px; }
    .brand-name small { display: none; }
    .nav-btn span { display: none; }
    .whatsapp-nav span { display: none; }
    .footer-top { grid-template-columns: 1fr; }
    .section { padding: 32px 16px; }
    .hero { padding: 40px 16px; }
    .products-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .cart-sidebar { width: 100%; }
  }
</style>
</head>
<body>

<!-- TOP BAR -->
<div class="topbar">
  <div class="topbar-inner">
    <div class="topbar-left">
      <span>🚚 Envíos disponibles</span>
      <span>⭐ Productos verificados</span>
    </div>
    <div class="topbar-right">
      <a href="https://komercia.online">Powered by Komercia</a>
    </div>
  </div>
</div>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="navbar-inner">
    <a href="/tienda/<?= htmlspecialchars($slug) ?>" class="brand">
      <?php if ($logoUrl): ?>
        <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" style="width:40px;height:40px;border-radius:10px;object-fit:cover;">
      <?php else: ?>
        <div class="brand-icon"><?= strtoupper(substr($nombreTienda, 0, 1)) ?></div>
      <?php endif; ?>
      <div class="brand-name">
        <?= htmlspecialchars($nombreTienda) ?>
        <small>Tienda Online</small>
      </div>
    </a>

    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="Buscar productos..." autocomplete="off">
      <button class="search-btn">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </button>
      <div class="search-results" id="searchResults"></div>
    </div>

    <div class="nav-actions">
      <button class="nav-btn" onclick="toggleCart()">
        <svg viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        <span id="cartCount" style="font-size:10px;background:var(--orange);color:#fff;border-radius:10px;padding:1px 6px;display:none;position:absolute;top:2px;right:2px;font-weight:700;"></span>
        Carrito
      </button>
      <?php if ($telefono): ?>
      <button class="whatsapp-nav" onclick="openWhatsApp()">
        <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        <span>Contactar</span>
      </button>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- SUBNAV -->
<div class="subnav">
  <div class="subnav-inner" id="subnavInner">
    <div class="subnav-item active" onclick="filterCategory('', this)">Todos los productos</div>
    <?php
      $categorias = array_unique(array_filter(array_column($productos, 'categoria')));
      sort($categorias);
      foreach ($categorias as $cat):
    ?>
    <div class="subnav-item" onclick="filterCategory('<?= htmlspecialchars($cat) ?>', this)"><?= htmlspecialchars($cat) ?></div>
    <?php endforeach; ?>
  </div>
</div>

<!-- HERO -->
<section class="hero">
  <div class="hero-inner">
    <div class="hero-content">
      <div class="hero-badge">🔥 Tienda Oficial</div>
      <h1 class="hero-title">
        Bienvenido a<br>
        <span><?= htmlspecialchars($nombreTienda) ?></span>
      </h1>
      <p class="hero-sub"><?= htmlspecialchars($descripcion) ?> Encuentra los mejores productos con la mejor calidad y precios.</p>
      <div class="hero-ctas">
        <button class="btn-orange" onclick="document.getElementById('productos').scrollIntoView({behavior:'smooth'})">
          Ver productos
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </button>
        <?php if ($telefono): ?>
        <button class="btn-ghost" onclick="openWhatsApp()">Escribir al vendedor</button>
        <?php endif; ?>
      </div>
      <div class="hero-stats">
        <div class="stat">
          <div class="stat-num" id="heroProductCount"><?= count($productos) ?></div>
          <div class="stat-label">Productos</div>
        </div>
        <div class="stat">
          <div class="stat-num">⭐ 5.0</div>
          <div class="stat-label">Calificación</div>
        </div>
        <div class="stat">
          <div class="stat-num">✓</div>
          <div class="stat-label">Verificado</div>
        </div>
      </div>
    </div>
    <div class="hero-visual">
      <div class="hero-cards-stack">
        <div class="hero-card-bg"></div>
        <div class="hero-card-bg"></div>
        <?php if (!empty($productos[0])): ?>
        <div class="hero-card-main">
          <?php if ($productos[0]['imagen']): ?>
            <img src="<?= htmlspecialchars($productos[0]['imagen']) ?>" style="width:100%;height:140px;object-fit:cover;" alt="">
          <?php else: ?>
            <div class="hero-card-img">🛍️</div>
          <?php endif; ?>
          <div class="hero-card-body">
            <div class="price">S/ <?= number_format($productos[0]['precio'], 2) ?></div>
            <div class="name"><?= htmlspecialchars(substr($productos[0]['nombre'], 0, 28)) ?></div>
          </div>
        </div>
        <?php else: ?>
        <div class="hero-card-main">
          <div class="hero-card-img">🛍️</div>
          <div class="hero-card-body">
            <div class="price">Tu tienda</div>
            <div class="name">¡Agrega productos!</div>
          </div>
        </div>
        <?php endif; ?>
        <div class="hero-float-badge">💬 WhatsApp</div>
      </div>
    </div>
  </div>
</section>

<!-- TRUST BADGES -->
<div class="trust">
  <div class="trust-inner">
    <div class="trust-item">
      <div class="trust-icon">🚚</div>
      <div class="trust-text">
        <strong>Envío Rápido</strong>
        <span>Coordinamos contigo</span>
      </div>
    </div>
    <div class="trust-item">
      <div class="trust-icon">🔒</div>
      <div class="trust-text">
        <strong>Compra Segura</strong>
        <span>Vendedor verificado</span>
      </div>
    </div>
    <div class="trust-item">
      <div class="trust-icon">💬</div>
      <div class="trust-text">
        <strong>Soporte Directo</strong>
        <span>Respuesta por WhatsApp</span>
      </div>
    </div>
    <div class="trust-item">
      <div class="trust-icon">✅</div>
      <div class="trust-text">
        <strong>Calidad Garantizada</strong>
        <span>Productos seleccionados</span>
      </div>
    </div>
  </div>
</div>

<!-- PRODUCTS SECTION -->
<section class="section" id="productos">
  <div class="section-header">
    <h2 class="section-title">Nuestros Productos</h2>
    <span class="section-link" id="productCountLabel"><?= count($productos) ?> productos</span>
  </div>

  <?php if (empty($productos)): ?>
  <div class="empty-state">
    <div class="empty-icon">📦</div>
    <h3>Sin productos aún</h3>
    <p>Esta tienda está preparando sus productos. ¡Vuelve pronto!</p>
  </div>
  <?php else: ?>
  <div class="products-grid" id="productsGrid">
    <?php foreach ($productos as $i => $p): ?>
    <div class="product-card" onclick="openProduct(<?= $i ?>)" data-index="<?= $i ?>">
      <?php if ($p['stock'] > 0 && $p['stock'] <= 5): ?>
        <div class="product-badge">Últimas unidades</div>
      <?php endif; ?>

      <?php if ($p['imagen']): ?>
        <img src="<?= htmlspecialchars($p['imagen']) ?>" class="product-img" alt="<?= htmlspecialchars($p['nombre']) ?>" loading="lazy">
      <?php else: ?>
        <div class="product-img-placeholder">🛍️</div>
      <?php endif; ?>

      <div class="product-body">
        <div class="product-name"><?= htmlspecialchars($p['nombre']) ?></div>
        <div class="product-rating">
          <span class="stars">★★★★★</span>
          <span class="rating-count">(nuevo)</span>
        </div>
        <div style="display:flex;align-items:baseline;gap:4px;">
          <span class="product-price">S/ <?= number_format($p['precio'], 2) ?></span>
        </div>
        <div class="product-footer">
          <span class="product-stock <?= ($p['stock'] <= 5 && $p['stock'] > 0) ? 'low' : '' ?>">
            <?php if ($p['stock'] == 0): ?>Sin stock<?php elseif ($p['stock'] <= 5): ?>¡Solo <?= $p['stock'] ?> disponibles!<?php else: ?>En stock (<?= $p['stock'] ?>)<?php endif; ?>
          </span>
          <button class="add-cart-btn" onclick="event.stopPropagation(); addToCart(<?= $i ?>)" title="Agregar al carrito">
            <svg viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          </button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-inner">
    <div class="footer-top">
      <div class="footer-brand">
        <div class="brand" style="margin-bottom:0">
          <div class="brand-icon"><?= strtoupper(substr($nombreTienda, 0, 1)) ?></div>
          <div class="brand-name"><?= htmlspecialchars($nombreTienda) ?><small>Tienda Online</small></div>
        </div>
        <p><?= htmlspecialchars($descripcion) ?></p>
        <?php if ($telefono): ?>
        <a href="https://wa.me/<?= $telefono ?>" target="_blank" style="display:inline-flex;align-items:center;gap:8px;background:#25d366;color:#fff;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;margin-top:12px;">
          <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:#fff;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          WhatsApp
        </a>
        <?php endif; ?>
      </div>
      <div class="footer-col">
        <h4>Tienda</h4>
        <ul>
          <li><a href="#productos">Todos los productos</a></li>
          <li><a href="#productos">Novedades</a></li>
          <li><a href="#productos">Ofertas</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Ayuda</h4>
        <ul>
          <?php if ($telefono): ?>
          <li><a href="https://wa.me/<?= $telefono ?>" target="_blank">Contactar vendedor</a></li>
          <?php endif; ?>
          <li><a href="#">Preguntas frecuentes</a></li>
          <li><a href="#">Política de devoluciones</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Plataforma</h4>
        <ul>
          <li><a href="https://komercia.online">Komercia</a></li>
          <li><a href="https://komercia.online/registro">Crear mi tienda</a></li>
          <li><a href="https://komercia.online/login">Panel de vendedor</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© <?= date('Y') ?> <?= htmlspecialchars($nombreTienda) ?>. Todos los derechos reservados.</p>
      <div class="footer-powered">
        Tienda creada con <a href="https://komercia.online">Komercia</a> 🚀
      </div>
    </div>
  </div>
</footer>

<!-- PRODUCT MODAL -->
<div class="modal-overlay" id="modalOverlay" onclick="if(event.target===this) closeModal()">
  <div class="modal-wrapper">
    <button class="modal-close" onclick="closeModal()">✕</button>
    <div class="modal" id="modalContent"></div>
  </div>
</div>

<!-- CART SIDEBAR -->
<div class="cart-sidebar" id="cartSidebar">
  <div class="cart-header">
    <h3>🛒 Mi carrito</h3>
    <button class="cart-close" onclick="toggleCart()">✕</button>
  </div>
  <div class="cart-items" id="cartItems"></div>
  <div class="cart-footer" id="cartFooter"></div>
</div>

<!-- WHATSAPP FLOAT -->
<?php if ($telefono): ?>
<div class="whatsapp-float" id="waFloat">
  <div class="wa-bubble" id="waBubble">¿Necesitas ayuda? ¡Escríbenos!</div>
  <button class="wa-button" onclick="openWhatsApp()" title="Contactar por WhatsApp">
    <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
  </button>
</div>
<?php endif; ?>

<script>
const tienda = <?= $tiendaJson ?>;
const productos = <?= json_encode($productos) ?>;
let cart = JSON.parse(localStorage.getItem('cart_' + tienda.slug) || '[]');

// ===== WHATSAPP =====
function openWhatsApp(producto) {
  if (!tienda.telefono) return;
  let msg = producto
    ? `Hola! Estoy interesado en *${producto.nombre}* (S/ ${Number(producto.precio).toFixed(2)}). ¿Está disponible?`
    : `Hola ${tienda.nombre}! Vi tu tienda en Komercia y me gustaría más información.`;
  const cartItems = cart.filter(c => c.qty > 0);
  if (cartItems.length > 0 && !producto) {
    msg = `Hola ${tienda.nombre}! Quiero hacer un pedido:\n\n`;
    cartItems.forEach(item => {
      msg += `• ${item.nombre} x${item.qty} = S/ ${(item.precio * item.qty).toFixed(2)}\n`;
    });
    const total = cartItems.reduce((s, i) => s + i.precio * i.qty, 0);
    msg += `\n*Total: S/ ${total.toFixed(2)}*`;
  }
  window.open(`https://wa.me/${tienda.telefono}?text=${encodeURIComponent(msg)}`, '_blank');
}

// ===== MODAL =====
function openProduct(idx) {
  const p = productos[idx];
  const modal = document.getElementById('modalContent');
  const imgHtml = p.imagen
    ? `<img src="${p.imagen}" class="modal-img" alt="${escHtml(p.nombre)}">`
    : `<div class="modal-img-placeholder">🛍️</div>`;
  modal.innerHTML = `
    ${imgHtml}
    <div class="modal-body">
      <div class="modal-title">${escHtml(p.nombre)}</div>
      <div class="modal-price">S/ ${Number(p.precio).toFixed(2)}</div>
      ${p.descripcion ? `<div class="modal-desc">${escHtml(p.descripcion)}</div>` : ''}
      <div class="modal-stock-badge">
        📦 ${p.stock > 0 ? `${p.stock} unidades disponibles` : 'Sin stock'}
      </div>
      <div class="modal-actions">
        ${p.stock > 0 ? `<button class="btn-orange" style="flex:1" onclick="addToCart(${idx}); closeModal()">
          Agregar al carrito
        </button>` : `<button class="btn-ghost" style="flex:1;cursor:not-allowed;opacity:.6" disabled>Sin stock</button>`}
        ${tienda.telefono ? `<button class="btn-ghost" onclick="openWhatsApp(productos[${idx}])">
          💬 WhatsApp
        </button>` : ''}
      </div>
    </div>
  `;
  document.getElementById('modalOverlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  document.getElementById('modalOverlay').classList.remove('active');
  document.body.style.overflow = '';
}

// ===== CART =====
function saveCart() {
  localStorage.setItem('cart_' + tienda.slug, JSON.stringify(cart));
  renderCart();
  updateCartBadge();
}
function addToCart(idx) {
  const p = productos[idx];
  const found = cart.find(i => i.nombre === p.nombre);
  if (found) { found.qty++; }
  else { cart.push({ nombre: p.nombre, precio: p.precio, imagen: p.imagen, qty: 1 }); }
  saveCart();
  // Visual feedback
  const btn = document.querySelector(`[data-index="${idx}"] .add-cart-btn`);
  if (btn) {
    btn.style.background = '#1a7a3f';
    setTimeout(() => btn.style.background = '', 500);
  }
}
function updateCartBadge() {
  const total = cart.reduce((s, i) => s + i.qty, 0);
  const badge = document.getElementById('cartCount');
  if (total > 0) {
    badge.textContent = total;
    badge.style.display = 'block';
  } else {
    badge.style.display = 'none';
  }
}
function renderCart() {
  const container = document.getElementById('cartItems');
  const footer = document.getElementById('cartFooter');
  if (cart.length === 0) {
    container.innerHTML = `<div class="cart-empty"><div class="icon">🛒</div><p>Tu carrito está vacío</p></div>`;
    footer.innerHTML = '';
    return;
  }
  container.innerHTML = cart.map((item, i) => `
    <div class="cart-item">
      ${item.imagen
        ? `<img src="${item.imagen}" class="cart-item-img" alt="" onerror="this.innerHTML='🛍️'">`
        : `<div class="cart-item-img">🛍️</div>`}
      <div class="cart-item-info">
        <div class="cart-item-name">${escHtml(item.nombre)}</div>
        <div class="cart-item-price">S/ ${(item.precio * item.qty).toFixed(2)}</div>
      </div>
      <div class="cart-qty">
        <button onclick="changeQty(${i}, -1)">-</button>
        <span>${item.qty}</span>
        <button onclick="changeQty(${i}, 1)">+</button>
      </div>
    </div>
  `).join('');
  const total = cart.reduce((s, i) => s + i.precio * i.qty, 0);
  footer.innerHTML = `
    <div class="cart-total"><span>Total</span><span>S/ ${total.toFixed(2)}</span></div>
    ${tienda.telefono
      ? `<button class="btn-orange" style="width:100%;justify-content:center" onclick="openWhatsApp()">
          💬 Pedir por WhatsApp
        </button>`
      : `<div style="text-align:center;color:var(--text3);font-size:13px">Contacta al vendedor para completar tu pedido</div>`}
    <button class="btn-ghost" style="width:100%;margin-top:8px;text-align:center" onclick="clearCart()">Vaciar carrito</button>
  `;
}
function changeQty(i, delta) {
  cart[i].qty += delta;
  if (cart[i].qty <= 0) cart.splice(i, 1);
  saveCart();
}
function clearCart() {
  cart = [];
  saveCart();
}
function toggleCart() {
  document.getElementById('cartSidebar').classList.toggle('open');
}

// ===== SEARCH =====
document.getElementById('searchInput').addEventListener('input', function() {
  const q = this.value.toLowerCase().trim();
  const results = document.getElementById('searchResults');
  if (!q) { results.classList.remove('show'); return; }
  const matches = productos.filter(p => p.nombre.toLowerCase().includes(q));
  if (matches.length === 0) {
    results.innerHTML = `<div class="search-no-results">No se encontraron productos</div>`;
  } else {
    results.innerHTML = matches.slice(0,6).map((p, i) => `
      <div class="search-result-item" onclick="openProduct(productos.indexOf(p))">
        ${p.imagen
          ? `<img src="${p.imagen}" class="search-result-img" alt="">`
          : `<div class="search-result-img" style="display:flex;align-items:center;justify-content:center;font-size:22px">🛍️</div>`}
        <div style="flex:1;min-width:0">
          <div class="search-result-name">${escHtml(p.nombre)}</div>
        </div>
        <div class="search-result-price">S/ ${Number(p.precio).toFixed(2)}</div>
      </div>
    `).join('');
    // Fix the onclick references
    results.querySelectorAll('.search-result-item').forEach((el, idx) => {
      const prod = matches[idx];
      el.onclick = () => { results.classList.remove('show'); openProduct(productos.indexOf(prod)); };
    });
  }
  results.classList.add('show');
});
document.addEventListener('click', (e) => {
  if (!e.target.closest('.search-bar')) {
    document.getElementById('searchResults').classList.remove('show');
  }
});

// ===== FILTER POR CATEGORÍA =====
function filterCategory(cat, el) {
  document.querySelectorAll('.subnav-item').forEach(i => i.classList.remove('active'));
  el.classList.add('active');
  const cards = document.querySelectorAll('.product-card');
  let visible = 0;
  cards.forEach(card => {
    const idx = parseInt(card.dataset.index);
    const p = productos[idx];
    const show = cat === '' || (p.categoria || '') === cat;
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  document.getElementById('productCountLabel').textContent = visible + ' producto' + (visible !== 1 ? 's' : '');
}

// ===== WA BUBBLE =====
setTimeout(() => {
  const bubble = document.getElementById('waBubble');
  if (bubble) { bubble.style.display = 'none'; }
}, 5000);

// ===== UTILS =====
function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Init
updateCartBadge();
renderCart();
</script>
</body>
</html>
