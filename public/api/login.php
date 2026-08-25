<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

require_once '/var/www/komercia/config/firebase.php';
define('FIREBASE_API_KEY', 'AIzaSyDDXhReEpwcXb44-jcJr5upWYeTmg-DUbM');

$body     = json_decode(file_get_contents('php://input'), true);
$email    = trim($body['email'] ?? '');
$password = $body['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['ok' => false, 'mensaje' => 'Correo y contraseña son obligatorios.']);
    exit;
}

$authResp = @file_get_contents(
    "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=" . FIREBASE_API_KEY,
    false,
    stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => 'Content-Type: application/json',
            'content'       => json_encode(['email' => $email, 'password' => $password, 'returnSecureToken' => true]),
            'ignore_errors' => true
        ]
    ])
);

$authData = json_decode($authResp, true);

if (isset($authData['error'])) {
    $msg = match($authData['error']['message'] ?? '') {
        'EMAIL_NOT_FOUND'       => 'No existe una cuenta con ese correo.',
        'INVALID_PASSWORD'      => 'Contraseña incorrecta.',
        'USER_DISABLED'         => 'Tu cuenta ha sido desactivada.',
        'INVALID_LOGIN_CREDENTIALS' => 'Correo o contraseña incorrectos.',
        default                 => 'No se pudo iniciar sesión. Intenta de nuevo.'
    };
    echo json_encode(['ok' => false, 'mensaje' => $msg]);
    exit;
}

$uid = $authData['localId'];

// Obtener datos del comerciante desde Firestore
try {
    $comerciante = firestoreRequest('GET', "comerciantes/{$uid}");
    $fields = $comerciante['fields'] ?? [];
    $slug   = $fields['slug']['stringValue'] ?? '';
    $nombre = $fields['nombre']['stringValue'] ?? '';
    $plan   = $fields['plan']['stringValue'] ?? 'trial';
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'mensaje' => 'Error al obtener datos de tu cuenta.']);
    exit;
}

// Si slug estaba vacío en Firestore, intentar recuperarlo desde tiendas raíz
if (empty($slug)) {
    $tiendasRes = firestoreRequest('GET', "tiendas?pageSize=5");
    foreach ($tiendasRes['documents'] ?? [] as $tdoc) {
        $tf = $tdoc['fields'] ?? [];
        if (($tf['uid']['stringValue'] ?? '') === $uid && !empty($tf['slug']['stringValue'])) {
            $slug = $tf['slug']['stringValue'];
            break;
        }
    }
}

session_start();
$_SESSION['uid']           = $uid;
$_SESSION['email']         = $email;
$_SESSION['slug']          = $slug;
$_SESSION['nombre']        = $nombre;
$_SESSION['plan']          = $plan;
// Limpiar contexto de tienda adicional al iniciar sesión
unset($_SESSION['tienda_activa'], $_SESSION['tienda_nombre']);

echo json_encode(['ok' => true, 'uid' => $uid, 'slug' => $slug, 'nombre' => $nombre]);
