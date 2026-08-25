<?php
header('Content-Type: application/json');
session_start();
ob_start();

require_once '/var/www/komercia/config/firebase.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['accion'] ?? '';
$prodId = $_GET['producto_id'] ?? '';

// ─── LISTAR reseñas de un producto (público) ─────────────────
if ($method === 'GET' && $action === 'listar' && $prodId) {
    $slug = $_GET['slug'] ?? '';
    if (!$slug) { ob_end_clean(); echo json_encode(['ok'=>false,'error'=>'slug requerido']); exit; }

    $tiendaDoc = firestoreRequest('GET', "tiendas/{$slug}");
    $uid = $tiendaDoc['fields']['uid']['stringValue'] ?? '';
    if (!$uid) { ob_end_clean(); echo json_encode(['ok'=>false,'resenas',[]]); exit; }

    $res  = firestoreRequest('GET', "comerciantes/{$uid}/productos/{$prodId}/resenas");
    $docs = $res['documents'] ?? [];
    $resenas = [];
    foreach ($docs as $doc) {
        $f = $doc['fields'] ?? [];
        $aprobada = $f['aprobada']['booleanValue'] ?? false;
        if (!$aprobada) continue;
        $resenas[] = [
            'id'         => basename($doc['name']),
            'nombre'     => $f['nombre']['stringValue'] ?? 'Anónimo',
            'pais'       => $f['pais']['stringValue'] ?? '',
            'estrellas'  => (int)($f['estrellas']['integerValue'] ?? 5),
            'comentario' => $f['comentario']['stringValue'] ?? '',
            'fecha'      => $f['fecha']['stringValue'] ?? '',
        ];
    }
    // Ordenar por fecha desc
    usort($resenas, fn($a,$b) => strcmp($b['fecha'], $a['fecha']));
    ob_end_clean();
    echo json_encode(['ok'=>true,'resenas'=>$resenas]);
    exit;
}

// ─── CREAR reseña (público, cliente desde tienda) ────────────
if ($method === 'POST' && $action === 'crear') {
    $slug       = trim($_POST['slug'] ?? '');
    $prodId     = trim($_POST['producto_id'] ?? '');
    $nombre     = trim($_POST['nombre'] ?? '');
    $pais       = trim($_POST['pais'] ?? '');
    $estrellas  = max(1, min(5, intval($_POST['estrellas'] ?? 5)));
    $comentario = trim($_POST['comentario'] ?? '');

    if (!$slug || !$prodId || !$nombre || !$comentario) {
        ob_end_clean();
        echo json_encode(['ok'=>false,'error'=>'Datos incompletos']);
        exit;
    }

    $tiendaDoc = firestoreRequest('GET', "tiendas/{$slug}");
    $uid = $tiendaDoc['fields']['uid']['stringValue'] ?? '';
    if (!$uid) { ob_end_clean(); echo json_encode(['ok'=>false,'error'=>'Tienda no encontrada']); exit; }

    $resenaId = uniqid('res_');
    $data = ['fields' => [
        'nombre'     => ['stringValue'  => $nombre],
        'pais'       => ['stringValue'  => $pais],
        'estrellas'  => ['integerValue' => $estrellas],
        'comentario' => ['stringValue'  => $comentario],
        'fecha'      => ['stringValue'  => date('c')],
        'aprobada'   => ['booleanValue' => false], // pendiente moderación
        'manual'     => ['booleanValue' => false],
    ]];

    $maskR = 'updateMask.fieldPaths=nombre&updateMask.fieldPaths=pais&updateMask.fieldPaths=estrellas&updateMask.fieldPaths=comentario&updateMask.fieldPaths=fecha&updateMask.fieldPaths=aprobada&updateMask.fieldPaths=manual';
    firestoreRequest('PATCH', "comerciantes/{$uid}/productos/{$prodId}/resenas/{$resenaId}?{$maskR}", $data);
    ob_end_clean();
    echo json_encode(['ok'=>true,'msg'=>'Reseña enviada, pendiente de aprobación']);
    exit;
}

// ─── A partir de aquí requiere sesión ────────────────────────
if (empty($_SESSION['uid'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'No autenticado']);
    exit;
}
$uid = $_SESSION['uid'];

