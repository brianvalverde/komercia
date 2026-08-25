<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
session_start();

require_once '/var/www/komercia/config/firebase.php';
define('FIREBASE_API_KEY', 'AIzaSyDDXhReEpwcXb44-jcJr5upWYeTmg-DUbM');

$data = json_decode(file_get_contents('php://input'), true);

$nombre       = trim($data['nombre'] ?? '');
$email        = trim($data['email'] ?? '');
$telefono     = trim($data['telefono'] ?? '');
$nombreTienda = trim($data['nombreTienda'] ?? '');
$slug         = trim($data['slug'] ?? '');
$password     = $data['password'] ?? '';

// Validaciones
if (!$nombre || !$email || !$password || !$nombreTienda || !$slug) {
    echo json_encode(['ok' => false, 'error' => 'Todos los campos son requeridos']);
    exit;
}
if (strlen($password) < 8) {
    echo json_encode(['ok' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Email inválido']);
    exit;
}

// Verificar que el slug no esté ocupado
$check = firestoreRequest('GET', "tiendas/{$slug}");
if (!isset($check['error'])) {
    echo json_encode(['ok' => false, 'error' => 'Ese nombre de tienda ya está ocupado']);
    exit;
}

// Crear usuario en Firebase Auth
$authRes = @file_get_contents(
    'https://identitytoolkit.googleapis.com/v1/accounts:signUp?key=' . FIREBASE_API_KEY,
    false,
    stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/json',
            'content' => json_encode([
                'email'             => $email,
                'password'          => $password,
                'returnSecureToken' => true,
            ]),
            'ignore_errors' => true,
        ]
    ])
);

$auth = json_decode($authRes, true);

if (isset($auth['error'])) {
    $msg = $auth['error']['message'] ?? 'Error al crear cuenta';
    if ($msg === 'EMAIL_EXISTS') $msg = 'Este email ya está registrado';
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

$uid = $auth['localId'];

// Guardar comerciante en Firestore
$trialExpira = date('c', strtotime('+7 days'));
$comercianteData = [
    'fields' => [
        'uid'          => ['stringValue'  => $uid],
        'nombre'       => ['stringValue'  => $nombre],
        'email'        => ['stringValue'  => $email],
        'telefono'     => ['stringValue'  => $telefono],
        'nombreTienda' => ['stringValue'  => $nombreTienda],
        'slug'         => ['stringValue'  => $slug],
        'plan'         => ['stringValue'  => 'trial'],
        'trial_expira' => ['stringValue'  => $trialExpira],
        'activo'       => ['booleanValue' => true],
        'creado_en'    => ['stringValue'  => date('c')],
    ]
];
$maskCom = 'updateMask.fieldPaths=nombre&updateMask.fieldPaths=email&updateMask.fieldPaths=telefono&updateMask.fieldPaths=nombreTienda&updateMask.fieldPaths=slug&updateMask.fieldPaths=plan&updateMask.fieldPaths=trial_expira&updateMask.fieldPaths=activo&updateMask.fieldPaths=creado_en';
firestoreRequest('PATCH', "comerciantes/{$uid}?{$maskCom}", $comercianteData);

// Guardar tienda en Firestore (con nombreTienda y telefono)
$tiendaData = [
    'fields' => [
        'uid'          => ['stringValue'  => $uid],
        'slug'         => ['stringValue'  => $slug],
        'nombre'       => ['stringValue'  => $nombreTienda],
        'nombreTienda' => ['stringValue'  => $nombreTienda],
        'telefono'     => ['stringValue'  => $telefono],
        'activo'       => ['booleanValue' => true],
        'creado_en'    => ['stringValue'  => date('c')],
    ]
];
$maskTda = 'updateMask.fieldPaths=uid&updateMask.fieldPaths=slug&updateMask.fieldPaths=nombre&updateMask.fieldPaths=nombreTienda&updateMask.fieldPaths=telefono&updateMask.fieldPaths=activo&updateMask.fieldPaths=creado_en';
firestoreRequest('PATCH', "tiendas/{$slug}?{$maskTda}", $tiendaData);

// Iniciar sesión
$_SESSION['uid']    = $uid;
$_SESSION['email']  = $email;
$_SESSION['slug']   = $slug;
$_SESSION['nombre'] = $nombre;
$_SESSION['plan']   = 'trial';

echo json_encode([
    'ok'      => true,
    'mensaje' => 'Tienda creada correctamente',
    'uid'     => $uid,
    'slug'    => $slug,
]);
