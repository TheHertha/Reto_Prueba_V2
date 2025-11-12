<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Por favor, inicia sesión para acceder a tu perfil.";
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Hardcoded lists for facilitador and presidente
$facilitadores = ['Alex', 'Adriana', 'Esmeralda', 'Fide', 'Fernando','Francisco', 'Juan', 'Oscar', 'No asignado','No aplica'];
$presidentes = ['Juan Pérez', 'Sofía Martínez', 'Luis Fernández', 'No asignado'];

try {
    // Fetch user data including seleccion_couch, facilitador, and presidente
    $stmt = $pdo->prepare("SELECT nombre, apellido_paterno, email, rol, fecha_nacimiento, foto_perfil, contrasena, seleccion_couch, facilitador, presidente FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        $_SESSION['error'] = "Usuario no encontrado.";
        header("Location: login.php");
        exit;
    }
    $birthdate = new DateTime($user['fecha_nacimiento']);
    $today = new DateTime();
    $age = $today->diff($birthdate)->y;
    // Use the password directly from the database
    $password = $user['contrasena'];
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al cargar datos: " . $e->getMessage();
    header("Location: inicio.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['csrf_token'] ?? 'unset';
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Error de seguridad. Intenta de nuevo.";
        header("Location: perfil.php");
        exit;
    }

    // Handle photo upload
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
        $imagen = subirImagen($_FILES['foto_perfil'], 'user_' . $_SESSION['user_id'] . '_');
        if ($imagen !== false) {
            try {
                $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
                $stmt->execute([$imagen, $_SESSION['user_id']]);
                $user['foto_perfil'] = $imagen;
                $_SESSION['success'] = "Foto de perfil actualizada exitosamente.";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error al actualizar la foto: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Error al subir la imagen. Verifica el formato (JPG, PNG, GIF) y tamaño (<5MB).";
        }
    }

    // Handle facilitador update
    if (isset($_POST['facilitador'])) {
        $facilitador = in_array($_POST['facilitador'], $facilitadores) && $_POST['facilitador'] !== 'No asignado' ? $_POST['facilitador'] : null;
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET facilitador = ? WHERE id = ?");
            $stmt->execute([$facilitador, $_SESSION['user_id']]);
            $user['facilitador'] = $facilitador;
            $_SESSION['success'] = "Facilitador actualizado exitosamente.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al actualizar facilitador: " . $e->getMessage();
        }
    }

    // Handle presidente update
    if (isset($_POST['presidente'])) {
        $presidente = in_array($_POST['presidente'], $presidentes) && $_POST['presidente'] !== 'No asignado' ? $_POST['presidente'] : null;
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET presidente = ? WHERE id = ?");
            $stmt->execute([$presidente, $_SESSION['user_id']]);
            $user['presidente'] = $presidente;
            $_SESSION['success'] = "Presidente actualizado exitosamente.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al actualizar presidente: " . $e->getMessage();
        }
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header("Location: perfil.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Perfil - CAT21</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #ffffff;
    color: #000000;
    min-height: 100vh;
    line-height: 1.6;
}

.header {
    background: #000000;
    padding: 40px 60px;
    text-align: center;
    border-bottom: 1px solid #333333;
    position: relative;
    animation: fadeIn 0.8s ease-out;
}

.header h1 {
    font-size: 28px;
    font-weight: 300;
    color: #ffffff;
    letter-spacing: 4px;
    text-transform: uppercase;
    margin: 0;
}

.header .role {
    font-size: 14px;
    font-weight: 400;
    color: #FFD700;
    margin-top: 15px;
    text-transform: uppercase;
    letter-spacing: 2px;
    opacity: 0.9;
}

.profile-container {
    display: flex;
    max-width: 1200px;
    margin: 60px auto;
    background: #ffffff;
    border: 1px solid #333333;
    overflow: hidden;
    animation: fadeIn 0.8s ease-out;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.sidebar {
    width: 350px;
    background: #f8f8f8;
    padding: 60px 40px;
    border-right: 1px solid #333333;
    position: relative;
}

.sidebar::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 3px;
    height: 100%;
    background: linear-gradient(to bottom, #FFD700, #FF0000, #FFD700);
    opacity: 0.3;
}

.avatar {
    width: 120px;
    height: 120px;
    border-radius: 8px;
    overflow: hidden;
    margin: 0 auto 40px;
    border: 1px solid #333333;
    transition: all 0.3s ease;
    cursor: pointer;
}

.avatar:hover {
    transform: rotate(2deg);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    background: #FFD700;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 300;
    font-size: 24px;
    color: #000000;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.info-section {
    margin-bottom: 30px;
    position: relative;
}

.info-label {
    font-size: 12px;
    font-weight: 400;
    color: #000000;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.8;
}

.info-value {
    background: transparent;
    padding: 16px 0;
    border-bottom: 1px solid #e0e0e0;
    font-size: 16px;
    font-weight: 300;
    color: #000000;
    transition: all 0.3s ease;
}

.info-value:hover {
    border-bottom-color: #FFD700;
    padding-left: 10px;
}

.info-section:nth-child(odd) .info-value::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 1px;
    background: #FFD700;
    transition: width 0.3s ease;
}

