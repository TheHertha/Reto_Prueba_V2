<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Por favor, inicia sesión para ver los eventos.";
    header("Location: login.php");
    exit;
}

// Check if user has admin role
$stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$is_admin = $user && $user['rol'] === 'admin';

// Get all active events (no cycle filtering)
$stmt = $pdo->query("SELECT * FROM eventos WHERE activo = 1 ORDER BY fecha ASC, hora ASC");
$eventos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos - CAT21</title>
    <link rel="stylesheet" href="assets/css/eventos.css">

    
</head>
<body>
    <header class="header">
        <a href="inicio.php" class="back-btn">← Regresar</a>
        <h1 class="page-title">Eventos - CAT21</h1>
        <?php if ($is_admin): ?>
            <a href="admin.php" class="admin-link">Administrar Eventos</a>
        <?php endif; ?>
    </header>
    <br>
    <main class="main-content">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-container"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-container"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <div class="eventos-wrapper">
            <?php if (empty($eventos)): ?>
                <div class="no-eventos">
                    No hay eventos programados en este momento.
                </div>
            <?php else: ?>
                <?php foreach ($eventos as $evento): ?>
                    <div class="event-container">
                        <div class="event-title"><?php echo strtoupper(htmlspecialchars($evento['nombre'])); ?></div>
                        
                        <div class="event-image">
                            <?php if ($evento['imagen'] && file_exists('Uploads/' . $evento['imagen'])): ?>
                                <img src="Uploads/<?php echo htmlspecialchars($evento['imagen']); ?>" 
                                     alt="<?php echo htmlspecialchars($evento['nombre']); ?>">
                            <?php else: ?>
                                <img src="../Styles/GND.jpeg" 
                                     alt="Imagen por defecto">
                            <?php endif; ?>
                        </div>
                        
                        <div class="event-info">
                            <div><?php echo strtoupper(htmlspecialchars($evento['nombre'])); ?></div>
                            <?php if ($evento['descripcion']): ?>
                                <div><?php echo htmlspecialchars($evento['descripcion']); ?></div>
                            <?php endif; ?>
                            <div>
                                Fecha: <span><?php echo formatearFecha($evento['fecha']); ?></span> | 
                                Hora: <span><?php echo formatearHora($evento['hora']); ?></span>
                            </div>
                        </div>

                        <?php if ($evento['enlace_zoom'] || $evento['enlace_youtube'] || $evento['enlace_facebook']): ?>
                            <div style="text-align: center; font-weight: 400; font-size: 14px; text-transform: uppercase; letter-spacing: 2px; margin-top: 10px;">
                                Enlaces para ingresar:
                            </div>
                            <br>
                            <div class="event-links">
                                <?php if ($evento['enlace_zoom']): ?>
                                    <a href="<?php echo htmlspecialchars($evento['enlace_zoom']); ?>" target="_blank">Zoom</a>
                                <?php endif; ?>
                                <?php if ($evento['enlace_youtube']): ?>
                                    <a href="<?php echo htmlspecialchars($evento['enlace_youtube']); ?>" target="_blank">YouTube</a>
                                <?php endif; ?>
                                <?php if ($evento['enlace_facebook']): ?>
                                    <a href="<?php echo htmlspecialchars($evento['enlace_facebook']); ?>" target="_blank">Facebook</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer-minimal">
        <p>© 2025 CAT21 - Todos los derechos reservados</p>
    </footer>
</body>
</html>