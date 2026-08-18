<?php
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['uid'])) {
    echo json_encode(['ok' => false]);
    exit;
}

require_once '/var/www/komercia/config/firebase.php';

try {
    $uid = $_SESSION['uid'];
    $comerciante = firestoreRequest('GET', "comerciantes/{$uid}");
    $f = $comerciante['fields'] ?? [];
    echo json_encode([
        'ok'           => true,
        'uid'          => $uid,
        'nombre'       => $f['nombre']['stringValue'] ?? '',
        'email'        => $f['email']['stringValue'] ?? '',
        'slug'         => $f['slug']['stringValue'] ?? '',
        'plan'         => $f['plan']['stringValue'] ?? 'trial',
        'trial_expira' => $f['trial_expira']['stringValue'] ?? '',
    ]);
} catch (Exception $e) {
    echo json_encode(['ok' => false]);
}
