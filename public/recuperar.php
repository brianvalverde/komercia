<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar contraseña – Komercia</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --orange: #ff6a00;
    --orange-dark: #e05a00;
    --bg: #0f0f0f;
    --card: #1a1a1a;
    --border: #2a2a2a;
    --text: #f0f0f0;
    --muted: #888;
    --radius: 14px;
  }

  body {
    font-family: 'Inter', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }

  .card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 40px 36px;
    width: 100%;
    max-width: 420px;
  }

  .logo {
    font-size: 26px;
    font-weight: 800;
    color: var(--orange);
    margin-bottom: 28px;
    display: block;
    text-align: center;
  }

  h1 {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 8px;
    text-align: center;
  }

  .subtitle {
    color: var(--muted);
    font-size: 14px;
    text-align: center;
    margin-bottom: 28px;
    line-height: 1.5;
  }

  .form-group {
    margin-bottom: 18px;
  }

  label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 6px;
    color: #ccc;
  }

  input[type="email"] {
    width: 100%;
    padding: 12px 14px;
    background: #111;
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    transition: border-color 0.2s;
    outline: none;
  }

  input[type="email"]:focus {
    border-color: var(--orange);
  }

  .btn {
    width: 100%;
    padding: 13px;
    background: var(--orange);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 8px;
  }

  .btn:hover { background: var(--orange-dark); }
  .btn:disabled { opacity: 0.6; cursor: not-allowed; }

  .spinner {
    width: 18px; height: 18px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    display: none;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  .alert {
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 18px;
    display: none;
    line-height: 1.5;
  }
  .alert-error   { background: #2a1111; border: 1px solid #5a2020; color: #ff8080; }
  .alert-success { background: #0f2a1a; border: 1px solid #1a5a30; color: #60d090; }

  .success-icon {
    font-size: 48px;
    text-align: center;
    display: block;
    margin-bottom: 16px;
  }

  .links {
    text-align: center;
    margin-top: 24px;
    font-size: 13px;
    color: var(--muted);
  }

  .links a {
    color: var(--orange);
    text-decoration: none;
    font-weight: 500;
  }

  .links a:hover { text-decoration: underline; }

  /* Estado de éxito */
  .success-state { display: none; text-align: center; }
  .success-state h2 { font-size: 20px; margin-bottom: 10px; }
  .success-state p { color: var(--muted); font-size: 14px; line-height: 1.6; }

  @media (max-width: 480px) {
    .card { padding: 28px 20px; }
  }
</style>
</head>
<body>

<div class="card">
  <span class="logo">⚡ Komercia</span>

  <!-- Formulario -->
  <div id="form-state">
    <h1>Recuperar contraseña</h1>
    <p class="subtitle">Ingresa tu email y te enviaremos un enlace para restablecer tu contraseña.</p>

    <div class="alert alert-error" id="alert-error"></div>

    <div class="form-group">
      <label for="email">Correo electrónico</label>
      <input type="email" id="email" placeholder="tu@email.com" autocomplete="email">
    </div>

    <button class="btn" id="btn-enviar" onclick="enviar()">
      <span class="spinner" id="spinner"></span>
      <span id="btn-text">Enviar enlace</span>
    </button>
  </div>

  <!-- Estado de éxito -->
  <div class="success-state" id="success-state">
    <span class="success-icon">📧</span>
    <h2>¡Revisa tu correo!</h2>
    <p>Te enviamos un enlace para restablecer tu contraseña a <strong id="email-enviado"></strong>.<br><br>Revisa también tu carpeta de spam si no lo encuentras.</p>
    <div class="links" style="margin-top:28px;">
      <a href="/login">← Volver al inicio de sesión</a>
    </div>
  </div>

  <!-- Links del formulario -->
  <div class="links" id="form-links">
    <a href="/login">← Volver al inicio de sesión</a>
  </div>
</div>

<script>
const FIREBASE_API_KEY = 'AIzaSyDDXhReEpwcXb44-jcJr5upWYeTmg-DUbM';

async function enviar() {
  const email = document.getElementById('email').value.trim();
  const btn   = document.getElementById('btn-enviar');
  const spinner = document.getElementById('spinner');
  const btnText = document.getElementById('btn-text');
  const alertError = document.getElementById('alert-error');

  alertError.style.display = 'none';

  if (!email) {
    mostrarError('Ingresa tu correo electrónico.');
    return;
  }

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    mostrarError('Ingresa un correo válido.');
    return;
  }

  btn.disabled = true;
  spinner.style.display = 'block';
  btnText.textContent = 'Enviando...';

  try {
    const res = await fetch(
      `https://identitytoolkit.googleapis.com/v1/accounts:sendOobCode?key=${FIREBASE_API_KEY}`,
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          requestType: 'PASSWORD_RESET',
          email: email,
        }),
      }
    );

    const data = await res.json();

    if (data.error) {
      const msg = data.error.message;
      if (msg === 'EMAIL_NOT_FOUND') {
        mostrarError('No encontramos una cuenta con ese correo.');
      } else if (msg === 'INVALID_EMAIL') {
        mostrarError('El correo ingresado no es válido.');
      } else {
        mostrarError('Ocurrió un error. Inténtalo de nuevo.');
      }
      btn.disabled = false;
      spinner.style.display = 'none';
      btnText.textContent = 'Enviar enlace';
      return;
    }

    // Éxito
    document.getElementById('email-enviado').textContent = email;
    document.getElementById('form-state').style.display  = 'none';
    document.getElementById('form-links').style.display  = 'none';
    document.getElementById('success-state').style.display = 'block';

  } catch (e) {
    mostrarError('Error de conexión. Inténtalo de nuevo.');
    btn.disabled = false;
    spinner.style.display = 'none';
    btnText.textContent = 'Enviar enlace';
  }
}

function mostrarError(msg) {
  const el = document.getElementById('alert-error');
  el.textContent = msg;
  el.style.display = 'block';
}

// Enter para enviar
document.addEventListener('keydown', e => {
  if (e.key === 'Enter') enviar();
});
</script>
</body>
</html>
