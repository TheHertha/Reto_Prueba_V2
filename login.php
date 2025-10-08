<?php
session_start();
require_once 'config.php';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
unset($_SESSION['error']);

// Define allowed email domains
$ALLOWED_EMAIL_DOMAINS = [
    '@gmail.com' => '@gmail.com',
    '@hotmail.com' => '@hotmail.com',
    '@outlook.com' => '@outlook.com'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Inicia sesión</title>
    <style>
       /* Reinicia márgenes, paddings y configura box-sizing para consistencia */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Configura el cuerpo de la página con fuente moderna y altura mínima */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #ffffff;
    color: #000000;
    min-height: 100vh;
    line-height: 1.6;
}

/* Contenedor principal con fondo de imagen y centrado */
.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
    background-image: url('assets/img/AF1.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    position: relative;
}

/* Capa de superposición para oscurecer el fondo */
.login-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    z-index: 1;
}

/* Tarjeta de login con diseño flexible y efecto de desenfoque */
.login-card {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: row;
    width: 90%;
    max-width: 1400px;
    min-height: 700px;
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid #333333;
    border-radius: 12px;
    overflow: hidden;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    animation: fadeIn 0.8s ease-out;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

/* Contenedor de la imagen con gradiente de fondo */
.login-image {
    flex: 1;
    background: linear-gradient(135deg, #000000, #333333);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 40px;
    border-right: 1px solid #333333;
    position: relative;
}

/* Capa de superposición para la imagen */
.login-image::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.1);
    z-index: 1;
}

/* Estilo de la imagen con efecto de sombra */
.login-image img {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 350px;
    height: auto;
    object-fit: contain;
    filter: drop-shadow(0 0 20px rgba(255, 215, 0, 0.4));
    transition: all 0.3s ease;
}

/* Efecto hover para la imagen */
.login-image img:hover {
    transform: scale(1.05);
    filter: drop-shadow(0 0 30px rgba(255, 215, 0, 0.6));
}

/* Contenedor del formulario con padding y fondo claro */
.login-form-container {
    flex: 1.5;
    padding: 80px 60px;
    background: rgba(255, 255, 255, 0.98);
    display: flex;
    flex-direction: column;
    justify-content: center;
    color: #000000;
    position: relative;
}

/* Título principal del formulario */
.login-form-container h1 {
    font-size: 52px;
    font-weight: 100;
    margin-bottom: 50px;
    letter-spacing: 3px;
    text-transform: uppercase;
    text-align: center;
    color: #000000;
    border-left: 3px solid #FFD700;
    padding-left: 20px;
}

/* Grupo de inputs del formulario */
.form-group {
    margin-bottom: 35px;
    position: relative;
}

/* Grupo de correo con disposición flexible */
.email-group {
    display: flex;
    flex-direction: row;
    align-items: flex-end;
    gap: 15px;
    width: 100%;
}

/* Input de correo electrónico */
.email-input {
    flex: 1;
    min-width: 0;
}

/* Selector de dominio de correo */
.email-domain {
    flex: 0 0 160px;
    width: 160px;
    max-width: 160px;
}

/* Etiquetas de los inputs */
.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 300;
    margin-bottom: 10px;
    color: #000000;
    letter-spacing: 1px;
    text-transform: uppercase;
}

/* Estilo de inputs y selectores */
.form-group input[type="text"],
.form-group input[type="password"],
.form-group select {
    width: 100%;
    padding: 16px 0;
    border: none;
    border-bottom: 1px solid #333333;
    background: transparent;
    color: #000000;
    font-size: 16px;
    font-weight: 300;
    transition: all 0.3s ease;
    outline: none;
    pointer-events: auto;
}

/* Efecto de foco para inputs y selectores */
.form-group input[type="text"]:focus,
.form-group input[type="password"]:focus,
.form-group select:focus {
    border-bottom: 2px solid #FFD700;
}

/* Placeholder para inputs */
.form-group input[type="text"]::placeholder,
.form-group input[type="password"]::placeholder {
    color: #999999;
    font-weight: 300;
}

/* Estilo del selector con flecha personalizada */
.form-group select {
    cursor: pointer;
    appearance: none;
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"><path fill="%23333333" d="M6 9L2 5h8z"/></svg>');
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 12px;
    padding-right: 30px;
}