.info-section:nth-child(odd) .info-value:hover::after {
    width: 100%;
}

.info-section:nth-child(even) .info-value::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 1px;
    background: #FF0000;
    transition: width 0.3s ease;
}

.info-section:nth-child(even) .info-value:hover::after {
    width: 100%;
}

.password-container {
    display: flex;
    align-items: center;
    gap: 10px;
}

.password-field {
    background: transparent;
    padding: 16px 0;
    border-bottom: 1px solid #e0e0e0;
    font-size: 16px;
    font-weight: 300;
    color: #000000;
    transition: all 0.3s ease;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.password-field.hidden {
    color: transparent;
    text-shadow: 0 0 8px #000000;
}

.password-field:hover {
    border-bottom-color: #FFD700;
    padding-left: 10px;
}

.btn-toggle {
    background: transparent;
    color: #000000;
    border: 1px solid #000000;
    padding: 8px 16px;
    border-radius: 0;
    font-size: 12px;
    font-weight: 400;
    cursor: pointer;
    transition: all 0.4s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
    position: relative;
    overflow: hidden;
    z-index: 1;
}

.btn-toggle::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 0%;
    background: #000000;
    z-index: -1;
    transition: width 0.4s ease;
}

.btn-toggle:hover::before {
    width: 100%;
}

.btn-toggle:hover {
    color: #FFD700;
    box-shadow: 0 0 0 2px #FFD700;
}

.form-container {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid #e0e0e0;
}

.form-container input[type="file"] {
    width: 100%;
    padding: 12px 0;
    margin-bottom: 20px;
    border: none;
    border-bottom: 1px solid #e0e0e0;
    background: transparent;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-size: 14px;
    color: #000000;
    transition: all 0.3s ease;
}

.form-container input[type="file"]:focus {
    outline: none;
    border-bottom-color: #FFD700;
}

.btn {
    background: transparent;
    color: #000000;
    border: 1px solid #000000;
    padding: 16px 32px;
    border-radius: 0;
    font-size: 14px;
    font-weight: 400;
    cursor: pointer;
    transition: all 0.4s ease;
    text-transform: uppercase;
    letter-spacing: 2px;
    position: relative;
    overflow: hidden;
    width: 100%;
    margin-bottom: 30px;
    z-index: 1;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 0%;
    background: #000000;
    z-index: -1;
    transition: width 0.4s ease;
}

.btn:hover::before {
    width: 100%;
}

.btn:hover {
    color: #FFD700;
    box-shadow: 0 0 0 2px #FFD700;
}

.main-content {
    flex: 1;
    padding: 60px;
    background: #ffffff;
}

.team-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 40px;
}

.team-member {
    background: #f8f8f8;
    padding: 30px;
    border-left: 3px solid #FFD700;
    position: relative;
    transition: all 0.3s ease;
    cursor: pointer;
    text-align: center;
}

.team-member:hover {
    background: #ffffff;
    border-left-color: #FF0000;
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.team-member:nth-child(2) {
    border-left-color: #FF0000;
}

.team-member:nth-child(2):hover {
    border-left-color: #FFD700;
}

.team-member:nth-child(3) {
    border-left-color: #FFD700;
}

.team-member:nth-child(3):hover {
    border-left-color: #FF0000;
}

.team-avatar {
    font-size: 24px;
    margin-bottom: 15px;
}

.team-role {
    color: #000000;
    font-size: 12px;
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 15px;
    opacity: 0.8;
}

.team-name {
    font-size: 20px;
    font-weight: 300;
    color: #000000;
    letter-spacing: 1px;
    margin-bottom: 15px;
    position: relative;
}

.team-name::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 50%;
    transform: translateX(-50%);
    width: 50px;
    height: 2px;
    background: #FFD700;
    transition: width 0.3s ease;
}

.team-member:hover .team-name::after {
    width: 80%;
}

.team-form {
    margin-top: 10px;
}

.team-form select {
    width: 100%;
    padding: 10px 16px;
    border: 1px solid #000000;
    border-radius: 0;
    background: transparent;
    color: #000000;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-size: 12px;
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
    transition: all 0.4s ease;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23FF0000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 12px;
}

.team-form select:hover {
    border-color: #FF0000;
    color: #FF0000;
    box-shadow: 0 0 0 2px #FF0000;
}

.team-form select:focus {
    outline: none;
    border-color: #FF0000;
}

.alert-success,
.alert-error {
    border-radius: 0;
    border: 1px solid #333333;
    font-weight: 300;
    letter-spacing: 1px;
    text-transform: uppercase;
    font-size: 12px;
    padding: 16px;
    margin-top: 20px;
}

