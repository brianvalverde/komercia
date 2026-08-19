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

$uid    = $_SESSION['uid'];
$slug   = $_SESSION['slug'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['accion'] ?? '';

// URL directa de Wasabi para videos (CDN no soporta range requests)
define('WASABI_DIRECT_URL', 'https://s3.us-west-1.wasabisys.com/' . WASABI_BUCKET);

// ── Helpers ───────────────────────────────────────────────────
function uploadFile(string $tmpPath, string $mime, string $key, bool $useDirectUrl = false): string {
    $s3 = getWasabiClient();
    $s3->putObject([
        'Bucket'      => WASABI_BUCKET,
        'Key'         => $key,
        'Body'        => fopen($tmpPath, 'r'),
        'ContentType' => $mime,
        'ACL'         => 'public-read',
    ]);
    // Videos usan URL directa de Wasabi (CDN no soporta range requests para streaming)
    return ($useDirectUrl ? WASABI_DIRECT_URL : WASABI_URL_BASE) . '/' . $key;
}

function deleteWasabiUrl(string $url): void {
    $base = rtrim(WASABI_URL_BASE, '/') . '/';
    if (strpos($url, $base) === 0) {
        $key = substr($url, strlen($base));
        try {
            getWasabiClient()->deleteObject(['Bucket' => WASABI_BUCKET, 'Key' => $key]);
        } catch (Exception $e) { /* ignore */ }
    }
}

function fsUrlArray(array $urls): array {
    return ['arrayValue' => ['values' => array_map(fn($u) => ['stringValue' => $u], $urls)]];
}

function fsPromoArray(array $promos): array {
    $vals = [];
    foreach ($promos as $p) {
        $vals[] = ['mapValue' => ['fields' => [
            'nombre' => ['stringValue' => $p['nombre'] ?? ''],
            'precio' => ['doubleValue'  => (float)($p['precio'] ?? 0)],
            'detalle'=> ['stringValue' => $p['detalle'] ?? ''],
        ]]];
    }
    return ['arrayValue' => ['values' => $vals]];
}

// ─── LISTAR productos ─────────────────────────────────────────
if ($method === 'GET' && $action === 'listar') {
    $res  = firestoreRequest('GET', "comerciantes/{$uid}/productos");
    $docs = $res['documents'] ?? [];
    $productos = [];
    foreach ($docs as $doc) {
        $f  = $doc['fields'] ?? [];
        $id = basename($doc['name']);

        // Imagenes (campo nuevo: imagenes[] o campo viejo: imagen)
        $imagenes = [];
        foreach ($f['imagenes']['arrayValue']['values'] ?? [] as $v) {
            if (isset($v['stringValue']) && $v['stringValue']) $imagenes[] = $v['stringValue'];
        }
        if (!$imagenes && isset($f['imagen']['stringValue']) && $f['imagen']['stringValue']) {
            $imagenes[] = $f['imagen']['stringValue'];
        }

        // Videos
        $videos = [];
        foreach ($f['videos']['arrayValue']['values'] ?? [] as $v) {
            if (isset($v['stringValue']) && $v['stringValue']) $videos[] = $v['stringValue'];
        }

        // Promociones
        $promociones = [];
        foreach ($f['promociones']['arrayValue']['values'] ?? [] as $v) {
            $pf = $v['mapValue']['fields'] ?? [];
            $promociones[] = [
                'nombre'  => $pf['nombre']['stringValue'] ?? '',
                'precio'  => (float)($pf['precio']['doubleValue'] ?? $pf['precio']['integerValue'] ?? 0),
                'detalle' => $pf['detalle']['stringValue'] ?? '',
            ];
        }

        $productos[] = [
            'id'          => $id,
            'nombre'      => $f['nombre']['stringValue'] ?? '',
            'descripcion' => $f['descripcion']['stringValue'] ?? '',
            'precio'      => (float)($f['precio']['doubleValue'] ?? $f['precio']['integerValue'] ?? 0),
            'stock'       => (int)($f['stock']['integerValue'] ?? 0),
            'imagen'      => $imagenes[0] ?? '',      // compatibilidad
            'imagenes'    => $imagenes,
            'videos'      => $videos,
            'categoria'   => $f['categoria']['stringValue'] ?? '',
            'activo'      => $f['activo']['booleanValue'] ?? true,
            'promociones' => $promociones,
        ];
    }
    echo json_encode(['ok' => true, 'productos' => $productos]);
    exit;
}

// ─── CREAR / EDITAR producto ──────────────────────────────────
if ($method === 'POST' && in_array($action, ['crear', 'editar'])) {
    $isEdit      = ($action === 'editar');
    $prodId      = trim($_POST['id'] ?? '');
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = (float)($_POST['precio'] ?? 0);
    $stock       = (int)($_POST['stock'] ?? 0);
    $categoria   = trim($_POST['categoria'] ?? '');
    $promoJson   = $_POST['promociones'] ?? '[]';
    $imgEliminar = json_decode($_POST['imagenes_eliminar'] ?? '[]', true) ?: [];
    $vidEliminar = json_decode($_POST['videos_eliminar'] ?? '[]', true) ?: [];
    $imgExiste   = json_decode($_POST['imagenes_existentes'] ?? '[]', true) ?: [];

    if (!$nombre || $precio <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Nombre y precio son requeridos']);
        exit;
    }
    if ($isEdit && !$prodId) {
        echo json_encode(['ok' => false, 'error' => 'ID requerido para editar']);
        exit;
    }

    $promociones = json_decode($promoJson, true) ?: [];

    // ── Leer estado actual si es edición ──────────────────────
    $imagenesActuales = $imgExiste;
    $videosActuales   = [];

    if ($isEdit && $prodId) {
        $docActual = firestoreRequest('GET', "comerciantes/{$uid}/productos/{$prodId}");
        $fa = $docActual['fields'] ?? [];
        // Videos existentes en Firestore (base para fusionar)
        foreach ($fa['videos']['arrayValue']['values'] ?? [] as $v) {
            if (isset($v['stringValue']) && $v['stringValue']) $videosActuales[] = $v['stringValue'];
        }
        // Imágenes: si el panel no envió imagenes_existentes, leerlas de Firestore
        if (empty($imagenesActuales)) {
            foreach ($fa['imagenes']['arrayValue']['values'] ?? [] as $v) {
                if (isset($v['stringValue']) && $v['stringValue']) $imagenesActuales[] = $v['stringValue'];
            }
            if (!$imagenesActuales && isset($fa['imagen']['stringValue'])) {
                $imagenesActuales[] = $fa['imagen']['stringValue'];
            }
        }
    }

    // ── Eliminar archivos borrados de Wasabi ──────────────────
    foreach ($imgEliminar as $url) deleteWasabiUrl($url);
    foreach ($vidEliminar as $url) deleteWasabiUrl($url);

    // Quitar eliminadas de las listas actuales
    $imagenesActuales = array_values(array_filter($imagenesActuales, fn($u) => !in_array($u, $imgEliminar)));
    $videosActuales   = array_values(array_filter($videosActuales,   fn($u) => !in_array($u, $vidEliminar)));

    // ── Subir imágenes nuevas ─────────────────────────────────
    if (!empty($_FILES['imagenes']['name'][0])) {
        $allowedImg = ['jpg','jpeg','png','webp'];
        $count = count($_FILES['imagenes']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['imagenes']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($_FILES['imagenes']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedImg)) continue;
            if ($_FILES['imagenes']['size'][$i] > 5 * 1024 * 1024) continue;
            $key = "tiendas/{$slug}/productos/" . uniqid('img_') . ".{$ext}";
            try {
                $imagenesActuales[] = uploadFile($_FILES['imagenes']['tmp_name'][$i], $_FILES['imagenes']['type'][$i], $key);
            } catch (Exception $e) {
                echo json_encode(['ok' => false, 'error' => 'Error subiendo imagen: ' . $e->getMessage()]);
                exit;
            }
        }
    }

    // ── Subir videos nuevos ───────────────────────────────────
    if (!empty($_FILES['videos']['name'][0])) {
        $allowedVid = ['mp4','webm','mov'];
        $count = count($_FILES['videos']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['videos']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($_FILES['videos']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedVid)) continue;
            if ($_FILES['videos']['size'][$i] > 100 * 1024 * 1024) continue; // 100MB máx
            $key = "tiendas/{$slug}/productos/" . uniqid('vid_') . ".{$ext}";
            try {
                $videosActuales[] = uploadFile($_FILES['videos']['tmp_name'][$i], $_FILES['videos']['type'][$i], $key, true);
            } catch (Exception $e) {
                echo json_encode(['ok' => false, 'error' => 'Error subiendo video: ' . $e->getMessage()]);
                exit;
            }
        }
    }

    // ── Guardar en Firestore ──────────────────────────────────
    $docId = $isEdit ? $prodId : uniqid('prod_');
    $primerImagen = $imagenesActuales[0] ?? '';

    $fields = [
        'nombre'      => ['stringValue'  => $nombre],
        'descripcion' => ['stringValue'  => $descripcion],
        'precio'      => ['doubleValue'  => $precio],
        'stock'       => ['integerValue' => $stock],
        'imagen'      => ['stringValue'  => $primerImagen],  // compatibilidad
        'imagenes'    => fsUrlArray($imagenesActuales),
        'videos'      => fsUrlArray($videosActuales),
        'categoria'   => ['stringValue'  => $categoria],
        'activo'      => ['booleanValue' => true],
        'promociones' => fsPromoArray($promociones),
    ];
    if (!$isEdit) {
        $fields['creado_en'] = ['stringValue' => date('c')];
    }

    // updateMask para PATCH (Firestore requiere listar cada campo)
    $fieldPaths = array_keys($fields);
    $maskQuery  = implode('&', array_map(fn($fp) => 'updateMask.fieldPaths=' . urlencode($fp), $fieldPaths));

    firestoreRequest('PATCH', "comerciantes/{$uid}/productos/{$docId}?{$maskQuery}", ['fields' => $fields]);

    echo json_encode([
        'ok'       => true,
        'id'       => $docId,
        'imagenes' => $imagenesActuales,
        'videos'   => $videosActuales,
    ]);
    exit;
}

// ─── ELIMINAR producto ────────────────────────────────────────
if ($method === 'DELETE' && $action === 'eliminar') {
    $id = $_GET['id'] ?? '';
    if (!$id) {
        echo json_encode(['ok' => false, 'error' => 'ID requerido']);
        exit;
    }
    // Leer imágenes y videos para borrar de Wasabi
    $doc = firestoreRequest('GET', "comerciantes/{$uid}/productos/{$id}");
    $f   = $doc['fields'] ?? [];
    foreach ($f['imagenes']['arrayValue']['values'] ?? [] as $v) deleteWasabiUrl($v['stringValue'] ?? '');
    if ($f['imagen']['stringValue'] ?? '') deleteWasabiUrl($f['imagen']['stringValue']);
    foreach ($f['videos']['arrayValue']['values'] ?? [] as $v)   deleteWasabiUrl($v['stringValue'] ?? '');

    firestoreRequest('DELETE', "comerciantes/{$uid}/productos/{$id}");
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Acción no reconocida']);
