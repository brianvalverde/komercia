<?php
require_once __DIR__ . '/../config/firebase.php';

try {
    $token = getFirebaseToken();
    if ($token) {
        echo "✅ Conexión con Firebase exitosa!";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
