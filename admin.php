<?php
session_start();
require_once 'config.php';

// Debug session
error_log("admin.php: user_id=" . ($_SESSION['user_id'] ?? 'unset') . ", rol=" . ($_SESSION['rol'] ?? 'unset'));

// Generate or reuse CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
error_log("admin.php: CSRF token=$csrf_token");

// Check authentication
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Por favor, inicia sesión para acceder al panel de administración.";
    error_log("admin.php: Redirecting to login.php, no user_id");
    header("Location: login.php");
    exit;
}

// Check admin role
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    $_SESSION['error'] = "No tienes permisos para acceder a esta página.";
    error_log("admin.php: Redirecting to inicio.php, rol=" . ($_SESSION['rol'] ?? 'unset'));
    header("Location: inicio.php");
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $submitted_token = $_POST['csrf_token'] ?? 'unset';
    error_log("admin.php: Submitted CSRF token=$submitted_token, Expected=$csrf_token");
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Error de seguridad. Intenta de nuevo.";
        error_log("admin.php: CSRF token validation failed");
        header("Location: admin.php");
        exit;
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $enlace_zoom = filter_var(trim($_POST['enlace_zoom']), FILTER_VALIDATE_URL) ?: null;
    $enlace_youtube = filter_var(trim($_POST['enlace_youtube']), FILTER_VALIDATE_URL) ?: null;
    $enlace_facebook = filter_var(trim($_POST['enlace_facebook']), FILTER_VALIDATE_URL) ?: null;

    // Validate inputs
    if (empty($nombre)) {
        $_SESSION['error'] = "El nombre del evento es obligatorio.";
        error_log("admin.php: Validation failed, empty nombre");
        header("Location: admin.php" . ($id ? "?edit=$id" : ""));
        exit;
    }
    if (empty($fecha) || empty($hora)) {
        $_SESSION['error'] = "La fecha y hora son obligatorias.";
        error_log("admin.php: Validation failed, empty fecha or hora");
        header("Location: admin.php" . ($id ? "?edit=$id" : ""));
        exit;
    }

    $imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $imagen = subirImagen($_FILES['imagen']);
        if ($imagen === false) {
            $_SESSION['error'] = "Error al subir la imagen. Verifica el formato (JPG, PNG, GIF) y tamaño (<5MB).";
            error_log("admin.php: Image upload failed");
            header("Location: admin.php" . ($id ? "?edit=$id" : ""));
            exit;
        }
    }

    try {
        if ($id > 0) {
            // Update event
            $sql = "UPDATE eventos SET nombre = ?, descripcion = ?, fecha = ?, hora = ?, enlace_zoom = ?, enlace_youtube = ?, enlace_facebook = ?";
            $params = [$nombre, $descripcion, $fecha, $hora, $enlace_zoom, $enlace_youtube, $enlace_facebook];
            if ($imagen) {
                $sql .= ", imagen = ?";
                $params[] = $imagen;
            }
            $sql .= " WHERE id = ?";
            $params[] = $id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $_SESSION['success'] = "Evento actualizado exitosamente.";
            error_log("admin.php: Event updated, id=$id");
        } else {
            // Create new event
            $imagen = $imagen ?: 'default.jpg';
            $sql = "INSERT INTO eventos (nombre, descripcion, fecha, hora, imagen, enlace_zoom, enlace_youtube, enlace_facebook, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $descripcion, $fecha, $hora, $imagen, $enlace_zoom, $enlace_youtube, $enlace_facebook]);
            $_SESSION['success'] = "Evento creado exitosamente.";
            error_log("admin.php: Event created");
        }
        // Regenerate CSRF token after successful submission
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: admin.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error en la base de datos: " . $e->getMessage();
        error_log("admin.php: Database error: " . $e->getMessage());
        header("Location: admin.php" . ($id ? "?edit=$id" : ""));
        exit;
    }
}

// Delete event
if (isset($_GET['delete'])) {
    // Validate CSRF for deletion
    $submitted_token = $_GET['csrf_token'] ?? 'unset';
    error_log("admin.php: Delete CSRF token=$submitted_token, Expected=$csrf_token");
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Error de seguridad. Intenta de nuevo.";
        error_log("admin.php: CSRF token validation failed for delete");
        header("Location: admin.php");
        exit;
    }

    try {
        $id = intval($_GET['delete']);
        $stmt = $pdo->prepare("UPDATE eventos SET activo = 0 WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Evento eliminado exitosamente.";
        error_log("admin.php: Event deleted, id=$id");
        // Regenerate CSRF token after successful deletion
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: admin.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al eliminar el evento: " . $e->getMessage();
        error_log("admin.php: Delete error: " . $e->getMessage());
        header("Location: admin.php");
        exit;
    }
}

