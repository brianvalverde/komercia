<?php
// ─────────────────────────────────────────────────────────────
// admin/accion.php — Endpoint AJAX del panel admin
// Acciones: activar, pausar, reactivar, pagos_lista, pago_agregar
// ─────────────────────────────────────────────────────────────
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'No autorizado']);
    exit;
}

require_once '/var/www/komercia/config/firebase.php';

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$accion = $input['accion'] ?? '';
$uid    = $input['uid']    ?? '';

function fsv($f,$k,$d=''){return $f[$k]['stringValue']??$f[$k]['booleanValue']??$f[$k]['integerValue']??$d;}

// ── Helpers de cascada ────────────────────────────────────────

/**
 * Recorre todas las tiendas adicionales del comerciante y
 * actualiza su campo 'activo' en Firestore.
 */
function actualizarTiendasCascada($uid, $activo) {
    $res = firestoreRequest('GET', "comerciantes/{$uid}/tiendas?pageSize=50");
    foreach ($res['documents'] ?? [] as $doc) {
        $parts = explode('/', $doc['name']);
        $tid   = end($parts);
        firestoreRequest('PATCH', "comerciantes/{$uid}/tiendas/{$tid}", [
            'fields' => ['activo' => ['booleanValue' => $activo]]
        ]);
    }
}

// ── pausar ────────────────────────────────────────────────────
if ($accion === 'pausar') {
    if (!$uid) { echo json_encode(['ok'=>false,'error'=>'uid requerido']); exit; }

    // 1. Marcar plan inactivo en comerciante principal
    firestoreRequest('PATCH', "comerciantes/{$uid}", [
        'fields' => ['plan_activo' => ['booleanValue' => false]]
    ]);

    // 2. Desactivar todas las tiendas adicionales en cascada
    actualizarTiendasCascada($uid, false);

    echo json_encode(['ok'=>true]);
    exit;
}

// ── reactivar ─────────────────────────────────────────────────
if ($accion === 'reactivar') {
    if (!$uid) { echo json_encode(['ok'=>false,'error'=>'uid requerido']); exit; }

    // 1. Reactivar plan en comerciante principal
    firestoreRequest('PATCH', "comerciantes/{$uid}", [
        'fields' => ['plan_activo' => ['booleanValue' => true]]
    ]);

    // 2. Reactivar todas las tiendas adicionales en cascada
    actualizarTiendasCascada($uid, true);

    echo json_encode(['ok'=>true]);
    exit;
}

// ── activar (cambiar plan manualmente) ────────────────────────
if ($accion === 'activar') {
    $plan  = $input['plan']  ?? 'pro';
    $meses = intval($input['meses'] ?? 1);
    if (!$uid) { echo json_encode(['ok'=>false,'error'=>'uid requerido']); exit; }

    $expira = date('c', strtotime("+{$meses} months"));
    firestoreRequest('PATCH', "comerciantes/{$uid}", [
        'fields' => [
            'plan'        => ['stringValue'  => $plan],
            'plan_activo' => ['booleanValue' => true],
            'plan_expira' => ['stringValue'  => $expira],
        ]
    ]);

    echo json_encode(['ok'=>true,'plan'=>$plan,'expira'=>$expira]);
    exit;
}

// ── pagos_lista ───────────────────────────────────────────────
if ($accion === 'pagos_lista') {
    if (!$uid) { echo json_encode(['ok'=>false,'error'=>'uid requerido']); exit; }
    $res    = firestoreRequest('GET', "comerciantes/{$uid}/pagos?pageSize=50");
    $pagos  = [];
    foreach ($res['documents'] ?? [] as $d) {
        $f = $d['fields'] ?? [];
        $pagos[] = [
            'fecha'    => fsv($f,'fecha'),
            'plan'     => fsv($f,'plan'),
            'meses'    => fsv($f,'meses',1),
            'monto'    => fsv($f,'monto',''),
            'metodo'   => fsv($f,'metodo',''),
            'referencia'=> fsv($f,'referencia',''),
            'nota'     => fsv($f,'nota',''),
        ];
    }
    // Ordenar por fecha desc
    usort($pagos, fn($a,$b) => strcmp($b['fecha'],$a['fecha']));
    echo json_encode(['ok'=>true,'pagos'=>$pagos]);
    exit;
}

// ── pago_agregar ──────────────────────────────────────────────
if ($accion === 'pago_agregar') {
    if (!$uid) { echo json_encode(['ok'=>false,'error'=>'uid requerido']); exit; }

    $plan      = $input['plan']      ?? 'pro';
    $meses     = intval($input['meses']    ?? 1);
    $monto     = $input['monto']     ?? '';
    $metodo    = $input['metodo']    ?? '';
    $referencia= $input['referencia']?? '';
    $nota      = $input['nota']      ?? '';
    $now       = date('c');
    $pid       = 'pago_'.uniqid();

    // Registrar pago
    firestoreRequest('PATCH', "comerciantes/{$uid}/pagos/{$pid}", ['fields' => [
        'fecha'      => ['stringValue' => $now],
        'plan'       => ['stringValue' => $plan],
        'meses'      => ['integerValue'=> $meses],
        'monto'      => ['stringValue' => (string)$monto],
        'metodo'     => ['stringValue' => $metodo],
        'referencia' => ['stringValue' => $referencia],
        'nota'       => ['stringValue' => $nota],
    ]]);

    // Calcular nueva fecha de expiración
    // Si ya tenía un plan activo, extender desde la fecha actual de expiración
    $doc     = firestoreRequest('GET', "comerciantes/{$uid}");
    $f       = $doc['fields'] ?? [];
    $expActual = $f['plan_expira']['stringValue'] ?? '';
    $base    = ($expActual && strtotime($expActual) > time()) ? strtotime($expActual) : time();
    $expira  = date('c', strtotime("+{$meses} months", $base));

    // Activar/extender plan — también reactiva tiendas en cascada si era empresarial
    firestoreRequest('PATCH', "comerciantes/{$uid}", ['fields' => [
        'plan'        => ['stringValue'  => $plan],
        'plan_activo' => ['booleanValue' => true],
        'plan_expira' => ['stringValue'  => $expira],
    ]]);

    // Si el plan es empresarial, reactivar tiendas que pudieran estar pausadas
    if ($plan === 'empresarial') {
        actualizarTiendasCascada($uid, true);
    }

    echo json_encode(['ok'=>true,'pid'=>$pid,'expira'=>$expira]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Acción no reconocida']);
