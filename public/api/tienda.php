<?php
session_start();
ob_start(); // captura cualquier warning/notice antes del JSON

header('Content-Type: application/json; charset=utf-8');

// Auth guard
if (empty($_SESSION['uid']) || empty($_SESSION['slug'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once '/var/www/komercia/config/firebase.php';
require_once '/var/www/komercia/config/wasabi.php';

$slug   = $_SESSION['slug'];
$accion = $_GET['accion'] ?? '';

// ─── GET: obtener datos de la tienda ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'obtener') {

    $response = firestoreRequest('GET', "tiendas/{$slug}");

    if (!$response || isset($response['error'])) {
        // Document may not exist yet — return empty object
        echo json_encode([
            'nombre'          => '',
            'descripcion'     => '',
            'logo'            => '',
            'telefono'        => '',
            'direccion'       => '',
            'email'           => '',
            'color_primario'  => '#ff6a00',
        ]);
        exit;
    }

    $fields = $response['fields'] ?? [];

    function firestoreValue(array $fields, string $key): string {
        if (!isset($fields[$key])) return '';
        $field = $fields[$key];
        return $field['stringValue']
            ?? $field['integerValue']
            ?? $field['doubleValue']
            ?? '';
    }

    echo json_encode([
        'nombre'         => firestoreValue($fields, 'nombre'),
        'descripcion'    => firestoreValue($fields, 'descripcion'),
        'logo'           => firestoreValue($fields, 'logo'),
        'telefono'       => firestoreValue($fields, 'telefono'),
        'direccion'      => firestoreValue($fields, 'direccion'),
        'email'          => firestoreValue($fields, 'email'),
        'color_primario' => firestoreValue($fields, 'color_primario') ?: '#ff6a00',
    ]);
    exit;
}

// ─── POST: guardar datos de la tienda ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'guardar') {

    $nombre         = trim($_POST['nombre']         ?? '');
    $descripcion    = trim($_POST['descripcion']    ?? '');
    $email          = trim($_POST['email']          ?? '');
    $telefono       = trim($_POST['telefono']       ?? '');
    $direccion      = trim($_POST['direccion']      ?? '');
    $color_primario = trim($_POST['color_primario'] ?? '#ff6a00');

    if ($nombre === '') {
        http_response_code(422);
        echo json_encode(['error' => 'El nombre de la tienda es obligatorio']);
        exit;
    }

    // Validate hex color
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color_primario)) {
        $color_primario = '#ff6a00';
    }

    // Build Firestore fields map
    // uid se incluye siempre para que nunca se pierda (aunque updateMask ya lo protege)
    $uid_session = $_SESSION['uid'];
    $fields = [
        'uid'            => ['stringValue' => $uid_session],
        'nombre'         => ['stringValue' => $nombre],
        'descripcion'    => ['stringValue' => $descripcion],
        'email'          => ['stringValue' => $email],
        'telefono'       => ['stringValue' => $telefono],
        'direccion'      => ['stringValue' => $direccion],
        'color_primario' => ['stringValue' => $color_primario],
    ];

    // ── Logo upload ──────────────────────────────────────────────────────────
    if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['logo'];
        $maxBytes = 5 * 1024 * 1024; // 5 MB

        if ($file['size'] > $maxBytes) {
            http_response_code(422);
            echo json_encode(['error' => 'El logo supera los 5 MB']);
            exit;
        }

        // Determine extension; client sends webp but allow fallbacks
        $mime = mime_content_type($file['tmp_name']);
        $extMap = [
            'image/webp' => 'webp',
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
        ];
        $ext = $extMap[$mime] ?? 'webp';

        $key      = "tiendas/{$slug}/logo/" . uniqid('', true) . ".{$ext}";
        $tmpPath  = $file['tmp_name'];
        $fileData = file_get_contents($tmpPath);

        if ($fileData === false) {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo leer el archivo de logo']);
            exit;
        }

        // Upload to Wasabi usando el mismo patrón que api_productos.php
        try {
            $s3 = getWasabiClient();
            $s3->putObject([
                'Bucket'      => WASABI_BUCKET,
                'Key'         => $key,
                'Body'        => $fileData,
                'ContentType' => $mime,
                'ACL'         => 'public-read',
            ]);
        } catch (Exception $e) {
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Error al subir el logo: ' . $e->getMessage()]);
            exit;
        }

        $logoUrl = rtrim(WASABI_URL_BASE, '/') . '/' . $key;
        $fields['logo'] = ['stringValue' => $logoUrl];
    }

    // ── PATCH Firestore con updateMask para no borrar uid ni otros campos ──────
    $body = ['fields' => $fields];

    // Construir updateMask solo con los campos que estamos actualizando
    $maskFields = array_keys($fields);
    $maskQuery  = implode('&', array_map(fn($f) => 'updateMask.fieldPaths=' . urlencode($f), $maskFields));

    $result = firestoreRequest('PATCH', "tiendas/{$slug}?{$maskQuery}", $body);

    if (!$result || isset($result['error'])) {
        $errMsg = is_array($result['error'] ?? null)
            ? ($result['error']['message'] ?? 'Error al guardar en Firestore')
            : ($result['error'] ?? 'Error al guardar en Firestore');
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['error' => $errMsg]);
        exit;
    }

    // Return updated logo URL if it was changed
    $responseData = ['ok' => true];
    if (isset($fields['logo'])) {
        $responseData['logo'] = $fields['logo']['stringValue'];
    }

    ob_end_clean();
    echo json_encode($responseData);
    exit;
}

// ─── Fallback ─────────────────────────────────────────────────────────────────
ob_end_clean();
http_response_code(400);
echo json_encode(['error' => 'Acción no válida']);
