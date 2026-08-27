<?php
session_start();
if (empty($_SESSION['admin'])) {
    header('Location: /admin/login');
    exit;
}
require_once '/var/www/komercia/config/firebase.php';

function fsv($fields, $key, $default = '') {
    return $fields[$key]['stringValue']  ??
           $fields[$key]['booleanValue'] ??
           $fields[$key]['integerValue'] ??
           $fields[$key]['doubleValue']  ?? $default;
}

// ── AJAX ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $accion = $input['accion'] ?? '';
    $uid    = trim($input['uid'] ?? '');

    if (!$uid && $accion !== 'pagos_lista') {
        echo json_encode(['ok'=>false,'error'=>'UID requerido']); exit;
    }

    if ($accion === 'activar') {
        $plan  = $input['plan']  ?? '';
        $meses = max(1,(int)($input['meses'] ?? 1));
        if (!in_array($plan, ['trial','pro','empresarial'])) {
            echo json_encode(['ok'=>false,'error'=>'Plan inválido']); exit;
        }
        $expira = date('c', strtotime("+{$meses} month"));
        $res = firestoreRequest('PATCH',
            "comerciantes/{$uid}?updateMask.fieldPaths=plan&updateMask.fieldPaths=plan_activo&updateMask.fieldPaths=plan_expira",
            ['fields' => [
                'plan'        => ['stringValue'  => $plan],
                'plan_activo' => ['booleanValue' => true],
                'plan_expira' => ['stringValue'  => $expira],
            ]]
        );
        echo json_encode(['ok'=>true,'plan'=>$plan,'expira'=>$expira]);

    } elseif ($accion === 'pausar') {
        firestoreRequest('PATCH',
            "comerciantes/{$uid}?updateMask.fieldPaths=plan_activo",
            ['fields' => ['plan_activo' => ['booleanValue' => false]]]
        );
        echo json_encode(['ok'=>true]);

    } elseif ($accion === 'reactivar') {
        firestoreRequest('PATCH',
            "comerciantes/{$uid}?updateMask.fieldPaths=plan_activo",
            ['fields' => ['plan_activo' => ['booleanValue' => true]]]
        );
        echo json_encode(['ok'=>true]);

    } elseif ($accion === 'pagos_lista') {
        // Lista pagos de un comerciante
        $res  = firestoreRequest('GET', "comerciantes/{$uid}/pagos?orderBy=fecha desc&pageSize=50");
        $docs = $res['documents'] ?? [];
        $pagos = [];
        foreach ($docs as $doc) {
            $parts = explode('/', $doc['name']);
            $pid   = end($parts);
            $f     = $doc['fields'] ?? [];
            $pagos[] = [
                'id'      => $pid,
                'plan'    => fsv($f,'plan',''),
                'monto'   => fsv($f,'monto',0),
                'metodo'  => fsv($f,'metodo',''),
                'ref'     => fsv($f,'referencia',''),
                'fecha'   => fsv($f,'fecha',''),
                'meses'   => fsv($f,'meses',1),
                'nota'    => fsv($f,'nota',''),
            ];
        }
        echo json_encode(['ok'=>true,'pagos'=>$pagos]);

    } elseif ($accion === 'pago_agregar') {
        // Registrar pago manual
        $plan   = $input['plan']   ?? '';
        $monto  = (float)($input['monto']  ?? 0);
        $metodo = $input['metodo'] ?? '';
        $ref    = $input['ref']    ?? '';
        $meses  = max(1,(int)($input['meses'] ?? 1));
        $nota   = $input['nota']   ?? '';
        $fecha  = date('c');
        $pid    = uniqid('pago_');

        firestoreRequest('PATCH',
            "comerciantes/{$uid}/pagos/{$pid}",
            ['fields' => [
                'plan'       => ['stringValue'  => $plan],
                'monto'      => ['doubleValue'  => $monto],
                'metodo'     => ['stringValue'  => $metodo],
                'referencia' => ['stringValue'  => $ref],
                'meses'      => ['integerValue' => $meses],
                'nota'       => ['stringValue'  => $nota],
                'fecha'      => ['stringValue'  => $fecha],
            ]]
        );

        // Activar plan automáticamente al registrar pago
        $expira = date('c', strtotime("+{$meses} month"));
        firestoreRequest('PATCH',
            "comerciantes/{$uid}?updateMask.fieldPaths=plan&updateMask.fieldPaths=plan_activo&updateMask.fieldPaths=plan_expira",
            ['fields' => [
                'plan'        => ['stringValue'  => $plan],
                'plan_activo' => ['booleanValue' => true],
                'plan_expira' => ['stringValue'  => $expira],
            ]]
        );

        echo json_encode(['ok'=>true,'expira'=>$expira]);

    } elseif ($accion === 'sub_tiendas') {
        // Lista sub-tiendas de un comerciante empresarial
        $res  = firestoreRequest('GET', "comerciantes/{$uid}/tiendas?pageSize=50");
        $docs = $res['documents'] ?? [];
        $tiendas = [];
        foreach ($docs as $doc) {
            $parts = explode('/', $doc['name']);
            $tid   = end($parts);
            $f     = $doc['fields'] ?? [];
            $tiendas[] = [
                'id'     => $tid,
                'nombre' => fsv($f,'nombre','—'),
                'slug'   => fsv($f,'slug',''),
                'activa' => $f['activa']['booleanValue'] ?? true,
                'tipo'   => fsv($f,'tipo','adicional'),
            ];
        }
        echo json_encode(['ok'=>true,'tiendas'=>$tiendas]);

    } else {
        echo json_encode(['ok'=>false,'error'=>'Acción desconocida']);
    }
    exit;
}

