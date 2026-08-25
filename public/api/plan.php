<?php
// ─────────────────────────────────────────────────────────────
// api/plan.php — Gestión de planes de comerciantes
// GET  ?accion=info      → devuelve plan actual del usuario logueado
// POST ?accion=activar   → activa/cambia plan (webhook o admin)
// ─────────────────────────────────────────────────────────────
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

require_once '/var/www/komercia/config/firebase.php';

$uid    = $_SESSION['uid'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['accion'] ?? 'info';

// ── Helpers ────────────────────────────────────────────────────
function fsv2(array $f, string $k, $d = '') {
    return $f[$k]['stringValue']  ??
           $f[$k]['booleanValue'] ??
           $f[$k]['integerValue'] ??
           $f[$k]['doubleValue']  ?? $d;
}

// ── GET info ────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'info') {
    $doc = firestoreRequest('GET', "comerciantes/{$uid}");
    $f   = $doc['fields'] ?? [];

    $plan       = fsv2($f, 'plan', 'trial');
    $planActivo = fsv2($f, 'plan_activo', true);
    $planExpira = fsv2($f, 'plan_expira', '');
    $trialIni   = fsv2($f, 'trial_inicio', '');

    // Calcular si está vencido
    $vencido = false;
    $diasRestantes = null;

    if ($planExpira) {
        $expTs = strtotime($planExpira);
        $vencido = $expTs < time();
        $diasRestantes = max(0, (int)ceil(($expTs - time()) / 86400));
    } elseif ($plan === 'trial' && $trialIni) {
        // Trial: 7 días desde inicio
        $expTs = strtotime($trialIni) + (7 * 86400);
        $vencido = $expTs < time();
        $diasRestantes = max(0, (int)ceil(($expTs - time()) / 86400));
        // Guardar fecha de expiración calculada si no existía
        if (!$planExpira) {
            $planExpira = date('c', $expTs);
        }
    } elseif ($plan === 'trial' && !$trialIni) {
        // Sin fecha de inicio → iniciamos el trial ahora
        $trialIni   = date('c');
        $planExpira = date('c', time() + 7 * 86400);
        $diasRestantes = 7;
        // Escribir en Firestore
        firestoreRequest('PATCH',
            "comerciantes/{$uid}?updateMask.fieldPaths=trial_inicio&updateMask.fieldPaths=plan_expira&updateMask.fieldPaths=plan&updateMask.fieldPaths=plan_activo",
            ['fields' => [
                'plan'         => ['stringValue'  => 'trial'],
                'plan_activo'  => ['booleanValue' => true],
                'trial_inicio' => ['stringValue'  => $trialIni],
                'plan_expira'  => ['stringValue'  => $planExpira],
            ]]
        );
    }

    // Límites por plan
    $limites = [
        'trial'        => ['productos' => 10,  'label' => 'Trial'],
        'pro'          => ['productos' => 500,  'label' => 'Pro'],
        'empresarial'  => ['productos' => 9999, 'label' => 'Empresarial'],
    ];
    $limite = $limites[$plan] ?? $limites['trial'];

    echo json_encode([
        'ok'             => true,
        'plan'           => $plan,
        'plan_label'     => $limite['label'],
        'plan_activo'    => (bool)$planActivo,
        'plan_expira'    => $planExpira,
        'trial_inicio'   => $trialIni,
        'vencido'        => $vencido,
        'dias_restantes' => $diasRestantes,
        'limite_productos'=> $limite['productos'],
    ]);
    exit;
}

// ── POST activar (futuro webhook Culqi/Stripe) ─────────────────
if ($method === 'POST' && $action === 'activar') {
    $plan      = trim($_POST['plan'] ?? '');
    $meses     = (int)($_POST['meses'] ?? 1);
    $allowedPlans = ['trial', 'pro', 'empresarial'];
    if (!in_array($plan, $allowedPlans)) {
        echo json_encode(['ok' => false, 'error' => 'Plan inválido']);
        exit;
    }
    $expira = date('c', strtotime("+{$meses} month"));
    firestoreRequest('PATCH',
        "comerciantes/{$uid}?updateMask.fieldPaths=plan&updateMask.fieldPaths=plan_activo&updateMask.fieldPaths=plan_expira",
        ['fields' => [
            'plan'        => ['stringValue'  => $plan],
            'plan_activo' => ['booleanValue' => true],
            'plan_expira' => ['stringValue'  => $expira],
        ]]
    );
    echo json_encode(['ok' => true, 'plan' => $plan, 'expira' => $expira]);
    exit;
}

// ── POST desactivar (cuando falla el cobro) ────────────────────
if ($method === 'POST' && $action === 'desactivar') {
    firestoreRequest('PATCH',
        "comerciantes/{$uid}?updateMask.fieldPaths=plan_activo",
        ['fields' => ['plan_activo' => ['booleanValue' => false]]]
    );
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Acción no reconocida']);