/* Opciones del selector */
.form-group select option {
    background: #ffffff;
    color: #000000;
    padding: 10px;
    font-size: 16px;
}

/* Efecto hover para el selector */
.form-group select:hover {
    border-bottom: 2px solid #FFD700;
}

/* Contenedor del checkbox */
.checkbox-container {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 40px;
    font-size: 14px;
    color: #000000;
}

/* Estilo del checkbox */
.checkbox-container input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #FFD700;
    cursor: pointer;
}

/* Etiqueta del checkbox */
.checkbox-container label {
    font-weight: 300;
    cursor: pointer;
    letter-spacing: 0.5px;
}

/* Botón de login */
.login-btn {
    background: transparent;
    color: #000000;
    border: 1px solid #000000;
    padding: 18px 36px;
    font-size: 15px;
    font-weight: 400;
    cursor: pointer;
    text-transform: uppercase;
    letter-spacing: 2px;
    border-radius: 4px;
    margin-bottom: 30px;
    transition: background-color 0.4s ease, color 0.4s ease, box-shadow 0.4s ease;
}

/* Efecto hover para el botón */
.login-btn:hover {
    background-color: #000000;
    color: #ffffff;
    box-shadow: 0 0 0 2px #FFD700;
}

/* Contenedor de enlaces */
.form-links {
    display: flex;
    flex-direction: column;
    gap: 18px;
    text-align: center;
}

/* Estilo de los enlaces */
.form-links a {
    color: #000000;
    text-decoration: none;
    font-size: 13px;
    font-weight: 300;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: all 0.3s ease;
    position: relative;
}

/* Línea decorativa bajo los enlaces */
.form-links a::after {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 1px;
    background: #FFD700;
    transition: width 0.3s ease;
}

/* Efecto hover para la línea de los enlaces */
.form-links a:hover::after {
    width: 100%;
}

/* Efecto hover para los enlaces */
.form-links a:hover {
    color: #FFD700;
}

/* Mensaje de error */
.error {
    background: rgba(248, 215, 218, 0.9);
    color: #721c24;
    padding: 15px;
    margin-bottom: 30px;
    text-align: center;
    font-weight: 400;
    font-size: 14px;
    border-left: 3px solid #dc3545;
    border-radius: 4px;
}

/* Animación de entrada */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Media query para pantallas de hasta 1024px */
@media (max-width: 1024px) {
    .login-card {
        max-width: 95%;
        min-height: 600px;
    }

    .login-form-container {
        padding: 60px 40px;
    }

    .login-image {
        padding: 30px;
    }

    .login-image img {
        max-width: 300px;
    }
}

