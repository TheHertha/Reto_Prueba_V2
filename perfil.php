<?php
session_start();
require_once 'config.php';

// === FUNCIÓN PARA MOSTRAR ROL BONITO ===
function mostrarRol($rol) {
    return $rol === 'coach' ? 'Coach' : 'Retador';
}
function claseRol($rol) {
    return $rol === 'coach' ? 'role-coach' : 'role-retador';
}

// === SEGURIDAD ===
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Por favor, inicia sesión.";
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// === DATOS DEL USUARIO ===
try {
    $stmt = $pdo->prepare("
        SELECT nombre, apellido_paterno, email, role, fecha_nacimiento, 
               foto_perfil, contrasena, seleccion_couch, facilitador, presidente 
        FROM usuarios WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "Usuario no encontrado.";
        header("Location: login.php");
        exit;
    }

    $birthdate = new DateTime($user['fecha_nacimiento']);
    $today = new DateTime();
    $age = $today->diff($birthdate)->y;
    $password = $user['contrasena'];
    $rol = $user['role'] ?? 'user'; // compatible con 'user' y 'coach'

} catch (Exception $e) {
    error_log("Error perfil: " . $e->getMessage());
    $_SESSION['error'] = "Error del sistema.";
    header("Location: inicio.php");
    exit;
}

// === LISTAS ===
$facilitadores = ['Alex', 'Adriana', 'Esmeralda', 'Fide', 'Fernando', 'Francisco', 'Juan', 'Oscar', 'No asignado', 'No aplica'];
$presidentes = ['Juan Pérez', 'Sofía Martínez', 'Luis Fernández', 'No asignado'];

// === PROCESAR FORMULARIOS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Error de seguridad.";
        header("Location: perfil.php");
        exit;
    }

    // SUBIR FOTO
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === 0) {
        $imagen = subirImagen($_FILES['foto_perfil'], 'user_' . $_SESSION['user_id'] . '_');
        if ($imagen !== false) {
            $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
            $stmt->execute([$imagen, $_SESSION['user_id']]);
            $user['foto_perfil'] = $imagen;
            $_SESSION['success'] = "Foto actualizada con éxito.";
        } else {
            $_SESSION['error'] = "Error: solo JPG, PNG, GIF. Máx 5MB.";
        }
    }

    // FACILITADOR
    if (isset($_POST['facilitador'])) {
        $f = in_array($_POST['facilitador'], $facilitadores) ? $_POST['facilitador'] : null;
        $stmt = $pdo->prepare("UPDATE usuarios SET facilitador = ? WHERE id = ?");
        $stmt->execute([$f, $_SESSION['user_id']]);
        $user['facilitador'] = $f;
        $_SESSION['success'] = "Facilitador actualizado.";
    }

    // PRESIDENTE
    if (isset($_POST['presidente'])) {
        $p = in_array($_POST['presidente'], $presidentes) ? $_POST['presidente'] : null;
        $stmt = $pdo->prepare("UPDATE usuarios SET presidente = ? WHERE id = ?");
        $stmt->execute([$p, $_SESSION['user_id']]);
        $user['presidente'] = $p;
        $_SESSION['success'] = "Presidente actualizado.";
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
<title>Mi Perfil - CAT21</title>
<style>
    /* TU CSS ORIGINAL + mejoras */
    .role-retador { background: #FFD700; color: #000; padding: 8px 20px; border-radius: 0; font-weight: 600; letter-spacing: 2px; }
    .role-coach   { background: #FF0000; color: #fff; padding: 8px 20px; border-radius: 0; font-weight: 600; letter-spacing: 2px; }
    .team-member.coach-only { display: <?php echo $rol === 'retador' ? 'block' : 'none'; ?>; }
    .alert-success, .alert-error { margin: 20px 0; padding: 16px; font-size: 13px; text-align: center; border: 1px solid; }
    .alert-success { background: #000; color: #FFD700; border-color: #FFD700; }
    .alert-error   { background: #000; color: #FF0000; border-color: #FF0000; }
</style>
<link rel="stylesheet" href="styles.css"> <!-- si tienes CSS externo -->
</head>
<body>

<div class="header">
    <h1><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido_paterno']); ?></h1>
    <div class="role <?php echo claseRol($rol); ?>">
        <?php echo mostrarRol($rol); ?>
    </div>
</div>

<div class="profile-container">
    <div class="sidebar">
        <div class="avatar">
            <?php if ($user['foto_perfil'] && file_exists('Uploads/' . $user['foto_perfil'])): ?>
                <img src="Uploads/<?php echo htmlspecialchars($user['foto_perfil']); ?>" alt="Foto" />
            <?php else: ?>
                <div class="avatar-placeholder">
                    <?php echo strtoupper(substr($user['nombre'], 0, 1) . substr($user['apellido_paterno'], 0, 1)); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="info-label">Cambiar Foto de Perfil</div>
                <input type="file" name="foto_perfil" accept="image/jpeg,image/png,image/gif" required>
                <button type="submit" class="btn">Subir Foto</button>
            </form>
        </div>

        <div class="info-section"><div class="info-label">Correo</div><div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div></div>
        <div class="info-section"><div class="info-label">Edad</div><div class="info-value"><?php echo $age; ?> años</div></div>

        <div class="info-section">
            <div class="info-label">Contraseña</div>
            <div class="password-container">
                <div id="password-field" class="password-field hidden">••••••••</div>
                <button type="button" id="toggle-password" class="btn-toggle">Ver</button>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
    </div>

    <div class="main-content">
        <div class="team-section">

            <!-- SOLO RETADORES VEN SU COACH -->
            <?php if ($rol === 'user'): ?>
            <div class="team-member">
                <div class="team-avatar">Coach</div>
                <div class="team-role">Mi Coach</div>
                <div class="team-name"><?php echo htmlspecialchars($user['seleccion_couch'] ?: 'No asignado'); ?></div>
            </div>
            <?php endif; ?>

            <div class="team-member">
                <div class="team-avatar">Mentor</div>
                <div class="team-role">Mi Facilitador</div>
                <div class="team-name"><?php echo htmlspecialchars($user['facilitador'] ?: 'No asignado'); ?></div>
                <form method="POST" class="team-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <select name="facilitador" onchange="this.form.submit()">
                        <?php foreach ($facilitadores as $f): ?>
                            <option value="<?php echo htmlspecialchars($f); ?>" 
                                <?php echo ($user['facilitador'] ?? '') === $f ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($f); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="team-member">
                <div class="team-avatar">Líder</div>
                <div class="team-role">Mi Presidente</div>
                <div class="team-name"><?php echo htmlspecialchars($user['presidente'] ?: 'No asignado'); ?></div>
                <form method="POST" class="team-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <select name="presidente" onchange="this.form.submit()">
                        <?php foreach ($presidentes as $p): ?>
                            <option value="<?php echo htmlspecialchars($p); ?>" 
                                <?php echo ($user['presidente'] ?? '') === $p ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p); ?>
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
function togglePassword() {
    const field = document.getElementById('password-field');
    const btn = document.getElementById('toggle-password');
    if (field.textContent === '••••••••') {
        field.textContent = '<?php echo addslashes($password); ?>';
        btn.textContent = 'Ocultar';
    } else {
        field.textContent = '••••••••';
        btn.textContent = 'Ver';
    }
}
document.getElementById('toggle-password').onclick = togglePassword;
</script>
</body>
</html>