<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar contraseña – Komercia</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--naranja:#ff6a00;--negro:#0a0a0a;--negro-2:#111;--negro-3:#1a1a1a}
body{font-family:'Segoe UI',sans-serif;background:var(--negro);color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:var(--negro-2);border:1px solid rgba(255,255,255,.07);border-radius:20px;padding:40px;width:100%;max-width:420px}
.logo{text-align:center;margin-bottom:28px}
.logo img{height:48px}
h1{font-size:24px;font-weight:800;text-align:center;margin-bottom:8px}
.subtitle{text-align:center;color:#888;font-size:14px;margin-bottom:28px;line-height:1.5}
.form-group{margin-bottom:18px}
label{display:block;font-size:13px;font-weight:600;color:#ccc;margin-bottom:7px}
input{width:100%;background:var(--negro-3);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:13px 16px;color:#fff;font-size:15px;outline:none;transition:.2s}
input:focus{border-color:var(--naranja)}
.btn{width:100%;background:var(--naranja);color:#fff;border:none;border-radius:10px;padding:14px;font-size:16px;font-weight:700;cursor:pointer;transition:.2s;margin-top:4px}
.btn:hover{background:#e55d00}
.btn:disabled{opacity:.6;cursor:not-allowed}
.alert{padding:12px 16px;border-radius:10px;font-size:14px;margin-bottom:20px;display:none}
.alert-error{background:rgba(255,80,80,.1);border:1px solid rgba(255,80,80,.3);color:#ff6060}
.alert-success{background:rgba(80,200,120,.1);border:1px solid rgba(80,200,120,.3);color:#50c878}
.footer-link{text-align:center;margin-top:24px;font-size:14px;color:#666}
.footer-link a{color:var(--naranja);text-decoration:none;font-weight:600}
.icon-email{font-size:48px;text-align:center;margin-bottom:16px}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <a href="/"><img src="/assets/logo.png" alt="Komercia" onerror="this.style.display='none';this.nextElementSibling.style.display='block'"><span style="display:none;font-size:24px;font-weight:800;color:#ff6a00">⚡ Komercia</span></a>
  </div>

  <div class="icon-email">📧</div>
  <h1>Recupera tu acceso</h1>
  <p class="subtitle">Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>

  <div class="alert alert-error" id="alertError"></div>
  <div class="alert alert-success" id="alertSuccess"></div>

  <form id="form-recuperar">
    <div class="form-group">
      <label>Correo electrónico</label>
      <input type="email" id="email" placeholder="tu@correo.com" required autocomplete="email">
    </div>
    <button type="submit" class="btn" id="btnSubmit">📨 Enviar enlace de recuperación</button>
  </form>

  <div class="footer-link">
    <a href="/login">← Volver al inicio de sesión</a>
  </div>
</div>

<script>
document.getElementById('form-recuperar').addEventListener('submit', async function(e) {
  e.preventDefault();
  const email = document.getElementById('email').value.trim();
  const btn   = document.getElementById('btnSubmit');
  const alertError   = document.getElementById('alertError');
  const alertSuccess = document.getElementById('alertSuccess');

  alertError.style.display   = 'none';
  alertSuccess.style.display = 'none';
  btn.disabled = true;
  btn.textContent = 'Enviando...';

  try {
    const resp = await fetch('/api/recuperar.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ email })
    });
    const data = await resp.json();
    if (data.ok) {
      alertSuccess.textContent = '✅ Revisa tu correo. Si el email está registrado recibirás el enlace en unos minutos.';
      alertSuccess.style.display = 'block';
      document.getElementById('form-recuperar').style.display = 'none';
    } else {
      alertError.textContent = '⚠️ ' + (data.error || 'Ocurrió un error.');
      alertError.style.display = 'block';
    }
  } catch(err) {
    alertError.textContent = '⚠️ No se pudo conectar. Intenta de nuevo.';
    alertError.style.display = 'block';
  } finally {
    btn.disabled = false;
    btn.textContent = '📨 Enviar enlace de recuperación';
  }
});
</script>
</body>
</html>