// ─── LISTAR reseñas de un producto (panel) ───────────────────
if ($method === 'GET' && $action === 'listar_panel' && $prodId) {
    $res  = firestoreRequest('GET', "comerciantes/{$uid}/productos/{$prodId}/resenas");
    $docs = $res['documents'] ?? [];
    $resenas = [];
    foreach ($docs as $doc) {
        $f = $doc['fields'] ?? [];
        $resenas[] = [
            'id'         => basename($doc['name']),
            'nombre'     => $f['nombre']['stringValue'] ?? '',
            'pais'       => $f['pais']['stringValue'] ?? '',
            'estrellas'  => (int)($f['estrellas']['integerValue'] ?? 5),
            'comentario' => $f['comentario']['stringValue'] ?? '',
            'fecha'      => $f['fecha']['stringValue'] ?? '',
            'aprobada'   => $f['aprobada']['booleanValue'] ?? false,
            'manual'     => $f['manual']['booleanValue'] ?? false,
        ];
    }
    usort($resenas, fn($a,$b) => strcmp($b['fecha'], $a['fecha']));
    ob_end_clean();
    echo json_encode(['ok'=>true,'resenas'=>$resenas]);
    exit;
}

// ─── CREAR reseña manual (comerciante desde panel) ───────────
if ($method === 'POST' && $action === 'crear_manual') {
    $prodId     = trim($_POST['producto_id'] ?? '');
    $nombre     = trim($_POST['nombre'] ?? '');
    $pais       = trim($_POST['pais'] ?? '');
    $estrellas  = max(1, min(5, intval($_POST['estrellas'] ?? 5)));
    $comentario = trim($_POST['comentario'] ?? '');
    $fecha      = trim($_POST['fecha'] ?? date('Y-m-d'));

    if (!$prodId || !$nombre || !$comentario) {
        ob_end_clean();
        echo json_encode(['ok'=>false,'error'=>'Datos incompletos']);
        exit;
    }

    $resenaId = uniqid('res_');
    $data = ['fields' => [
        'nombre'     => ['stringValue'  => $nombre],
        'pais'       => ['stringValue'  => $pais],
        'estrellas'  => ['integerValue' => $estrellas],
        'comentario' => ['stringValue'  => $comentario],
        'fecha'      => ['stringValue'  => $fecha . 'T00:00:00-05:00'],
        'aprobada'   => ['booleanValue' => true], // aprobada automáticamente
        'manual'     => ['booleanValue' => true],
    ]];

    $maskM = 'updateMask.fieldPaths=nombre&updateMask.fieldPaths=pais&updateMask.fieldPaths=estrellas&updateMask.fieldPaths=comentario&updateMask.fieldPaths=fecha&updateMask.fieldPaths=aprobada&updateMask.fieldPaths=manual';
    firestoreRequest('PATCH', "comerciantes/{$uid}/productos/{$prodId}/resenas/{$resenaId}?{$maskM}", $data);
    ob_end_clean();
    echo json_encode(['ok'=>true,'id'=>$resenaId]);
    exit;
}

// ─── APROBAR / RECHAZAR reseña ───────────────────────────────
if ($method === 'POST' && $action === 'moderar') {
    $prodId   = trim($_POST['producto_id'] ?? '');
    $resenaId = trim($_POST['resena_id'] ?? '');
    $aprobada = ($_POST['aprobada'] ?? 'false') === 'true';

    if (!$prodId || !$resenaId) { ob_end_clean(); echo json_encode(['ok'=>false,'error'=>'IDs requeridos']); exit; }

    $fields = ['aprobada' => ['booleanValue' => $aprobada]];
    $maskQuery = 'updateMask.fieldPaths=aprobada';
    firestoreRequest('PATCH', "comerciantes/{$uid}/productos/{$prodId}/resenas/{$resenaId}?{$maskQuery}", ['fields'=>$fields]);
    ob_end_clean();
    echo json_encode(['ok'=>true]);
    exit;
}

// ─── ELIMINAR reseña ─────────────────────────────────────────
if ($method === 'DELETE' && $action === 'eliminar') {
    $resenaId = $_GET['resena_id'] ?? '';
    if (!$prodId || !$resenaId) { ob_end_clean(); echo json_encode(['ok'=>false,'error'=>'IDs requeridos']); exit; }
    firestoreRequest('DELETE', "comerciantes/{$uid}/productos/{$prodId}/resenas/{$resenaId}");
    ob_end_clean();
    echo json_encode(['ok'=>true]);
    exit;
}

ob_end_clean();
echo json_encode(['ok'=>false,'error'=>'Acción no reconocida']);
