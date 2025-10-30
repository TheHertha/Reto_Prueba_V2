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
    /* [CSS COMPLETO - SIN CAMBIOS] */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #ffffff; color: #000000; min-height: 100vh; line-height: 1.6; }
    .header { background: #000000; padding: 30px 60px; display: flex; align-items: center; justify-content: center; position: relative; border-bottom: 1px solid #333333; }
    .logo { position: absolute; left: 60px; width: 60px; height: 60px; border-radius: 8px; transition: all 0.3s ease; object-fit: cover; cursor: pointer; }
    .logo:hover { transform: rotate(5deg); box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2); }
    .title { font-size: 28px; font-weight: 300; color: #ffffff; letter-spacing: 4px; text-transform: uppercase; }
    .container { max-width: 800px; margin: 60px auto; padding: 0 60px; }
    .form-wrapper { background: #f8f8f8; border: 1px solid #333333; padding: 60px; position: relative; overflow: hidden; }
    .form-header { text-align: center; margin-bottom: 40px; }
    .form-header h1 { font-size: 32px; font-weight: 100; color: #000000; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 10px; }
    .form-header p { font-size: 14px; font-weight: 300; color: #666666; letter-spacing: 1px; text-transform: uppercase; }
    .progress-container { margin-bottom: 50px; }
    .progress-bar { width: 100%; height: 1px; background: rgba(0, 0, 0, 0.2); margin-bottom: 20px; overflow: hidden; }
    .progress-fill { height: 100%; background: #000000; transition: width 0.4s ease; }
    .progress-indicators { display: flex; justify-content: space-between; align-items: center; }
    .progress-step { font-size: 12px; font-weight: 300; color: rgba(0, 0, 0, 0.4); letter-spacing: 1px; text-transform: uppercase; transition: all 0.3s ease; }
    .progress-step.active { color: #000000; font-weight: 400; }
    .step { display: none; animation: fadeIn 0.6s ease; }
    .step.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    .step-title { font-size: 24px; font-weight: 100; color: #000000; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 30px; text-align: center; border-bottom: 1px solid #000000; padding-bottom: 15px; }
    .form-group { margin-bottom: 30px; }
    label { display: block; font-size: 12px; font-weight: 400; color: #000000; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 10px; }
    input, select { width: 100%; padding: 15px 0; border: none; border-bottom: 1px solid #333333; background: transparent; color: #000000; font-size: 16px; font-weight: 300; transition: all 0.3s ease; }
    input:focus, select:focus { outline: none; border-bottom-color: #000000; border-bottom-width: 2px; }
    input::placeholder { color: #999999; font-weight: 300; }
    select { cursor: pointer; appearance: none; background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"><path fill="%23333333" d="M6 9L2 5h8z"/></svg>'); background-repeat: no-repeat; background-position: right 10px center; background-size: 12px; padding-right: 30px; }
    .button-group { display: flex; gap: 20px; margin-top: 50px; justify-content: center; }
    .btn { background: transparent; color: #000000; border: 1px solid #000000; padding: 16px 32px; border-radius: 0; font-size: 12px; font-weight: 400; cursor: pointer; transition: all 0.4s ease; text-transform: uppercase; letter-spacing: 2px; position: relative; overflow: hidden; min-width: 120px; }
    .btn::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: #000000; transition: left 0.4s ease; z-index: -1; }
    .btn:hover::before { left: 0; }
    .btn:hover { color: #ffffff; border-color: #000000; }
    .btn-primary { border-color: #000000; }
    .btn-primary:hover { box-shadow: 0 0 0 2px #FFD700; }
    .btn-secondary { border-color: #666666; }
    .btn-secondary:hover { box-shadow: 0 0 0 2px #FF0000; }
    .error, .success { margin-bottom: 30px; padding: 20px; text-align: center; font-size: 12px; font-weight: 300; letter-spacing: 1px; text-transform: uppercase; border: 1px solid; }
    .error { background: rgba(255, 0, 0, 0.1); color: #FF0000; border-color: #FF0000; }
    .success { background: rgba(255, 215, 0, 0.1); color: #B8860B; border-color: #FFD700; }
    .login-link { display: block; text-align: center; margin-top: 40px; color: #000000; text-decoration: none; font-size: 12px; font-weight: 300; letter-spacing: 1px; text-transform: uppercase; transition: all 0.3s ease; border-bottom: 1px solid transparent; }
    .login-link:hover { border-bottom-color: #000000; }
    .footer-minimal { text-align: center; padding: 40px; border-top: 1px solid #e0e0e0; margin-top: 40px; }
    .footer-minimal p { font-size: 12px; font-weight: 300; color: #999999; letter-spacing: 1px; text-transform: uppercase; }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 1000; justify-content: center; align-items: center; }
    .modal-content { background: #f8f8f8; border: 1px solid #333333; padding: 30px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto; position: relative; animation: fadeIn 0.3s ease; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .modal-header h2 { font-size: 20px; font-weight: 300; color: #000000; letter-spacing: 2px; text-transform: uppercase; }
    .close-modal { font-size: 24px; cursor: pointer; color: #000000; background: none; border: none; }
    .search-bar { width: 100%; padding: 15px; border: 1px solid #333333; margin-bottom: 20px; font-size: 16px; font-weight: 300; color: #000000; background: #ffffff; }
    .search-bar:focus { outline: none; border-color: #000000; }
    .coach-list { max-height: 400px; overflow-y: auto; }
    .coach-item { padding: 10px; border-bottom: 1px solid #e0e0e0; cursor: pointer; font-size: 16px; font-weight: 300; color: #000000; transition: background 0.2s ease; }
    .coach-item:hover { background: #e0e0e0; }
    .coach-item:last-child { border-bottom: none; }
    @media (max-width: 768px) { .header { padding: 20px 30px; } .logo { left: 30px; width: 50px; height: 50px; } .title { font-size: 20px; } .container { margin: 40px auto; padding: 0 20px; } .form-wrapper { padding: 40px 20px; } .form-header h1 { font-size: 24px; } .step-title { font-size: 18px; } .button-group { flex-direction: column; gap: 15px; } .btn { padding: 14px 24px; font-size: 11px; width: 100%; } .progress-indicators { flex-direction: column; gap: 10px; } .progress-step { font-size: 10px; } .modal-content { width: 95%; padding: 20px; } }
    @media (max-width: 480px) { .header { padding: 15px 20px; } .logo { left: 20px; width: 40px; height: 40px; } .title { font-size: 18px; } .form-header h1 { font-size: 20px; } .step-title { font-size: 16px; } .form-group { margin-bottom: 20px; } input, select { font-size: 14px; padding: 12px 0; } .btn { padding: 12px 20px; font-size: 10px; } }
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
        <input type="hidden" name="seleccionCouch" id="seleccionCouch" required>
        
        <!-- Paso 1: Información de cuenta -->
        <div class="step active" id="step1">
          <div class="step-title">Información de Cuenta</div>
          
          <div class="form-group">
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" placeholder="micorreo@gmail.com" required>
            <span id="email-check" style="color:red;font-size:12px;"></span>
          </div>

          <div class="form-group">
            <label for="password">Contraseña</label>
            <div style="position:relative;">
              <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" required style="width:100%;padding-right:40px;">
              <span id="togglePassword" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;">
                <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke="#333" stroke-width="2" fill="none"/>
                  <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="#333" stroke-width="2" fill="none"/>
                  <line x1="4" y1="20" x2="20" y2="4" stroke="#333" stroke-width="2"/>
                </svg>
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
            <label for="apellidoMaterno">Apellido Materno <small>(opcional)</small></label>
            <input type="text" id="apellidoMaterno" name="apellidoMaterno">
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
            <label for="idHerbalife">ID de Herbalife <small>(opcional)</small></label>
            <input type="text" id="idHerbalife" name="idHerbalife">
            <span id="id-check" style="color:red;font-size:12px;"></span>
          </div>
          <div class="form-group">
            <label for="coachDisplay">Seleccione Coach *</label>
            <input type="text" id="coachDisplay" placeholder="Haga clic para seleccionar un coach" readonly required onclick="openCoachModal()" style="cursor:pointer;">
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
            <button type="button" class="close-modal" onclick="closeCoachModal()">×</button>
          </div>
          <input type="text" class="search-bar" id="coachSearch" placeholder="Buscar coach..." oninput="filterCoaches()">
          <div class="coach-list" id="coachList">
            <!-- Cargando... -->
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
    let emailExists = false;
    let idHerbalifeExists = false;

    // === CORREGIDO: Abrir modal ===
    function openCoachModal() {
      const modal = document.getElementById('coachModal'); // ¡CORREGIDO!
      fetchCoaches();
      modal.style.display = 'flex';
      document.getElementById('coachSearch').focus();
    }
    
    // === CORREGIDO: Cerrar modal ===
    function closeCoachModal() {
      document.getElementById('coachModal').style.display = 'none';
      document.getElementById('coachSearch').value = '';
    }
    
    function selectCoach(coachName) {
      document.getElementById('coachDisplay').value = coachName;
      document.getElementById('seleccionCouch').value = coachName;
      closeCoachModal();
    }

    function fetchCoaches(searchTerm = '') {
      const list = document.getElementById('coachList');
      list.innerHTML = '<div style="padding:15px;text-align:center;">Cargando coaches...</div>';

      fetch(`get_coaches.php?search=${encodeURIComponent(searchTerm)}`)
        .then(res => res.json())
        .then(data => {
          if (data.error) throw new Error(data.error);
          coaches = data;
          populateCoachList(coaches);
        })
        .catch(err => {
          list.innerHTML = `<div style="padding:15px;color:#FF0000;text-align:center;">Error: ${err.message}</div>`;
        });
    }

    function populateCoachList(list) {
      const container = document.getElementById('coachList');
      container.innerHTML = '';
      if (list.length === 0) {
        container.innerHTML = '<div style="padding:15px;text-align:center;color:#666;">No se encontraron coaches.</div>';
        return;
      }
      list.forEach(coach => {
        const item = document.createElement('div');
        item.className = 'coach-item';
        item.textContent = coach.name;
        item.onclick = () => selectCoach(coach.name);
        container.appendChild(item);
      });
    }

    function filterCoaches() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        const term = document.getElementById('coachSearch').value.trim();
        fetchCoaches(term);
      }, 300);
    }

    // === Resto del código (validaciones, pasos, etc.) ===
    function updateProgress() {
      const progress = (currentStep / totalSteps) * 100;
      document.getElementById('progressBar').style.width = progress + '%';
      for (let i = 1; i <= totalSteps; i++) {
        const el = document.getElementById('progressStep' + i);
        el.classList.toggle('active', i <= currentStep);
      }
    }

    function showStep(n) {
      document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
      document.getElementById('step' + n).classList.add('active');
      currentStep = n;
      updateProgress();
    }

    function nextStep(step) { if (validateStep(step)) showStep(step + 1); }
    function prevStep(step) { if (step > 1) showStep(step - 1); }

    function validateStep(step) {
      switch(step) {
        case 1: return validateStep1();
        case 2: return validateStep2();
        case 3: return validateStep3();
        case 4: return validateStep4();
        default: return true;
      }
    }

    function validateStep1() {
      const email = document.getElementById('email').value.trim();
      const pwd = document.getElementById('password').value;
      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { alert('Correo inválido.'); return false; }
      if (pwd.length < 8 || !/[a-zA-Z]/.test(pwd) || !/[0-9]/.test(pwd)) { alert('Contraseña: mínimo 8 caracteres, 1 letra, 1 número.'); return false; }
      if (emailExists) { alert('Este correo ya está registrado.'); return false; }
      return true;
    }

    function validateStep2() {
      const n = document.getElementById('nombre').value.trim();
      const ap = document.getElementById('apellidoPaterno').value.trim();
      if (!n || !/^[a-zA-ZÀ-ÿ\s]+$/.test(n)) { alert('Nombre obligatorio y solo letras.'); return false; }
      if (!ap || !/^[a-zA-ZÀ-ÿ\s]+$/.test(ap)) { alert('Apellido paterno obligatorio y solo letras.'); return false; }
      return true;
    }

    function validateStep3() {
      const fn = document.getElementById('fechaNacimiento').value;
      const g = document.getElementById('genero').value;
      const p = document.getElementById('pais').value;
      if (!fn || !g || !p) { alert('Complete todos los campos.'); return false; }
      const age = new Date().getFullYear() - new Date(fn).getFullYear();
      if (age < 13) { alert('Debes tener al menos 13 años.'); return false; }
      return true;
    }

    function validateStep4() {
      const tel = document.getElementById('telefono').value;
      const coach = document.getElementById('seleccionCouch').value;
      if (!/^[0-9]{10,15}$/.test(tel)) { alert('Teléfono: 10-15 dígitos.'); return false; }
      if (!coach) { alert('Debe seleccionar un coach.'); return false; }
      if (idHerbalifeExists) { alert('Este ID de Herbalife ya está en uso.'); return false; }
      return true;
    }

    // Verificación en tiempo real
    document.getElementById('email').addEventListener('input', () => {
      const email = document.getElementById('email').value.trim();
      const msg = document.getElementById('email-check');
      if (!email.includes('@')) { msg.textContent = ''; emailExists = false; return; }
      fetch('check_user.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'email=' + encodeURIComponent(email) })
        .then(r => r.json())
        .then(d => { msg.textContent = d.exists ? 'Este correo ya está registrado.' : ''; emailExists = d.exists; });
    });

    document.getElementById('idHerbalife').addEventListener('input', () => {
      const id = document.getElementById('idHerbalife').value.trim();
      const msg = document.getElementById('id-check');
      if (!id) { msg.textContent = ''; idHerbalifeExists = false; return; }
      fetch('check_user.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'idHerbalife=' + encodeURIComponent(id) })
        .then(r => r.json())
        .then(d => { msg.textContent = d.exists ? 'Este ID de Herbalife ya está registrado.' : ''; idHerbalifeExists = d.exists; });
    });

    // Toggle password
    document.getElementById('togglePassword').addEventListener('click', () => {
      const p = document.getElementById('password');
      const o = document.getElementById('eyeOpen');
      const c = document.getElementById('eyeClosed');
      if (p.type === 'password') { p.type = 'text'; o.style.display = ''; c.style.display = 'none'; }
      else { p.type = 'password'; o.style.display = 'none'; c.style.display = ''; }
    });

    // Form submit
    document.getElementById('registrationForm').addEventListener('submit', e => {
      e.preventDefault();
      if (validateStep1() && validateStep2() && validateStep3() && validateStep4()) {
        e.target.submit();
      }
    });

    // Inicializar
    updateProgress();
  </script>
</body>
</html>