// ── Listar comerciantes ────────────────────────────────────────
$res  = firestoreRequest('GET', 'comerciantes?pageSize=200');
$docs = $res['documents'] ?? [];
$rows = [];
foreach ($docs as $doc) {
    $parts = explode('/', $doc['name']);
    $uid   = end($parts);
    $f     = $doc['fields'] ?? [];
    $plan     = fsv($f,'plan','trial');
    $activo   = $f['plan_activo']['booleanValue'] ?? true;
    $expira   = fsv($f,'plan_expira','');
    $trialIni = fsv($f,'trial_inicio','');
    $vencido  = false; $dias = null;
    if ($expira) {
        $expTs   = strtotime($expira);
        $vencido = $expTs < time();
        $dias    = max(0,(int)ceil(($expTs - time())/86400));
    } elseif ($plan==='trial' && $trialIni) {
        $expTs   = strtotime($trialIni)+7*86400;
        $vencido = $expTs < time();
        $dias    = max(0,(int)ceil(($expTs - time())/86400));
    }
    $rows[] = [
        'uid'    => $uid,
        'nombre' => fsv($f,'nombre_tienda',fsv($f,'nombre','—')),
        'slug'   => fsv($f,'slug',''),
        'email'  => fsv($f,'email',''),
        'plan'   => $plan,
        'activo' => (bool)$activo,
        'expira' => $expira,
        'vencido'=> $vencido,
        'dias'   => $dias,
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Komercia</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#f0f2f5;color:#1a1a2e;min-height:100vh}
.topbar{background:#fff;border-bottom:1px solid #e8eaf0;padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.logo{font-size:20px;font-weight:800;background:linear-gradient(135deg,#ff6a00,#ee0979);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.logo small{font-size:12px;font-weight:500;color:#888;-webkit-text-fill-color:#888;margin-left:8px}
.logout-btn{font-size:13px;color:#888;text-decoration:none;padding:7px 14px;border-radius:8px;border:1.5px solid #e0e0e0;transition:all .2s}
.logout-btn:hover{border-color:#ee0979;color:#ee0979}
.content{padding:28px;max-width:1340px;margin:0 auto}
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.stat{background:#fff;border-radius:14px;padding:20px 24px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
.stat-val{font-size:32px;font-weight:800;margin-bottom:4px}
.stat-label{font-size:12px;color:#888;font-weight:500}
.v-orange{color:#ff6a00}.v-green{color:#27ae60}.v-purple{color:#9b59b6}.v-red{color:#e74c3c}
.toolbar{display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;align-items:center}
.search-box{flex:1;min-width:220px;padding:10px 14px;border:1.5px solid #e0e0e0;border-radius:10px;font-size:14px;font-family:inherit;outline:none;transition:border .2s}
.search-box:focus{border-color:#ff6a00}
.fb{padding:9px 16px;border-radius:10px;border:1.5px solid #e0e0e0;background:#fff;font-size:13px;font-weight:600;cursor:pointer;color:#555;transition:all .2s}
.fb.active,.fb:hover{border-color:#ff6a00;color:#ff6a00;background:#fff5f0}
.table-wrap{background:#fff;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,.05);overflow:hidden}
table{width:100%;border-collapse:collapse}
thead{background:#f8f9fb}
th{padding:13px 16px;text-align:left;font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap}
td{padding:13px 16px;font-size:14px;border-top:1px solid #f0f2f5;vertical-align:middle}
tr:hover td{background:#fafafa}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:99px;font-size:12px;font-weight:700}
.b-trial{background:#fff8e1;color:#b8860b}
.b-pro{background:#e8f5e9;color:#2e7d32}
.b-emp{background:#f3e5f5;color:#7b1fa2}
.b-vencido{background:#ffebee;color:#c62828}
.b-pausado{background:#f5f5f5;color:#757575}
.ac{display:flex;gap:6px;flex-wrap:wrap}
.btn{padding:6px 11px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;transition:all .2s;white-space:nowrap;display:inline-flex;align-items:center;gap:4px}
.btn-primary{background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff}
.btn-primary:hover{opacity:.85}
.btn-gray{background:#f0f0f0;color:#555}.btn-gray:hover{background:#e0e0e0}
.btn-red{background:#ffebee;color:#c62828}.btn-red:hover{background:#ffcdd2}
.btn-green{background:#e8f5e9;color:#2e7d32}.btn-green:hover{background:#c8e6c9}
.btn-blue{background:#e3f2fd;color:#1565c0}.btn-blue:hover{background:#bbdefb}
.btn:disabled{opacity:.4;cursor:not-allowed}

/* ── Modal base ── */
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;display:none;align-items:center;justify-content:center;padding:20px}
.overlay.open{display:flex}
.modal{background:#fff;border-radius:18px;padding:0;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.2);max-height:90vh;display:flex;flex-direction:column;overflow:hidden}
.modal-head{padding:24px 28px 20px;border-bottom:1px solid #f0f2f5;flex-shrink:0}
.modal-head h3{font-size:17px;font-weight:700}
.modal-head p{font-size:13px;color:#888;margin-top:4px}
.modal-body{padding:24px 28px;overflow-y:auto;flex:1}
.modal-foot{padding:16px 28px;border-top:1px solid #f0f2f5;display:flex;justify-content:flex-end;gap:10px;flex-shrink:0}
label{display:block;font-size:13px;font-weight:600;color:#444;margin-bottom:6px;margin-top:14px}
label:first-child{margin-top:0}
select,input[type=number],input[type=text],textarea{width:100%;padding:11px 14px;border:1.5px solid #e0e0e0;border-radius:10px;font-size:14px;font-family:inherit;outline:none;transition:border .2s}
select:focus,input:focus,textarea:focus{border-color:#ff6a00}
textarea{resize:vertical;min-height:60px}
.btn-cancel{padding:10px 20px;border-radius:10px;border:1.5px solid #e0e0e0;background:#fff;font-size:14px;font-weight:600;cursor:pointer;color:#555}
.btn-confirm{padding:10px 22px;border-radius:10px;background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff;border:none;font-size:14px;font-weight:700;cursor:pointer}
.btn-confirm:hover{opacity:.9}
.btn-confirm:disabled{opacity:.5;cursor:not-allowed}

/* ── Detalle / pagos ── */
.modal-det{max-width:680px}
.det-tabs{display:flex;gap:4px;margin-bottom:20px}
.det-tab{padding:8px 16px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;border:none;background:#f0f2f5;color:#555;transition:all .2s}
.det-tab.active{background:linear-gradient(135deg,#ff6a00,#ee0979);color:#fff}
.tab-panel{display:none}.tab-panel.active{display:block}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.info-card{background:#f8f9fb;border-radius:12px;padding:14px 16px}
.info-card .ik{font-size:11px;color:#aaa;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
.info-card .iv{font-size:15px;font-weight:700;color:#1a1a2e}
.pagos-list{display:flex;flex-direction:column;gap:10px}
.pago-row{background:#f8f9fb;border-radius:12px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px}
.pago-info .pm{font-size:14px;font-weight:700}
.pago-info .ps{font-size:12px;color:#888;margin-top:2px}
.pago-monto{font-size:18px;font-weight:800;color:#27ae60}
.pago-empty{text-align:center;padding:40px;color:#bbb;font-size:14px}
.add-pago-form{background:#f8f9fb;border-radius:14px;padding:20px;margin-top:16px}
.add-pago-form h4{font-size:14px;font-weight:700;margin-bottom:12px;color:#1a1a2e}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}

/* ── Sub-tiendas ── */
.sub-card{background:#f8f9fb;border-radius:12px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;border-left:3px solid #7b1fa2}
.sub-card .sc-info .sc-nombre{font-size:14px;font-weight:700;color:#1a1a2e}
.sub-card .sc-info .sc-slug{font-size:12px;color:#aaa;margin-top:2px}
.sub-tipo{font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px;background:#f3e5f5;color:#7b1fa2;white-space:nowrap}

/* ── Toast ── */
#toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);background:#1a1a2e;color:#fff;padding:12px 22px;border-radius:12px;font-size:14px;font-weight:500;opacity:0;transition:all .3s;z-index:999;pointer-events:none;white-space:nowrap}
#toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
#toast.ok{background:#1a7a3e}#toast.err{background:#c0392b}
</style>
</head>
<body>

<div class="topbar">
  <span class="logo">Komercia <small>Admin</small></span>
  <div style="display:flex;align-items:center;gap:12px">
    <span style="font-size:13px;color:#888">Hola, <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong></span>
    <a href="/admin/logout" class="logout-btn">Cerrar sesión</a>
  </div>
</div>

<div class="content">
  <div class="stats-row">
    <div class="stat"><div class="stat-val v-orange" id="st-total">—</div><div class="stat-label">Total comerciantes</div></div>
    <div class="stat"><div class="stat-val v-green"  id="st-activos">—</div><div class="stat-label">Planes activos (pagos)</div></div>
    <div class="stat"><div class="stat-val v-purple" id="st-trial">—</div><div class="stat-label">En trial</div></div>
    <div class="stat"><div class="stat-val v-red"    id="st-vencidos">—</div><div class="stat-label">Vencidos / pausados</div></div>
  </div>

  <div class="toolbar">
    <input class="search-box" id="search" placeholder="🔍  Buscar por nombre, slug o email…" oninput="filtrar()">
    <button class="fb active" data-f="todos"       onclick="setF(this)">Todos</button>
    <button class="fb" data-f="trial"              onclick="setF(this)">Trial</button>
    <button class="fb" data-f="pro"                onclick="setF(this)">Pro</button>
    <button class="fb" data-f="empresarial"        onclick="setF(this)">Empresarial</button>
    <button class="fb" data-f="vencidos"           onclick="setF(this)">Vencidos</button>
  </div>

  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>Tienda / Slug</th><th>Email</th><th>Plan</th><th>Vence</th><th>Días</th><th>Acciones</th>
      </tr></thead>
      <tbody id="tbody"></tbody>
    </table>
  </div>
</div>

<!-- Modal: cambiar plan -->
<div class="overlay" id="ov-plan" onclick="if(event.target===this)cerrar('ov-plan')">
  <div class="modal" style="max-width:420px">
    <div class="modal-head">
      <h3>Cambiar plan</h3>
      <p id="mp-sub">—</p>
    </div>
    <div class="modal-body">
      <input type="hidden" id="mp-uid">
      <label>Plan</label>
      <select id="mp-plan">
        <option value="trial">Trial (gratis)</option>
        <option value="pro">Pro — S/ 49/mes</option>
        <option value="empresarial">Empresarial — S/ 149/mes</option>
      </select>
      <label>Duración (meses)</label>
      <input type="number" id="mp-meses" value="1" min="1" max="24">
    </div>
    <div class="modal-foot">
      <button class="btn-cancel" onclick="cerrar('ov-plan')">Cancelar</button>
      <button class="btn-confirm" onclick="confirmarPlan()">Activar plan</button>
    </div>
  </div>
</div>

<!-- Modal: detalle comerciante -->
<div class="overlay" id="ov-det" onclick="if(event.target===this)cerrar('ov-det')">
  <div class="modal modal-det">
    <div class="modal-head">
      <h3 id="det-nombre">—</h3>
      <p id="det-sub">—</p>
    </div>
    <div class="modal-body">
      <div class="det-tabs">
        <button class="det-tab active" onclick="showTab('tab-info',this)">📋 Información</button>
        <button class="det-tab" onclick="showTab('tab-pagos',this)">💳 Pagos</button>
        <button class="det-tab" id="tab-sub-btn" style="display:none" onclick="showTab('tab-sub',this)">🏪 Sub-tiendas</button>
        <button class="det-tab" onclick="showTab('tab-add',this)">➕ Registrar pago</button>
      </div>

      <!-- Tab: info -->
      <div class="tab-panel active" id="tab-info">
        <div class="info-grid" id="det-info-grid"></div>
      </div>

      <!-- Tab: pagos -->
      <div class="tab-panel" id="tab-pagos">
        <div class="pagos-list" id="pagos-list">
          <div class="pago-empty">Cargando pagos…</div>
        </div>
      </div>

      <!-- Tab: sub-tiendas (solo empresarial) -->
      <div class="tab-panel" id="tab-sub">
        <div class="pagos-list" id="sub-list">
          <div class="pago-empty">Cargando sub-tiendas…</div>
        </div>
      </div>

      <!-- Tab: registrar pago -->
      <div class="tab-panel" id="tab-add">
        <div class="add-pago-form">
          <h4>Registrar pago manual</h4>
          <div class="form-row">
            <div>
              <label>Plan</label>
              <select id="ap-plan">
                <option value="pro">Pro — S/ 49</option>
                <option value="empresarial">Empresarial — S/ 149</option>
              </select>
            </div>
            <div>
              <label>Meses</label>
              <input type="number" id="ap-meses" value="1" min="1" max="24">
            </div>
          </div>
          <div class="form-row">
            <div>
              <label>Monto cobrado (S/)</label>
              <input type="number" id="ap-monto" value="49" step="0.01" min="0">
            </div>
            <div>
              <label>Método de pago</label>
              <select id="ap-metodo">
                <option value="Yape">Yape</option>
                <option value="Plin">Plin</option>
                <option value="Transferencia">Transferencia bancaria</option>
                <option value="Efectivo">Efectivo</option>
                <option value="WhatsApp">WhatsApp / manual</option>
              </select>
            </div>
          </div>
          <label>N° referencia / operación (opcional)</label>
          <input type="text" id="ap-ref" placeholder="Ej: 123456789">
          <label>Nota (opcional)</label>
          <textarea id="ap-nota" placeholder="Ej: Pago por transferencia BCP"></textarea>
          <div style="margin-top:16px;display:flex;justify-content:flex-end">
            <button class="btn-confirm" id="ap-btn" onclick="registrarPago()">💾 Registrar y activar plan</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-cancel" onclick="cerrar('ov-det')">Cerrar</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
const ROWS = <?= json_encode($rows) ?>;
let filtro = 'todos';
let detUID = '';

// ── Stats ─────────────────────────────────────────────────────
function stats(d){
  document.getElementById('st-total').textContent   = d.length;
  document.getElementById('st-activos').textContent  = d.filter(r=>r.activo&&!r.vencido&&r.plan!=='trial').length;
  document.getElementById('st-trial').textContent   = d.filter(r=>r.plan==='trial'&&r.activo&&!r.vencido).length;
  document.getElementById('st-vencidos').textContent = d.filter(r=>r.vencido||!r.activo).length;
}

// ── Render ────────────────────────────────────────────────────
function render(d){
  const tb = document.getElementById('tbody');
  if(!d.length){tb.innerHTML='<tr><td colspan="6" style="text-align:center;padding:40px;color:#aaa">Sin resultados</td></tr>';return;}
  tb.innerHTML = d.map(r=>{
    let badge='';
    if(!r.activo)            badge='<span class="badge b-pausado">⏸ Pausado</span>';
    else if(r.vencido)       badge='<span class="badge b-vencido">⚠️ Vencido</span>';
    else if(r.plan==='trial')badge='<span class="badge b-trial">🕐 Trial</span>';
    else if(r.plan==='pro')  badge='<span class="badge b-pro">⚡ Pro</span>';
    else                     badge='<span class="badge b-emp">👑 Empresarial</span>';
    const exp = r.expira ? new Date(r.expira).toLocaleDateString('es-PE',{day:'numeric',month:'short',year:'numeric'}) : '—';
    const dias = r.dias!==null ? (r.vencido?`<span style="color:#c62828">-${r.dias}d</span>`:`${r.dias}d`) : '—';
    const pausaBtn = r.activo
      ? `<button class="btn btn-red" onclick="pausar('${r.uid}',this)">⏸ Pausar</button>`
      : `<button class="btn btn-green" onclick="reactivar('${r.uid}',this)">▶️ Activar</button>`;
    const verBtn = r.slug ? `<a href="https://komercia.online/tienda/${r.slug}" target="_blank" class="btn btn-gray">🔗 Tienda</a>` : '';
    return `<tr>
      <td><strong>${esc(r.nombre)}</strong><br><span style="font-size:12px;color:#aaa">${esc(r.slug)}</span></td>
      <td style="color:#666;font-size:13px">${esc(r.email)}</td>
      <td>${badge}</td>
      <td style="font-size:13px;color:#666">${exp}</td>
      <td style="font-size:13px">${dias}</td>
      <td><div class="ac">
        <button class="btn btn-blue" onclick="abrirDet('${r.uid}')">📄 Detalle</button>
        <button class="btn btn-primary" onclick="abrirPlan('${r.uid}','${esc(r.nombre)}','${r.plan}')">✏️ Plan</button>
        ${pausaBtn}
        ${verBtn}
      </div></td>
    </tr>`;
  }).join('');
}

function esc(s){return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}

// ── Filtros ───────────────────────────────────────────────────
function filtrar(){
  const q = document.getElementById('search').value.toLowerCase();
  let d = ROWS.filter(r=>r.nombre.toLowerCase().includes(q)||r.slug.toLowerCase().includes(q)||r.email.toLowerCase().includes(q));
  if(filtro==='trial')       d=d.filter(r=>r.plan==='trial'&&r.activo&&!r.vencido);
  if(filtro==='pro')         d=d.filter(r=>r.plan==='pro');
  if(filtro==='empresarial') d=d.filter(r=>r.plan==='empresarial');
  if(filtro==='vencidos')    d=d.filter(r=>r.vencido||!r.activo);
  render(d);
}
function setF(btn){
  document.querySelectorAll('.fb').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  filtro=btn.dataset.f;
  filtrar();
}

// ── Modal plan ────────────────────────────────────────────────
function abrirPlan(uid,nombre,planActual){
  document.getElementById('mp-uid').value  = uid;
  document.getElementById('mp-sub').textContent = 'Comerciante: '+nombre;
  document.getElementById('mp-plan').value = planActual;
  document.getElementById('mp-meses').value= 1;
  document.getElementById('ov-plan').classList.add('open');
}
async function confirmarPlan(){
  const uid   = document.getElementById('mp-uid').value;
  const plan  = document.getElementById('mp-plan').value;
  const meses = document.getElementById('mp-meses').value;
  const r = await api({accion:'activar',uid,plan,meses});
  if(r.ok){toast('✅ Plan '+plan+' activado','ok');cerrar('ov-plan');setTimeout(()=>location.reload(),1400);}
  else toast('❌ '+(r.error||'Error'),'err');
}

// ── Pausar / Reactivar ────────────────────────────────────────
async function pausar(uid,btn){
  btn.disabled=true;
  const r=await api({accion:'pausar',uid});
  if(r.ok){toast('⏸ Tienda pausada','ok');setTimeout(()=>location.reload(),1200);}
  else{btn.disabled=false;toast('❌ '+(r.error||'Error al pausar'),'err');}
}
async function reactivar(uid,btn){
  btn.disabled=true;
  const r=await api({accion:'reactivar',uid});
  if(r.ok){toast('▶️ Tienda reactivada','ok');setTimeout(()=>location.reload(),1200);}
  else{btn.disabled=false;toast('❌ '+(r.error||'Error al reactivar'),'err');}
}

// ── Detalle ───────────────────────────────────────────────────
function abrirDet(uid){
  detUID=uid;
  const r=ROWS.find(x=>x.uid===uid)||{};
  document.getElementById('det-nombre').textContent = r.nombre||'—';
  document.getElementById('det-sub').textContent    = r.email+' · '+r.slug;
  // info grid
  let planLabel='—';
  if(!r.activo)planLabel='⏸ Pausado';
  else if(r.vencido)planLabel='⚠️ Vencido';
  else if(r.plan==='trial')planLabel='🕐 Trial';
  else if(r.plan==='pro')planLabel='⚡ Pro';
  else planLabel='👑 Empresarial';
  const exp=r.expira?new Date(r.expira).toLocaleDateString('es-PE',{day:'numeric',month:'long',year:'numeric'}):'—';
  const dias=r.dias!==null?(r.vencido?`Venció hace ${r.dias} días`:`${r.dias} días restantes`):'—';
  document.getElementById('det-info-grid').innerHTML=`
    <div class="info-card"><div class="ik">Plan actual</div><div class="iv">${planLabel}</div></div>
    <div class="info-card"><div class="ik">Vencimiento</div><div class="iv">${exp}</div></div>
    <div class="info-card"><div class="ik">Estado</div><div class="iv">${dias}</div></div>
    <div class="info-card"><div class="ik">UID Firebase</div><div class="iv" style="font-size:12px;word-break:break-all">${r.uid}</div></div>
  `;
  // mostrar/ocultar tab sub-tiendas según plan
  const subBtn = document.getElementById('tab-sub-btn');
  if(r.plan==='empresarial'){
    subBtn.style.display='';
    cargarSubTiendas(uid);
  } else {
    subBtn.style.display='none';
    document.getElementById('sub-list').innerHTML='';
  }
  // reset tabs
  showTab('tab-info',document.querySelector('.det-tab'));
  // cargar pagos en background
  cargarPagos(uid);
  document.getElementById('ov-det').classList.add('open');
  // pre-fill plan en form de agregar pago
  document.getElementById('ap-plan').value=(r.plan==='trial'||!r.plan)?'pro':r.plan;
}

async function cargarSubTiendas(uid){
  const lista=document.getElementById('sub-list');
  lista.innerHTML='<div class="pago-empty">Cargando…</div>';
  const r=await api({accion:'sub_tiendas',uid});
  if(!r.ok||!r.tiendas.length){lista.innerHTML='<div class="pago-empty">📭 Sin sub-tiendas registradas</div>';return;}
  lista.innerHTML=r.tiendas.map(t=>{
    const url=t.slug?`https://komercia.online/tienda/${t.slug}`:'';
    const tipoBadge=t.tipo==='principal'?'<span class="sub-tipo" style="background:#e8f5e9;color:#2e7d32">PRINCIPAL</span>':'<span class="sub-tipo">ADICIONAL</span>';
    const actBadge=t.activa?'<span class="sub-tipo" style="background:#e3f2fd;color:#1565c0">Activa</span>':'<span class="sub-tipo" style="background:#f5f5f5;color:#757575">Inactiva</span>';
    const enlace=url?`<a href="${url}" target="_blank" class="btn btn-gray" style="font-size:12px">🔗 Abrir</a>`:'';
    return `<div class="sub-card">
      <div class="sc-info">
        <div class="sc-nombre">${esc(t.nombre)}</div>
        <div class="sc-slug">${t.slug||'sin slug'}</div>
      </div>
      <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
        ${tipoBadge}${actBadge}${enlace}
      </div>
    </div>`;
  }).join('');
}

async function cargarPagos(uid){
  document.getElementById('pagos-list').innerHTML='<div class="pago-empty">Cargando…</div>';
  const r=await api({accion:'pagos_lista',uid});
  const lista=document.getElementById('pagos-list');
  if(!r.ok||!r.pagos.length){lista.innerHTML='<div class="pago-empty">📭 Sin pagos registrados</div>';return;}
  lista.innerHTML=r.pagos.map(p=>{
    const fecha=p.fecha?new Date(p.fecha).toLocaleDateString('es-PE',{day:'numeric',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}):'—';
    return `<div class="pago-row">
      <div class="pago-info">
        <div class="pm">💳 ${p.metodo||'—'} · Plan ${p.plan} · ${p.meses} mes${p.meses!=1?'es':''}</div>
        <div class="ps">${fecha}${p.ref?' · Ref: '+esc(p.ref):''}${p.nota?' · '+esc(p.nota):''}</div>
      </div>
      <div class="pago-monto">S/ ${parseFloat(p.monto||0).toFixed(2)}</div>
    </div>`;
  }).join('');
}

async function registrarPago(){
  const btn=document.getElementById('ap-btn');
  btn.disabled=true;btn.textContent='Guardando…';
  const r=await api({
    accion:'pago_agregar', uid:detUID,
    plan:  document.getElementById('ap-plan').value,
    meses: document.getElementById('ap-meses').value,
    monto: document.getElementById('ap-monto').value,
    metodo:document.getElementById('ap-metodo').value,
    ref:   document.getElementById('ap-ref').value,
    nota:  document.getElementById('ap-nota').value,
  });
  btn.disabled=false;btn.textContent='💾 Registrar y activar plan';
  if(r.ok){
    toast('✅ Pago registrado y plan activado hasta '+new Date(r.expira).toLocaleDateString('es-PE'),'ok');
    cargarPagos(detUID);
    showTab('tab-pagos',document.querySelectorAll('.det-tab')[1]);
    setTimeout(()=>location.reload(),2000);
  } else toast('❌ '+(r.error||'Error'),'err');
}

// ── Tabs ──────────────────────────────────────────────────────
function showTab(id,btn){
  document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.det-tab').forEach(b=>b.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  if(btn)btn.classList.add('active');
}

// ── API ───────────────────────────────────────────────────────
async function api(data){
  try{
    const r=await fetch('/admin/accion',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
    return r.json();
  }catch(e){return{ok:false,error:'Error de red'};}
}

// ── Toast ─────────────────────────────────────────────────────
let _tt;
function toast(msg,type=''){
  const el=document.getElementById('toast');
  el.textContent=msg;el.className='show '+(type==='ok'?'ok':type==='err'?'err':'');
  clearTimeout(_tt);_tt=setTimeout(()=>el.className='',3200);
}

function cerrar(id){document.getElementById(id).classList.remove('open');}

// ── Init ──────────────────────────────────────────────────────
stats(ROWS);render(ROWS);
</script>
</body>
</html>
