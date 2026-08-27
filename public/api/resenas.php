<?php
/**
 * API Reseñas — acepta campos del panel (autor/texto/calificacion)
 * y del formulario público (nombre/comentario/estrellas).
 * Almacena con nombres canónicos: autor, texto, calificacion, pais, aprobada, creado_en.
 */
header('Content-Type: application/json');
session_start();

require_once '/var/www/komercia/config/firebase.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['accion'] ?? '';

// ── Autenticación ─────────────────────────────────────────────
// Las acciones públicas (crear desde la tienda) no requieren sesión;
// el resto sí (admin del panel).
$publicActions = ['crear', 'listar_pub'];
$adminActions  = ['listar', 'editar', 'aprobar', 'eliminar'];

$uid           = $_SESSION['uid'] ?? null;
$tienda_activa = $_SESSION['tienda_activa'] ?? 'main';

// Para acciones admin exigir sesión
if (in_array($action, $adminActions) && !$uid) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

// ── Base de productos ─────────────────────────────────────────
// Para crear (público) el uid viene del slug → lo resolvemos con el GET param
function getProductosBase(string $uid, string $tienda): string {
    return ($tienda && $tienda !== 'main')
        ? "comerciantes/{$uid}/tiendas/{$tienda}/productos"
        : "comerciantes/{$uid}/productos";
}

function resenasPath(string $productosBase, string $prodId): string {
    return "{$productosBase}/{$prodId}/resenas";
}

// ── Ayuda: coger primer valor no vacío entre varias claves POST ──
function postFirst(array $keys, string $default = ''): string {
    foreach ($keys as $k) {
        $v = trim($_POST[$k] ?? '');
        if ($v !== '') return $v;
    }
    return $default;
}

// ─── CREAR reseña (público o admin) ──────────────────────────
if ($method === 'POST' && $action === 'crear') {
    // Obtener uid: del panel (sesión) o del formulario público (slug → lookup)
    $prodId = trim($_POST['producto_id'] ?? '');
    $slug   = trim($_POST['slug'] ?? '');

    // Resolver uid cuando viene del front público (usa el slug de tienda)
    if (!$uid && $slug) {
        $tiendaDoc = firestoreRequest('GET', "tiendas/{$slug}");
        $uid       = $tiendaDoc['fields']['uid']['stringValue'] ?? null;
        // Reseñas públicas van siempre a la ruta principal del comerciante
        $tienda_activa = 'main';
    }

    if (!$uid || !$prodId) {
        echo json_encode(['ok' => false, 'error' => 'producto_id requerido']); exit;
    }

    // Campos: acepta alias del formulario público
    $autor   = postFirst(['autor','nombre'], 'Anónimo');
    $texto   = postFirst(['texto','comentario']);
    $cal     = max(1, min(5, (int)postFirst(['calificacion','estrellas'], '5')));
    $pais    = trim($_POST['pais'] ?? '');
    // Desde el panel el admin puede aprobar directamente; desde el público siempre pendiente
    $desdeAdmin = $uid && isset($_SESSION['uid']);
    $aprobada   = $desdeAdmin ? (($_POST['aprobada'] ?? 'false') === 'true') : false;

    if (!$texto) {
        echo json_encode(['ok' => false, 'error' => 'El texto/comentario es requerido']); exit;
    }

    $id     = uniqid('res_');
    $fields = [
        'autor'        => ['stringValue'  => $autor],
        'texto'        => ['stringValue'  => $texto],
        'calificacion' => ['integerValue' => $cal],
        'pais'         => ['stringValue'  => $pais],
        'aprobada'     => ['booleanValue' => $aprobada],
        'creado_en'    => ['stringValue'  => date('c')],
    ];
    $base = getProductosBase($uid, $tienda_activa);
    $mask = implode('&', array_map(fn($k) => 'updateMask.fieldPaths=' . urlencode($k), array_keys($fields)));
    firestoreRequest('PATCH', resenasPath($base, $prodId) . "/{$id}?{$mask}", ['fields' => $fields]);
    echo json_encode(['ok' => true, 'id' => $id]);
    exit;
}