.alert-success {
    background: #000000;
    color: #FFD700;
    border-color: #FFD700;
}

.alert-error {
    background: #000000;
    color: #FF0000;
    border-color: #FF0000;
}

.footer-nav {
    text-align: center;
    margin: 60px 0;
    padding: 40px;
    border-top: 1px solid #e0e0e0;
    background: #ffffff;
}

.footer-nav a {
    background: transparent;
    color: #000000;
    border: 1px solid #000000;
    padding: 16px 32px;
    text-decoration: none;
    border-radius: 0;
    font-size: 14px;
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: 2px;
    position: relative;
    overflow: hidden;
    transition: all 0.4s ease;
    z-index: 1;
}

.footer-nav a::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 0%;
    background: #000000;
    transition: height 0.4s ease;
    z-index: -1;
}

.footer-nav a:hover::before {
    height: 100%;
}

.footer-nav a:hover {
    color: #FFD700;
    box-shadow: 0 0 0 2px #FFD700;
}

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

@media (max-width: 768px) {
    .header {
        padding: 30px 20px;
    }
    
    .header h1 {
        font-size: 20px;
        letter-spacing: 2px;
    }
    
    .profile-container {
        flex-direction: column;
        margin: 40px 20px;
    }
    
    .sidebar {
        width: 100%;
        padding: 40px 30px;
    }
    
    .main-content {
        padding: 40px 30px;
    }
    
    .team-section {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .footer-nav {
        padding: 30px 20px;
    }
}
</style>
</head>
<body>
<div class="header">
    <h1><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido_paterno']); ?></h1>
</div>

<div class="profile-container">
    <div class="sidebar">
        <div class="avatar">
            <?php if ($user['foto_perfil'] && file_exists('Uploads/' . $user['foto_perfil'])): ?>
                <img src="Uploads/<?php echo htmlspecialchars($user['foto_perfil']); ?>" alt="Foto de Perfil" />
            <?php else: ?>
                <div class="avatar-placeholder"><?php echo htmlspecialchars(substr($user['nombre'], 0, 1) . substr($user['apellido_paterno'], 0, 1)); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
                <div class="info-label">Actualizar Foto</div>
                <input type="file" name="foto_perfil" accept="image/*" />
                <button type="submit" class="btn">Subir</button>
            </form>
        </div>

        <div class="info-section">
            <div class="info-label">Correo</div>
            <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
        </div>

        <div class="info-section">
            <div class="info-label">Edad</div>
            <div class="info-value"><?php echo $age; ?> años</div>
        </div>

        <div class="info-section">
            <div class="info-label">Contraseña</div>
            <div class="password-container">
                <div id="password-field" class="password-field hidden"><?php echo htmlspecialchars($password); ?></div>
                <button type="button" id="toggle-password" class="btn-toggle" onclick="togglePassword()">Ver</button>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="info-value alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="info-value alert-error"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </div>

    <div class="main-content">
        <div class="team-section">
            <div class="team-member">
                <div class="team-avatar">🏋️</div>
                <div class="team-role">Mi Coach</div>
                <div class="team-name"><?php echo htmlspecialchars($user['seleccion_couch'] ?: 'No asignado'); ?></div>
            </div>

            <div class="team-member">
                <div class="team-avatar">🧠</div>
                <div class="team-role">Mi Facilitador</div>
                <div class="team-name"><?php echo htmlspecialchars($user['facilitador'] ?: 'No asignado'); ?></div>
                <form method="POST" class="team-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <select name="facilitador" onchange="this.form.submit()" aria-label="Seleccionar Facilitador">
                        <?php foreach ($facilitadores as $fac): ?>
                            <option value="<?php echo htmlspecialchars($fac); ?>" <?php echo $user['facilitador'] === $fac || (!$user['facilitador'] && $fac === 'No asignado') ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($fac); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="team-member">
                <div class="team-avatar">👑</div>
                <div class="team-role">Mi Presidente</div>
                <div class="team-name"><?php echo htmlspecialchars($user['presidente'] ?: 'No asignado'); ?></div>
                <form method="POST" class="team-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <select name="presidente" onchange="this.form.submit()" aria-label="Seleccionar Presidente">
                        <?php foreach ($presidentes as $pres): ?>
                            <option value="<?php echo htmlspecialchars($pres); ?>" <?php echo $user['presidente'] === $pres || (!$user['presidente'] && $pres === 'No asignado') ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pres); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="footer-nav">
    <a href="inicio.php">Volver al Inicio</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function togglePassword() {
        const passwordField = document.getElementById('password-field');
        const toggleButton = document.getElementById('toggle-password');
        if (passwordField.classList.contains('hidden')) {
            passwordField.classList.remove('hidden');
            toggleButton.textContent = 'Ocultar';
        } else {
            passwordField.classList.add('hidden');
            toggleButton.textContent = 'Ver';
        }
    }

    document.getElementById('toggle-password').addEventListener('click', togglePassword);
});
</script>
</body>
</html>