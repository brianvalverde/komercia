<?php
session_start();
if (empty($_SESSION['uid']) || empty($_SESSION['slug'])) {
    header('Location: /login.php');
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --orange: #ff6a00;
            --orange-dark: #e05a00;
            --sidebar-bg: #111;
            --sidebar-width: 240px;
            --body-bg: #f5f5f5;
            --card-bg: #fff;
            --text: #1a1a1a;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --radius: 12px;
            --transition: 0.2s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--body-bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 200;
            transition: transform var(--transition);
        }

        .sidebar-logo {
            padding: 24px 20px 20px;
            border-bottom: 1px solid #222;
        }

        .sidebar-logo img {
            height: 32px;
        }

        .sidebar-logo span {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--orange);
            letter-spacing: -0.5px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            color: #aaa;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background var(--transition), color var(--transition);
        }

        .sidebar-nav a:hover {
            background: #1e1e1e;
            color: #fff;
        }

        .sidebar-nav a.active {
            background: var(--orange);
            color: #fff;
        }

        .sidebar-nav a svg {
            width: 18px; height: 18px;
            flex-shrink: 0;
        }

        .sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid #222;
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            color: #aaa;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background var(--transition), color var(--transition);
        }

        .sidebar-footer a:hover {
            background: #1e1e1e;
            color: #ff4444;
        }

        /* ── OVERLAY ── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 199;
        }

        /* ── MAIN ── */
        .main {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ── TOPBAR ── */
        .topbar {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .hamburger {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px;
            border-radius: 8px;
            color: var(--text);
            transition: background var(--transition);
        }

        .hamburger:hover { background: var(--body-bg); }

        .topbar h1 {
            font-size: 1.125rem;
            font-weight: 600;
            flex: 1;
        }

        .topbar-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: var(--orange);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            flex-shrink: 0;
        }

        /* ── CONTENT ── */
        .content {
            padding: 28px 24px;
            max-width: 760px;
            width: 100%;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .page-header p {
            color: var(--text-muted);
            margin-top: 4px;
            font-size: 0.9rem;
        }

        /* ── CARD ── */
        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title svg {
            width: 18px; height: 18px;
            color: var(--orange);
        }

        /* ── FORM ── */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }

        .form-group:last-child { margin-bottom: 0; }

        .form-group label {
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group textarea {
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            color: var(--text);
            background: var(--card-bg);
            transition: border-color var(--transition), box-shadow var(--transition);
            outline: none;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(255,106,0,0.1);
        }

        .form-group textarea {
            min-height: 90px;
        }

        /* ── LOGO UPLOAD ── */
        .drop-zone {
            border: 2px dashed var(--border);
            border-radius: 10px;
            padding: 32px 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color var(--transition), background var(--transition);
            position: relative;
            background: #fafafa;
        }

        .drop-zone:hover,
        .drop-zone.dragover {
            border-color: var(--orange);
            background: rgba(255,106,0,0.03);
        }

        .drop-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .drop-zone-icon {
            width: 48px; height: 48px;
            margin: 0 auto 12px;
            background: rgba(255,106,0,0.08);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--orange);
        }

        .drop-zone-icon svg { width: 24px; height: 24px; }

        .drop-zone p {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .drop-zone strong { color: var(--orange); }

        .logo-preview-wrap {
            display: none;
            align-items: center;
            gap: 16px;
            padding: 16px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            background: #fafafa;
        }

        .logo-preview-wrap.visible { display: flex; }

        .logo-preview-wrap img {
            width: 72px; height: 72px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .logo-preview-info {
            flex: 1;
        }

        .logo-preview-info p {
            font-size: 0.875rem;
            font-weight: 500;
        }

        .logo-preview-info span {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .btn-remove-logo {
            background: none;
            border: 1.5px solid #e5e7eb;
            border-radius: 6px;
            padding: 6px 10px;
            cursor: pointer;
            color: #ef4444;
            font-size: 0.8rem;
            font-weight: 500;
            transition: background var(--transition);
        }

        .btn-remove-logo:hover { background: #fef2f2; }

        /* ── COLOR PICKER ── */
        .color-picker-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .color-picker-group input[type="color"] {
            width: 44px; height: 44px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            padding: 3px;
            cursor: pointer;
            background: #fff;
            flex-shrink: 0;
        }

        .color-picker-group input[type="text"] {
            flex: 1;
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: var(--text);
            outline: none;
            transition: border-color var(--transition), box-shadow var(--transition);
        }

        .color-picker-group input[type="text"]:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(255,106,0,0.1);
        }

        .color-preview-btn {
            margin-top: 12px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: default;
            transition: background var(--transition);
        }

        .color-preview-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 6px;
        }

        /* ── BUTTONS ── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background var(--transition), opacity var(--transition), transform 0.1s;
            border: none;
            text-decoration: none;
        }

        .btn:active { transform: scale(0.98); }

        .btn-primary {
            background: var(--orange);
            color: #fff;
        }

        .btn-primary:hover { background: var(--orange-dark); }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-outline {
            background: transparent;
            border: 1.5px solid var(--border);
            color: var(--text);
        }

        .btn-outline:hover { background: var(--body-bg); }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 4px;
        }

        /* ── SKELETON ── */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 6px;
            height: 40px;
            margin-bottom: 16px;
        }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* ── TOAST ── */
        .toast-container {
            position: fixed;
            bottom: 24px; right: 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 9999;
        }

        .toast {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #1a1a1a;
            color: #fff;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 500;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease;
            max-width: 320px;
        }

        .toast.success { border-left: 4px solid #22c55e; }
        .toast.error   { border-left: 4px solid #ef4444; }

        .toast svg { width: 18px; height: 18px; flex-shrink: 0; }
        .toast.success svg { color: #22c55e; }
        .toast.error svg   { color: #ef4444; }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideOut {
            from { opacity: 1; transform: translateY(0); }
            to   { opacity: 0; transform: translateY(12px); }
        }

        /* ── SPINNER ── */
        .spinner {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .sidebar-overlay.open {
                display: block;
            }

            .main {
                margin-left: 0;
            }

            .hamburger {
                display: flex;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 20px 16px;
            }

            .topbar {
                padding: 0 16px;
            }
        }
    </style>
</head>
<body>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <span>Komercia</span>
    </div>

    <nav class="sidebar-nav">
        <a href="/panel/productos.php">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a1 1 0 00-1 1v10a1 1 0 001 1h16a1 1 0 001-1V8a1 1 0 00-1-1z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
            </svg>
            Productos
        </a>
        <a href="/panel/tienda.php">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                <polyline stroke-linecap="round" stroke-linejoin="round" points="9 22 9 12 15 12 15 22"/>
            </svg>
            Mi Tienda
        </a>
        <a href="/panel/configuracion.php" class="active">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
            Configuración
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="/logout.php">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Cerrar sesión
        </a>
    </div>
</aside>

<!-- Main -->
<div class="main">
    <!-- Topbar -->
    <header class="topbar">
        <button class="hamburger" id="hamburgerBtn" aria-label="Menú">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
        <h1>Configuración</h1>
        <div class="topbar-avatar" title="<?= htmlspecialchars($slug) ?>">
            <?= strtoupper(substr($slug, 0, 1)) ?>
        </div>
    </header>

    <!-- Content -->
    <div class="content">
        <div class="page-header">
            <h2>Configuración de tu tienda</h2>
            <p>Personaliza la información y apariencia de tu tienda.</p>
        </div>

        <form id="configForm" novalidate>

            <!-- Información básica -->
            <div class="card">
                <div class="card-title">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <circle cx="12" cy="8" r="4"/>
                        <path stroke-linecap="round" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                    Información básica
                </div>

                <div class="form-group">
                    <label for="nombre">Nombre de la tienda</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Mi Tienda" maxlength="100" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" placeholder="Describe tu tienda brevemente…" maxlength="500"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email de contacto</label>
                        <input type="email" id="email" name="email" placeholder="contacto@mitienda.com">
                    </div>
                    <div class="form-group">
                        <label for="telefono">Teléfono WhatsApp</label>
                        <input type="text" id="telefono" name="telefono" placeholder="+51 999 999 999" maxlength="30">
                    </div>
                </div>
            </div>

            <!-- Logo -->
            <div class="card">
                <div class="card-title">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="18" rx="3"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 15l-5-5L5 21"/>
                    </svg>
                    Logo de la tienda
                </div>

                <!-- Preview (shown when logo exists) -->
                <div class="logo-preview-wrap" id="logoPreviewWrap">
                    <img id="logoPreviewImg" src="" alt="Logo actual">
                    <div class="logo-preview-info">
                        <p id="logoPreviewName">logo.webp</p>
                        <span id="logoPreviewSize"></span>
                    </div>
                    <button type="button" class="btn-remove-logo" id="btnRemoveLogo">Cambiar</button>
                </div>

                <!-- Drop zone (shown when no logo or after clicking Cambiar) -->
                <div class="drop-zone" id="dropZone">
                    <input type="file" id="logoInput" accept="image/*">
                    <div class="drop-zone-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M16 12l-4-4m0 0l-4 4m4-4v12"/>
                        </svg>
                    </div>
                    <p><strong>Haz clic o arrastra</strong> tu logo aquí</p>
                    <p style="margin-top:4px; font-size:0.8rem;">PNG, JPG, SVG — máx. 5 MB. Se convertirá a WebP.</p>
                </div>
            </div>

            <!-- Apariencia -->
            <div class="card">
                <div class="card-title">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <circle cx="13.5" cy="6.5" r="2.5"/>
                        <circle cx="19" cy="13" r="2.5"/>
                        <circle cx="6" cy="13" r="2.5"/>
                        <circle cx="13.5" cy="19.5" r="2.5"/>
                        <path stroke-linecap="round" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z"/>
                    </svg>
                    Apariencia
                </div>

                <div class="form-group">
                    <label>Color primario</label>
                    <div class="color-picker-group">
                        <input type="color" id="colorPicker" value="#ff6a00">
                        <input type="text"  id="colorHex"    value="#ff6a00" maxlength="7" placeholder="#ff6a00">
                    </div>
                    <div style="margin-top:12px;">
                        <button type="button" class="color-preview-btn" id="colorPreviewBtn">Botón de ejemplo</button>
                        <p class="color-preview-label">Así se verá tu color en botones y elementos de tu tienda.</p>
                    </div>
                </div>
            </div>

            <!-- Dirección -->
            <div class="card">
                <div class="card-title">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Dirección
                </div>

                <div class="form-group">
                    <label for="direccion">Dirección física</label>
                    <input type="text" id="direccion" name="direccion" placeholder="Av. Principal 123, Lima, Perú" maxlength="200">
                </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="btnGuardar">
                    <div class="spinner" id="saveSpinner"></div>
                    <span id="btnGuardarText">Guardar cambios</span>
                </button>
            </div>

        </form>
    </div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<script>
(function () {
    'use strict';

    /* ── helpers ── */
    const $ = id => document.getElementById(id);

    function showToast(message, type = 'success') {
        const tc = $('toastContainer');
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        const icon = type === 'success'
            ? `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>`
            : `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;
        t.innerHTML = icon + `<span>${message}</span>`;
        tc.appendChild(t);
        setTimeout(() => {
            t.style.animation = 'slideOut 0.3s ease forwards';
            setTimeout(() => t.remove(), 300);
        }, 3000);
    }

    /* ── sidebar ── */
    const sidebar = $('sidebar');
    const overlay = $('sidebarOverlay');
    $('hamburgerBtn').addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('open');
    });
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
    });

    /* ── color picker sync ── */
    const colorPicker = $('colorPicker');
    const colorHex    = $('colorHex');
    const previewBtn  = $('colorPreviewBtn');

    function applyColor(hex) {
        previewBtn.style.background = hex;
    }

    applyColor(colorPicker.value);

    colorPicker.addEventListener('input', () => {
        colorHex.value = colorPicker.value;
        applyColor(colorPicker.value);
    });

    colorHex.addEventListener('input', () => {
        const val = colorHex.value.trim();
        if (/^#[0-9a-fA-F]{6}$/.test(val)) {
            colorPicker.value = val;
            applyColor(val);
        }
    });

    colorHex.addEventListener('blur', () => {
        let val = colorHex.value.trim();
        if (!val.startsWith('#')) val = '#' + val;
        if (/^#[0-9a-fA-F]{6}$/.test(val)) {
            colorHex.value = val;
            colorPicker.value = val;
            applyColor(val);
        } else {
            colorHex.value = colorPicker.value;
        }
    });

    /* ── logo upload & compression ── */
    let logoBlob = null;
    let logoChanged = false;

    const dropZone      = $('dropZone');
    const logoInput     = $('logoInput');
    const previewWrap   = $('logoPreviewWrap');
    const previewImg    = $('logoPreviewImg');
    const previewName   = $('logoPreviewName');
    const previewSize   = $('logoPreviewSize');
    const btnRemoveLogo = $('btnRemoveLogo');

    function showPreview(src, name, bytes) {
        previewImg.src = src;
        previewName.textContent = name;
        previewSize.textContent = bytes ? formatBytes(bytes) : '';
        previewWrap.classList.add('visible');
        dropZone.style.display = 'none';
    }

    function showDropZone() {
        previewWrap.classList.remove('visible');
        dropZone.style.display = '';
        logoBlob = null;
        logoChanged = false;
        logoInput.value = '';
    }

    btnRemoveLogo.addEventListener('click', showDropZone);

    function formatBytes(b) {
        if (b < 1024) return b + ' B';
        if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
        return (b / (1024 * 1024)).toFixed(2) + ' MB';
    }

    function compressToWebP(file, cb) {
        const MAX = 5 * 1024 * 1024;
        if (file.size > MAX) { showToast('El archivo supera los 5 MB.', 'error'); return; }

        const reader = new FileReader();
        reader.onload = e => {
            const img = new Image();
            img.onload = () => {
                const maxPx = 400;
                let w = img.width, h = img.height;
                if (w > maxPx || h > maxPx) {
                    const ratio = Math.min(maxPx / w, maxPx / h);
                    w = Math.round(w * ratio);
                    h = Math.round(h * ratio);
                }
                const canvas = document.createElement('canvas');
                canvas.width = w; canvas.height = h;
                canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                canvas.toBlob(blob => {
                    if (!blob) { showToast('Error al procesar la imagen.', 'error'); return; }
                    cb(blob);
                }, 'image/webp', 0.9);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    function handleLogoFile(file) {
        if (!file || !file.type.startsWith('image/')) {
            showToast('Por favor selecciona una imagen válida.', 'error');
            return;
        }
        compressToWebP(file, blob => {
            logoBlob = blob;
            logoChanged = true;
            const url = URL.createObjectURL(blob);
            showPreview(url, 'logo.webp', blob.size);
        });
    }

    logoInput.addEventListener('change', e => {
        if (e.target.files[0]) handleLogoFile(e.target.files[0]);
    });

    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files[0]) handleLogoFile(e.dataTransfer.files[0]);
    });

    /* ── load store data ── */
    async function loadTienda() {
        try {
            const res = await fetch('/api/tienda.php?accion=obtener', { credentials: 'include' });
            if (!res.ok) throw new Error('Error al cargar datos');
            const data = await res.json();

            $('nombre').value      = data.nombre       || '';
            $('descripcion').value = data.descripcion  || '';
            $('email').value       = data.email        || '';
            $('telefono').value    = data.telefono     || '';
            $('direccion').value   = data.direccion    || '';

            const color = data.color_primario || '#ff6a00';
            colorPicker.value = color;
            colorHex.value    = color;
            applyColor(color);

            if (data.logo) {
                showPreview(data.logo, 'Logo actual', null);
            }
        } catch (err) {
            showToast('No se pudieron cargar los datos de la tienda.', 'error');
        }
    }

    loadTienda();

    /* ── save form ── */
    $('configForm').addEventListener('submit', async e => {
        e.preventDefault();

        const nombre = $('nombre').value.trim();
        if (!nombre) { showToast('El nombre de la tienda es obligatorio.', 'error'); $('nombre').focus(); return; }

        const btn     = $('btnGuardar');
        const spinner = $('saveSpinner');
        const btnText = $('btnGuardarText');

        btn.disabled    = true;
        spinner.style.display = 'block';
        btnText.textContent   = 'Guardando…';

        try {
            const fd = new FormData();
            fd.append('nombre',         nombre);
            fd.append('descripcion',    $('descripcion').value.trim());
            fd.append('email',          $('email').value.trim());
            fd.append('telefono',       $('telefono').value.trim());
            fd.append('direccion',      $('direccion').value.trim());
            fd.append('color_primario', colorHex.value.trim() || colorPicker.value);

            if (logoChanged && logoBlob) {
                fd.append('logo', logoBlob, 'logo.webp');
            }

            const res = await fetch('/api/tienda.php?accion=guardar', {
                method: 'POST',
                body: fd,
                credentials: 'include'
            });

            const result = await res.json();

            if (!res.ok || result.error) {
                throw new Error(result.error || 'Error al guardar');
            }

            logoChanged = false;
            showToast('¡Cambios guardados correctamente!', 'success');
        } catch (err) {
            showToast(err.message || 'Error al guardar los cambios.', 'error');
        } finally {
            btn.disabled = false;
            spinner.style.display = 'none';
            btnText.textContent   = 'Guardar cambios';
        }
    });

})();
</script>
</body>
</html>
