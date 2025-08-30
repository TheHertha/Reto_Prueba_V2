<?php
session_start();
require_once 'config.php';

// Set timezone to CST (adjust if your server uses a different timezone)
$pdo->exec("SET time_zone = '-06:00'"); // CST is UTC-6

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Solo administradores pueden acceder a esta página.";
    error_log("admin_reto.php: Access denied, user_id=" . ($_SESSION['user_id'] ?? 'none') . ", rol=" . ($_SESSION['rol'] ?? 'none'));
    header("Location: login.php");
    exit;
}

// Generate or reuse CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
error_log("admin_reto.php: CSRF token=$csrf_token");

try {
    // Check for retos starting exactly today, but only disable if no active reto exists
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT id FROM retos WHERE start_date <= CURDATE() AND end_date >= CURDATE()");
    $stmt->execute();
    $active_reto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$active_reto) {
        // No active reto, check for retos starting exactly today
        $stmt = $pdo->prepare("SELECT id FROM retos WHERE DATE(start_date) = CURDATE() AND id NOT IN (SELECT reto_id FROM disable_log)");
        $stmt->execute();
        $started_retos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($started_retos)) {
            // Disable all non-admin users
            $stmt = $pdo->prepare("UPDATE usuarios SET habilitado = 0 WHERE rol != 'admin'");
            $stmt->execute();
            $disabled_count = $stmt->rowCount();
            
            // Log each started reto
            foreach ($started_retos as $reto_id) {
                $stmt = $pdo->prepare("INSERT INTO disable_log (reto_id, disabled_at) VALUES (:reto_id, NOW())");
                $stmt->execute(['reto_id' => $reto_id]);
            }
            $_SESSION['success'] = "Usuarios no administradores ($disabled_count) deshabilitados para retos iniciados: " . implode(', ', $started_retos) . ".";
            error_log("admin_reto.php: Disabled $disabled_count non-admin users for started retos: " . implode(',', $started_retos));
        }
    }
    $pdo->commit();

    // Handle AJAX requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['error' => true, 'message' => 'Error de seguridad. Intenta de nuevo.']);
            error_log("admin_reto.php: CSRF token validation failed");
            exit;
        }

        // Handle reto creation
        if (isset($_POST['create_reto'])) {
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';

            // Validate dates
            if (!$start_date || !$end_date || strtotime($end_date) <= strtotime($start_date)) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['error' => true, 'message' => 'Fechas inválidas: la fecha de fin debe ser posterior a la de inicio.']);
                error_log("admin_reto.php: Validation failed, invalid dates: start=$start_date, end=$end_date");
                exit;
            }

            // Check for overlapping retos
            $stmt = $pdo->prepare("SELECT id FROM retos WHERE (start_date <= :end_date AND end_date >= :start_date)");
            $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
            if ($stmt->fetch()) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['error' => true, 'message' => 'Ya existe un reto con fechas superpuestas.']);
                error_log("admin_reto.php: Overlapping reto detected");
                exit;
            }

            $pdo->beginTransaction();

            // Insert new reto
            $stmt = $pdo->prepare("INSERT INTO retos (start_date, end_date) VALUES (:start_date, :end_date)");
            $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
            $new_reto_id = $pdo->lastInsertId();

            // Check if new reto starts today and no active reto exists
            if (strtotime($start_date) <= time()) {
                $stmt = $pdo->prepare("SELECT id FROM retos WHERE start_date <= CURDATE() AND end_date >= CURDATE() AND id != :new_reto_id");
                $stmt->execute(['new_reto_id' => $new_reto_id]);
                $active_reto = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$active_reto && date('Y-m-d', strtotime($start_date)) === date('Y-m-d')) {
                    // No active reto and starts today, disable users
                    $stmt = $pdo->prepare("UPDATE usuarios SET habilitado = 0 WHERE rol != 'admin'");
                    $stmt->execute();
                    $disabled_count = $stmt->rowCount();
                    
                    // Log the new reto
                    $stmt = $pdo->prepare("INSERT INTO disable_log (reto_id, disabled_at) VALUES (:reto_id, NOW())");
                    $stmt->execute(['reto_id' => $new_reto_id]);
                    
                    error_log("admin_reto.php: Disabled $disabled_count non-admin users for new reto id=$new_reto_id starting on $start_date");
                }
            }

            $pdo->commit();
            $_SESSION['success'] = "Reto $new_reto_id creado correctamente." . (isset($disabled_count) ? " Usuarios no administradores ($disabled_count) deshabilitados." : "");
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $_SESSION['success'], 'new_reto_id' => $new_reto_id]);
            error_log("admin_reto.php: Reto created, id=$new_reto_id, start_date=$start_date, end=$end_date");
            exit;
        }

        // Handle manual ranking update
        if (isset($_POST['update_ranking'])) {
            $reto_id = filter_input(INPUT_POST, 'reto_id', FILTER_VALIDATE_INT);
            $tipo = $_POST['tipo'] ?? '';
            $posicion = filter_input(INPUT_POST, 'posicion', FILTER_VALIDATE_INT);
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
            $usuario_id = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT) ?: null;
            $foto = null;

            if (!$reto_id || !in_array($tipo, ['fotos', 'elite']) || !$posicion || !$nombre || $posicion < 1 || $posicion > 3) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['error' => true, 'message' => 'Datos inválidos: verifica reto, tipo, posición o nombre.']);
                error_log("admin_reto.php: Invalid ranking data, reto_id=$reto_id, tipo=$tipo, posicion=$posicion, nombre=$nombre");
                exit;
            }

            if (in_array($tipo, ['fotos', 'elite']) && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = mime_content_type($_FILES['foto']['tmp_name']);
                if (!in_array($file_type, $allowed_types)) {
                    ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['error' => true, 'message' => 'Tipo de archivo no permitido.']);
                    error_log("admin_reto.php: Invalid file type for foto, type=$file_type");
                    exit;
                }
                $foto_name = uniqid('rank_') . '_' . basename($_FILES['foto']['name']);
                $foto_path = 'Uploads/' . $foto_name;
                if (!move_uploaded_file($_FILES['foto']['tmp_name'], $foto_path)) {
                    ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['error' => true, 'message' => 'Error al subir la imagen.']);
                    error_log("admin_reto.php: Failed to move uploaded file to $foto_path");
                    exit;
                }
                $foto = $foto_name;
                error_log("admin_reto.php: Photo uploaded, path=$foto_path");
            } elseif (in_array($tipo, ['fotos', 'elite'])) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['error' => true, 'message' => 'Se requiere una foto para el ranking de ' . $tipo . '.']);
                error_log("admin_reto.php: No photo uploaded for tipo=$tipo");
                exit;
            }

            // Check for existing position
            $stmt = $pdo->prepare("SELECT id FROM rankings WHERE reto_id = :reto_id AND tipo = :tipo AND posicion = :posicion");
            $stmt->execute(['reto_id' => $reto_id, 'tipo' => $tipo, 'posicion' => $posicion]);
            if ($stmt->fetch()) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['error' => true, 'message' => "La posición $posicion ya está asignada para este reto y tipo ($tipo)."]);
                error_log("admin_reto.php: Position $posicion already taken for reto_id=$reto_id, tipo=$tipo");
                exit;
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                INSERT INTO rankings (reto_id, tipo, posicion, usuario_id, nombre, foto)
                VALUES (:reto_id, :tipo, :posicion, :usuario_id, :nombre, :foto)
            ");
            $stmt->execute([
                'reto_id' => $reto_id,
                'tipo' => $tipo,
                'posicion' => $posicion,
                'usuario_id' => $usuario_id,
                'nombre' => $nombre,
                'foto' => $foto
            ]);
            $pdo->commit();

            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => "Ranking de $tipo actualizado correctamente."]);
            error_log("admin_reto.php: Ranking updated, reto_id=$reto_id, tipo=$tipo, posicion=$posicion, nombre=$nombre, usuario_id=" . ($usuario_id ?? 'null') . ", foto=$foto");
            exit;
        }

        // Handle toggle user
        if (isset($_POST['toggle_habilitado'])) {
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            if ($user_id === (int)$_SESSION['user_id']) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['error' => true, 'message' => 'No puedes cambiar tu propio estado.']);
                error_log("admin_reto.php: Admin attempted to toggle own habilitado, user_id=$user_id");
                exit;
            }
            $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['error' => true, 'message' => "Usuario no encontrado: ID $user_id"]);
                error_log("admin_reto.php: User not found, user_id=$user_id");
                exit;
            }
            if ($user['rol'] === 'admin') {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['error' => true, 'message' => 'No puedes cambiar el estado de otro administrador.']);
                error_log("admin_reto.php: Attempt to toggle admin user, user_id=$user_id");
                exit;
            }
            $stmt = $pdo->prepare("UPDATE usuarios SET habilitado = !habilitado WHERE id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            $stmt = $pdo->prepare("SELECT habilitado FROM usuarios WHERE id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            $habilitado = $stmt->fetchColumn();
            if ($habilitado === false) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['error' => true, 'message' => "Usuario no encontrado: ID $user_id"]);
                error_log("admin_reto.php: User not found after toggle, user_id=$user_id");
                exit;
            }
            $_SESSION['success'] = "Usuario " . ($habilitado ? 'habilitado' : 'deshabilitado') . " correctamente.";
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $_SESSION['success'], 'habilitado' => $habilitado]);
            error_log("admin_reto.php: User toggled, id=$user_id, habilitado=$habilitado");
            exit;
        }

        // Invalid POST request
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['error' => true, 'message' => 'Solicitud inválida.']);
        error_log("admin_reto.php: Invalid POST request");
        exit;
    }

    // Fetch all retos and users
    $stmt = $pdo->query("SELECT id, start_date, end_date FROM retos ORDER BY start_date DESC");
    $retos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT id, nombre, rol, habilitado FROM usuarios ORDER BY nombre");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = "Error: " . $e->getMessage();
    error_log("admin_reto.php: Error: " . $e->getMessage());
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['error' => true, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Reto 2025 - CAT21</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; color: #333; min-height: 100vh; line-height: 1.6; }
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #000000; color: #ffffff; padding: 20px; display: flex; flex-direction: column; gap: 20px; }
        .sidebar-header { display: flex; align-items: center; gap: 15px; padding-bottom: 20px; border-bottom: 1px solid #333; }
        .logo { width: 50px; height: 50px; border-radius: 8px; object-fit: cover; transition: transform 0.3s ease; }
        .logo:hover { transform: rotate(5deg); }
        .sidebar h2 { font-size: 1.5rem; font-weight: 300; letter-spacing: 2px; text-transform: uppercase; }
        .sidebar-nav { display: flex; flex-direction: column; gap: 10px; }
        .nav-item { display: block; padding: 12px 20px; color: #ffffff; text-decoration: none; font-size: 1rem; font-weight: 400; border-radius: 4px; transition: all 0.3s ease; }
        .nav-item:hover { background: #FFD700; color: #000000; }
        .nav-item.active { background: #FFD700; color: #000000; }
        .nav-item.logout { margin-top: auto; }
        .main-content { flex: 1; padding: 40px; background: #ffffff; }
        .main-header h1 { font-size: 2rem; font-weight: 300; text-transform: uppercase; letter-spacing: 3px; color: #000000; margin-bottom: 30px; }
        .alert { position: relative; padding: 15px 40px 15px 20px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; justify-content: space-between; }
        .alert-success { background: #e6f4ea; color: #2e7d32; border: 1px solid #a5d6a7; }
        .alert-error { background: #fce4ec; color: #c62828; border: 1px solid #ef9a9a; }
        .alert-close { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: inherit; }
        .form-card { background: #ffffff; border-radius: 8px; padding: 30px; margin-bottom: 40px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: box-shadow 0.3s ease; }
        .form-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.15); }
        .form-card h2 { font-size: 1.5rem; font-weight: 400; margin-bottom: 20px; color: #000000; text-transform: uppercase; letter-spacing: 1px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 12px; font-size: 1rem; border: 1px solid #ddd; border-radius: 6px; transition: border-color 0.3s ease, box-shadow 0.3s ease; }
        .form-group input:focus, .form-group select:focus { border-color: #FFD700; box-shadow: 0 0 6px rgba(255, 215, 0, 0.3); outline: none; }
        .form-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .btn { position: relative; overflow: hidden; padding: 12px 24px; border-radius: 6px; font-size: 1rem; font-weight: 500; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: all 0.3s ease; border: none; }
        .btn-submit { background: #000000; color: #FFD700; border: 1px solid #FFD700; }
        .btn-submit::before { content: ""; position: absolute; top: 0; left: 0; width: 0%; height: 100%; background: #FFD700; z-index: -1; transition: width 0.3s ease; }
        .btn-submit:hover::before { width: 100%; }
        .btn-submit:hover { color: #000000; }
        .btn-submit:disabled { background: #666; color: #999; border: 1px solid #666; cursor: not-allowed; }
        .btn-submit:disabled::before { width: 0; }
        .btn-toggle { background: #000000; color: #FFD700; border: 1px solid #FFD700; }
        .btn-toggle::before { content: ""; position: absolute; top: 0; left: 0; width: 0%; height: 100%; background: #FFD700; z-index: -1; transition: width 0.3s ease; }
        .btn-toggle:hover::before { width: 100%; }
        .btn-toggle:hover { color: #000000; }
        .btn-toggle:disabled { background: #666; color: #999; border: 1px solid #666; cursor: not-allowed; }
        .retos-section, .users-section, .rankings-section { margin-bottom: 40px; }
        .retos-section h2, .users-section h2, .rankings-section h2 { font-size: 1.5rem; font-weight: 400; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; color: #000000; }
        .data-table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        .data-table th { background: #000000; color: #FFD700; font-weight: 500; text-transform: uppercase; letter-spacing: 1px; }
        .data-table tr:hover { background: #f8f8f8; }
        .search-filter { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .search-filter input, .search-filter select { padding: 10px; font-size: 1rem; border: 1px solid #ddd; border-radius: 6px; transition: border-color 0.3s ease; flex: 1; min-width: 150px; }
        .search-filter input:focus, .search-filter select:focus { border-color: #FFD700; box-shadow: 0 0 6px rgba(255, 215, 0, 0.3); outline: none; }
        @media (max-width: 1024px) {
            .layout { flex-direction: column; }
            .sidebar { width: 100%; padding: 20px; }
            .main-content { padding: 20px; }
            .main-header h1 { font-size: 1.5rem; }
            .search-filter { flex-direction: column; gap: 10px; }
        }
        @media (max-width: 768px) {
            .main-content { padding: 15px; }
            .form-card { padding: 20px; }
            .search-filter { flex-direction: column; gap: 10px; }
            .data-table { font-size: 0.9rem; }
            .data-table th, .data-table td { padding: 10px; }
        }
        @media (max-width: 480px) {
            .sidebar-header { flex-direction: column; align-items: flex-start; }
            .form-card h2, .retos-section h2, .users-section h2, .rankings-section h2 { font-size: 1.2rem; }
            .btn { padding: 10px 20px; font-size: 0.9rem; }
            .data-table { font-size: 0.8rem; }
            .search-filter input, .search-filter select { min-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/img/1-4.png" alt="CAT21 Logo" class="logo">
                <h2>CAT21 Admin</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="inicio.php" class="nav-item">Inicio</a>
                <a href="reto.php" class="nav-item">Reto</a>
                <a href="ranking.php" class="nav-item">Ranking</a>
                <a href="admin_reto.php" class="nav-item active">Administrar Reto</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="main-header">
                <h1>Administrar Reto 2025</h1>
            </header>
            <div class="content">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_SESSION['success']); ?>
                        <button class="alert-close">×</button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($_SESSION['error']); ?>
                        <button class="alert-close">×</button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Crear Reto -->
                <div class="form-card retos-section">
                    <h2>Crear Nuevo Reto</h2>
                    <form id="create-reto-form" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="create_reto" value="1">
                        <div class="form-group">
                            <label for="start_date">Fecha de Inicio</label>
                            <input type="date" id="start_date" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">Fecha de Fin</label>
                            <input type="date" id="end_date" name="end_date" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-submit">Crear Reto</button>
                        </div>
                    </form>
                </div>

                <!-- Lista de Retos -->
                <div class="retos-section">
                    <h2>Retos Existentes</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($retos as $reto): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reto['id']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($reto['start_date'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($reto['end_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($retos)): ?>
                                <tr>
                                    <td colspan="3">No hay retos disponibles.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Administrar Usuarios -->
                <div class="users-section">
                    <h2>Administrar Usuarios</h2>
                    <div class="search-filter">
                        <input type="text" id="search-users" placeholder="Buscar por nombre...">
                        <select id="filter-habilitado">
                            <option value="all">Todos los estados</option>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                        <select id="filter-rol">
                            <option value="all">Todos los roles</option>
                            <option value="admin">Administrador</option>
                            <option value="user">Usuario</option>
                        </select>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="users-table">
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['rol']); ?></td>
                                    <td><?php echo $usuario['habilitado'] ? 'Habilitado' : 'Deshabilitado'; ?></td>
                                    <td>
                                        <button class="btn btn-toggle toggle-habilitado" data-user-id="<?php echo $usuario['id']; ?>" <?php echo $usuario['id'] === $_SESSION['user_id'] || $usuario['rol'] === 'admin' ? 'disabled' : ''; ?>>
                                            <?php echo $usuario['habilitado'] ? 'Deshabilitar' : 'Habilitar'; ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="4">No hay usuarios disponibles.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Administrar Rankings -->
                <div class="rankings-section">
                    <h2>Administrar Rankings</h2>
                    <div class="form-card">
                        <h2>Actualizar Ranking de Fotos</h2>
                        <form id="update-ranking-fotos-form" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="update_ranking" value="1">
                            <input type="hidden" name="tipo" value="fotos">
                            <div class="form-group">
                                <label for="reto_id_fotos">Reto</label>
                                <select id="reto_id_fotos" name="reto_id" required>
                                    <?php foreach ($retos as $reto): ?>
                                        <option value="<?php echo $reto['id']; ?>">Ret_CT_<?php echo $reto['id']; ?> (<?php echo date('d/m/Y', strtotime($reto['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($reto['end_date'])); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="posicion_fotos">Posición</label>
                                <select id="posicion_fotos" name="posicion" required>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="nombre_fotos">Nombre</label>
                                <input type="text" id="nombre_fotos" name="nombre" required>
                            </div>
                            <div class="form-group">
                                <label for="usuario_id_fotos">Usuario (Opcional)</label>
                                <select id="usuario_id_fotos" name="usuario_id">
                                    <option value="">Ninguno</option>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <option value="<?php echo $usuario['id']; ?>"><?php echo htmlspecialchars($usuario['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="foto_fotos">Foto</label>
                                <input type="file" id="foto_fotos" name="foto" accept="image/jpeg,image/png,image/gif" required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-submit">Actualizar</button>
                            </div>
                        </form>
                    </div>
                    <div class="form-card">
                        <h2>Actualizar Ranking Elite</h2>
                        <form id="update-ranking-elite-form" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="update_ranking" value="1">
                            <input type="hidden" name="tipo" value="elite">
                            <div class="form-group">
                                <label for="reto_id_elite">Reto</label>
                                <select id="reto_id_elite" name="reto_id" required>
                                    <?php foreach ($retos as $reto): ?>
                                        <option value="<?php echo $reto['id']; ?>">Ret_CT_<?php echo $reto['id']; ?> (<?php echo date('d/m/Y', strtotime($reto['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($reto['end_date'])); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="posicion_elite">Posición</label>
                                <select id="posicion_elite" name="posicion" required>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="nombre_elite">Nombre</label>
                                <input type="text" id="nombre_elite" name="nombre" required>
                            </div>
                            <div class="form-group">
                                <label for="usuario_id_elite">Usuario (Opcional)</label>
                                <select id="usuario_id_elite" name="usuario_id">
                                    <option value="">Ninguno</option>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <option value="<?php echo $usuario['id']; ?>"><?php echo htmlspecialchars($usuario['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="foto_elite">Foto</label>
                                <input type="file" id="foto_elite" name="foto" accept="image/jpeg,image/png,image/gif" required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-submit">Actualizar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Close alerts
        document.querySelectorAll('.alert-close').forEach(button => {
            button.addEventListener('click', () => {
                button.parentElement.remove();
            });
        });

        // Search and filter users
        function filterUsers() {
            const search = document.getElementById('search-users').value.toLowerCase();
            const habilitadoFilter = document.getElementById('filter-habilitado').value;
            const rolFilter = document.getElementById('filter-rol').value;
            const rows = document.querySelectorAll('#users-table tr');

            rows.forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();
                const rol = row.cells[1].textContent.toLowerCase();
                const habilitado = row.cells[2].textContent.toLowerCase() === 'habilitado' ? '1' : '0';

                const matchesSearch = name.includes(search);
                const matchesHabilitado = habilitadoFilter === 'all' || habilitado === habilitadoFilter;
                const matchesRol = rolFilter === 'all' || rol === rolFilter;

                row.style.display = matchesSearch && matchesHabilitado && matchesRol ? '' : 'none';
            });
        }

        // Attach event listeners to filters
        document.getElementById('search-users').addEventListener('input', filterUsers);
        document.getElementById('filter-habilitado').addEventListener('change', filterUsers);
        document.getElementById('filter-rol').addEventListener('change', filterUsers);

        // Create reto form
        document.getElementById('create-reto-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('.btn-submit');
            try {
                submitButton.disabled = true;
                submitButton.textContent = 'Creando...';
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success';
                    alert.innerHTML = `${result.message} <button class="alert-close">×</button>`;
                    document.querySelector('.content').prepend(alert);
                    alert.querySelector('.alert-close').addEventListener('click', () => alert.remove());
                    setTimeout(() => location.reload(), 1000);
                } else {
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-error';
                    alert.innerHTML = `${result.message} <button class="alert-close">×</button>`;
                    document.querySelector('.content').prepend(alert);
                    alert.querySelector('.alert-close').addEventListener('click', () => alert.remove());
                }
            } catch (error) {
                const alert = document.createElement('div');
                alert.className = 'alert alert-error';
                alert.innerHTML = `Error al crear el reto: ${error.message}. <button class="alert-close">×</button>`;
                document.querySelector('.content').prepend(alert);
                alert.querySelector('.alert-close').addEventListener('click', () => alert.remove());
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Crear Reto';
            }
        });

        // Toggle user
        document.querySelectorAll('.toggle-habilitado').forEach(button => {
            button.addEventListener('click', async function() {
                if (this.disabled) return;
                const userId = this.dataset.userId;
                const formData = new FormData();
                formData.append('csrf_token', '<?php echo htmlspecialchars($csrf_token); ?>');
                formData.append('toggle_habilitado', '1');
                formData.append('user_id', userId);
                try {
                    this.disabled = true;
                    this.textContent = 'Procesando...';
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        this.textContent = result.habilitado ? 'Deshabilitar' : 'Habilitar';
                        this.parentElement.previousElementSibling.textContent = result.habilitado ? 'Habilitado' : 'Deshabilitado';
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success';
                        alert.innerHTML = `${result.message} <button class="alert-close">×</button>`;
                        document.querySelector('.content').prepend(alert);
                        alert.querySelector('.alert-close').addEventListener('click', () => alert.remove());
                    } else {
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-error';
                        alert.innerHTML = `${result.message} <button class="alert-close">×</button>`;
                        document.querySelector('.content').prepend(alert);
                        alert.querySelector('.alert-close').addEventListener('click', () => alert.remove());
                    }
                } catch (error) {
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-error';
                    alert.innerHTML = `Error al cambiar estado del usuario: ${error.message}. <button class="alert-close">×</button>`;
                    document.querySelector('.content').prepend(alert);
                    alert.querySelector('.alert-close').addEventListener('click', () => alert.remove());
                } finally {
                    this.disabled = false;
                }
            });
        });

        // Update ranking forms
        ['fotos', 'elite'].forEach(tipo => {
            document.getElementById(`update-ranking-${tipo}-form`).addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const submitButton = this.querySelector('.btn-submit');
                try {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Actualizando...';
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success';
                        alert.innerHTML = `${result.message} <button class="alert-close">×</button>`;
                        document.querySelector('.content').prepend(alert);
                        alert.querySelector('.alert-close').addEventListener('click', () => alert.remove());
                        this.reset();
                    } else {
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-error';
                        alert.innerHTML = `${result.message} <button class="alert-close">×</button>`;
                        document.querySelector('.content').prepend(alert);
                        alert.querySelector('.alert-close').addEventListener('click', () => alert.remove());
                    }
                } catch (error) {
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-error';
                    alert.innerHTML = `Error al actualizar el ranking: ${error.message}. <button class="alert-close">×</button>`;
                    document.querySelector('.content').prepend(alert);
                    alert.querySelector('.alert-close').addEventListener('click', () => alert.remove());
                } finally {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Actualizar';
                }
            });
        });
    </script>
</body>
</html>