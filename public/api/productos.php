<?php
header('Content-Type: application/json');
session_start();
ob_start();

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

// ─── LISTAR productos ────────────────────────────────────────
if ($method === 'GET' && $action === 'listar') {
    $res  = firestoreRequest('GET', "comerciantes/{$uid}/productos");
    $docs = $res['documents'] ?? [];
    $productos = [];
    foreach ($docs as $doc) {
        $f  = $doc['fields'] ?? [];
        $id = basename($doc['name']);

        // Múltiples imágenes
        $imagenes = [];
        if (isset($f['imagenes']['arrayValue']['values'])) {
            foreach ($f['imagenes']['arrayValue']['values'] as $v) {
                if (isset($v['stringValue'])) $imagenes[] = $v['stringValue'];
            }
        }
        if (empty($imagenes) && !empty($f['imagen']['stringValue'])) {
            $imagenes[] = $f['imagen']['stringValue'];
        }

        // Videos
        $videos = [];
        if (isset($f['videos']['arrayValue']['values'])) {
            foreach ($f['videos']['arrayValue']['values'] as $v) {
                if (isset($v['stringValue'])) $videos[] = $v['stringValue'];
            }
        }

        // Promociones
        $promociones = [];
        if (isset($f['promociones']['arrayValue']['values'])) {
            foreach ($f['promociones']['arrayValue']['values'] as $v) {
                $mf = $v['mapValue']['fields'] ?? [];
                $promociones[] = [
                    'nombre'  => $mf['nombre']['stringValue'] ?? '',
                    'precio'  => (float)($mf['precio']['doubleValue'] ?? $mf['precio']['integerValue'] ?? 0),
                    'detalle' => $mf['detalle']['stringValue'] ?? '',
                ];
            }
        }

        $productos[] = [
            'id'          => $id,
            'nombre'      => $f['nombre']['stringValue'] ?? '',
            'descripcion' => $f['descripcion']['stringValue'] ?? '',
            'precio'      => (float)($f['precio']['doubleValue'] ?? $f['precio']['integerValue'] ?? 0),
            'stock'       => (int)($f['stock']['integerValue'] ?? 0),
            'imagen'      => $imagenes[0] ?? '',
            'imagenes'    => $imagenes,
            'videos'      => $videos,
            'categoria'   => $f['categoria']['stringValue'] ?? '',
            'activo'      => $f['activo']['booleanValue'] ?? true,
            'promociones' => $promociones,
            'creado_en'   => $f['creado_en']['stringValue'] ?? '',
        ];
    }
    ob_end_clean();
    echo json_encode(['ok' => true, 'productos' => $productos]);
    exit;
}