/* Media query para pantallas de hasta 768px */
@media (max-width: 768px) {
    .login-container {
        padding: 15px;
    }

    .login-card {
        flex-direction: column;
        max-width: 100%;
        min-height: auto;
        border-radius: 8px;
    }

    .login-image {
        border-right: none;
        border-bottom: 1px solid #333333;
        padding: 20px;
    }

    .login-image img {
        max-width: 250px;
    }

    .login-form-container {
        padding: 40px 20px;
    }

    .login-form-container h1 {
        font-size: 36px;
        margin-bottom: 30px;
        letter-spacing: 2px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .email-group {
        flex-direction: column;
        gap: 10px;
    }

    .email-domain {
        width: 100%;
        max-width: none;
    }

    .login-btn {
        padding: 14px 28px;
        font-size: 13px;
        width: 100%;
    }

    .form-links a {
        font-size: 12px;
    }
}

/* Media query para pantallas de hasta 480px */
@media (max-width: 480px) {
    .login-container {
        padding: 10px;
    }

    .login-card {
        max-width: 100%;
        border-radius: 6px;
    }

    .login-form-container {
        padding: 30px 15px;
    }

    .login-form-container h1 {
        font-size: 28px;
        letter-spacing: 1.5px;
        padding-left: 15px;
    }

    .form-group input[type="text"],
    .form-group input[type="password"],
    .form-group select {
        font-size: 14px;
        padding: 12px 0;
    }

    .form-group select {
        background-position: right 8px center;
        background-size: 10px;
        padding-right: 25px;
    }

    .login-btn {
        padding: 12px 24px;
        font-size: 12px;
    }

    .error {
        font-size: 12px;
        padding: 10px;
    }

    .checkbox-container {
        font-size: 12px;
        gap: 10px;
    }

    .checkbox-container input[type="checkbox"] {
        width: 16px;
        height: 16px;
    }
}

/* Media query para pantallas muy pequeñas (hasta 320px) */
@media (max-width: 320px) {
    .login-form-container {
        padding: 20px 10px;
    }

    .login-form-container h1 {
        font-size: 24px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group input[type="text"],
    .form-group input[type="password"],
    .form-group select {
        font-size: 13px;
        padding: 10px 0;
    }

    .login-btn {
        padding: 10px 20px;
        font-size: 11px;
    }

    .form-links a {
        font-size: 11px;
    }

    .error {
        font-size: 11px;
        padding: 8px;
    }
}

.email-wrapper {
  position: relative;
}

.email-suggestions {
  position: absolute;
  left: 0;
  right: 0;
  top: calc(100% + 6px);
  background: #ffffff;
  border: 1px solid #333333;
  border-radius: 8px;
  list-style: none;
  padding: 6px 0;
  margin: 0;
  max-height: 180px;
  overflow-y: auto;
  box-shadow: 0 8px 24px rgba(0,0,0,.12);
  display: none;
  z-index: 10;
}

.email-suggestions li {
  padding: 10px 14px;
  cursor: pointer;
  font-size: 14px;
}

.email-suggestions li[aria-selected="true"],
.email-suggestions li:hover {
  background: #fff7cc;         
}

.email-suggestions .hint {
  display: block;
  font-size: 12px;
  color: #666;
  margin-top: 2px;
}


/* --- Input de correo: mismo estilo que el de contraseña --- */
.email-wrapper {
  position: relative;
  width: 100%;
}

.email-wrapper .email-input {
  width: 100%;
  padding: 16px 0;
  border: none;
  border-bottom: 1px solid #333333;
  background: transparent;
  color: #000000;
  font-size: 16px;
  font-weight: 300;
  transition: all 0.3s ease;
  outline: none;
}

.email-wrapper .email-input:focus {
  border-bottom: 2px solid #FFD700;
}

/* --- Menú de sugerencias (integrado y sobrio) --- */
.email-suggestions {
  position: absolute;
  left: 0;
  right: 0;
  top: calc(100% + 4px);
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid #ccc;
  border-radius: 4px;
  list-style: none;
  padding: 4px 0;
  margin: 0;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  max-height: 160px;
  overflow-y: auto;
  display: none;
  z-index: 10;
}

.email-suggestions li {
  padding: 10px 14px;
  cursor: pointer;
  font-size: 14px;
  color: #000;
  transition: background 0.2s;
}

.email-suggestions li:hover,
.email-suggestions li[aria-selected="true"] {
  background: rgba(255, 215, 0, 0.15);
}

/* Responsive: ocupa todo el ancho en móvil */
@media (max-width: 768px) {
  .email-wrapper .email-input {
    font-size: 15px;
    padding: 14px 0;
  }

  .email-suggestions {
    font-size: 15px;
    max-height: 180px;
  }
}

@media (max-width: 480px) {
  .email-wrapper .email-input {
    font-size: 16px; /* evita zoom */
  }
}



    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-image">
                <img src="assets/img/1-4.png" alt="Logo CAT 21">
            </div>
            <div class="login-form-container">
                <h1>Iniciar Sesión</h1>
                
                <?php if (!empty($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
<form id="loginForm" action="sesion_inicio.php" method="POST" autocomplete="on">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    <div class="form-group">
      <label for="email">Correo electrónico</label>
      <div class="email-wrapper">
        <input
          type="email"
          id="email"
          name="email"
          class="email-input"
          placeholder="correo@dominio.com"
          autocomplete="username"
          required
        />
        <ul id="emailSugg" class="email-suggestions" role="listbox" aria-label="Sugerencias de dominio"></ul>
      </div>
    </div>
    <div class="form-group">
      <label for="password">Contraseña</label>
      <input type="password" id="password" name="password" required autocomplete="current-password">
    </div>
    <div class="checkbox-container">
      <input type="checkbox" id="recordar" name="recordar">
      <label for="recordar">Recordar sesión</label>
    </div>
    <button type="submit" class="login-btn">Entrar</button>
    <div class="form-links">
      <a href="register.php">¿No tienes cuenta? Regístrate aquí</a>
      <a href="recuperar.php">¿Olvidaste tu contraseña?</a>
    </div>
</form>
            </div>
        </div>
    </div>

<script>
  // Dominios permitidos del lado PHP → JS
  const ALLOWED = <?php echo json_encode(array_values($ALLOWED_EMAIL_DOMAINS)); ?>; // ["@gmail.com", "@hotmail.com", "@outlook.com"]

  const emailInput = document.getElementById('email');
  const suggBox    = document.getElementById('emailSugg');
  const form       = document.getElementById('loginForm');

  let activeIndex = -1; // para navegación con teclas

  function buildSuggestions(list) {
    suggBox.innerHTML = '';
    list.forEach((dom, i) => {
      const li = document.createElement('li');
      li.role = 'option';
      li.dataset.index = i;
      li.innerHTML = `
        <strong>${dom}</strong>
        <span class="hint">Pulsa Enter para completar</span>
      `;
      li.addEventListener('mousedown', (e) => { // mousedown para no perder foco
        e.preventDefault();
        applySuggestion(dom);
      });
      suggBox.appendChild(li);
    });
    suggBox.style.display = list.length ? 'block' : 'none';
    activeIndex = list.length ? 0 : -1;
    highlightActive();
  }

  function highlightActive() {
    [...suggBox.children].forEach((li, idx) => {
      li.setAttribute('aria-selected', idx === activeIndex ? 'true' : 'false');
    });
  }

  function visibleSuggestions() {
    return suggBox.style.display === 'block';
  }

  function applySuggestion(domain) {
    const val = emailInput.value;
    const atPos = val.indexOf('@');
    const local = atPos === -1 ? val : val.slice(0, atPos);
    emailInput.value = local + domain;
    closeSuggestions();
  }

  function closeSuggestions() {
    suggBox.style.display = 'none';
    suggBox.innerHTML = '';
    activeIndex = -1;
  }

  // Filtra por lo que viene después de '@'
  function filterDomains(partial) {
    // partial llega SIN '@' (lo limpiamos abajo)
    const needle = '@' + (partial || '').toLowerCase();
    return ALLOWED.filter(dom => dom.toLowerCase().startsWith(needle));
  }

  emailInput.addEventListener('input', () => {
    const val = emailInput.value;
    const atPos = val.indexOf('@');
    if (atPos === -1) {
      closeSuggestions();
      return;
    }
    const domainPart = val.slice(atPos + 1); // lo que el usuario escribió tras '@'
    const list = filterDomains(domainPart);
    buildSuggestions(list);
  });

  emailInput.addEventListener('keydown', (e) => {
    if (!visibleSuggestions()) return;

    const items = [...suggBox.children];
    if (!items.length) return;

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        activeIndex = (activeIndex + 1) % items.length;
        highlightActive();
        break;
      case 'ArrowUp':
        e.preventDefault();
        activeIndex = (activeIndex - 1 + items.length) % items.length;
        highlightActive();
        break;
      case 'Enter':
        e.preventDefault();
        if (activeIndex >= 0) {
          const dom = ALLOWED.find(d => d === items[activeIndex].querySelector('strong').textContent);
          if (dom) applySuggestion(dom);
        }
        break;
      case 'Escape':
        e.preventDefault();
        closeSuggestions();
        break;
    }
  });

  // Ocultar si clic fuera
  document.addEventListener('click', (e) => {
    if (!suggBox.contains(e.target) && e.target !== emailInput) {
      closeSuggestions();
    }
  });

  // Validación del formulario
  form.addEventListener('submit', (e) => {
    const value = emailInput.value.trim();
    const atPos = value.lastIndexOf('@');
    if (atPos <= 0) {
      e.preventDefault();
      alert('Ingresa un correo válido (incluye @).');
      return;
    }
    const domain = value.slice(atPos);
    if (!ALLOWED.includes(domain)) {
      e.preventDefault();
      alert('Domino de correo no permitido. Usa: ' + ALLOWED.join(', '));
    }
  });
</script>

</body>
</html>