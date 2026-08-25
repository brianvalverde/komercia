<?php
// ─────────────────────────────────────────────────────────────
// api/tiendas.php — Gestión multi-tienda (plan Empresarial)
// GET  ?accion=lista        → lista tiendas del comerciante
// POST ?accion=crear        → crea tienda adicional
// POST ?accion=cambiar      → cambia tienda activa en sesión
// POST ?accion=actualizar   → edita nombre/slug de tienda
// POST ?accion=eliminar     → elimina tienda adicional
// ─────────────────────────────────────────────────────────────
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'No autenticado']);
    exit;
}

require_once '/var/www/komercia/config/firebase.php';

$uid    = $_SESSION['uid'];
$method = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? ($_POST['accion'] ?? (json_decode(file_get_contents('php://input'),true)['accion'] ?? ''));
$input  = $method === 'POST' ? (json_decode(file_get_contents('php://input'),true) ?? $_POST) : [];

function fsv($f,$k,$d=''){return $f[$k]['stringValue']??$f[$k]['booleanValue']??$f[$k]['integerValue']??$d;}

// ── GET lista ────────────────────────────────────────────────
if ($method === 'GET' && $accion === 'lista') {
    // Tienda principal desde comerciante
    $doc = firestoreRequest('GET', "comerciantes/{$uid}");
    $f   = $doc['fields'] ?? [];
    $tiendas = [[
        'id'       => 'main',
        'nombre'   => fsv($f,'nombre_tienda',fsv($f,'nombre','Mi tienda')),
        'slug'     => fsv($f,'slug',''),
        'principal'=> true,
        'activo'   => true,
    ]];

    // Tiendas adicionales
    $res  = firestoreRequest('GET', "comerciantes/{$uid}/tiendas?pageSize=20");
    foreach ($res['documents'] ?? [] as $d2) {
        $parts = explode('/', $d2['name']);
        $tid   = end($parts);
        $f2    = $d2['fields'] ?? [];
        $tiendas[] = [
            'id'       => $tid,
            'nombre'   => fsv($f2,'nombre','Sin nombre'),
            'slug'     => fsv($f2,'slug',''),
            'principal'=> false,
            'activo'   => $f2['activo']['booleanValue'] ?? true,
        ];
    }

    echo json_encode(['ok'=>true,'tiendas'=>$tiendas,'activa'=>$_SESSION['tienda_activa'] ?? 'main']);
    exit;
}

// ── POST crear ───────────────────────────────────────────────
if ($method === 'POST' && $accion === 'crear') {
    // Verificar plan empresarial
    $doc = firestoreRequest('GET', "comerciantes/{$uid}");
    $f   = $doc['fields'] ?? [];
    $plan = $f['plan']['stringValue'] ?? 'trial';
    if ($plan !== 'empresarial') {
        echo json_encode(['ok'=>false,'error'=>'Se requiere plan Empresarial']);
        exit;
    }

    $nombre = trim($input['nombre'] ?? '');
    $slug   = trim(preg_replace('/[^a-z0-9-]/', '-', strtolower($input['slug'] ?? $nombre)));
    $slug   = trim(preg_replace('/-+/', '-', $slug), '-');

    if (!$nombre || !$slug) {
        echo json_encode(['ok'=>false,'error'=>'Nombre y slug requeridos']);
        exit;
    }

    // Verificar slug único
    $check = firestoreRequest('GET', "tiendas/{$slug}");
    if (!empty($check['fields'])) {
        echo json_encode(['ok'=>false,'error'=>'El slug ya está en uso']);
        exit;
    }
    // También verificar en tiendas adicionales de otros comerciantes — simplificado
    $tid = 'tienda_' . uniqid();
    $now = date('c');

    // Crear en subcolección del comerciante
    $maskT = 'updateMask.fieldPaths=nombre&updateMask.fieldPaths=slug&updateMask.fieldPaths=activo&updateMask.fieldPaths=created_at';
    firestoreRequest('PATCH', "comerciantes/{$uid}/tiendas/{$tid}?{$maskT}", ['fields' => [
        'nombre'     => ['stringValue' => $nombre],
        'slug'       => ['stringValue' => $slug],
        'activo'     => ['booleanValue'=> true],
        'created_at' => ['stringValue' => $now],
    ]]);

    // Registrar slug en colección raíz tiendas (para lookup público)
    $maskS = 'updateMask.fieldPaths=uid&updateMask.fieldPaths=tienda_id&updateMask.fieldPaths=slug';
    firestoreRequest('PATCH', "tiendas/{$slug}?{$maskS}", ['fields' => [
        'uid'      => ['stringValue' => $uid],
        'tienda_id'=> ['stringValue' => $tid],
        'slug'     => ['stringValue' => $slug],
    ]]);

    echo json_encode(['ok'=>true,'id'=>$tid,'slug'=>$slug,'nombre'=>$nombre]);
    exit;
}

// ── POST cambiar (contexto de sesión) ────────────────────────
if ($method === 'POST' && $accion === 'cambiar') {
    $tid = trim($input['tienda_id'] ?? '');
    if (!$tid) { echo json_encode(['ok'=>false,'error'=>'tienda_id requerido']); exit; }

    if ($tid === 'main') {
        $doc  = firestoreRequest('GET', "comerciantes/{$uid}");
        $f    = $doc['fields'] ?? [];
        $_SESSION['tienda_activa'] = 'main';
        $_SESSION['slug']          = fsv($f,'slug','');
        $_SESSION['tienda_nombre'] = fsv($f,'nombre_tienda',fsv($f,'nombre',''));
        echo json_encode(['ok'=>true,'slug'=>$_SESSION['slug'],'nombre'=>$_SESSION['tienda_nombre']]);
    } else {
        $doc = firestoreRequest('GET', "comerciantes/{$uid}/tiendas/{$tid}");
        $f   = $doc['fields'] ?? [];
        if (empty($f)) { echo json_encode(['ok'=>false,'error'=>'Tienda no encontrada']); exit; }
        $_SESSION['tienda_activa'] = $tid;
        $_SESSION['slug']          = fsv($f,'slug','');
        $_SESSION['tienda_nombre'] = fsv($f,'nombre','');
        echo json_encode(['ok'=>true,'slug'=>$_SESSION['slug'],'nombre'=>$_SESSION['tienda_nombre']]);
    }
    exit;
}

// ── POST eliminar ────────────────────────────────────────────
if ($method === 'POST' && $accion === 'eliminar') {
    $tid = trim($input['tienda_id'] ?? '');
    if (!$tid || $tid === 'main') { echo json_encode(['ok'=>false,'error'=>'No se puede eliminar la tienda principal']); exit; }
    $doc  = firestoreRequest('GET', "comerciantes/{$uid}/tiendas/{$tid}");
    $slug = $doc['fields']['slug']['stringValue'] ?? '';
    firestoreRequest('DELETE', "comerciantes/{$uid}/tiendas/{$tid}");
    if ($slug) firestoreRequest('DELETE', "tiendas/{$slug}");
    if (($_SESSION['tienda_activa'] ?? '') === $tid) {
        // Volver a principal
        $pdoc = firestoreRequest('GET', "comerciantes/{$uid}");
        $_SESSION['tienda_activa'] = 'main';
        $_SESSION['slug']          = $pdoc['fields']['slug']['stringValue'] ?? '';
    }
    echo json_encode(['ok'=>true]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Acción no reconocida']);