// Get event for editing
$eventoEditar = null;
if (isset($_GET['edit'])) {
    try {
        $id = intval($_GET['edit']);
        $stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
        $stmt->execute([$id]);
        $eventoEditar = $stmt->fetch();
        if (!$eventoEditar) {
            $_SESSION['error'] = "Evento no encontrado.";
            error_log("admin.php: Event not found, id=$id");
            header("Location: admin.php");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al cargar el evento: " . $e->getMessage();
        error_log("admin.php: Edit load error: " . $e->getMessage());
        header("Location: admin.php");
        exit;
    }
}

// Get all events
try {
    $stmt = $pdo->query("SELECT * FROM eventos ORDER BY fecha DESC, hora DESC");
    $eventos = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al cargar eventos: " . $e->getMessage();
    error_log("admin.php: Events load error: " . $e->getMessage());
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Eventos - CAT21</title>
    <link rel="stylesheet" href="assets/css/eventos_a.css">
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/img/1-4.png" alt="CAT21 Logo" class="logo">
                <h2>CAT21 Admin</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="inicio.php" class="nav-item">Inicio</a>
                <a href="eventos.php" class="nav-item">Eventos</a>
                <a href="admin.php" class="nav-item active">Administrar Eventos</a>
            </nav>
        </aside>
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>Administrar Eventos</h1>
            </header>
            <div class="content">
                <!-- Alerts -->
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
                <!-- Form -->
                <div class="form-card">
                    <h2><?php echo $eventoEditar ? 'Editar Evento' : 'Agregar Evento'; ?></h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <?php if ($eventoEditar): ?>
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($eventoEditar['id']); ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="nombre">Nombre del Evento</label>
                            <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($eventoEditar['nombre'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion"><?php echo htmlspecialchars($eventoEditar['descripcion'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fecha">Fecha</label>
                                <input type="date" id="fecha" name="fecha" required value="<?php echo htmlspecialchars($eventoEditar['fecha'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="hora">Hora</label>
                                <input type="time" id="hora" name="hora" required value="<?php echo htmlspecialchars($eventoEditar['hora'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="imagen">Imagen del Evento</label>
                            <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/gif">
                            <?php if ($eventoEditar && $eventoEditar['imagen']): ?>
                                <small>Imagen actual: <?php echo htmlspecialchars($eventoEditar['imagen']); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="enlace_zoom">Enlace Zoom</label>
                            <input type="url" id="enlace_zoom" name="enlace_zoom" value="<?php echo htmlspecialchars($eventoEditar['enlace_zoom'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="enlace_youtube">Enlace YouTube</label>
                            <input type="url" id="enlace_youtube" name="enlace_youtube" value="<?php echo htmlspecialchars($eventoEditar['enlace_youtube'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="enlace_facebook">Enlace Facebook</label>
                            <input type="url" id="enlace_facebook" name="enlace_facebook" value="<?php echo htmlspecialchars($eventoEditar['enlace_facebook'] ?? ''); ?>">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-submit"><?php echo $eventoEditar ? 'Actualizar' : 'Crear'; ?></button>
                            <?php if ($eventoEditar): ?>
                                <a href="admin.php" class="btn btn-cancel">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <!-- Event List -->
                <div class="eventos-section">
                    <h2>Eventos Existentes</h2>
                    <div class="eventos-grid">
                        <?php foreach ($eventos as $evento): ?>
                            <div class="evento-card">
                                <?php if ($evento['imagen'] && file_exists('Uploads/' . $evento['imagen'])): ?>
                                    <img src="/Uploads/<?php echo htmlspecialchars($evento['imagen']); ?>" alt="<?php echo htmlspecialchars($evento['nombre']); ?>" class="evento-imagen">
                                <?php else: ?>
                                    <div class="evento-placeholder">Sin Imagen</div>
                                <?php endif; ?>
                                <div class="evento-info">
                                    <h3 class="evento-nombre"><?php echo htmlspecialchars($evento['nombre']); ?></h3>
                                    <p class="evento-descripcion"><?php echo htmlspecialchars($evento['descripcion']); ?></p>
                                    <p class="evento-fecha">Fecha: <?php echo htmlspecialchars(formatearFecha($evento['fecha'])); ?></p>
                                    <p class="evento-hora">Hora: <?php echo htmlspecialchars(formatearHora($evento['hora'])); ?></p>
                                    <?php if ($evento['enlace_zoom']): ?>
                                        <p class="evento-link"><a href="<?php echo htmlspecialchars($evento['enlace_zoom']); ?>" target="_blank">Zoom</a></p>
                                    <?php endif; ?>
                                    <?php if ($evento['enlace_youtube']): ?>
                                        <p class="evento-link"><a href="<?php echo htmlspecialchars($evento['enlace_youtube']); ?>" target="_blank">YouTube</a></p>
                                    <?php endif; ?>
                                    <?php if ($evento['enlace_facebook']): ?>
                                        <p class="evento-link"><a href="<?php echo htmlspecialchars($evento['enlace_facebook']); ?>" target="_blank">Facebook</a></p>
                                    <?php endif; ?>
                                    <p class="evento-status">Activo: <?php echo $evento['activo'] ? 'Sí' : 'No'; ?></p>
                                </div>
                                <div class="evento-actions">
                                    <a href="admin.php?edit=<?php echo $evento['id']; ?>" class="btn btn-edit">Editar</a>
                                    <a href="admin.php?delete=<?php echo $evento['id']; ?>&csrf_token=<?php echo htmlspecialchars($csrf_token); ?>" 
                                       class="btn btn-delete" 
                                       onclick="return confirm('¿Estás seguro de eliminar este evento?')">
                                       Eliminar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Close alert buttons
        document.querySelectorAll('.alert-close').forEach(button => {
            button.addEventListener('click', () => {
                button.parentElement.style.display = 'none';
            });
        });
    </script>
</body>
</html>