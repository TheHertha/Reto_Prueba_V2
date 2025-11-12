<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: inicio.php");
    exit;
}

$ALLOWED_GENDERS = ['Masculino','Femenino','Otro'];
$ALLOWED_COUNTRIES = ['Argentina','Bolivia','Canadá','Colombia','Costa Rica','Ecuador','El Salvador','Estados Unidos','Guatemala','Italia','México','Perú'];

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
unset($_SESSION['error'], $_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Registro - CAT21</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #ffffff; color: #000000; min-height: 100vh; line-height: 1.6; }
    .header { background: #000000; padding: 30px 60px; display: flex; align-items: center; justify-content: center; position: relative; border-bottom: 1px solid #333333; }
    .logo { position: absolute; left: 60px; width: 60px; height: 60px; border-radius: 8px; object-fit: cover; cursor: pointer; }
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
    .progress-step { 
    font-size: 12px; 
    font-weight: 300;
    color: rgba(0, 0, 0, 0.4); 
    letter-spacing: 1px; 
    text-transform: uppercase; 
    transition: all 0.3s ease;}
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
    .btn:hover { color: #ffffff; }
    .btn-primary { border-color: #000000; }
    .btn-primary:hover { box-shadow: 0 0 0 2px #FFD700; }
    .btn-secondary { border-color: #666666; }
    .btn-secondary:hover { box-shadow: 0 0 0 2px #FF0000; }
    .error, .success { margin-bottom: 30px; padding: 20px; text-align: center; font-size: 12px; font-weight: 300; letter-spacing: 1px; text-transform: uppercase; border: 1px solid; }
    .error { background: rgba(255, 0, 0, 0.1); color: #FF0000; border-color: #FF0000; }
    .success { background: rgba(255, 215, 0, 0.1); color: #B8860B; border-color: #FFD700; }
    .login-link { display: block; text-align: center; margin-top: 40px; color: #000000; text-decoration: none; font-size: 12px; font-weight: 300; letter-spacing: 1px; text-transform: uppercase; transition: all 0.3s ease; border-bottom: 1px solid transparent; }
    .login-link:hover { border-bottom-color: #000000; }
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
    .role-option { cursor: pointer; padding: 20px; border: 2px solid #333; border-radius: 8px; width: 220px; transition: all 0.3s; text-align: center; }
    .role-option:hover { border-color: #000; background: #f0f0f0; }
    .role-option.selected { border-color: #000; background: #f0f0f0; }
    .check-status { font-size: 12px; display: block; min-height: 20px; margin-top: 5px; }
    @media (max-width: 768px) { .header { padding: 20px 30px; } .logo { left: 30px; width: 50px; height: 50px; } .title { font-size: 20px; } .container { margin: 40px auto; padding: 0 20px; } .form-wrapper { padding: 40px 20px; } }
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
          <span class="progress-step active" id="progressStep1">Rol</span>
          <span class="progress-step" id="progressStep2">Cuenta</span>
          <span class="progress-step" id="progressStep3">Personal</span>
          <span class="progress-step" id="progressStep4">Adicional</span>
          <span class="progress-step" id="progressStep5">Contacto</span>
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
        <input type="hidden" name="rol" id="rol" value="" required>
        <input type="hidden" name="seleccionCouch" id="seleccionCouch" value="">

        <!-- Paso 1: Rol -->
        <div class="step active" id="step1">
          <div class="step-title">¿Qué tipo de usuario eres?</div>
          <div class="form-group" style="text-align:center;">
            <div style="display:flex; gap:40px; justify-content:center; flex-wrap:wrap; margin-top:50px;">
              <div class="role-option" onclick="selectRol('user')">
                <input type="radio" name="temp_rol" value="user" style="display:none;">
                <div style="font-size:28px; margin-bottom:10px;">Participante</div>
                <div style="font-size:14px; color:#666;">Quiero participar en los retos</div>
              </div>
              <div class="role-option" onclick="selectRol('coach')">
                <input type="radio" name="temp_rol" value="coach" style="display:none;">
                <div style="font-size:28px; margin-bottom:10px;">Coach</div>
                <div style="font-size:14px; color:#666;">Quiero guiar a mis participantes</div>
              </div>
            </div>
          </div>
          <div class="button-group">
           <button type="button" class="btn btn-primary" id="btnNextRole" disabled onclick="nextStep(1)">Siguiente</button>
          </div>
        </div>

        <!-- Paso 2: Cuenta -->
        <div class="step" id="step2">
          <div class="step-title">Información de Cuenta</div>
          <div class="form-group">
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" placeholder="micorreo@gmail.com" required oninput="checkEmail()">
            <span id="email-check" class="check-status"></span>
          </div>
          <div class="form-group">
            <label for="password">Contraseña</label>
            <div style="position:relative;">
              <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" required>
              <span id="togglePassword" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;">
                <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><line x1="4" y1="20" x2="20" y2="4"/></svg>
                <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" style="display:none;"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
              </span>
            </div>
          </div>
          <div class="button-group">
            <button type="button" class="btn btn-secondary" onclick="prevStep(2)">Anterior</button>
            <button type="button" class="btn btn-primary" onclick="nextStep(2)">Siguiente</button>
          </div>
        </div>

        <!-- Paso 3: Personal -->
        <div class="step" id="step3">
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
            <button type="button" class="btn btn-secondary" onclick="prevStep(3)">Anterior</button>
            <button type="button" class="btn btn-primary" onclick="nextStep(3)">Siguiente</button>
          </div>
        </div>

        <!-- Paso 4: Adicional -->
        <div class="step" id="step4">
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
            <button type="button" class="btn btn-secondary" onclick="prevStep(4)">Anterior</button>
            <button type="button" class="btn btn-primary" onclick="nextStep(4)">Siguiente</button>
          </div>
        </div>

        <!-- Paso 5: Contacto -->
        <div class="step" id="step5">
          <div class="step-title">Información de Contacto</div>
          <div class="form-group">
            <label for="telefono">Número de Teléfono</label>
            <input type="tel" id="telefono" name="telefono" pattern="[0-9]{10,15}" placeholder="1234567890" required>
          </div>
          <div class="form-group">
            <label for="idHerbalife">ID de Herbalife <small>(opcional)</small></label>
            <input type="text" id="idHerbalife" name="idHerbalife" oninput="checkIdHerbalife()">
            <span id="id-check" class="check-status"><small style="color:#666;">Opcional</small></span>
          </div>
          <div class="form-group" id="coachSelectionGroup">
            <label for="coachDisplay">Seleccione Coach <span style="color:red;">*</span></label>
            <input type="text" id="coachDisplay" placeholder="Haga clic para seleccionar un coach" readonly onclick="openCoachModal()" style="cursor:pointer;">
            <small style="color:#666;">Obligatorio solo para participantes</small>
          </div>
          <div class="button-group">
            <button type="button" class="btn btn-secondary" onclick="prevStep(5)">Anterior</button>
            <button type="submit" class="btn btn-primary" id="submitBtn">Crear Cuenta</button>
          </div>
        </div>
      </form>

      <!-- Modal Coach -->
      <div class="modal" id="coachModal">
        <div class="modal-content">
          <div class="modal-header">
            <h2>Seleccionar Coach</h2>
            <button type="button" class="close-modal" onclick="closeCoachModal()">×</button>
          </div>
          <input type="text" class="search-bar" id="coachSearch" placeholder="Buscar coach..." oninput="filterCoaches()">
          <div class="coach-list" id="coachList"><div style="padding:15px;text-align:center;">Cargando...</div></div>
        </div>
      </div>

      <a href="login.php" class="login-link">¿Ya tienes cuenta? Inicia sesión</a>
    </div>
  </div>

<script>
let currentStep = 1;
const totalSteps = 5;
let selectedRol = '';
let selectedCoachName = '';

// VALIDACIÓN EMAIL EN TIEMPO REAL
function checkEmail() {
    const email = document.getElementById('email').value.trim();
    const status = document.getElementById('email-check');
    if (email.length < 5) {
        status.innerHTML = '';
        return;
    }
    fetch('register_process.php?check_email=' + encodeURIComponent(email))
        .then(r => r.json())
        .then(data => {
            if (data.exists) {
                status.innerHTML = '<span style="color:red;">Este correo ya está registrado</span>';
            } else {
                status.innerHTML = '<span style="color:green;">Disponible</span>';
            }
        })
        .catch(() => {
            status.innerHTML = '<span style="color:orange;">Error de conexión</span>';
        });
}

// VALIDACIÓN ID HERBALIFE EN TIEMPO REAL
function checkIdHerbalife() {
    const id = document.getElementById('idHerbalife').value.trim();
    const status = document.getElementById('id-check');
    if (id === '') {
        status.innerHTML = '<small style="color:#666;">Opcional</small>';
        return;
    }
    fetch('register_process.php?check_id=' + encodeURIComponent(id))
        .then(r => r.json())
        .then(data => {
            if (data.exists) {
                status.innerHTML = '<span style="color:red;">Este ID ya está registrado</span>';
            } else {
                status.innerHTML = '<span style="color:green;">Disponible</span>';
            }
        })
        .catch(() => {
            status.innerHTML = '<span style="color:orange;">Error de conexión</span>';
        });
}

function selectRol(rol) {
    selectedRol = rol;
    document.getElementById('rol').value = rol;
    document.getElementById('btnNextRole').disabled = false;

    document.querySelectorAll('.role-option').forEach(el => {
        el.classList.remove('selected');
        el.style.borderColor = '#333';
        el.style.background = 'transparent';
    });
    event.target.closest('.role-option').classList.add('selected');
    event.target.closest('.role-option').style.borderColor = '#000';
    event.target.closest('.role-option').style.background = '#f0f0f0';
}

function showStep(n) {
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    document.getElementById('step' + n).classList.add('active');
    currentStep = n;
    updateProgress();
    if (n === 5) updateCoachVisibility();
}

function nextStep(step) {
    if (step === 1 && !selectedRol) {
        alert('Por favor, selecciona si eres Coach o Participante.');
        return;
    }
    showStep(step + 1);
}

function prevStep(step) {
    if (step > 1) showStep(step - 1);
}

function updateCoachVisibility() {
    const group = document.getElementById('coachSelectionGroup');
    const display = document.getElementById('coachDisplay');
    const hidden = document.getElementById('seleccionCouch');
    const submitBtn = document.getElementById('submitBtn');

    if (selectedRol === 'coach') {
        group.style.display = 'none';
        display.removeAttribute('required');
        hidden.removeAttribute('required');
        hidden.value = '';
        selectedCoachName = '';
        submitBtn.textContent = 'Registrarme como Coach';
    } else {
        group.style.display = 'block';
        display.setAttribute('required', 'required');
        hidden.setAttribute('required', 'required');
        submitBtn.textContent = 'Crear Cuenta como Participante';
    }
}

function updateProgress() {
    const progress = (currentStep / totalSteps) * 100;
    document.getElementById('progressBar').style.width = progress + '%';
    for (let i = 1; i <= totalSteps; i++) {
        const step = document.getElementById('progressStep' + i);
        if (step) step.classList.toggle('active', i <= currentStep);
    }
}

function openCoachModal() {
    document.getElementById('coachModal').style.display = 'flex';
    fetchCoaches();
}
function closeCoachModal() {
    document.getElementById('coachModal').style.display = 'none';
}
function selectCoach(coachName) {
    selectedCoachName = coachName;
    document.getElementById('coachDisplay').value = coachName;
    document.getElementById('seleccionCouch').value = coachName;
    closeCoachModal();
}

function fetchCoaches(search = '') {
    const list = document.getElementById('coachList');
    list.innerHTML = '<div style="padding:15px;text-align:center;">Cargando...</div>';
    fetch(`get_coaches.php?search=${encodeURIComponent(search)}`)
        .then(r => r.json())
        .then(data => {
            list.innerHTML = '';
            if (!data || data.length === 0) {
                list.innerHTML = '<div style="padding:15px;color:#666;text-align:center;">No se encontraron coaches.</div>';
                return;
            }
            data.forEach(c => {
                const item = document.createElement('div');
                item.className = 'coach-item';
                item.textContent = c.name;
                item.onclick = () => selectCoach(c.name);
                list.appendChild(item);
            });
        })
        .catch(() => {
            list.innerHTML = '<div style="padding:15px;color:red;">Error al cargar coaches.</div>';
        });
}

function filterCoaches() {
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(() => {
        fetchCoaches(document.getElementById('coachSearch').value.trim());
    }, 300);
}

document.getElementById('registrationForm').addEventListener('submit', function(e) {
    if (selectedRol === 'user' && !selectedCoachName) {
        e.preventDefault();
        alert('Debes seleccionar un Coach para registrarte como Participante.');
        openCoachModal();
        return false;
    }
    if (!selectedRol) {
        e.preventDefault();
        alert('Debes seleccionar un rol.');
        showStep(1);
        return false;
    }
});

updateProgress();
updateCoachVisibility();
</script>
</body>
</html>