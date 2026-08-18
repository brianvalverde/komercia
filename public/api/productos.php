<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}
require_once '/var/www/komercia/config/firebase.php';
require_once '/var/www/komercia/config/wasabi.php';
$uid  = $_SESSION['uid'];
$slug = $_SESSION['slug'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['accion'] ?? '';
// ─── LISTAR productos ─────────────────────────────────────────
if ($method === 'GET' && $action === 'listar') {
    $res = firestoreRequest('GET', "comerciantes/{$uid}/productos");
    $docs = $res['documents'] ?? [];
    $productos = [];
    foreach ($docs as $doc) {
        $f = $doc['fields'] ?? [];
        $id = basename($doc['name']);
        $productos[] = [
            'id'          => $id,
            'nombre'      => $f['nombre']['stringValue'] ?? '',
            'descripcion' => $f['descripcion']['stringValue'] ?? '',
            'precio'      => $f['precio']['doubleValue'] ?? ($f['precio']['integerValue'] ?? 0),
            'stock'       => $f['stock']['integerValue'] ?? 0,
            'imagen'      => $f['imagen']['stringValue'] ?? '',
            'categoria'   => $f['categoria']['stringValue'] ?? '',
            'activo'      => $f['activo']['booleanValue'] ?? true,
        ];
    }
    echo json_encode(['ok' => true, 'productos' => $productos]);
    exit;
}
// ─── CREAR producto ───────────────────────────────────────────
if ($method === 'POST' && $action === 'crear') {
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = floatval($_POST['precio'] ?? 0);
    $stock       = intval($_POST['stock'] ?? 0);
    $categoria   = trim($_POST['categoria'] ?? '');
    if (!$nombre || $precio <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Nombre y precio son requeridos']);
        exit;
    }
    $imagen_url = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $file     = $_FILES['imagen'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['ok' => false, 'error' => 'Formato de imagen no permitido']);
            exit;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['ok' => false, 'error' => 'Imagen demasiado grande (máx 5MB)']);
            exit;
        }
        $key = "tiendas/{$slug}/productos/" . uniqid() . ".{$ext}";
        try {
            $s3 = getWasabiClient();
            $s3->putObject([
                'Bucket'      => WASABI_BUCKET,
                'Key'         => $key,
                'Body'        => fopen($file['tmp_name'], 'r'),
                'ContentType' => $file['type'],
                'ACL'         => 'public-read',
            ]);
            $imagen_url = WASABI_URL_BASE . '/' . $key;
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => 'Error subiendo imagen: ' . $e->getMessage()]);
            exit;
        }
    }
    $docId = uniqid('prod_');
    $data  = [
        'fields' => [
            'nombre'      => ['stringValue'  => $nombre],
            'descripcion' => ['stringValue'  => $descripcion],
            'precio'      => ['doubleValue'  => $precio],
            'stock'       => ['integerValue' => $stock],
            'imagen'      => ['stringValue'  => $imagen_url],
            'categoria'   => ['stringValue'  => $categoria],
            'activo'      => ['booleanValue' => true],
            'creado_en'   => ['stringValue'  => date('c')],
        ]
    ];
    firestoreRequest('PATCH', "comerciantes/{$uid}/productos/{$docId}", $data);
    echo json_encode(['ok' => true, 'id' => $docId, 'imagen' => $imagen_url]);
    exit;
}
// ─── ELIMINAR producto ────────────────────────────────────────
if ($method === 'DELETE' && $action === 'eliminar') {
    $id = $_GET['id'] ?? '';
    if (!$id) { echo json_encode(['ok' => false, 'error' => 'ID requerido']); exit; }
    firestoreRequest('DELETE', "comerciantes/{$uid}/productos/{$id}");
    echo json_encode(['ok' => true]);
    exit;
}
echo json_encode(['ok' => false, 'error' => 'Acción no reconocida']);