// ─── LISTAR (admin — todas, incluyendo pendientes) ─────────────
if ($method === 'GET' && $action === 'listar') {
    $prodId = trim($_GET['producto_id'] ?? '');
    if (!$prodId) { echo json_encode(['ok' => false, 'error' => 'producto_id requerido']); exit; }

    $base = getProductosBase($uid, $tienda_activa);
    $res  = firestoreRequest('GET', resenasPath($base, $prodId));
    $docs = $res['documents'] ?? [];
    $resenas = [];
    foreach ($docs as $doc) {
        $f  = $doc['fields'] ?? [];
        $resenas[] = [
            'id'          => basename($doc['name']),
            'autor'       => $f['autor']['stringValue'] ?? ($f['nombre']['stringValue'] ?? 'Anónimo'),
            'texto'        => $f['texto']['stringValue'] ?? ($f['comentario']['stringValue'] ?? ''),
            'calificacion' => (int)($f['calificacion']['integerValue'] ?? $f['estrellas']['integerValue'] ?? 5),
            'pais'         => $f['pais']['stringValue'] ?? '',
            'aprobada'     => $f['aprobada']['booleanValue'] ?? false,
            'creado_en'    => $f['creado_en']['stringValue'] ?? ($f['fecha']['stringValue'] ?? ''),
        ];
    }
    usort($resenas, fn($a, $b) => strcmp($b['creado_en'], $a['creado_en']));
    echo json_encode(['ok' => true, 'resenas' => $resenas]);
    exit;
}

// ─── EDITAR (admin) ────────────────────────────────────────────
if ($method === 'POST' && $action === 'editar') {
    $id     = trim($_POST['id'] ?? '');
    $prodId = trim($_POST['producto_id'] ?? '');
    $autor  = postFirst(['autor','nombre'], 'Anónimo');
    $texto  = postFirst(['texto','comentario']);
    $cal    = max(1, min(5, (int)postFirst(['calificacion','estrellas'], '5')));
    $pais   = trim($_POST['pais'] ?? '');

    if (!$id || !$prodId || !$texto) {
        echo json_encode(['ok' => false, 'error' => 'id, producto_id y texto requeridos']); exit;
    }

    $base   = getProductosBase($uid, $tienda_activa);
    $fields = [
        'autor'        => ['stringValue'  => $autor],
        'texto'        => ['stringValue'  => $texto],
        'calificacion' => ['integerValue' => $cal],
        'pais'         => ['stringValue'  => $pais],
        'editado_en'   => ['stringValue'  => date('c')],
    ];
    $mask = implode('&', array_map(fn($k) => 'updateMask.fieldPaths=' . urlencode($k), array_keys($fields)));
    firestoreRequest('PATCH', resenasPath($base, $prodId) . "/{$id}?{$mask}", ['fields' => $fields]);
    echo json_encode(['ok' => true]);
    exit;
}

// ─── APROBAR / RECHAZAR (admin) ────────────────────────────────
if ($method === 'POST' && $action === 'aprobar') {
    $id      = trim($_POST['id'] ?? '');
    $prodId  = trim($_POST['producto_id'] ?? '');
    $aprobada= ($_POST['aprobada'] ?? 'true') === 'true';

    if (!$id || !$prodId) {
        echo json_encode(['ok' => false, 'error' => 'id y producto_id requeridos']); exit;
    }

    $base   = getProductosBase($uid, $tienda_activa);
    $fields = ['aprobada' => ['booleanValue' => $aprobada]];
    firestoreRequest('PATCH', resenasPath($base, $prodId) . "/{$id}?updateMask.fieldPaths=aprobada", ['fields' => $fields]);
    echo json_encode(['ok' => true]);
    exit;
}

// ─── ELIMINAR (admin) ──────────────────────────────────────────
if ($method === 'DELETE' && $action === 'eliminar') {
    $id     = $_GET['id'] ?? '';
    $prodId = $_GET['producto_id'] ?? '';
    if (!$id || !$prodId) {
        echo json_encode(['ok' => false, 'error' => 'id y producto_id requeridos']); exit;
    }
    $base = getProductosBase($uid, $tienda_activa);
    firestoreRequest('DELETE', resenasPath($base, $prodId) . "/{$id}");
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Acción no reconocida']);
