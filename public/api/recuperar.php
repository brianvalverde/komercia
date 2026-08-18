<?php
header('Content-Type: application/json');

define('FIREBASE_API_KEY', 'AIzaSyDDXhReEpwcXb44-jcJr5upWYeTmg-DUbM');

$data  = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Email inválido']);
    exit;
}

$res = @file_get_contents(
    'https://identitytoolkit.googleapis.com/v1/accounts:sendOobCode?key=' . FIREBASE_API_KEY,
    false,
    stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => 'Content-Type: application/json',
            'content'       => json_encode([
                'requestType' => 'PASSWORD_RESET',
                'email'       => $email,
            ]),
            'ignore_errors' => true,
        ]
    ])
);

$json = json_decode($res, true);

// Siempre devolvemos ok:true por seguridad (no revelar si el email existe)
echo json_encode(['ok' => true]);
