<?php
// ─────────────────────────────────────────────────────────────
// api/vendedores.php — Gestión de vendedores por tienda
// GET  ?accion=lista              → lista vendedores de la tienda activa
// POST ?accion=crear              → crea vendedor
// POST ?accion=actualizar         → edita nombre/email/telefono
// POST ?accion=eliminar           → elimina vendedor
// POST ?accion=login_vendedor     → login de vendedor (sesión especial)
// ─────────────────────────────────────────────────────────────
header('Content-Type: application/json');
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? ($_POST['accion'] ?? (json_decode(file_get_contents('php://input'),true)['accion'] ?? ''));
$input  = $method === 'POST' ? (json_decode(file_get_contents('php://input'),true) ?? $_POST) : [];

require_once '/var/www/komercia/config/firebase.php';

function fsv($f,$k,$d=''){return $f[$k]['stringValue']??$f[$k]['booleanValue']??$f[$k]['integerValue']??$d;}

// ── Login especial de vendedor ────────────────────────────────
// No requiere sesión de comerciante; autentica con codigo
if ($method === 'POST' && $accion === 'login_vendedor') {
    $codigo = trim($input['codigo'] ?? '');
    $uid_c  = trim($input['uid'] ?? '');    // uid del comerciante (pasado en URL de login vendedor)
    $tid    = trim($input['tienda_id'] ?? '');

    if (!$codigo || !$uid_c) {
        echo json_encode(['ok'=>false,'error'=>'Código requerido']);
        exit;
    }

    // Buscar vendedor por código en todas las tiendas del comerciante
    if ($tid && $tid !== 'main') {
        // Buscar en tienda adicional
        $res = firestoreRequest('GET', "comerciantes/{$uid_c}/tiendas/{$tid}/vendedores?pageSize=50");
        $docs = $res['documents'] ?? [];
    } else {
        // Buscar en tienda principal (ruta raíz)
        $res = firestoreRequest('GET', "comerciantes/{$uid_c}/vendedores?pageSize=50");
        $docs = $res['documents'] ?? [];
    }

    $encontrado = null;
    foreach ($docs as $d) {
        $f = $d['fields'] ?? [];
        if (fsv($f,'codigo') === $codigo && ($f['activo']['booleanValue'] ?? true)) {
            $parts = explode('/', $d['name']);
            $vid   = end($parts);
            $encontrado = [
                'id'     => $vid,
                'nombre' => fsv($f,'nombre'),
                'email'  => fsv($f,'email'),
            ];
            break;
        }
    }

    if (!$encontrado) {
        echo json_encode(['ok'=>false,'error'=>'Código incorrecto o vendedor inactivo']);
        exit;
    }

    $_SESSION['vendedor_id']  = $encontrado['id'];
    $_SESSION['vendedor_uid'] = $uid_c;
    $_SESSION['vendedor_tid'] = $tid ?: 'main';
    $_SESSION['vendedor_nombre'] = $encontrado['nombre'];
    echo json_encode(['ok'=>true,'nombre'=>$encontrado['nombre']]);
    exit;
}

// ── Resto de acciones requieren sesión de comerciante ─────────
if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'No autenticado']);
    exit;
}

$uid = $_SESSION['uid'];
$tid = $_SESSION['tienda_activa'] ?? 'main';

// Ruta base de vendedores según tienda activa
function vendedoresPath($uid, $tid) {
    if ($tid === 'main') return "comerciantes/{$uid}/vendedores";
    return "comerciantes/{$uid}/tiendas/{$tid}/vendedores";
}

