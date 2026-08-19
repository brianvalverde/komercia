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

// ─── Helper: extraer stringValue de un campo Firestore ──────
function fsStr(array $fields, string $key, string $default = ''): string {
    if (!isset($fields[$key])) return $default;
    $f = $fields[$key];
    return (string)($f['stringValue'] ?? $f['integerValue'] ?? $f['doubleValue'] ?? $default);
}

// ─── Helper: extraer array de strings de Firestore ──────────
function fsStrArray(array $fields, string $key): array {
    if (!isset($fields[$key]['arrayValue']['values'])) return [];
    $vals = [];
    foreach ($fields[$key]['arrayValue']['values'] as $v) {
        if (isset($v['stringValue'])) $vals[] = $v['stringValue'];
    }
    return $vals;
}

// ─── GET: obtener datos de la tienda ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'obtener') {

    $response = firestoreRequest('GET', "tiendas/{$slug}");

    $empty = [
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
        'facebook'        => '',
        'instagram'       => '',
        'tiktok'          => '',
        'banners'         => [],
    ];

    if (!$response || isset($response['error'])) {
        ob_end_clean();
        echo json_encode($empty);
        exit;
    }

    $fields = $response['fields'] ?? [];

    ob_end_clean();
    echo json_encode([
        'nombre'          => fsStr($fields, 'nombre'),
        'descripcion'     => fsStr($fields, 'descripcion'),
        'logo'            => fsStr($fields, 'logo'),
        'telefono'        => fsStr($fields, 'telefono'),
        'direccion'       => fsStr($fields, 'direccion'),
        'email'           => fsStr($fields, 'email'),
        'color_primario'  => fsStr($fields, 'color_primario', '#ff6a00'),
        'metodo_ventas'   => fsStr($fields, 'metodo_ventas', 'whatsapp'),
        'delivery_tipo'   => fsStr($fields, 'delivery_tipo', 'no_incluido'),
        'delivery_precio' => fsStr($fields, 'delivery_precio', '0'),
        'whatsapp'        => fsStr($fields, 'whatsapp'),
        'facebook'        => fsStr($fields, 'facebook'),
        'instagram'       => fsStr($fields, 'instagram'),
        'tiktok'          => fsStr($fields, 'tiktok'),
        'banners'         => fsStrArray($fields, 'banners'),
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
    $facebook        = trim($_POST['facebook']        ?? '');
    $instagram       = trim($_POST['instagram']       ?? '');
    $tiktok          = trim($_POST['tiktok']          ?? '');

    if ($nombre === '') {
        ob_end_clean();
        http_response_code(422);
        echo json_encode(['error' => 'El nombre de la tienda es obligatorio']);
        exit;
    }

    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color_primario)) $color_primario = '#ff6a00';
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
        'facebook'       => ['stringValue' => $facebook],
        'instagram'      => ['stringValue' => $instagram],
        'tiktok'         => ['stringValue' => $tiktok],
    ];

    // ── Logo upload ──────────────────────────────────────────
    if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['logo'];
        $maxBytes = 5 * 1024 * 1024;

        if ($file['size'] > $maxBytes) {
            ob_end_clean();
            http_response_code(422);
            echo json_encode(['error' => 'El logo supera los 5 MB']);
            exit;
        }

        $mime  = mime_content_type($file['tmp_name']);
        $extMap = ['image/webp'=>'webp','image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif'];
        $ext   = $extMap[$mime] ?? 'webp';
        $key   = "tiendas/{$slug}/logo/" . uniqid('', true) . ".{$ext}";
        $fileData = file_get_contents($file['tmp_name']);

        if ($fileData === false) {
            ob_end_clean();
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

        $fields['logo'] = ['stringValue' => rtrim(WASABI_URL_BASE, '/') . '/' . $key];
    }

    // ── PATCH Firestore ──────────────────────────────────────
    $body      = ['fields' => $fields];
    $maskQuery = implode('&', array_map(fn($f) => 'updateMask.fieldPaths=' . urlencode($f), array_keys($fields)));
    $result    = firestoreRequest('PATCH', "tiendas/{$slug}?{$maskQuery}", $body);

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
    if (isset($fields['logo'])) $responseData['logo'] = $fields['logo']['stringValue'];

    ob_end_clean();
    echo json_encode($responseData);
    exit;
}

