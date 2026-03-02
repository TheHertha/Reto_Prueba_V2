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


$facilitadores = ['Alex', 'Adriana', 'Esmeralda', 'Fide', 'Fernando','Francisco', 'Juan', 'Oscar', 'No asignado','No aplica'];
$presidentes = ['Juan Pérez', 'Sofía Martínez', 'Luis Fernández', 'No asignado'];

try {
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

// Siempre inicializar como array vacío (evita undefined y fatal error)
$coaches_disponibles = [];

// Solo cargar si el rol lo permite
if (in_array($user['rol'], ['user', 'coach', 'admin', 'facilitador_admin'])) {
    try {
        $stmt_coaches = $pdo->query("
            SELECT DISTINCT TRIM(name) AS name_clean 
            FROM coaches 
            WHERE name IS NOT NULL 
              AND TRIM(name) != ''
            ORDER BY name_clean ASC
        ");
        $coaches_disponibles = $stmt_coaches->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("perfil.php - Error al cargar coaches: " . $e->getMessage());
        // No romper la página, dejamos el array vacío
    }
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Error de seguridad (CSRF inválido).";
        header("Location: perfil.php");
        exit;
    }

       // Manejo del cambio de coach
    if (isset($_POST['nuevo_coach'])) {
        $nuevo_coach_raw = $_POST['nuevo_coach'] ?? '';
        $nuevo_coach = trim($nuevo_coach_raw);

        // Debug inicial
        error_log("DEBUG CAMBIO COACH - POST recibido | raw: '$nuevo_coach_raw' | trimmed: '$nuevo_coach' | user_id: " . $_SESSION['user_id']);

        $coach_id = null;
        $nombre_coach = null;
        $success_message = null;
        $error_message = null;

        if ($nuevo_coach !== '' && $nuevo_coach !== 'Ninguno') {
            // Buscar el coach por nombre exacto (TRIM para tolerar espacios del select)
            $stmt_check = $pdo->prepare("
                SELECT user_id, name 
                FROM coaches 
                WHERE TRIM(name) = TRIM(:name_check) 
                LIMIT 1
            ");
            $stmt_check->execute(['name_check' => $nuevo_coach]);
            $coach_row = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($coach_row) {
                $coach_id     = (int) $coach_row['user_id'];
                $nombre_coach = $coach_row['name'];  // nombre oficial desde la tabla coaches

                error_log("DEBUG CAMBIO COACH - Coach encontrado | user_id: $coach_id | name: '$nombre_coach'");
            } else {
                $error_message = "El coach '$nuevo_coach' no se encontró en la base de datos.";
                error_log("ERROR CAMBIO COACH - No encontrado: '$nuevo_coach'");
            }
        } else {
            // Caso "Ninguno" o vacío → quitar asignación
            $success_message = "Coach removido correctamente.";
            error_log("DEBUG CAMBIO COACH - Removiendo coach (Ninguno o vacío)");
        }

        // Proceder solo si hay decisión clara (asignar o quitar)
        if ($coach_id !== null || $nombre_coach === null) {
            try {
                $stmt_update = $pdo->prepare("
                    UPDATE usuarios 
                    SET 
                        coach_id       = :coach_id,
                        seleccion_couch = :seleccion_couch
                    WHERE id = :user_id
                ");
                $stmt_update->execute([
                    'coach_id'       => $coach_id,          // NULL si se quita
                    'seleccion_couch' => $nombre_coach,     // NULL si se quita
                    'user_id'        => $_SESSION['user_id']
                ]);

                $filas = $stmt_update->rowCount();

                // Recargar datos del usuario para reflejar cambio inmediato en la página
                if ($filas > 0) {
                    $stmt_reload = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
                    $stmt_reload->execute([$_SESSION['user_id']]);
                    $user = $stmt_reload->fetch(PDO::FETCH_ASSOC);
                }

                // Mensaje de éxito
                $success_message = $coach_id 
                    ? "Coach cambiado correctamente a: " . htmlspecialchars($nombre_coach)
                    : "Coach removido correctamente.";

                $_SESSION['success'] = $success_message;
                error_log("DEBUG CAMBIO COACH - Guardado OK | coach_id=$coach_id | seleccion_couch='$nombre_coach' | filas=$filas");

            } catch (PDOException $e) {
                $error_message = "Error al guardar la asignación del coach.";
                $_SESSION['error'] = $error_message;
                error_log("ERROR CAMBIO COACH - Excepción PDO: " . $e->getMessage());
            }
        } else if ($error_message) {
            $_SESSION['error'] = $error_message;
        }

        // Refrescar CSRF y redirigir
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: perfil.php");
        exit;
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
       <!-- Tarjeta Mi Coach -->
<div class="team-member">
    <div class="team-avatar">🏋️</div>
    <div class="team-role">Mi Coach</div>
    <div class="team-name">
        <?php echo htmlspecialchars($user['seleccion_couch'] ?: 'No asignado'); ?>
    </div>

    <?php if (in_array($user['rol'], ['user', 'coach', 'admin', 'facilitador_admin'])): ?>
        <?php if (!empty($coaches_disponibles)): ?>
            <form method="POST" class="team-form" style="margin-top: 20px;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <select name="nuevo_coach" onchange="this.form.submit()" aria-label="Cambiar mi coach">
                    <option value="">-- Cambiar coach --</option>
                    <?php foreach ($coaches_disponibles as $coach_name): ?>
                        <option value="<?php echo htmlspecialchars($coach_name); ?>"
                            <?php echo ($user['seleccion_couch'] ?? '') === $coach_name ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($coach_name); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="Ninguno" <?php echo empty($user['seleccion_couch']) ? 'selected' : ''; ?>>
                        Ninguno
                    </option>
                </select>
            </form>
        <?php else: ?>
            <div style="margin-top: 15px; color: #888; font-style: italic; font-size: 0.9rem; text-align: center;">
                No hay coaches disponibles en este momento
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

        <!-- Facilitador -->
        <div class="team-member">
            <div class="team-avatar">🧠</div>
            <div class="team-role">Mi Facilitador</div>
            <div class="team-name"><?php echo htmlspecialchars($user['facilitador'] ?: 'No asignado'); ?></div>
            <form method="POST" class="team-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <select name="facilitador" onchange="this.form.submit()" aria-label="Seleccionar Facilitador">
                    <?php foreach ($facilitadores as $fac): ?>
                        <option value="<?php echo htmlspecialchars($fac); ?>" 
                            <?php echo ($user['facilitador'] ?? '') === $fac || 
                                        (empty($user['facilitador']) && $fac === 'No asignado') ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($fac); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Presidente -->
        <div class="team-member">
            <div class="team-avatar">👑</div>
            <div class="team-role">Mi Presidente</div>
            <div class="team-name"><?php echo htmlspecialchars($user['presidente'] ?: 'No asignado'); ?></div>
            <form method="POST" class="team-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <select name="presidente" onchange="this.form.submit()" aria-label="Seleccionar Presidente">
                    <?php foreach ($presidentes as $pres): ?>
                        <option value="<?php echo htmlspecialchars($pres); ?>" 
                            <?php echo ($user['presidente'] ?? '') === $pres || 
                                        (empty($user['presidente']) && $pres === 'No asignado') ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pres); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>
</div>
</div>

<?php if ($user['rol'] === 'coach'): ?>

    <?php
    $participantes = [];
    $ultimos_pesajes = [];

    try {
        // ────────────────────────────────────────────────
        // Nueva consulta: usamos coach_id en lugar de nombre
        // ────────────────────────────────────────────────
        $stmt_part = $pdo->prepare("
            SELECT 
                id, nombre, apellido_paterno, apellido_materno, email
            FROM usuarios 
            WHERE coach_id = :coach_id
              AND rol IN ('user', 'coach', 'facilitador_admin', 'admin')
              AND id != :self_id
            ORDER BY apellido_paterno ASC, nombre ASC
        ");
        $stmt_part->execute([
            'coach_id' => $_SESSION['user_id'],   // ← ¡El ID del coach actual!
            'self_id'  => $_SESSION['user_id']
        ]);
        $participantes = $stmt_part->fetchAll(PDO::FETCH_ASSOC);

    

        // Cargar últimos pesajes (sin cambios aquí)
        if (!empty($participantes)) {
            $participant_ids = array_column($participantes, 'id');
            $placeholders = implode(',', array_fill(0, count($participant_ids), '?'));

            $stmt_pesaje = $pdo->prepare("
                SELECT 
                    ds.usuario_id,
                    ds.semana,
                    ds.peso,
                    ds.grasa,
                    ds.musculo,
                    ds.created_at,
                    ds.image
                FROM datos_semanales ds
                INNER JOIN (
                    SELECT usuario_id, MAX(created_at) AS max_created
                    FROM datos_semanales
                    WHERE usuario_id IN ($placeholders)
                    GROUP BY usuario_id
                ) latest ON ds.usuario_id = latest.usuario_id 
                         AND ds.created_at = latest.max_created
                ORDER BY ds.usuario_id
            ");
            $stmt_pesaje->execute($participant_ids);
            $pesajes_raw = $stmt_pesaje->fetchAll(PDO::FETCH_ASSOC);

            foreach ($pesajes_raw as $p) {
                $ultimos_pesajes[$p['usuario_id']] = $p;
            }
        }
    } catch (PDOException $e) {
    }
    ?>

    <div class="team-section" style="margin-top: 60px;">
        <h2 style="text-align: center; margin-bottom: 30px; font-size: 1.6rem; letter-spacing: 2px; color: #000;">
            Progreso de mis participantes
        </h2>

        <?php if (empty($participantes)): ?>
            <div class="info-section" style="text-align: center; padding: 40px; background: #f8f8f8; border: 1px dashed #ccc;">
                <p style="font-size: 1.1rem; color: #555;">
                    Aún no tienes participantes asignados.<br>
                    Cuando alguien te seleccione como coach, aparecerán aquí.
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($participantes as $part): 
                $nombre_part = trim($part['nombre'] . ' ' . $part['apellido_paterno'] . ' ' . ($part['apellido_materno'] ?? ''));
                $pesaje = $ultimos_pesajes[$part['id']] ?? null;
            ?>
                <div class="team-member" style="position: relative;">
                    <div class="team-avatar">📈</div>
                    <div class="team-role">Participante</div>
                    <div class="team-name"><?php echo htmlspecialchars($nombre_part); ?></div>

                    <?php if ($pesaje): ?>
                        <div style="margin-top: 15px; font-size: 0.95rem; color: #333; text-align: left; padding: 0 15px;">
                            <div><strong>Semana:</strong> <?php echo $pesaje['semana']; ?></div>
                            <div><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($pesaje['created_at'])); ?></div>
                            <div><strong>Peso:</strong> <?php echo number_format($pesaje['peso'], 1); ?> kg</div>
                            <div><strong>Grasa:</strong> <?php echo number_format($pesaje['grasa'], 1); ?> %</div>
                            <div><strong>Músculo:</strong> <?php echo number_format($pesaje['musculo'], 1); ?> kg</div>
                            <?php if (!empty($pesaje['image'])): ?>
                                <div style="margin-top: 10px;">
                                    <a href="Uploads/<?php echo htmlspecialchars($pesaje['image']); ?>" target="_blank" style="color: #000; text-decoration: underline;">
                                        Ver foto del pesaje
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 15px; color: #888; font-style: italic; text-align: center;">
                            Aún no ha registrado pesajes
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

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