// ── GET lista ────────────────────────────────────────────────
if ($method === 'GET' && $accion === 'lista') {
    $res = firestoreRequest('GET', vendedoresPath($uid,$tid).'?pageSize=50');
    $vendedores = [];
    foreach ($res['documents'] ?? [] as $d) {
        $parts = explode('/', $d['name']);
        $vid   = end($parts);
        $f     = $d['fields'] ?? [];
        $vendedores[] = [
            'id'       => $vid,
            'nombre'   => fsv($f,'nombre'),
            'email'    => fsv($f,'email'),
            'telefono' => fsv($f,'telefono'),
            'codigo'   => fsv($f,'codigo'),
            'activo'   => $f['activo']['booleanValue'] ?? true,
            'created_at'=> fsv($f,'created_at'),
        ];
    }
    echo json_encode(['ok'=>true,'vendedores'=>$vendedores,'tienda_activa'=>$tid]);
    exit;
}

// ── POST crear ───────────────────────────────────────────────
if ($method === 'POST' && $accion === 'crear') {
    $nombre   = trim($input['nombre'] ?? '');
    $email    = trim($input['email'] ?? '');
    $telefono = trim($input['telefono'] ?? '');

    if (!$nombre) {
        echo json_encode(['ok'=>false,'error'=>'Nombre requerido']);
        exit;
    }

    // Generar código de acceso único (6 chars alfanumérico)
    $codigo = strtoupper(substr(md5(uniqid().$nombre), 0, 6));
    $vid    = 'vnd_'.uniqid();
    $now    = date('c');

    firestoreRequest('PATCH', vendedoresPath($uid,$tid)."/{$vid}", ['fields' => [
        'nombre'     => ['stringValue' => $nombre],
        'email'      => ['stringValue' => $email],
        'telefono'   => ['stringValue' => $telefono],
        'codigo'     => ['stringValue' => $codigo],
        'activo'     => ['booleanValue'=> true],
        'created_at' => ['stringValue' => $now],
    ]]);

    echo json_encode(['ok'=>true,'id'=>$vid,'codigo'=>$codigo,'nombre'=>$nombre]);
    exit;
}

// ── POST actualizar ──────────────────────────────────────────
if ($method === 'POST' && $accion === 'actualizar') {
    $vid      = trim($input['vendedor_id'] ?? '');
    $nombre   = trim($input['nombre'] ?? '');
    $email    = trim($input['email'] ?? '');
    $telefono = trim($input['telefono'] ?? '');
    $activo   = isset($input['activo']) ? (bool)$input['activo'] : true;

    if (!$vid || !$nombre) {
        echo json_encode(['ok'=>false,'error'=>'ID y nombre requeridos']);
        exit;
    }

    firestoreRequest('PATCH', vendedoresPath($uid,$tid)."/{$vid}", ['fields' => [
        'nombre'   => ['stringValue' => $nombre],
        'email'    => ['stringValue' => $email],
        'telefono' => ['stringValue' => $telefono],
        'activo'   => ['booleanValue'=> $activo],
    ]]);

    echo json_encode(['ok'=>true]);
    exit;
}

// ── POST eliminar ────────────────────────────────────────────
if ($method === 'POST' && $accion === 'eliminar') {
    $vid = trim($input['vendedor_id'] ?? '');
    if (!$vid) { echo json_encode(['ok'=>false,'error'=>'vendedor_id requerido']); exit; }
    firestoreRequest('DELETE', vendedoresPath($uid,$tid)."/{$vid}");
    echo json_encode(['ok'=>true]);
    exit;
}

// ── POST regenerar código ────────────────────────────────────
if ($method === 'POST' && $accion === 'regenerar_codigo') {
    $vid    = trim($input['vendedor_id'] ?? '');
    $codigo = strtoupper(substr(md5(uniqid()), 0, 6));
    if (!$vid) { echo json_encode(['ok'=>false,'error'=>'vendedor_id requerido']); exit; }
    firestoreRequest('PATCH', vendedoresPath($uid,$tid)."/{$vid}", ['fields' => [
        'codigo' => ['stringValue' => $codigo],
    ]]);
    echo json_encode(['ok'=>true,'codigo'=>$codigo]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Acción no reconocida']);
