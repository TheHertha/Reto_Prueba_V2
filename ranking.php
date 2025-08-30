<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Por favor, inicia sesión para ver el ranking.";
    error_log("ranking.php: Redirecting to login.php, no user_id");
    header("Location: login.php");
    exit;
}

// Fetch user role
try {
    $stmt = $pdo->prepare("SELECT rol, habilitado FROM usuarios WHERE id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $_SESSION['error'] = "Usuario no encontrado.";
        error_log("ranking.php: Redirecting to login.php, user not found, user_id=" . $_SESSION['user_id']);
        header("Location: login.php");
        exit;
    }
    $is_admin = $user['rol'] === 'admin';
    if (!$is_admin && !$user['habilitado']) {
        $_SESSION['error'] = "Tu cuenta no está habilitada. Contacta al administrador.";
        error_log("ranking.php: Redirecting to inicio.php, not admin and not habilitado, user_id=" . $_SESSION['user_id']);
        header("Location: inicio.php?no_redirect=1");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al verificar el usuario: " . $e->getMessage();
    error_log("ranking.php: Database error: " . $e->getMessage());
    header("Location: login.php");
    exit;
}

// Get selected reto_id from GET or default to active reto
$reto_id = filter_input(INPUT_GET, 'reto_id', FILTER_VALIDATE_INT);
if (!$reto_id) {
    $stmt = $pdo->query("SELECT id, start_date, end_date FROM retos WHERE CURDATE() BETWEEN start_date AND end_date LIMIT 1");
    $reto = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reto) {
        $_SESSION['error'] = "No hay un reto activo para mostrar el ranking.";
        error_log("ranking.php: No active reto found");
        header("Location: inicio.php?no_redirect=1");
        exit;
    }
    $reto_id = $reto['id'];
} else {
    $stmt = $pdo->prepare("SELECT id, start_date, end_date FROM retos WHERE id = :reto_id");
    $stmt->execute(['reto_id' => $reto_id]);
    $reto = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reto) {
        $_SESSION['error'] = "Reto no encontrado.";
        error_log("ranking.php: Reto not found, reto_id=$reto_id");
        header("Location: inicio.php?no_redirect=1");
        exit;
    }
}
$reto_ended = (new DateTime() > new DateTime($reto['end_date']));
error_log("ranking.php: Reto selected, id=$reto_id, start_date={$reto['start_date']}, end_date={$reto['end_date']}, ended=" . ($reto_ended ? 'yes' : 'no'));

// Fetch all retos for selector
try {
    $stmt = $pdo->query("SELECT id, start_date, end_date FROM retos ORDER BY start_date DESC");
    $retos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("ranking.php: Retos fetched, count=" . count($retos));
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al cargar los retos: " . $e->getMessage();
    error_log("ranking.php: Error fetching retos: " . $e->getMessage());
    $retos = [];
}

