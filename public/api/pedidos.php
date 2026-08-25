<?php
header('Content-Type: application/json');
session_start();
ob_start();

require_once '/var/www/komercia/config/firebase.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['accion'] ?? '';

// ─── CREAR PEDIDO (público, desde tienda) ───────────────────
if ($method === 'POST' && $action === 'crear') {
    $slug      = trim($_POST['slug'] ?? '');
    $nombre    = trim($_POST['nombre'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $notas     = trim($_POST['notas'] ?? '');
    $itemsJson = $_POST['items'] ?? '[]';
    $total     = floatval($_POST['total'] ?? 0);

    if (!$slug || !$nombre || !$telefono) {
        ob_end_clean();
        echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
        exit;
    }

    // Obtener uid y tienda_id del comerciante
    $tiendaDoc = firestoreRequest('GET', "tiendas/{$slug}");
    $uid       = $tiendaDoc['fields']['uid']['stringValue']       ?? '';
    $tienda_id = $tiendaDoc['fields']['tienda_id']['stringValue'] ?? 'main';
    if (!$uid) {
        ob_end_clean();
        echo json_encode(['ok' => false, 'error' => 'Tienda no encontrada']);
        exit;
    }

    // Ruta según si es tienda principal o adicional
    $pedidosBase = ($tienda_id && $tienda_id !== 'main')
        ? "comerciantes/{$uid}/tiendas/{$tienda_id}/pedidos"
        : "comerciantes/{$uid}/pedidos";

    $items = json_decode($itemsJson, true);
    if (!is_array($items)) $items = [];

    // Construir items para Firestore (arrayValue de maps)
    $itemsFS = array_map(function($it) {
        return ['mapValue' => ['fields' => [
            'nombre' => ['stringValue' => $it['nombre'] ?? ''],
            'qty'    => ['integerValue' => (int)($it['qty'] ?? 1)],
            'precio' => ['doubleValue'  => (float)($it['precio'] ?? 0)],
        ]]];
    }, $items);

    $pedidoId = uniqid('ped_');
    $data = [
        'fields' => [
            'slug'       => ['stringValue'  => $slug],
            'nombre'     => ['stringValue'  => $nombre],
            'telefono'   => ['stringValue'  => $telefono],
            'direccion'  => ['stringValue'  => $direccion],
            'notas'      => ['stringValue'  => $notas],
            'items'      => ['arrayValue'   => ['values' => $itemsFS]],
            'total'      => ['doubleValue'  => $total],
            'estado'     => ['stringValue'  => 'pendiente'],
            'creado_en'  => ['stringValue'  => date('c')],
        ]
    ];

    $res = firestoreRequest('PATCH', "{$pedidosBase}/{$pedidoId}", $data);
    ob_end_clean();

    if (!$res || isset($res['error'])) {
        echo json_encode(['ok' => false, 'error' => 'Error al guardar pedido']);
        exit;
    }
    echo json_encode(['ok' => true, 'id' => $pedidoId]);
    exit;
}

// ─── Requiere sesión para lo siguiente ──────────────────────
if (empty($_SESSION['uid'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}
$uid           = $_SESSION['uid'];
$tienda_activa = $_SESSION['tienda_activa'] ?? 'main';

// Ruta de pedidos según tienda activa
$pedidosBase = ($tienda_activa && $tienda_activa !== 'main')
    ? "comerciantes/{$uid}/tiendas/{$tienda_activa}/pedidos"
    : "comerciantes/{$uid}/pedidos";

// ─── LISTAR pedidos ─────────────────────────────────────────
if ($method === 'GET' && $action === 'listar') {
    $res  = firestoreRequest('GET', $pedidosBase);
    $docs = $res['documents'] ?? [];
    $pedidos = [];
    foreach ($docs as $doc) {
        $f  = $doc['fields'] ?? [];
        $id = basename($doc['name']);

        $items = [];
        if (isset($f['items']['arrayValue']['values'])) {
            foreach ($f['items']['arrayValue']['values'] as $v) {
                $mf = $v['mapValue']['fields'] ?? [];
                $items[] = [
                    'nombre' => $mf['nombre']['stringValue'] ?? '',
                    'qty'    => (int)($mf['qty']['integerValue'] ?? 1),
                    'precio' => (float)($mf['precio']['doubleValue'] ?? $mf['precio']['integerValue'] ?? 0),
                ];
            }
        }

        $pedidos[] = [
            'id'         => $id,
            'nombre'     => $f['nombre']['stringValue'] ?? '',
            'telefono'   => $f['telefono']['stringValue'] ?? '',
            'direccion'  => $f['direccion']['stringValue'] ?? '',
            'notas'      => $f['notas']['stringValue'] ?? '',
            'total'      => (float)($f['total']['doubleValue'] ?? $f['total']['integerValue'] ?? 0),
            'estado'     => $f['estado']['stringValue'] ?? 'pendiente',
            'clave'      => $f['clave']['stringValue'] ?? '',
            'creado_en'  => $f['creado_en']['stringValue'] ?? '',
            'items'      => $items,
        ];
    }
    // Ordenar por fecha desc
    usort($pedidos, fn($a,$b) => strcmp($b['creado_en'], $a['creado_en']));
    ob_end_clean();
    echo json_encode(['ok' => true, 'pedidos' => $pedidos]);
    exit;
}

// ─── ACTUALIZAR estado / clave ───────────────────────────────
if ($method === 'POST' && $action === 'actualizar') {
    $id     = trim($_POST['id'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $clave  = trim($_POST['clave'] ?? '');

    if (!$id) { ob_end_clean(); echo json_encode(['ok' => false, 'error' => 'ID requerido']); exit; }

    $validEstados = ['pendiente','confirmado','entregado','cancelado'];
    if ($estado && !in_array($estado, $validEstados)) $estado = '';

    $fields = [];
    if ($estado) $fields['estado'] = ['stringValue' => $estado];
    if ($clave !== '') $fields['clave'] = ['stringValue' => $clave];

    if (empty($fields)) { ob_end_clean(); echo json_encode(['ok' => false, 'error' => 'Sin cambios']); exit; }

    $maskQuery = implode('&', array_map(fn($f) => 'updateMask.fieldPaths=' . urlencode($f), array_keys($fields)));
    firestoreRequest('PATCH', "{$pedidosBase}/{$id}?{$maskQuery}", ['fields' => $fields]);
    ob_end_clean();
    echo json_encode(['ok' => true]);
    exit;
}

ob_end_clean();
echo json_encode(['ok' => false, 'error' => 'Acción no reconocida']);