// ─── POST: subir banner ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'subir_banner') {

    if (empty($_FILES['banner']) || $_FILES['banner']['error'] !== UPLOAD_ERR_OK) {
        ob_end_clean();
        http_response_code(422);
        echo json_encode(['error' => 'Archivo no recibido']);
        exit;
    }

    $file     = $_FILES['banner'];
    $maxBytes = 5 * 1024 * 1024;

    if ($file['size'] > $maxBytes) {
        ob_end_clean();
        http_response_code(422);
        echo json_encode(['error' => 'El banner supera los 5 MB']);
        exit;
    }

    $mime   = mime_content_type($file['tmp_name']);
    $extMap = ['image/webp'=>'webp','image/jpeg'=>'jpg','image/png'=>'png'];
    if (!isset($extMap[$mime])) {
        ob_end_clean();
        http_response_code(422);
        echo json_encode(['error' => 'Formato no permitido. Usa JPG, PNG o WebP']);
        exit;
    }
    $ext      = $extMap[$mime];
    $key      = "tiendas/{$slug}/banners/" . uniqid('', true) . ".{$ext}";
    $fileData = file_get_contents($file['tmp_name']);

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
        echo json_encode(['error' => 'Error al subir banner: ' . $e->getMessage()]);
        exit;
    }

    $bannerUrl = rtrim(WASABI_URL_BASE, '/') . '/' . $key;

    // Agregar URL al array banners en Firestore
    // Leer banners actuales
    $tiendaDoc      = firestoreRequest('GET', "tiendas/{$slug}");
    $currentBanners = fsStrArray($tiendaDoc['fields'] ?? [], 'banners');
    $currentBanners[] = $bannerUrl;

    // Máx 5 banners
    if (count($currentBanners) > 5) $currentBanners = array_slice($currentBanners, -5);

    $bannersFS = ['arrayValue' => ['values' => array_map(fn($u) => ['stringValue' => $u], $currentBanners)]];
    firestoreRequest('PATCH', "tiendas/{$slug}?updateMask.fieldPaths=banners", ['fields' => ['banners' => $bannersFS]]);

    ob_end_clean();
    echo json_encode(['ok' => true, 'url' => $bannerUrl, 'banners' => $currentBanners]);
    exit;
}

// ─── POST: eliminar banner ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'eliminar_banner') {

    $urlToRemove = trim($_POST['url'] ?? '');
    if (!$urlToRemove) {
        ob_end_clean();
        echo json_encode(['error' => 'URL requerida']);
        exit;
    }

    $tiendaDoc      = firestoreRequest('GET', "tiendas/{$slug}");
    $currentBanners = fsStrArray($tiendaDoc['fields'] ?? [], 'banners');
    $newBanners     = array_values(array_filter($currentBanners, fn($u) => $u !== $urlToRemove));

    $bannersFS = ['arrayValue' => ['values' => array_map(fn($u) => ['stringValue' => $u], $newBanners)]];
    firestoreRequest('PATCH', "tiendas/{$slug}?updateMask.fieldPaths=banners", ['fields' => ['banners' => $bannersFS]]);

    ob_end_clean();
    echo json_encode(['ok' => true, 'banners' => $newBanners]);
    exit;
}

// ─── POST: reordenar banners ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'reordenar_banners') {
    $urlsJson = $_POST['banners'] ?? '[]';
    $newOrder = json_decode($urlsJson, true);
    if (!is_array($newOrder)) $newOrder = [];
    $newOrder = array_slice($newOrder, 0, 5);

    $bannersFS = ['arrayValue' => ['values' => array_map(fn($u) => ['stringValue' => (string)$u], $newOrder)]];
    firestoreRequest('PATCH', "tiendas/{$slug}?updateMask.fieldPaths=banners", ['fields' => ['banners' => $bannersFS]]);

    ob_end_clean();
    echo json_encode(['ok' => true]);
    exit;
}

ob_end_clean();
http_response_code(400);
echo json_encode(['error' => 'Acción no válida']);
