<?php
// Script de reparación de uid en tiendas/{slug}
// Ejecutar UNA vez desde el navegador mientras estés logueado
// Luego borrar este archivo del servidor
session_start();
header('Content-Type: text/plain; charset=utf-8');

if (empty($_SESSION['uid']) || empty($_SESSION['slug'])) {
    die("ERROR: No hay sesión activa. Inicia sesión primero en /login.php y luego vuelve a esta URL.");
}

require_once '/var/www/komercia/config/firebase.php';

$uid  = $_SESSION['uid'];
$slug = $_SESSION['slug'];

echo "uid en sesión: {$uid}\n";
echo "slug en sesión: {$slug}\n\n";

// Verificar estado actual del documento
$doc = firestoreRequest('GET', "tiendas/{$slug}");
$camposActuales = array_keys($doc['fields'] ?? []);
echo "Campos actuales en Firestore: " . implode(', ', $camposActuales) . "\n\n";

// Restaurar uid con updateMask para no tocar nada más
$body = [
    'fields' => [
        'uid' => ['stringValue' => $uid],
    ]
];

$result = firestoreRequest('PATCH', "tiendas/{$slug}?updateMask.fieldPaths=uid", $body);

if (isset($result['error'])) {
    echo "ERROR al restaurar uid: " . json_encode($result['error']) . "\n";
} else {
    echo "✅ uid restaurado correctamente en tiendas/{$slug}\n";
    echo "Ahora puedes borrar este archivo del servidor:\n";
    echo "  sudo rm /var/www/komercia/public/reparar_uid.php\n";
}
