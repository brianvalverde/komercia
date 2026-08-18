<?php
session_start();
ob_start();

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['uid']) || empty($_SESSION['slug'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once '/var/www/komercia/config/firebase.php';
require_once '/var/www/komercia/config/wasabi.php';

$slug   = $_SESSION['slug'];
$accion = $_GET['accion'] ?? '';

// ─── GET: obtener datos de la tienda ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'obtener') {

    $response = firestoreRequest('GET', "tiendas/{$slug}");

    if (!$response || isset($response['error'])) {
        echo json_encode([
            'nombre'          => '',
            'descripcion'     => '',
            'logo'            => '',
            'telefono'        => '',
            'direccion'       => '',
            'email'           => '',
            'color_primario'  => '#ff6a00',
            'metodo_ventas'   => 'whatsapp',
            'delivery_tipo'   => 'no_incluido',
            'delivery_precio' => '0',
            'whatsapp'        => '',
        ]);
        exit;
    }

    $fields = $response['fields'] ?? [];

    function firestoreValue(array $fields, string $key): string {
        if (!isset($fields[$key])) return '';
        $field = $fields[$key];
        return (string)($field['stringValue']
            ?? $field['integerValue']
            ?? $field['doubleValue']
            ?? '');
    }

    echo json_encode([
        'nombre'          => firestoreValue($fields, 'nombre'),
        'descripcion'     => firestoreValue($fields, 'descripcion'),
        'logo'            => firestoreValue($fields, 'logo'),
        'telefono'        => firestoreValue($fields, 'telefono'),
        'direccion'       => firestoreValue($fields, 'direccion'),
        'email'           => firestoreValue($fields, 'email'),
        'color_primario'  => firestoreValue($fields, 'color_primario') ?: '#ff6a00',
        'metodo_ventas'   => firestoreValue($fields, 'metodo_ventas') ?: 'whatsapp',
        'delivery_tipo'   => firestoreValue($fields, 'delivery_tipo') ?: 'no_incluido',
        'delivery_precio' => firestoreValue($fields, 'delivery_precio') ?: '0',
        'whatsapp'        => firestoreValue($fields, 'whatsapp'),
    ]);
    exit;
}

// ─── POST: guardar datos de la tienda ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'guardar') {

    $nombre          = trim($_POST['nombre']          ?? '');
    $descripcion     = trim($_POST['descripcion']     ?? '');
    $email           = trim($_POST['email']           ?? '');
    $telefono        = trim($_POST['telefono']        ?? '');
    $direccion       = trim($_POST['direccion']       ?? '');
    $color_primario  = trim($_POST['color_primario']  ?? '#ff6a00');
    $metodo_ventas   = trim($_POST['metodo_ventas']   ?? 'whatsapp');
    $delivery_tipo   = trim($_POST['delivery_tipo']   ?? 'no_incluido');
    $delivery_precio = trim($_POST['delivery_precio'] ?? '0');
    $whatsapp        = trim($_POST['whatsapp']        ?? '');

    if ($nombre === '') {
        http_response_code(422);
        echo json_encode(['error' => 'El nombre de la tienda es obligatorio']);
        exit;
    }

    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color_primario)) {
        $color_primario = '#ff6a00';
    }

    if (!in_array($metodo_ventas, ['whatsapp', 'formulario'])) $metodo_ventas = 'whatsapp';
    if (!in_array($delivery_tipo, ['gratis', 'no_incluido', 'costo_fijo'])) $delivery_tipo = 'no_incluido';

    $uid_session = $_SESSION['uid'];
    $fields = [
        'uid'            => ['stringValue' => $uid_session],
        'nombre'         => ['stringValue' => $nombre],
        'descripcion'    => ['stringValue' => $descripcion],
        'email'          => ['stringValue' => $email],
        'telefono'       => ['stringValue' => $telefono],
        'direccion'      => ['stringValue' => $direccion],
        'color_primario' => ['stringValue' => $color_primario],
        'metodo_ventas'  => ['stringValue' => $metodo_ventas],
        'delivery_tipo'  => ['stringValue' => $delivery_tipo],
        'delivery_precio'=> ['stringValue' => $delivery_precio],
        'whatsapp'       => ['stringValue' => $whatsapp],
    ];

    // ── Logo upload ──────────────────────────────────────────
    if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['logo'];
        $maxBytes = 5 * 1024 * 1024;

        if ($file['size'] > $maxBytes) {
            http_response_code(422);
            echo json_encode(['error' => 'El logo supera los 5 MB']);
            exit;
        }

        $mime = mime_content_type($file['tmp_name']);
        $extMap = [
            'image/webp' => 'webp',
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
        ];
        $ext = $extMap[$mime] ?? 'webp';

        $key     = "tiendas/{$slug}/logo/" . uniqid('', true) . ".{$ext}";
        $fileData = file_get_contents($file['tmp_name']);

        if ($fileData === false) {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo leer el archivo de logo']);
            exit;
        }

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

    // ── PATCH Firestore con updateMask ───────────────────────
    $body      = ['fields' => $fields];
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

    $responseData = ['ok' => true];
    if (isset($fields['logo'])) {
        $responseData['logo'] = $fields['logo']['stringValue'];
    }

    ob_end_clean();
    echo json_encode($responseData);
    exit;
}

ob_end_clean();
http_response_code(400);
echo json_encode(['error' => 'Acción no válida']);