// ─── CREAR / ACTUALIZAR producto ────────────────────────────
if ($method === 'POST' && ($action === 'crear' || $action === 'actualizar')) {
    $id          = trim($_POST['id'] ?? '');
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = floatval($_POST['precio'] ?? 0);
    $stock       = intval($_POST['stock'] ?? 0);
    $categoria   = trim($_POST['categoria'] ?? '');
    $activo      = ($_POST['activo'] ?? 'true') !== 'false';

    // Promociones JSON
    $promocionesRaw = $_POST['promociones'] ?? '[]';
    $promoArray = json_decode($promocionesRaw, true);
    if (!is_array($promoArray)) $promoArray = [];

    if (!$nombre || $precio <= 0) {
        ob_end_clean();
        echo json_encode(['ok' => false, 'error' => 'Nombre y precio son requeridos']);
        exit;
    }

    $docId = $id ?: uniqid('prod_');

    // ── Obtener imágenes existentes si es update ─────────────
    $existingImagenes = [];
    $existingVideos   = [];
    if ($action === 'actualizar' && $id) {
        $existing = firestoreRequest('GET', "comerciantes/{$uid}/productos/{$id}");
        $ef = $existing['fields'] ?? [];
        if (isset($ef['imagenes']['arrayValue']['values'])) {
            foreach ($ef['imagenes']['arrayValue']['values'] as $v) {
                if (isset($v['stringValue'])) $existingImagenes[] = $v['stringValue'];
            }
        } elseif (!empty($ef['imagen']['stringValue'])) {
            $existingImagenes[] = $ef['imagen']['stringValue'];
        }
        if (isset($ef['videos']['arrayValue']['values'])) {
            foreach ($ef['videos']['arrayValue']['values'] as $v) {
                if (isset($v['stringValue'])) $existingVideos[] = $v['stringValue'];
            }
        }
    }

    // Imágenes a eliminar
    $imagenesEliminar = json_decode($_POST['imagenes_eliminar'] ?? '[]', true);
    if (!is_array($imagenesEliminar)) $imagenesEliminar = [];
    $existingImagenes = array_values(array_filter($existingImagenes, fn($u) => !in_array($u, $imagenesEliminar)));

    $videosEliminar = json_decode($_POST['videos_eliminar'] ?? '[]', true);
    if (!is_array($videosEliminar)) $videosEliminar = [];
    $existingVideos = array_values(array_filter($existingVideos, fn($u) => !in_array($u, $videosEliminar)));

    // ── Subir nuevas imágenes ────────────────────────────────
    $nuevasImagenes = [];
    if (!empty($_FILES['imagenes'])) {
        $files = $_FILES['imagenes'];
        $count = is_array($files['name']) ? count($files['name']) : 1;
        for ($i = 0; $i < $count; $i++) {
            $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
            if ($error !== UPLOAD_ERR_OK) continue;
            $tmp   = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $size  = is_array($files['size'])     ? $files['size'][$i]     : $files['size'];
            $type  = is_array($files['type'])     ? $files['type'][$i]     : $files['type'];

            if ($size > 8 * 1024 * 1024) continue;
            $mime = mime_content_type($tmp);
            $extMap = ['image/webp'=>'webp','image/jpeg'=>'jpg','image/png'=>'png'];
            $ext = $extMap[$mime] ?? 'webp';
            $key = "tiendas/{$slug}/productos/{$docId}/img_" . uniqid() . ".{$ext}";

            try {
                $s3 = getWasabiClient();
                $s3->putObject([
                    'Bucket'      => WASABI_BUCKET,
                    'Key'         => $key,
                    'Body'        => fopen($tmp, 'r'),
                    'ContentType' => $mime,
                    'ACL'         => 'public-read',
                ]);
                $nuevasImagenes[] = rtrim(WASABI_URL_BASE, '/') . '/' . $key;
            } catch (Exception $e) { /* skip */ }

            if (count($existingImagenes) + count($nuevasImagenes) >= 5) break;
        }
    }

    // ── Subir nuevos videos ──────────────────────────────────
    $nuevosVideos = [];
    if (!empty($_FILES['videos'])) {
        $files = $_FILES['videos'];
        $count = is_array($files['name']) ? count($files['name']) : 1;
        for ($i = 0; $i < $count; $i++) {
            $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
            if ($error !== UPLOAD_ERR_OK) continue;
            $tmp   = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $size  = is_array($files['size'])     ? $files['size'][$i]     : $files['size'];

            if ($size > 50 * 1024 * 1024) {
                ob_end_clean();
                echo json_encode(['ok' => false, 'error' => 'El video no puede superar 50 MB']);
                exit;
            }
            $key = "tiendas/{$slug}/productos/{$docId}/vid_" . uniqid() . ".mp4";
            try {
                $s3 = getWasabiClient();
                $s3->putObject([
                    'Bucket'      => WASABI_BUCKET,
                    'Key'         => $key,
                    'Body'        => fopen($tmp, 'r'),
                    'ContentType' => 'video/mp4',
                    'ACL'         => 'public-read',
                ]);
                $nuevosVideos[] = rtrim(WASABI_URL_BASE, '/') . '/' . $key;
            } catch (Exception $e) { /* skip */ }

            if (count($existingVideos) + count($nuevosVideos) >= 2) break;
        }
    }

    $todasImagenes = array_merge($existingImagenes, $nuevasImagenes);
    $todosVideos   = array_merge($existingVideos,   $nuevosVideos);

    // ── Construir campos Firestore ───────────────────────────
    $imagenesFS = array_map(fn($u) => ['stringValue' => $u], $todasImagenes);
    $videosFS   = array_map(fn($u) => ['stringValue' => $u], $todosVideos);

    $promoFS = array_map(function($p) {
        return ['mapValue' => ['fields' => [
            'nombre'  => ['stringValue' => $p['nombre'] ?? ''],
            'precio'  => ['doubleValue' => (float)($p['precio'] ?? 0)],
            'detalle' => ['stringValue' => $p['detalle'] ?? ''],
        ]]];
    }, $promoArray);

    $fields = [
        'nombre'      => ['stringValue'  => $nombre],
        'descripcion' => ['stringValue'  => $descripcion],
        'precio'      => ['doubleValue'  => $precio],
        'stock'       => ['integerValue' => $stock],
        'imagen'      => ['stringValue'  => $todasImagenes[0] ?? ''],
        'imagenes'    => ['arrayValue'   => ['values' => $imagenesFS]],
        'videos'      => ['arrayValue'   => ['values' => $videosFS]],
        'categoria'   => ['stringValue'  => $categoria],
        'activo'      => ['booleanValue' => $activo],
        'promociones' => ['arrayValue'   => ['values' => $promoFS]],
        'creado_en'   => ['stringValue'  => date('c')],
    ];

    $maskQuery = implode('&', array_map(fn($f) => 'updateMask.fieldPaths=' . urlencode($f), array_keys($fields)));
    firestoreRequest('PATCH', "comerciantes/{$uid}/productos/{$docId}?{$maskQuery}", ['fields' => $fields]);

    ob_end_clean();
    echo json_encode(['ok' => true, 'id' => $docId, 'imagen' => $todasImagenes[0] ?? '']);
    exit;
}

// ─── TOGGLE activo ───────────────────────────────────────────
if ($method === 'POST' && $action === 'toggle_activo') {
    $id     = trim($_POST['id'] ?? '');
    $activo = ($_POST['activo'] ?? 'true') !== 'false';
    if (!$id) { ob_end_clean(); echo json_encode(['ok' => false, 'error' => 'ID requerido']); exit; }

    $fields = ['activo' => ['booleanValue' => $activo]];
    $maskQuery = 'updateMask.fieldPaths=activo';
    firestoreRequest('PATCH', "comerciantes/{$uid}/productos/{$id}?{$maskQuery}", ['fields' => $fields]);

    ob_end_clean();
    echo json_encode(['ok' => true]);
    exit;
}

// ─── ELIMINAR producto ───────────────────────────────────────
if ($method === 'DELETE' && $action === 'eliminar') {
    $id = $_GET['id'] ?? '';
    if (!$id) { ob_end_clean(); echo json_encode(['ok' => false, 'error' => 'ID requerido']); exit; }
    firestoreRequest('DELETE', "comerciantes/{$uid}/productos/{$id}");
    ob_end_clean();
    echo json_encode(['ok' => true]);
    exit;
}

ob_end_clean();
echo json_encode(['ok' => false, 'error' => 'Acción no reconocida']);