// Fetch main ranking (top 3)
try {
    $stmt = $pdo->prepare("
        SELECT 
            CONCAT(u.nombre, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS nombre_completo, 
            (
                (
                    ((ds0.peso * ds0.grasa / 100) - (ds3.peso * ds3.grasa / 100)) / 
                    NULLIF((CASE 
                        WHEN (YEAR(CURDATE()) - YEAR(u.fecha_nacimiento)) BETWEEN 20 AND 39 THEN 23 
                        WHEN (YEAR(CURDATE()) - YEAR(u.fecha_nacimiento)) BETWEEN 40 AND 59 THEN 25 
                        ELSE 27 
                    END * (ds0.estatura - 100)) / 100, 0)
                ) +
                (
                    ((ds3.peso * ds3.musculo / 100) - (ds0.peso * ds0.musculo / 100)) / 
                    NULLIF((CASE 
                        WHEN (YEAR(CURDATE()) - YEAR(u.fecha_nacimiento)) BETWEEN 20 AND 39 THEN 41.7 
                        WHEN (YEAR(CURDATE()) - YEAR(u.fecha_nacimiento)) BETWEEN 40 AND 59 THEN 41.5 
                        ELSE 41.3 
                    END * (ds0.estatura - 100)) / 100, 0)
                ) +
                (
                    CASE 
                        WHEN (ds0.masa - 7) != 0 
                        THEN ((ds0.masa - ds3.masa) / (ds0.masa - 7)) 
                        ELSE 0 
                    END
                )
            ) / 3 AS promedio_avance_semana3
        FROM usuarios u
        JOIN datos_semanales ds0 ON u.id = ds0.usuario_id AND ds0.semana = 0 AND ds0.reto_id = :reto_id
        JOIN datos_semanales ds3 ON u.id = ds3.usuario_id AND ds3.semana = 3 AND ds3.reto_id = :reto_id
        WHERE u.habilitado = 1
        ORDER BY promedio_avance_semana3 DESC
        LIMIT 3
    ");
    $stmt->execute(['reto_id' => $reto_id]);
    $main_ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("ranking.php: Main ranking fetched, rows=" . count($main_ranking));
    foreach ($main_ranking as $index => $rank) {
        error_log("ranking.php: Main ranking position=" . ($index + 1) . ", nombre_completo={$rank['nombre_completo']}, promedio_avance_semana3=" . ($rank['promedio_avance_semana3'] ?? 'null'));
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al cargar el ranking general: " . $e->getMessage();
    error_log("ranking.php: Error fetching main ranking: " . $e->getMessage());
    $main_ranking = [];
}

// Fetch photo ranking (top 3)
try {
    $stmt = $pdo->prepare("
        SELECT 
            r.posicion,
            COALESCE(CONCAT(u.nombre, ' ', u.apellido_paterno, ' ', u.apellido_materno), r.nombre) AS nombre_completo,
            r.foto,
            r.usuario_id
        FROM rankings r
        LEFT JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.reto_id = :reto_id AND r.tipo = 'fotos'
        ORDER BY r.posicion ASC
        LIMIT 3
    ");
    $stmt->execute(['reto_id' => $reto_id]);
    $photo_ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("ranking.php: Photo ranking fetched, rows=" . count($photo_ranking));
    foreach ($photo_ranking as $rank) {
        error_log("ranking.php: Photo ranking posicion={$rank['posicion']}, nombre_completo={$rank['nombre_completo']}, foto=" . ($rank['foto'] ?? 'null') . ", usuario_id=" . ($rank['usuario_id'] ?? 'null'));
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al cargar el ranking de fotos: " . $e->getMessage();
    error_log("ranking.php: Error fetching photo ranking: " . $e->getMessage());
    $photo_ranking = [];
}

// Fetch elite ranking (top 3)
try {
    $stmt = $pdo->prepare("
        SELECT 
            r.posicion,
            COALESCE(CONCAT(u.nombre, ' ', u.apellido_paterno, ' ', u.apellido_materno), r.nombre) AS nombre_completo,
            r.foto,
            r.usuario_id
        FROM rankings r
        LEFT JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.reto_id = :reto_id AND r.tipo = 'elite'
        ORDER BY r.posicion ASC
        LIMIT 3
    ");
    $stmt->execute(['reto_id' => $reto_id]);
    $elite_ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("ranking.php: Elite ranking fetched, rows=" . count($elite_ranking));
    foreach ($elite_ranking as $rank) {
        error_log("ranking.php: Elite ranking posicion={$rank['posicion']}, nombre_completo={$rank['nombre_completo']}, foto=" . ($rank['foto'] ?? 'null') . ", usuario_id=" . ($rank['usuario_id'] ?? 'null'));
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al cargar el ranking elite: " . $e->getMessage();
    error_log("ranking.php: Error fetching elite ranking: " . $e->getMessage());
    $elite_ranking = [];
}

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking - CAT21 Reto <?php echo $reto_id; ?></title>
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

/* Encabezado con fondo oscuro y disposición flexible */
.header {
    background: #000000;
    padding: 30px 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    border-bottom: 1px solid #333333;
}

/* Estilo de los botones de regresar y administrar */
.back-btn,
.admin-btn {
    background: transparent;
    color: #ffffff;
    border: 1px solid #ffffff;
    padding: 10px 20px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 400;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
    text-decoration: none;
}

/* Efecto hover para los botones */
.back-btn:hover,
.admin-btn:hover {
    background: #ffffff;
    color: #000000;
}

/* Título del encabezado */
.title {
    font-size: 28px;
    font-weight: 300;
    color: #ffffff;
    letter-spacing: 4px;
    text-transform: uppercase;
    flex-grow: 1;
    text-align: center;
}

/* Contenido principal con animación de entrada */
.main-content {
    padding: 60px;
    animation: fadeIn 0.8s ease-out;
}

/* Contenedores de error y éxito */
.error-container,
.success-container {
    text-align: center;
    padding: 30px;
    margin-bottom: 40px;
    border-radius: 4px;
    font-weight: 400;
    letter-spacing: 1px;
}

/* Estilo del contenedor de error */
.error-container {
    border: 1px solid #FF0000;
    background: #fff5f5;
    color: #FF0000;
}

/* Estilo del contenedor de éxito */
.success-container {
    border: 1px solid #008000;
    background: #f0fff0;
    color: #008000;
}

/* Selector de retos */
.reto-selector {
    margin-bottom: 30px;
    text-align: center;
}

/* Etiqueta del selector de retos */
.reto-selector label {
    font-size: 1.2rem;
    font-weight: 500;
    margin-right: 10px;
    color: #000000;
}

/* Estilo del elemento select */
.reto-selector select {
    padding: 10px;
    font-size: 1rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #f8f8f8;
    cursor: pointer;
    transition: border-color 0.3s ease;
}

/* Efecto de foco para el select */
.reto-selector select:focus {
    border-color: #FFD700;
    outline: none;
}

/* Contenedor de los rankings */
.ranking-container {
    margin-bottom: 60px;
}

/* Título de los rankings */
.ranking-container h2 {
    font-size: 24px;
    font-weight: 300;
    color: #000000;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 30px;
    text-align: center;
    position: relative;
}

/* Línea decorativa debajo del título */
.ranking-container h2::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: linear-gradient(90deg, transparent, #FFD700, transparent);
    border-radius: 2px;
}

/* Contenedor del podio con perspectiva 3D */
.podium-container {
    perspective: 1000px;
    margin: 40px 0;
}

/* Tabla del podio */
.podium-table {
    width: 100%;
    max-width: 800px;
    margin: 0 auto 40px;
    border-collapse: separate;
    border-spacing: 0 10px;
    transform-style: preserve-3d;
}

/* Encabezados de la tabla */
.podium-table th {
    background: linear-gradient(135deg, #000000, #333333);
    color: #FFD700;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 2px;
    padding: 20px 15px;
    text-align: center;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    position: relative;
}

/* Efecto shimmer para los encabezados */
.podium-table th::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(255,215,0,0.1) 50%, transparent 70%);
    animation: shimmer 3s infinite;
}

/* Celdas de la tabla */
.podium-table td {
    padding: 20px 15px;
    text-align: center;
    border: none;
    position: relative;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 300px;
}

/* Estilos para la primera posición (oro) */
.podium-table tr.position-1 {
    background: linear-gradient(135deg, #FFD700 0%, #FFED4E 50%, #FFD700 100%);
    color: #000000;
    transform: translateY(-25px) scale(1.05);
    box-shadow:
        0 20px 40px rgba(255,215,0,0.4),
        0 0 30px rgba(255,215,0,0.3),
        inset 0 1px 0 rgba(255,255,255,0.3);
    z-index: 10;
    border-radius: 10px;
    animation: goldGlow 2s ease-in-out infinite alternate;
}

/* Estilos para la segunda posición (plata) */
.podium-table tr.position-2 {
    background: linear-gradient(135deg, #C0C0C0 0%, #E5E5E5 50%, #C0C0C0 100%);
    color: #000000;
    transform: translateY(-15px) scale(1.02);
    box-shadow:
        0 15px 30px rgba(192,192,192,0.4),
        0 0 20px rgba(192,192,192,0.2),
        inset 0 1px 0 rgba(255,255,255,0.4);
    z-index: 9;
    border-radius: 8px;
    animation: silverGlow 2.5s ease-in-out infinite alternate;
}

/* Estilos para la tercera posición (bronce) */
.podium-table tr.position-3 {
    background: linear-gradient(135deg, #CD7F32 0%, #E6A85C 50%, #CD7F32 100%);
    color: #000000;
    transform: translateY(-5px);
    box-shadow:
        0 12px 25px rgba(205,127,50,0.4),
        0 0 15px rgba(205,127,50,0.2),
        inset 0 1px 0 rgba(255,255,255,0.2);
    z-index: 8;
    border-radius: 6px;
    animation: bronzeGlow 3s ease-in-out infinite alternate;
}

/* Animación de brillo para la primera posición (oro) */
@keyframes goldGlow {
    0% { box-shadow: 0 20px 40px rgba(255,215,0,0.4), 0 0 30px rgba(255,215,0,0.3), inset 0 1px 0 rgba(255,255,255,0.3); }
    100% { box-shadow: 0 25px 50px rgba(255,215,0,0.6), 0 0 40px rgba(255,215,0,0.5), inset 0 1px 0 rgba(255,255,255,0.5); }
}

/* Animación de brillo para la segunda posición (plata) */
@keyframes silverGlow {
    0% { box-shadow: 0 15px 30px rgba(192,192,192,0.4), 0 0 20px rgba(192,192,192,0.2), inset 0 1px 0 rgba(255,255,255,0.4); }
    100% { box-shadow: 0 18px 35px rgba(192,192,192,0.5), 0 0 25px rgba(192,192,192,0.3), inset 0 1px 0 rgba(255,255,255,0.6); }
}

/* Animación de brillo para la tercera posición (bronce) */
@keyframes bronzeGlow {
    0% { box-shadow: 0 12px 25px rgba(205,127,50,0.4), 0 0 15px rgba(205,127,50,0.2), inset 0 1px 0 rgba(255,255,255,0.2); }
    100% { box-shadow: 0 15px 30px rgba(205,127,50,0.5), 0 0 20px rgba(205,127,50,0.3), inset 0 1px 0 rgba(255,255,255,0.3); }
}

/* Animación shimmer para los encabezados */
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Estilo del número de posición para la primera posición */
.podium-table tr.position-1 td:first-child {
    font-size: 1.8rem;
    font-weight: 800;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    position: relative;
}

/* Icono de corona para la primera posición */
.podium-table tr.position-1 td:first-child::before {
    content: '👑';
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 1.2rem;
    animation: bounce 2s infinite;
}

/* Estilo del número de posición para la segunda posición */
.podium-table tr.position-2 td:first-child {
    font-size: 1.5rem;
    font-weight: 700;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
}

/* Estilo del número de posición para la tercera posición */
.podium-table tr.position-3 td:first-child {
    font-size: 1.3rem;
    font-weight: 600;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
}

/* Animación de rebote para la corona */
@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
    40% { transform: translateX(-50%) translateY(-10px); }
    60% { transform: translateX(-50%) translateY(-5px); }
}

/* Estilo del nombre para la primera posición */
.podium-table tr.position-1 td:nth-child(2) {
    font-size: 1.4rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Estilo del nombre para la segunda posición */
.podium-table tr.position-2 td:nth-child(2) {
    font-size: 1.2rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Estilo del nombre para la tercera posición */
.podium-table tr.position-3 td:nth-child(2) {
    font-size: 1.1rem;
    font-weight: 500;
    text-transform: uppercase;
}

/* Efecto hover para las filas */
.podium-table tr:hover {
    transform: translateY(-30px) scale(1.08) !important;
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    cursor: pointer;
}

/* Efecto hover para la primera posición */
.podium-table tr.position-1:hover {
    animation: none;
    box-shadow:
        0 30px 60px rgba(255,215,0,0.7),
        0 0 50px rgba(255,215,0,0.6),
        inset 0 2px 0 rgba(255,255,255,0.6);
}

/* Estilo de las fotos */
.photo-preview {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.8);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
}

/* Borde y sombra para la foto de la primera posición */
.podium-table tr.position-1 .photo-preview {
    border-color: #FFD700;
    box-shadow: 0 0 20px rgba(255,215,0,0.5);
}

/* Borde y sombra para la foto de la segunda posición */
.podium-table tr.position-2 .photo-preview {
    border-color: #C0C0C0;
    box-shadow: 0 0 15px rgba(192,192,192,0.4);
}

/* Borde y sombra para la foto de la tercera posición */
.podium-table tr.position-3 .photo-preview {
    border-color: #CD7F32;
    box-shadow: 0 0 12px rgba(205,127,50,0.4);
}

/* Efecto hover para las fotos */
.photo-preview:hover {
    transform: scale(1.1);
    box-shadow: 0 15px 40px rgba(0,0,0,0.4);
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
    color: #999;
    text-transform: uppercase;
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

/* Media query para pantallas de hasta 768px */
@media (max-width: 768px) {
    /* Ajusta el encabezado */
    .header {
        padding: 20px 30px;
        flex-direction: column;
        gap: 15px;
        height: auto;
    }

    /* Ajusta los botones del encabezado */
    .back-btn,
    .admin-btn {
        width: 100%;
        text-align: center;
        padding: 8px 15px;
        font-size: 12px;
    }

    /* Ajusta el título del encabezado */
    .title {
        font-size: 20px;
        letter-spacing: 2px;
    }

    /* Ajusta el contenido principal */
    .main-content {
        padding: 40px 20px;
    }

    /* Ajusta el selector de retos */
    .reto-selector {
        margin-bottom: 20px;
    }

    /* Ajusta la etiqueta del selector */
    .reto-selector label {
        font-size: 1rem;
    }

    /* Ajusta el elemento select */
    .reto-selector select {
        font-size: 0.9rem;
        padding: 8px;
    }

    /* Ajusta el contenedor de ranking */
    .ranking-container {
        margin-bottom: 40px;
    }

    /* Ajusta el título del ranking */
    .ranking-container h2 {
        font-size: 20px;
    }

    /* Ajusta la tabla del podio */
    .podium-table {
        max-width: 100%;
        overflow-x: auto;
    }

    /* Ajusta los encabezados y celdas de la tabla */
    .podium-table th,
    .podium-table td {
        padding: 15px 8px;
        font-size: 0.9rem;
        max-width: 200px;
    }

    /* Ajusta las fotos */
    .photo-preview {
        width: 80px;
        height: 80px;
    }

    /* Ajusta las transformaciones de las posiciones */
    .podium-table tr.position-1 {
        transform: translateY(-15px) scale(1.02);
    }

    .podium-table tr.position-2 {
        transform: translateY(-10px) scale(1.01);
    }

    .podium-table tr.position-3 {
        transform: translateY(-5px);
    }

    /* Ajusta el número de posición */
    .podium-table tr.position-1 td:first-child {
        font-size: 1.4rem;
    }

    .podium-table tr.position-2 td:first-child {
        font-size: 1.2rem;
    }

    .podium-table tr.position-3 td:first-child {
        font-size: 1.1rem;
    }

    /* Ajusta el nombre */
    .podium-table tr.position-1 td:nth-child(2) {
        font-size: 1.2rem;
    }

    .podium-table tr.position-2 td:nth-child(2) {
        font-size: 1.1rem;
    }

    .podium-table tr.position-3 td:nth-child(2) {
        font-size: 1rem;
    }

    /* Ajusta el pie de página */
    .footer-minimal {
        padding: 20px;
    }
}

/* Media query para pantallas de hasta 480px */
@media (max-width: 480px) {
    /* Ajusta el encabezado */
    .header {
        padding: 15px 20px;
        gap: 10px;
    }

    /* Ajusta los botones del encabezado */
    .back-btn,
    .admin-btn {
        padding: 6px 12px;
        font-size: 11px;
    }

    /* Ajusta el título del encabezado */
    .title {
        font-size: 18px;
        letter-spacing: 1.5px;
    }

    /* Ajusta el contenido principal */
    .main-content {
        padding: 30px 15px;
    }

    /* Ajusta el selector de retos */
    .reto-selector select {
        width: 100%;
        font-size: 0.85rem;
        padding: 6px;
    }

    /* Ajusta el contenedor de error y éxito */
    .error-container,
    .success-container {
        padding: 20px;
        font-size: 12px;
    }

    /* Ajusta el título del ranking */
    .ranking-container h2 {
        font-size: 18px;
    }

    /* Ajusta los encabezados y celdas de la tabla */
    .podium-table th,
    .podium-table td {
        font-size: 0.8rem;
        padding: 12px 5px;
        max-width: 150px;
    }

    /* Ajusta las fotos */
    .photo-preview {
        width: 60px;
        height: 60px;
    }

    /* Ajusta el número de posición */
    .podium-table tr.position-1 td:first-child {
        font-size: 1.2rem;
    }

    .podium-table tr.position-2 td:first-child {
        font-size: 1.1rem;
    }

    .podium-table tr.position-3 td:first-child {
        font-size: 1rem;
    }

    /* Ajusta el nombre */
    .podium-table tr.position-1 td:nth-child(2) {
        font-size: 1.1rem;
    }

    .podium-table tr.position-2 td:nth-child(2) {
        font-size: 1rem;
    }

    .podium-table tr.position-3 td:nth-child(2) {
        font-size: 0.9rem;
    }

    /* Ajusta el pie de página */
    .footer-minimal {
        padding: 15px;
    }

    /* Ajusta el texto del pie de página */
    .footer-minimal p {
        font-size: 11px;
    }
}

/* Media query para pantallas muy pequeñas (hasta 320px) */
@media (max-width: 320px) {
    /* Ajusta el encabezado */
    .header {
        padding: 10px 15px;
        gap: 8px;
    }

    /* Ajusta los botones del encabezado */
    .back-btn,
    .admin-btn {
        padding: 5px 10px;
        font-size: 10px;
    }

    /* Ajusta el título del encabezado */
    .title {
        font-size: 16px;
        letter-spacing: 1px;
    }

    /* Ajusta el contenido principal */
    .main-content {
        padding: 20px 10px;
    }

    /* Ajusta el selector de retos */
    .reto-selector label {
        font-size: 0.9rem;
    }

    /* Ajusta el elemento select */
    .reto-selector select {
        font-size: 0.8rem;
        padding: 5px;
    }

    /* Ajusta el contenedor de error y éxito */
    .error-container,
    .success-container {
        padding: 15px;
        font-size: 11px;
    }

    /* Ajusta el título del ranking */
    .ranking-container h2 {
        font-size: 16px;
    }

    /* Ajusta los encabezados y celdas de la tabla */
    .podium-table th,
    .podium-table td {
        font-size: 0.75rem;
        padding: 10px 4px;
        max-width: 120px;
    }

    /* Ajusta las fotos */
    .photo-preview {
        width: 50px;
        height: 50px;
    }

    /* Ajusta el número de posición */
    .podium-table tr.position-1 td:first-child {
        font-size: 1.1rem;
    }

    .podium-table tr.position-2 td:first-child {
        font-size: 1rem;
    }

    .podium-table tr.position-3 td:first-child {
        font-size: 0.9rem;
    }

    /* Ajusta el nombre */
    .podium-table tr.position-1 td:nth-child(2) {
        font-size: 1rem;
    }

    .podium-table tr.position-2 td:nth-child(2) {
        font-size: 0.9rem;
    }

    .podium-table tr.position-3 td:nth-child(2) {
        font-size: 0.85rem;
    }

    /* Ajusta el pie de página */
    .footer-minimal {
        padding: 10px;
    }

    /* Ajusta el texto del pie de página */
    .footer-minimal p {
        font-size: 10px;
    }
}
    </style>
</head>
<body>
    <header class="header">
        <a href="inicio.php" class="back-btn">← Regresar</a>
        <h1 class="title">Ranking - CAT21 Reto <?php echo $reto_id; ?></h1>
        <?php if ($is_admin): ?>
            <a href="admin_reto.php" class="admin-btn">Administrar Reto</a>
        <?php endif; ?>
    </header>

    <main class="main-content">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-container"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-container"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="reto-selector">
            <label for="reto_id">Seleccionar Reto:</label>
            <select id="reto_id" onchange="location.href='?reto_id=' + this.value">
                <?php foreach ($retos as $r): ?>
                    <option value="<?php echo $r['id']; ?>" <?php echo $r['id'] == $reto_id ? 'selected' : ''; ?>>
                        Ret_CT_<?php echo $r['id']; ?> (<?php echo date('d/m/Y', strtotime($r['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($r['end_date'])); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="ranking-container">
            <h2>Ranking General (Reto <?php echo $reto_id; ?>)</h2>
            <?php if (empty($main_ranking)): ?>
                <p>No hay datos disponibles para el ranking general. Asegúrate de haber registrado datos para la Semana Inicial y Semana 3, y que el usuario esté habilitado.</p>
            <?php else: ?>
                <div class="podium-container">
                    <table class="podium-table">
                        <thead>
                            <tr>
                                <th>Lugar</th>
                                <th>Nombre</th>
                                <th>Avance Promedio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($main_ranking as $index => $rank): ?>
                                <tr class="position-<?php echo $index + 1; ?>">
                                    <td><?php echo $index + 1; ?>°</td>
                                    <td><?php echo htmlspecialchars($rank['nombre_completo']); ?></td>
                                    <td><?php echo number_format($rank['promedio_avance_semana3'], 4); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="ranking-container">
            <h2>Ranking de Fotos (Reto <?php echo $reto_id; ?>)</h2>
            <?php if (empty($photo_ranking)): ?>
                <p>No hay fotos disponibles para el ranking. Asegúrate de que el administrador haya subido fotos para este reto.</p>
            <?php else: ?>
                <div class="podium-container">
                    <table class="podium-table">
                        <thead>
                            <tr>
                                <th>Lugar</th>
                                <th>Nombre Completo</th>
                                <th>Foto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($photo_ranking as $rank): ?>
                                <tr class="position-<?php echo $rank['posicion']; ?>">
                                    <td><?php echo htmlspecialchars($rank['posicion']); ?>°</td>
                                    <td><?php echo htmlspecialchars($rank['nombre_completo']); ?></td>
                                    <td>
                                        <?php if ($rank['foto'] && file_exists("Uploads/" . $rank['foto'])): ?>
                                            <img src="Uploads/<?php echo htmlspecialchars($rank['foto']); ?>" class="photo-preview">
                                        <?php else: ?>
                                            Sin foto válida
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="ranking-container">
            <h2>Ranking Elite (Reto <?php echo $reto_id; ?>)</h2>
            <?php if (empty($elite_ranking)): ?>
                <p>No hay datos disponibles para el ranking elite. Asegúrate de que el administrador haya configurado el ranking para este reto.</p>
            <?php else: ?>
                <div class="podium-container">
                    <table class="podium-table">
                        <thead>
                            <tr>
                                <th>Lugar</th>
                                <th>Nombre Completo</th>
                                <th>Foto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($elite_ranking as $rank): ?>
                                <tr class="position-<?php echo $rank['posicion']; ?>">
                                    <td><?php echo htmlspecialchars($rank['posicion']); ?>°</td>
                                    <td><?php echo htmlspecialchars($rank['nombre_completo']); ?></td>
                                    <td>
                                        <?php if ($rank['foto'] && file_exists("Uploads/" . $rank['foto'])): ?>
                                            <img src="Uploads/<?php echo htmlspecialchars($rank['foto']); ?>" class="photo-preview">
                                        <?php else: ?>
                                            Sin foto válida
                                        <?php endif; ?>
                                    </td>
                                </-retr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer-minimal">
        <p>© 2025 CAT21 - Todos los derechos reservados</p>
    </footer>
</body>
</html>