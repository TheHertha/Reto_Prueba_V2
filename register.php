<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: inicio.php");
    exit;
}

// Define hardcoded arrays for dropdowns
$ALLOWED_GENDERS = [
    'Masculino' => 'Masculino',
    'Femenino' => 'Femenino',
    'Otro' => 'Otro'
];
$ALLOWED_COUNTRIES = [
    'Argentina' => 'Argentina',
    'Bolivia' => 'Bolivia',
    'Canadá' => 'Canadá',
    'Colombia' => 'Colombia',
    'Costa Rica' => 'Costa Rica',
    'Ecuador' => 'Ecuador',
    'El Salvador' => 'El Salvador',
    'Estados Unidos' => 'Estados Unidos',
    'Guatemala' => 'Guatemala',
    'Italia' => 'Italia', 
    'México' => 'México',
    'Perú' => 'Perú'
];
$ALLOWED_EMAIL_DOMAINS = [
    '@gmail.com' => '@gmail.com',
    '@hotmail.com' => '@hotmail.com',
    '@outlook.com' => '@outlook.com'
];

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
unset($_SESSION['error'], $_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Formulario de Registro - CAT21</title>
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

/* Encabezado con fondo oscuro y centrado */
.header {
    background: #000000;
    padding: 30px 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    border-bottom: 1px solid #333333;
}

/* Logo en la esquina superior izquierda */
.logo {
    position: absolute;
    left: 60px;
    width: 60px;
    height: 60px;
    border-radius: 8px;
    transition: all 0.3s ease;
    object-fit: cover;
    cursor: pointer;
}

/* Efecto hover para el logo */
.logo:hover {
    transform: rotate(5deg);
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
}

/* Título del encabezado */
.title {
    font-size: 28px;
    font-weight: 300;
    color: #ffffff;
    letter-spacing: 4px;
    text-transform: uppercase;
}

/* Contenedor principal del formulario */
.container {
    max-width: 800px;
    margin: 60px auto;
    padding: 0 60px;
}

/* Envoltura del formulario con fondo claro */
.form-wrapper {
    background: #f8f8f8;
    border: 1px solid #333333;
    padding: 60px;
    position: relative;
    overflow: hidden;
}

/* Encabezado del formulario */
.form-header {
    text-align: center;
    margin-bottom: 40px;
}

/* Título principal del formulario */
.form-header h1 {
    font-size: 32px;
    font-weight: 100;
    color: #000000;
    letter-spacing: 3px;
    text-transform: uppercase;
    margin-bottom: 10px;
}

/* Subtítulo del formulario */
.form-header p {
    font-size: 14px;
    font-weight: 300;
    color: #666666;
    letter-spacing: 1px;
    text-transform: uppercase;
}

/* Contenedor de la barra de progreso */
.progress-container {
    margin-bottom: 50px;
}

/* Barra de progreso */
.progress-bar {
    width: 100%;
    height: 1px;
    background: rgba(0, 0, 0, 0.2);
    margin-bottom: 20px;
    overflow: hidden;
}

/* Relleno de la barra de progreso */
.progress-fill {
    height: 100%;
    background: #000000;
    transition: width 0.4s ease;
}

/* Indicadores de pasos en la barra de progreso */
.progress-indicators {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Estilo de cada paso en la barra de progreso */
.progress-step {
    font-size: 12px;
    font-weight: 300;
    color: rgba(0, 0, 0, 0.4);
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: all 0.3s ease;
}

/* Estilo del paso activo */
.progress-step.active {
    color: #000000;
    font-weight: 400;
}

/* Contenedor de cada paso del formulario */
.step {
    display: none;
    animation: fadeIn 0.6s ease;
}

/* Estilo del paso activo */
.step.active {
    display: block;
}

/* Animación de entrada para los pasos */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Título de cada paso */
.step-title {
    font-size: 24px;
    font-weight: 100;
    color: #000000;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 30px;
    text-align: center;
    border-bottom: 1px solid #000000;
    padding-bottom: 15px;
}

/* Grupo de inputs del formulario */
.form-group {
    margin-bottom: 30px;
}

/* Grupo de correo con disposición flexible */
.email-group {
    display: flex;
    align-items: flex-end;
    gap: 10px;
}

/* Input de correo electrónico */
.email-input {
    flex: 1;
}

/* Selector de dominio de correo */
.email-domain {
    flex: 0 0 auto;
    width: 150px;
}

/* Etiquetas de los inputs */
label {
    display: block;
    font-size: 12px;
    font-weight: 400;
    color: #000000;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 10px;
}

/* Estilo de inputs y selectores */
input,
select {
    width: 100%;
    padding: 15px 0;
    border: none;
    border-bottom: 1px solid #333333;
    background: transparent;
    color: #000000;
    font-size: 16px;
    font-weight: 300;
    transition: all 0.3s ease;
}

/* Efecto de foco para inputs y selectores */
input:focus,
select:focus {
    outline: none;
    border-bottom-color: #000000;
    border-bottom-width: 2px;
}

/* Placeholder para inputs */
input::placeholder {
    color: #999999;
    font-weight: 300;
}

/* Estilo del selector */
select {
    cursor: pointer;
    appearance: none;
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"><path fill="%23333333" d="M6 9L2 5h8z"/></svg>');
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 12px;
    padding-right: 30px;
}

/* Opciones del selector */
select option {
    background: #ffffff;
    color: #000000;
    padding: 10px;
}

/* Grupo de botones */
.button-group {
    display: flex;
    gap: 20px;
    margin-top: 50px;
    justify-content: center;
}

/* Estilo general de los botones */
.btn {
    background: transparent;
    color: #000000;
    border: 1px solid #000000;
    padding: 16px 32px;
    border-radius: 0;
    font-size: 12px;
    font-weight: 400;
    cursor: pointer;
    transition: all 0.4s ease;
    text-transform: uppercase;
    letter-spacing: 2px;
    position: relative;
    overflow: hidden;
    min-width: 120px;
}

/* Efecto de fondo para los botones */
.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: #000000;
    transition: left 0.4s ease;
    z-index: -1;
}

/* Efecto hover para los botones */
.btn:hover::before {
    left: 0;
}

/* Efecto hover para el color del texto */
.btn:hover {
    color: #ffffff;
    border-color: #000000;
}

/* Estilo del botón primario */
.btn-primary {
    border-color: #000000;
}

/* Efecto hover para el botón primario */
.btn-primary:hover {
    box-shadow: 0 0 0 2px #FFD700;
}

/* Estilo del botón secundario */
.btn-secondary {
    border-color: #666666;
}

/* Efecto hover para el botón secundario */
.btn-secondary:hover {
    box-shadow: 0 0 0 2px #FF0000;
}

/* Estilo de mensajes de error y éxito */
.error,
.success {
    margin-bottom: 30px;
    padding: 20px;
    text-align: center;
    font-size: 12px;
    font-weight: 300;
    letter-spacing: 1px;
    text-transform: uppercase;
    border: 1px solid;
}

/* Estilo del mensaje de error */
.error {
    background: rgba(255, 0, 0, 0.1);
    color: #FF0000;
    border-color: #FF0000;
}

/* Estilo del mensaje de éxito */
.success {
    background: rgba(255, 215, 0, 0.1);
    color: #B8860B;
    border-color: #FFD700;
}

/* Enlace para iniciar sesión */
.login-link {
    display: block;
    text-align: center;
    margin-top: 40px;
    color: #000000;
    text-decoration: none;
    font-size: 12px;
    font-weight: 300;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: all 0.3s ease;
    border-bottom: 1px solid transparent;
}

/* Efecto hover para el enlace de login */
.login-link:hover {
    border-bottom-color: #000000;
}

/* Pie de página minimalista */
.footer-minimal {
    text-align: center;
    padding: 40px;
    border-top: 1px solid #e0e0e0;
    margin-top: 40px;
}

/* Texto del pie de página */
.footer-minimal p {
    font-size: 12px;
    font-weight: 300;
    color: #999999;
    letter-spacing: 1px;
    text-transform: uppercase;
}

/* Estilo del modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

/* Contenido del modal */
.modal-content {
    background: #f8f8f8;
    border: 1px solid #333333;
    padding: 30px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
    animation: fadeIn 0.3s ease;
}

/* Encabezado del modal */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

/* Título del modal */
.modal-header h2 {
    font-size: 20px;
    font-weight: 300;
    color: #000000;
    letter-spacing: 2px;
    text-transform: uppercase;
}

/* Botón de cierre del modal */
.close-modal {
    font-size: 24px;
    cursor: pointer;
    color: #000000;
    background: none;
    border: none;
}

/* Barra de búsqueda del modal */
.search-bar {
    width: 100%;
    padding: 15px;
    border: 1px solid #333333;
    margin-bottom: 20px;
    font-size: 16px;
    font-weight: 300;
    color: #000000;
    background: #ffffff;
}

/* Efecto de foco para la barra de búsqueda */
.search-bar:focus {
    outline: none;
    border-color: #000000;
}

/* Lista de coaches en el modal */
.coach-list {
    max-height: 400px;
    overflow-y: auto;
}

/* Elemento individual de la lista de coaches */
.coach-item {
    padding: 10px;
    border-bottom: 1px solid #e0e0e0;
    cursor: pointer;
    font-size: 16px;
    font-weight: 300;
    color: #000000;
    transition: background 0.2s ease;
}

/* Efecto hover para los elementos de la lista */
.coach-item:hover {
    background: #e0e0e0;
}

/* Último elemento de la lista sin borde inferior */
.coach-item:last-child {
    border-bottom: none;
}

/* Media query para pantallas de hasta 768px */
@media (max-width: 768px) {
    .header {
        padding: 20px 30px;
    }

    .logo {
        left: 30px;
        width: 50px;
        height: 50px;
    }

    .title {
        font-size: 20px;
        letter-spacing: 2px;
    }

    .container {
        margin: 40px auto;
        padding: 0 20px;
    }

    .form-wrapper {
        padding: 40px 20px;
    }

    .form-header h1 {
        font-size: 24px;
        letter-spacing: 2px;
    }

    .step-title {
        font-size: 18px;
        letter-spacing: 1px;
    }

    .button-group {
        flex-direction: column;
        gap: 15px;
    }

    .btn {
        padding: 14px 24px;
        font-size: 11px;
        width: 100%;
    }

    .progress-indicators {
        flex-direction: column;
        gap: 10px;
    }

    .progress-step {
        font-size: 10px;
    }

    .email-group {
        flex-direction: column;
        gap: 15px;
    }

    .email-domain {
        width: 100%;
    }

    .modal-content {
        width: 95%;
        padding: 20px;
    }

    .modal-header h2 {
        font-size: 18px;
    }

    .search-bar {
        font-size: 14px;
        padding: 12px;
    }

    .coach-item {
        font-size: 14px;
    }
}

/* Media query para pantallas de hasta 480px */
@media (max-width: 480px) {
    .header {
        padding: 15px 20px;
    }

    .logo {
        left: 20px;
        width: 40px;
        height: 40px;
    }

    .title {
        font-size: 18px;
        letter-spacing: 1.5px;
    }

    .container {
        margin: 30px auto;
        padding: 0 15px;
    }

    .form-wrapper {
        padding: 30px 15px;
    }

    .form-header h1 {
        font-size: 20px;
        letter-spacing: 1.5px;
    }

    .form-header p {
        font-size: 12px;
    }

    .step-title {
        font-size: 16px;
        padding-bottom: 10px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    input,
    select {
        font-size: 14px;
        padding: 12px 0;
    }

    select {
        background-position: right 8px center;
        background-size: 10px;
        padding-right: 25px;
    }

    .btn {
        padding: 12px 20px;
        font-size: 10px;
    }

    .error,
    .success {
        font-size: 11px;
        padding: 15px;
    }

    .login-link {
        font-size: 11px;
    }

    .footer-minimal {
        padding: 30px 15px;
    }

    .footer-minimal p {
        font-size: 11px;
    }

    .modal-content {
        padding: 15px;
    }

    .modal-header h2 {
        font-size: 16px;
    }

    .close-modal {
        font-size: 20px;
    }

    .search-bar {
        font-size: 13px;
        padding: 10px;
    }

    .coach-list {
        max-height: 300px;
    }

    .coach-item {
        font-size: 13px;
        padding: 8px;
    }
}

/* Media query para pantallas muy pequeñas (hasta 320px) */
@media (max-width: 320px) {
    .header {
        padding: 10px 15px;
    }

    .logo {
        left: 15px;
        width: 35px;
        height: 35px;
    }

    .title {
        font-size: 16px;
        letter-spacing: 1px;
    }

    .container {
        margin: 20px auto;
        padding: 0 10px;
    }

    .form-wrapper {
        padding: 20px 10px;
    }

    .form-header h1 {
        font-size: 18px;
        letter-spacing: 1px;
    }

    .form-header p {
        font-size: 11px;
    }

    .step-title {
        font-size: 14px;
        padding-bottom: 8px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    input,
    select {
        font-size: 13px;
        padding: 10px 0;
    }

    .btn {
        padding: 10px 16px;
        font-size: 9px;
    }

    .progress-step {
        font-size: 9px;
    }

    .error,
    .success {
        font-size: 10px;
        padding: 12px;
    }

    .login-link {
        font-size: 10px;
    }

    .footer-minimal p {
        font-size: 10px;
    }

    .modal-content {
        padding: 10px;
    }

    .modal-header h2 {
        font-size: 14px;
    }

    .search-bar {
        font-size: 12px;
        padding: 8px;
    }

    .coach-item {
        font-size: 12px;
        padding: 6px;
    }
}
  </style>
</head>
<body>
  <div class="container">
    <div class="form-wrapper">
      <div class="form-header">
        <h1>Crear Cuenta</h1>
        <p>Complete su información paso a paso</p>
      </div>
      
      <div class="progress-container">
        <div class="progress-bar">
          <div class="progress-fill" id="progressBar"></div>
        </div>
        <div class="progress-indicators">
          <span class="progress-step active" id="progressStep1">Cuenta</span>
          <span class="progress-step" id="progressStep2">Personal</span>
          <span class="progress-step" id="progressStep3">Adicional</span>
          <span class="progress-step" id="progressStep4">Contacto</span>
        </div>
      </div>
      
      <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>
      
      <form action="register_process.php" method="POST" id="registrationForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" name="email" id="fullEmail">
        <input type="hidden" name="seleccionCouch" id="seleccionCouch">
        
        <!-- Paso 1: Información de cuenta -->
        <div class="step active" id="step1">
          <div class="step-title">Información de Cuenta</div>
                   <!-- ...existing code... -->
          <div class="form-group">
            <label for="emailUsername">Correo Electrónico</label>
            <div class="email-group">
              <input type="text" id="emailUsername" class="email-input" placeholder="MiCorreo" required>
              <select id="emailDomain" class="email-domain" required>
                <option value="" disabled selected>Seleccione dominio</option>
                <?php foreach ($ALLOWED_EMAIL_DOMAINS as $value => $label): ?>
                  <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <span id="email-check" style="color:red;font-size:12px;"></span>
          </div>
                    <div class="form-group">
            <label for="password">Contraseña</label>
            <div style="position:relative;">
              <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" required style="width:100%;padding-right:40px;">
              <span id="togglePassword" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;">
                <!-- Ojo cerrado SVG -->
                <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke="#333" stroke-width="2" fill="none"/>
                  <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="#333" stroke-width="2" fill="none"/>
                  <line x1="4" y1="20" x2="20" y2="4" stroke="#333" stroke-width="2"/>
                </svg>
                <!-- Ojo abierto SVG (oculto por defecto) -->
                <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" style="display:none;">
                  <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke="#333" stroke-width="2" fill="none"/>
                  <circle cx="12" cy="12" r="3" stroke="#333" stroke-width="2" fill="none"/>
                </svg>
              </span>
            </div>
          </div>
          <div class="button-group">
            <button type="button" class="btn btn-primary" onclick="nextStep(1)">Siguiente</button>
          </div>
        </div>
        
        <!-- Paso 2: Información personal -->
        <div class="step" id="step2">
          <div class="step-title">Información Personal</div>
          <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" required>
          </div>
          <div class="form-group">
            <label for="apellidoPaterno">Apellido Paterno</label>
            <input type="text" id="apellidoPaterno" name="apellidoPaterno" required>
          </div>
          <div class="form-group">
            <label for="apellidoMaterno">Apellido Materno</label>
            <input type="text" id="apellidoMaterno" name="apellidoMaterno" required>
          </div>
          <div class="button-group">
            <button type="button" class="btn btn-secondary" onclick="prevStep(2)">Anterior</button>
            <button type="button" class="btn btn-primary" onclick="nextStep(2)">Siguiente</button>
          </div>
        </div>
        
        <!-- Paso 3: Información adicional -->
        <div class="step" id="step3">
          <div class="step-title">Información Adicional</div>
          <div class="form-group">
            <label for="fechaNacimiento">Fecha de Nacimiento</label>
            <input type="date" id="fechaNacimiento" name="fechaNacimiento" required>
          </div>
          <div class="form-group">
            <label for="genero">Género</label>
            <select id="genero" name="genero" required>
              <option value="" disabled selected>Seleccione</option>
              <?php foreach ($ALLOWED_GENDERS as $value => $label): ?>
                <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="pais">País</label>
            <select id="pais" name="pais" required>
              <option value="" disabled selected>Seleccione</option>
              <?php foreach ($ALLOWED_COUNTRIES as $value => $label): ?>
                <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="button-group">
            <button type="button" class="btn btn-secondary" onclick="prevStep(3)">Anterior</button>
            <button type="button" class="btn btn-primary" onclick="nextStep(3)">Siguiente</button>
          </div>
        </div>
        
        <!-- Paso 4: Información de contacto y Herbalife -->
        <div class="step" id="step4">
          <div class="step-title">Información de Contacto</div>
          <div class="form-group">
            <label for="telefono">Número de Teléfono</label>
            <input type="tel" id="telefono" name="telefono" pattern="[0-9]{10,15}" placeholder="1234567890" required>
          </div>
          <div class="form-group">
            <label for="idHerbalife">ID de Herbalife</label>
            <input type="text" id="idHerbalife" name="idHerbalife" required>
            <span id="id-check" style="color:red;font-size:12px;"></span>
          </div>
          <div class="form-group">
            <label for="coachDisplay">Seleccione Coach</label>
            <input type="text" id="coachDisplay" placeholder="Haga clic para seleccionar un coach" readonly onclick="openCoachModal()">
          </div>
          <div class="button-group">
            <button type="button" class="btn btn-secondary" onclick="prevStep(4)">Anterior</button>
            <button type="submit" class="btn btn-primary">Crear Cuenta</button>
          </div>
        </div>
      </form>
      
      <!-- Modal for Coach Selection -->
      <div class="modal" id="coachModal">
        <div class="modal-content">
          <div class="modal-header">
            <h2>Seleccionar Coach</h2>
            <button class="close-modal" onclick="closeCoachModal()">&times;</button>
          </div>
          <input type="text" class="search-bar" id="coachSearch" placeholder="Buscar coach..." oninput="filterCoaches()">
          <div class="coach-list" id="coachList">
            <!-- Coaches will be populated dynamically -->
          </div>
        </div>
      </div>
      
      <a href="login.php" class="login-link">¿Ya tienes cuenta? Inicia sesión</a>
    </div>
  </div>

  <div class="footer-minimal">
    <p>© 2025 Todos los derechos reservados</p>
  </div>
  
  <script>
    let currentStep = 1;
    const totalSteps = 4;
    let coaches = [];
    let searchTimeout = null;

    // Fetch coaches from server
    function fetchCoaches(searchTerm = '') {
      const coachListElement = document.getElementById('coachList');
      coachListElement.innerHTML = '<div style="padding: 10px; text-align: center;">Cargando...</div>';
      
      fetch(`get_coaches.php?search=${encodeURIComponent(searchTerm)}`)
        .then(response => {
          if (!response.ok) {
            throw new Error('Error al cargar la lista de coaches');
          }
          return response.json();
        })
        .then(data => {
          if (data.error) {
            throw new Error(data.error);
          }
          coaches = data;
          populateCoachList(coaches);
        })
        .catch(error => {
          console.error('Error:', error);
          coachListElement.innerHTML = '<div style="padding: 10px; text-align: center; color: #FF0000;">Error al cargar coaches</div>';
        });
    }
    
    function populateCoachList(coachList) {
      const coachListElement = document.getElementById('coachList');
      coachListElement.innerHTML = '';
      if (coachList.length === 0) {
        coachListElement.innerHTML = '<div style="padding: 10px; text-align: center;">No se encontraron coaches</div>';
        return;
      }
      coachList.forEach(coach => {
        const div = document.createElement('div');
        div.className = 'coach-item';
        div.textContent = coach.name;
        div.onclick = () => selectCoach(coach.name);
        coachListElement.appendChild(div);
      });
    }
    
    function updateProgress() {
      const progress = (currentStep / totalSteps) * 100;
      document.getElementById('progressBar').style.width = progress + '%';
      
      for (let i = 1; i <= totalSteps; i++) {
        const stepElement = document.getElementById('progressStep' + i);
        if (i <= currentStep) {
          stepElement.classList.add('active');
        } else {
          stepElement.classList.remove('active');
        }
      }
    }
    
    function showStep(stepNumber) {
      document.querySelectorAll('.step').forEach(step => {
        step.classList.remove('active');
      });
      
      document.getElementById('step' + stepNumber).classList.add('active');
      currentStep = stepNumber;
      updateProgress();
    }
    
    function nextStep(step) {
      if (validateStep(step)) {
        if (step < totalSteps) {
          showStep(step + 1);
        }
      }
    }
    
    function prevStep(step) {
      if (step > 1) {
        showStep(step - 1);
      }
    }
    
    function validateStep(step) {
      switch(step) {
        case 1:
          return validateStep1();
        case 2:
          return validateStep2();
        case 3:
          return validateStep3();
        case 4:
          return validateStep4();
        default:
          return true;
      }
    }
    
    function validateStep1() {
      const emailUsername = document.getElementById('emailUsername').value;
      const emailDomain = document.getElementById('emailDomain').value;
      const password = document.getElementById('password').value;
      
      const emailUsernameRegex = /^[^\s@]+$/;
      if (!emailUsernameRegex.test(emailUsername)) {
        alert('El nombre de usuario del correo no debe contener espacios ni @.');
        return false;
      }
      
      if (!emailDomain) {
        alert('Por favor, seleccione un dominio de correo.');
        return false;
      }
      
      if (password.length < 8 || !/[a-zA-Z]/.test(password) || !/[0-9]/.test(password)) {
        alert('La contraseña debe tener al menos 8 caracteres, incluyendo una letra y un número.');
        return false;
      }
      
      // Update hidden email field
      document.getElementById('fullEmail').value = emailUsername + emailDomain;
      
      return true;
    }
    
    function validateStep2() {
      const nombre = document.getElementById('nombre').value;
      const apellidoPaterno = document.getElementById('apellidoPaterno').value;
      const apellidoMaterno = document.getElementById('apellidoMaterno').value;
      
      const nameRegex = /^[a-zA-ZÀ-ÿ\s]+$/;
      if (!nameRegex.test(nombre)) {
        alert('El nombre solo debe contener letras y espacios.');
        return false;
      }
      
      if (!nameRegex.test(apellidoPaterno)) {
        alert('El apellido paterno solo debe contener letras y espacios.');
        return false;
      }
      
      if (!nameRegex.test(apellidoMaterno)) {
        alert('El apellido materno solo debe contener letras y espacios.');
        return false;
      }
      
      return true;
    }
    
    function validateStep3() {
      const fechaNacimiento = document.getElementById('fechaNacimiento').value;
      const genero = document.getElementById('genero').value;
      const pais = document.getElementById('pais').value;
      
      if (!fechaNacimiento) {
        alert('Por favor, ingrese su fecha de nacimiento.');
        return false;
      }
      
      const birthDate = new Date(fechaNacimiento);
      const today = new Date();
      let age = today.getFullYear() - birthDate.getFullYear();
      const monthDiff = today.getMonth() - birthDate.getMonth();
      
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
      }
      
      if (age < 13) {
        alert('Debes tener al menos 13 años para registrarte.');
        return false;
      }
      
      if (!genero) {
        alert('Por favor, seleccione su género.');
        return false;
      }
      
      if (!pais) {
        alert('Por favor, seleccione su país.');
        return false;
      }
      
      return true;
    }
    
    function validateStep4() {
      const telefono = document.getElementById('telefono').value;
      const idHerbalife = document.getElementById('idHerbalife').value;
      const seleccionCouch = document.getElementById('seleccionCouch').value;
      
      if (!/^[0-9]{10,15}$/.test(telefono)) {
        alert('El número de teléfono debe tener entre 10 y 15 dígitos.');
        return false;
      }
      
      if (!idHerbalife.trim()) {
        alert('Por favor, ingrese su ID de Herbalife.');
        return false;
      }
      
      if (!seleccionCouch) {
        alert('Por favor, seleccione un coach.');
        return false;
      }
      
      return true;
    }
    
    // Modal and Coach Selection Functions
    function openCoachModal() {
      const modal = document.getElementById('coachModal');
      fetchCoaches(); // Fetch coaches without search term initially
      modal.style.display = 'flex';
      document.getElementById('coachSearch').focus();
    }
    
    function closeCoachModal() {
      document.getElementById('coachModal').style.display = 'none';
      document.getElementById('coachSearch').value = '';
      fetchCoaches(); // Reset list when closing
    }
    
    function selectCoach(coach) {
      document.getElementById('coachDisplay').value = coach;
      document.getElementById('seleccionCouch').value = coach;
      closeCoachModal();
    }
    
    function filterCoaches() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        const searchTerm = document.getElementById('coachSearch').value;
        fetchCoaches(searchTerm); // Fetch filtered list from server
      }, 300); // Debounce to reduce server requests
    }
    
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      if (validateStep1() && validateStep2() && validateStep3() && validateStep4()) {
        // Ensure email is updated before submission
        const emailUsername = document.getElementById('emailUsername').value;
        const emailDomain = document.getElementById('emailDomain').value;
        document.getElementById('fullEmail').value = emailUsername + emailDomain;
        this.submit();
      }
    });
    
    // Update full email on input change
    document.getElementById('emailUsername').addEventListener('input', function() {
      const emailUsername = this.value;
      const emailDomain = document.getElementById('emailDomain').value;
      if (emailDomain) {
        document.getElementById('fullEmail').value = emailUsername + emailDomain;
      }
    });
    
    document.getElementById('emailDomain').addEventListener('change', function() {
      const emailUsername = document.getElementById('emailUsername').value;
      const emailDomain = this.value;
      if (emailUsername) {
        document.getElementById('fullEmail').value = emailUsername + emailDomain;
      }
    });
    
    updateProgress();

     
    
    let emailExists = false;
    let idHerbalifeExists = false;
    
    // Verificación en tiempo real de correo
    document.getElementById('emailUsername').addEventListener('input', checkEmailUnique);
    document.getElementById('emailDomain').addEventListener('change', checkEmailUnique);
    
    function checkEmailUnique() {
      const username = document.getElementById('emailUsername').value;
      const domain = document.getElementById('emailDomain').value;
      const email = username && domain ? username + domain : '';
      const emailMsg = document.getElementById('email-check');
      if (!email) {
        emailMsg.textContent = '';
        emailExists = false;
        return;
      }
      fetch('check_user.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'email=' + encodeURIComponent(email)
      })
        .then(res => res.json())
        .then(data => {
          if (data.exists) {
            emailMsg.textContent = 'Este correo ya está registrado.';
            emailExists = true;
          } else {
            emailMsg.textContent = '';
            emailExists = false;
          }
        });
    }
    
    // Verificación en tiempo real de ID Herbalife
    document.getElementById('idHerbalife').addEventListener('input', function() {
      const id = this.value;
      const idMsg = document.getElementById('id-check');
      if (!id) {
        idMsg.textContent = '';
        idHerbalifeExists = false;
        return;
      }
      fetch('check_user.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'idHerbalife=' + encodeURIComponent(id)
      })
        .then(res => res.json())
        .then(data => {
          if (data.exists) {
            idMsg.textContent = 'Este ID de Herbalife ya está registrado.';
            idHerbalifeExists = true;
          } else {
            idMsg.textContent = '';
            idHerbalifeExists = false;
          }
        });
    });
    
    // Bloquea el avance si ya existen
    function validateStep1() {
      const emailUsername = document.getElementById('emailUsername').value;
      const emailDomain = document.getElementById('emailDomain').value;
      const password = document.getElementById('password').value;
    
      const emailUsernameRegex = /^[^\s@]+$/;
      if (!emailUsernameRegex.test(emailUsername)) {
        alert('El nombre de usuario del correo no debe contener espacios ni @.');
        return false;
      }
      if (!emailDomain) {
        alert('Por favor, seleccione un dominio de correo.');
        return false;
      }
      if (password.length < 8 || !/[a-zA-Z]/.test(password) || !/[0-9]/.test(password)) {
        alert('La contraseña debe tener al menos 8 caracteres, incluyendo una letra y un número.');
        return false;
      }
      document.getElementById('fullEmail').value = emailUsername + emailDomain;
    
      if (emailExists) {
        alert('Este correo ya está registrado.');
        return false;
      }
      return true;
    }
    
    function validateStep4() {
      const telefono = document.getElementById('telefono').value;
      const idHerbalife = document.getElementById('idHerbalife').value;
      const seleccionCouch = document.getElementById('seleccionCouch').value;
    
      if (!/^[0-9]{10,15}$/.test(telefono)) {
        alert('El número de teléfono debe tener entre 10 y 15 dígitos.');
        return false;
      }
      if (!idHerbalife.trim()) {
        alert('Por favor, ingrese su ID de Herbalife.');
        return false;
      }
      if (!seleccionCouch) {
        alert('Por favor, seleccione un coach.');
        return false;
      }
      if (idHerbalifeExists) {
        alert('Este ID de Herbalife ya está registrado.');
        return false;
      }
      return true;
    }

        // ...existing code...
    document.getElementById('togglePassword').addEventListener('change', function() {
      const pwd = document.getElementById('password');
      pwd.type = this.checked ? 'text' : 'password';
    });


    document.getElementById('togglePassword').addEventListener('click', function() {
      const pwd = document.getElementById('password');
      const eyeOpen = document.getElementById('eyeOpen');
      const eyeClosed = document.getElementById('eyeClosed');
      if (pwd.type === 'password') {
        pwd.type = 'text';
        eyeOpen.style.display = '';
        eyeClosed.style.display = 'none';
      } else {
        pwd.type = 'password';
        eyeOpen.style.display = 'none';
        eyeClosed.style.display = '';
      }
    });
  </script>
</body>
</html>
