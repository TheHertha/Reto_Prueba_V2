<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Por favor, inicia sesión para acceder al reto.";
    error_log("reto.php: Redirecting to login.php, no user_id, session=" . json_encode($_SESSION));
    header("Location: login.php");
    exit;
}


try {
    $stmt = $pdo->prepare("SELECT rol, habilitado, fecha_nacimiento FROM usuarios WHERE id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $_SESSION['error'] = "Usuario no encontrado.";
        error_log("reto.php: Redirecting to login.php, user not found, user_id=" . $_SESSION['user_id']);
        header("Location: login.php");
        exit;
    }
    $_SESSION['rol'] = $user['rol'];
    $is_admin = $user['rol'] === 'admin';
    error_log("reto.php: User fetched, user_id=" . $_SESSION['user_id'] . ", rol=" . $user['rol'] . ", habilitado=" . $user['habilitado']);
   
    if (!$is_admin && !$user['habilitado']) {
        $_SESSION['error'] = "Tu cuenta no está habilitada. Contacta al administrador.";
        error_log("reto.php: Redirecting to inicio.php, not admin and not habilitado, user_id=" . $_SESSION['user_id']);
        header("Location: inicio.php?no_redirect=1");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al verificar el usuario: " . $e->getMessage();
    error_log("reto.php: Database error: " . $e->getMessage());
    header("Location: login.php");
    exit;
}


$birth_date = new DateTime($user['fecha_nacimiento']);
$current_date = new DateTime();
$age = $current_date->diff($birth_date)->y;
$rango_edad = ($age >= 20 && $age <= 39) ? '20-39' : (($age >= 40 && $age <= 59) ? '40-59' : '+60');


try {
    $stmt = $pdo->query("SELECT id, start_date, end_date FROM retos WHERE CURDATE() BETWEEN start_date AND end_date LIMIT 1");
    $reto = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reto) {
        $_SESSION['error'] = "No hay un reto activo. Contacta al administrador para crear uno.";
        error_log("reto.php: No active reto found");
        header("Location: inicio.php?no_redirect=1");
        exit;
    }
    $reto_id = $reto['id'];


   $stmt = $pdo->prepare("
    SELECT 
        semana, 
        estatura, 
        peso, 
        masa, 
        grasa, 
        musculo, 
        image,
        avance_grasa,
        avance_musculo,
        avance_grasa_visceral,
        promedio_avance,
        created_at  -- opcional, pero útil para depuración
    FROM datos_semanales 
    WHERE usuario_id = :usuario_id 
      AND reto_id = :reto_id
");
    $stmt->execute(['usuario_id' => $_SESSION['user_id'], 'reto_id' => $reto_id]);
    $user_data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user_data[$row['semana']] = $row;
    }
} catch (PDOException $e) {
    error_log("reto.php: Error in reto or data fetch: " . $e->getMessage());
    $_SESSION['error'] = "Error al cargar datos del reto: " . $e->getMessage();
    header("Location: inicio.php?no_redirect=1");
    exit;
}

// Calculate progress (only if Semana 0 and Semana 3 data exist)
$progress = ['rango_edad' => $rango_edad];

if (isset($user_data[0]) && isset($user_data[3])) {
    $datos0 = $user_data[0];
    $datos3 = $user_data[3];

    // Caso 1: Ya existe avance guardado → usar directamente (más rápido y consistente)
    if ($datos3['promedio_avance'] !== null) {
        $progress = [
            'rango_edad'                    => $rango_edad,
            'peso_ideal'                    => $datos0['estatura'] - 100,
            'avance_grasa_semana3'          => $datos3['avance_grasa'],
            'avance_musculo_semana3'        => $datos3['avance_musculo'],
            'avance_grasa_visceral_semana3' => $datos3['avance_grasa_visceral'],
            'promedio_avance_semana3'       => $datos3['promedio_avance']
        ];
        error_log("reto.php: Leyendo avances GUARDADOS para usuario {$_SESSION['user_id']} - reto $reto_id");
    } 
    // Caso 2: No existe avance → calcular y guardar
    else {
        error_log("reto.php: Calculando avances (primera vez) para usuario {$_SESSION['user_id']} - reto $reto_id");

        $estatura = $datos0['estatura'];
        $peso_inicial = $datos0['peso'];
        $grasa_inicial = $datos0['grasa'];
        $musculo_inicial = $datos0['musculo'];
        $masa_inicial = $datos0['masa'];
        $peso_semana3 = $datos3['peso'];
        $grasa_semana3 = $datos3['grasa'];
        $musculo_semana3 = $datos3['musculo'];
        $masa_semana3 = $datos3['masa'];

        $peso_ideal = $estatura - 100;

        $grasa_ideal = ($rango_edad === '20-39') ? 23 : ($rango_edad === '40-59' ? 25 : 27);
        $grasa_ideal_kg = ($grasa_ideal * $peso_ideal) / 100;

        $grasa_porcentaje_kg_inicial = ($peso_inicial * $grasa_inicial) / 100;
        $grasa_porcentaje_kg_semana3 = ($peso_semana3 * $grasa_semana3) / 100;
        $diferencia_grasa_semana3 = $grasa_porcentaje_kg_inicial - $grasa_porcentaje_kg_semana3;
        $avance_grasa_semana3 = ($grasa_ideal_kg > 0) ? ($diferencia_grasa_semana3 / $grasa_ideal_kg) * 100 : 0; // CORREGIDO ×100

        $musculo_ideal = ($rango_edad === '20-39') ? 41.7 : ($rango_edad === '40-59' ? 41.5 : 41.3);
        $musculo_ideal_kg = ($musculo_ideal * $peso_ideal) / 100;

        $musculo_porcentaje_kg_inicial = ($peso_inicial * $musculo_inicial) / 100;
        $musculo_porcentaje_kg_semana3 = ($peso_semana3 * $musculo_semana3) / 100;
        $diferencia_musculo_semana3 = $musculo_porcentaje_kg_semana3 - $musculo_porcentaje_kg_inicial;
        $avance_musculo_semana3 = ($musculo_ideal_kg > 0) ? ($diferencia_musculo_semana3 / $musculo_ideal_kg) * 100 : 0;

        $grasa_visceral_ideal = 7;
        $grasa_visceral_diferencia_ideal = $masa_inicial - $grasa_visceral_ideal;
        $diferencia_grasa_visceral_semana3 = $masa_inicial - $masa_semana3;
        $avance_grasa_visceral_semana3 = ($grasa_visceral_diferencia_ideal != 0) ? ($diferencia_grasa_visceral_semana3 / $grasa_visceral_diferencia_ideal) * 100 : 0;

        $promedio_avance_semana3 = ($avance_grasa_visceral_semana3 + $avance_grasa_semana3 + $avance_musculo_semana3) / 3;

        $progress = [
            'rango_edad'                    => $rango_edad,
            'peso_ideal'                    => $peso_ideal,
            'avance_grasa_semana3'          => $avance_grasa_semana3,
            'avance_musculo_semana3'        => $avance_musculo_semana3,
            'avance_grasa_visceral_semana3' => $avance_grasa_visceral_semana3,
            'promedio_avance_semana3'       => $promedio_avance_semana3
        ];

        // Guardar en BD
        try {
            $stmt_update = $pdo->prepare("
                UPDATE datos_semanales
                   SET avance_grasa          = :ag,
                       avance_musculo        = :am,
                       avance_grasa_visceral = :av,
                       promedio_avance       = :prom
                 WHERE usuario_id = :uid
                   AND reto_id    = :rid
                   AND semana     = 3
            ");
            $stmt_update->execute([
                ':ag'   => round($avance_grasa_semana3, 3),
                ':am'   => round($avance_musculo_semana3, 3),
                ':av'   => round($avance_grasa_visceral_semana3, 3),
                ':prom' => round($promedio_avance_semana3, 3),
                ':uid'  => $_SESSION['user_id'],
                ':rid'  => $reto_id
            ]);

            $filas = $stmt_update->rowCount();
            error_log("reto.php: UPDATE ejecutado - filas afectadas: $filas para uid={$_SESSION['user_id']}, rid=$reto_id");

            // Actualizar variable local (para esta carga)
            $user_data[3]['avance_grasa']          = round($avance_grasa_semana3, 3);
            $user_data[3]['avance_musculo']        = round($avance_musculo_semana3, 3);
            $user_data[3]['avance_grasa_visceral'] = round($avance_grasa_visceral_semana3, 3);
            $user_data[3]['promedio_avance']       = round($promedio_avance_semana3, 3);
        } catch (PDOException $e) {
            error_log("reto.php ERROR: Falló UPDATE avances: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAT21 - RETO 2025</title>
    <style>

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

/* Estilo de los botones de regresar, cerrar sesión y administrar */
.back-btn,
.logout-btn,
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
.logout-btn:hover,
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

/* Contenedor de las tarjetas de semana */
.carousel-container {
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
    justify-content: space-between;
    margin-bottom: 60px;
}

/* Estilo de las tarjetas de semana */
.semana-card {
    flex: 1 1 calc(25% - 30px);
    min-width: 280px;
    border: 1px solid #333333;
    background: #ffffff;
    padding: 30px;
    transition: all 0.3s ease;
    position: relative;
}

/* Efecto hover para las tarjetas */
.semana-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

/* Título de las tarjetas */
.semana-card h2 {
    font-size: 24px;
    font-weight: 300;
    color: #000000;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 20px;
    text-align: center;
}

/* Formulario dentro de las tarjetas */
.semana-card form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Estilo de los inputs de número y archivo */
.semana-card input[type="number"],
.semana-card input[type="file"] {
    width: 100%;
    padding: 12px;
    border: 1px solid #333333;
    background: #f8f8f8;
    font-size: 14px;
    color: #000000;
    transition: all 0.3s ease;
}

/* Efecto de foco para los inputs */
.semana-card input[type="number"]:focus,
.semana-card input[type="file"]:focus {
    outline: none;
    border-color: #FFD700;
    background: #ffffff;
}

/* Placeholder para los inputs */
.semana-card input[type="number"]::placeholder {
    color: #999999;
}

/* Inputs deshabilitados */
.semana-card input:disabled {
    background: #e0e0e0;
    cursor: not-allowed;
}

/* Estilo de los botones en las tarjetas */
.semana-card button {
    padding: 16px;
    border: none;
    font-size: 14px;
    font-weight: 400;
    cursor: pointer;
    transition: all 0.4s ease;
    text-transform: uppercase;
    letter-spacing: 2px;
}

/* Botón rojo */
.btn-rojo {
    background: #FF0000;
    color: #ffffff;
}

/* Efecto hover para el botón rojo */
.btn-rojo:hover:not(:disabled) {
    background: #cc0000;
    box-shadow: 0 0 0 2px #FFD700;
}

/* Botón amarillo */
.btn-amarillo {
    background: #FFD700;
    color: #000000;
}

/* Efecto hover para el botón amarillo */
.btn-amarillo:hover:not(:disabled) {
    background: #ffca28;
    box-shadow: 0 0 0 2px #FF0000;
}

/* Botón deshabilitado */
.btn-disabled {
    background: #cccccc;
    color: #666666;
    cursor: not-allowed;
}

/* Vista previa de la imagen */
.image-preview {
    width: 100%;
    max-height: 200px;
    object-fit: cover;
    margin-top: 10px;
    display: none;
}

/* Mostrar la vista previa de la imagen */
.image-preview.show {
    display: block;
}

/* Contenedor de progreso */
.progress-container {
    margin-top: 60px;
    padding: 30px;
    border: 1px solid #333333;
    background: #f8f8f8;
    text-align: center;
}

/* Título del contenedor de progreso */
.progress-container h2 {
    font-size: 24px;
    font-weight: 300;
    color: #000000;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 20px;
}

/* Cuadrícula de progreso */
.progress-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

/* Elementos de la cuadrícula de progreso */
.progress-item {
    padding: 20px;
    border: 1px solid #333333;
    background: #ffffff;
}

/* Título de los elementos de progreso */
.progress-item h3 {
    font-size: 18px;
    font-weight: 400;
    color: #000000;
    margin-bottom: 10px;
}

/* Valor de los elementos de progreso */
.progress-item p {
    font-size: 24px;
    font-weight: 300;
    color: #FF0000;
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
    .logout-btn,
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

    /* Ajusta el contenedor de tarjetas */
    .carousel-container {
        flex-direction: column;
        gap: 30px;
    }

    /* Ajusta las tarjetas de semana */
    .semana-card {
        width: 100%;
        min-width: unset;
        padding: 20px;
    }

    /* Ajusta el título de las tarjetas */
    .semana-card h2 {
        font-size: 20px;
    }

    /* Ajusta los inputs */
    .semana-card input[type="number"],
    .semana-card input[type="file"] {
        font-size: 13px;
        padding: 10px;
    }

    /* Ajusta los botones de las tarjetas */
    .semana-card button {
        font-size: 13px;
        padding: 12px;
    }

    /* Ajusta la vista previa de la imagen */
    .image-preview {
        max-height: 150px;
    }

    /* Ajusta el contenedor de progreso */
    .progress-container {
        padding: 20px;
        margin-top: 40px;
    }

    /* Ajusta el título del contenedor de progreso */
    .progress-container h2 {
        font-size: 20px;
    }

    /* Ajusta la cuadrícula de progreso */
    .progress-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    /* Ajusta los elementos de progreso */
    .progress-item {
        padding: 15px;
    }

    /* Ajusta el título de los elementos de progreso */
    .progress-item h3 {
        font-size: 16px;
    }

    /* Ajusta el valor de los elementos de progreso */
    .progress-item p {
        font-size: 20px;
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
    .logout-btn,
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

    /* Ajusta el contenedor de tarjetas */
    .carousel-container {
        gap: 20px;
    }

    /* Ajusta las tarjetas de semana */
    .semana-card {
        padding: 15px;
    }

    /* Ajusta el título de las tarjetas */
    .semana-card h2 {
        font-size: 18px;
    }

    /* Ajusta los inputs */
    .semana-card input[type="number"],
    .semana-card input[type="file"] {
        font-size: 12px;
        padding: 8px;
    }

    /* Ajusta los botones de las tarjetas */
    .semana-card button {
        font-size: 12px;
        padding: 10px;
    }

    /* Ajusta la vista previa de la imagen */
    .image-preview {
        max-height: 120px;
    }

    /* Ajusta el contenedor de error y éxito */
    .error-container,
    .success-container {
        padding: 20px;
        font-size: 12px;
    }

    /* Ajusta el contenedor de progreso */
    .progress-container {
        padding: 15px;
        margin-top: 30px;
    }

    /* Ajusta el título del contenedor de progreso */
    .progress-container h2 {
        font-size: 18px;
    }

    /* Ajusta los elementos de progreso */
    .progress-item {
        padding: 12px;
    }

    /* Ajusta el título de los elementos de progreso */
    .progress-item h3 {
        font-size: 14px;
    }

    /* Ajusta el valor de los elementos de progreso */
    .progress-item p {
        font-size: 18px;
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
    .logout-btn,
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

    /* Ajusta el contenedor de tarjetas */
    .carousel-container {
        gap: 15px;
    }

    /* Ajusta las tarjetas de semana */
    .semana-card {
        padding: 10px;
    }

    /* Ajusta el título de las tarjetas */
    .semana-card h2 {
        font-size: 16px;
    }

    /* Ajusta los inputs */
    .semana-card input[type="number"],
    .semana-card input[type="file"] {
        font-size: 11px;
        padding: 6px;
    }

    /* Ajusta los botones de las tarjetas */
    .semana-card button {
        font-size: 11px;
        padding: 8px;
    }

    /* Ajusta la vista previa de la imagen */
    .image-preview {
        max-height: 100px;
    }

    /* Ajusta el contenedor de error y éxito */
    .error-container,
    .success-container {
        padding: 15px;
        font-size: 11px;
    }

    /* Ajusta el contenedor de progreso */
    .progress-container {
        padding: 10px;
        margin-top: 20px;
    }

    /* Ajusta el título del contenedor de progreso */
    .progress-container h2 {
        font-size: 16px;
    }

    /* Ajusta los elementos de progreso */
    .progress-item {
        padding: 10px;
    }

    /* Ajusta el título de los elementos de progreso */
    .progress-item h3 {
        font-size: 13px;
    }

    /* Ajusta el valor de los elementos de progreso */
    .progress-item p {
        font-size: 16px;
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
        <h1 class="title">CAT21 - RETO 2025 (Reto <?php echo $reto_id; ?>)</h1>
        <div>
            <?php if ($is_admin): ?>
                <a href="admin_reto.php" class="admin-btn">Administrar Reto</a>
            <?php endif; ?>
        </div>
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

        <div class="carousel-container">
            <?php
            $semanas = [
                ['name' => 'Semana Inicial', 'semana' => 0, 'color' => 'rojo'],
                ['name' => 'Semana 1', 'semana' => 1, 'color' => 'amarillo'],
                ['name' => 'Semana 2', 'semana' => 2, 'color' => 'rojo'],
                ['name' => 'Semana 3', 'semana' => 3, 'color' => 'amarillo']
            ];
            foreach ($semanas as $semana): ?>
                <div class="semana-card">
                    <h2><?php echo $semana['name']; ?></h2>
                    <form action="<?php echo $semana['semana'] == 1 || $semana['semana'] == 2 ? '#' : 'submit_reto.php'; ?>" method="POST" enctype="multipart/form-data" <?php echo isset($user_data[$semana['semana']]) ? 'class="disabled"' : ''; ?>>
                        <input type="hidden" name="semana" value="<?php echo $semana['semana']; ?>">
                        <input type="hidden" name="reto_id" value="<?php echo $reto_id; ?>">
                        <input type="number" step="0.1" name="estatura" placeholder="Estatura (cm)" value="<?php echo isset($user_data[$semana['semana']]) ? $user_data[$semana['semana']]['estatura'] : ''; ?>" <?php echo isset($user_data[$semana['semana']]) ? 'disabled' : ($semana['semana'] != 0 ? 'disabled' : ''); ?> <?php echo $semana['semana'] == 0 ? 'required' : ''; ?>>
                        <input type="number" step="0.1" name="peso" placeholder="Peso (kg)" value="<?php echo isset($user_data[$semana['semana']]) ? $user_data[$semana['semana']]['peso'] : ''; ?>" <?php echo isset($user_data[$semana['semana']]) ? 'disabled' : ''; ?> required>
                        <input type="number" step="0.1" name="masa" placeholder="Grasa Visceral" value="<?php echo isset($user_data[$semana['semana']]) ? $user_data[$semana['semana']]['masa'] : ''; ?>" <?php echo isset($user_data[$semana['semana']]) ? 'disabled' : ''; ?> required>
                        <input type="number" step="0.1" name="grasa" placeholder="Grasa (%)" value="<?php echo isset($user_data[$semana['semana']]) ? $user_data[$semana['semana']]['grasa'] : ''; ?>" <?php echo isset($user_data[$semana['semana']]) ? 'disabled' : ''; ?> required>
                        <input type="number" step="0.1" name="musculo" placeholder="Músculo (%)" value="<?php echo isset($user_data[$semana['semana']]) ? $user_data[$semana['semana']]['musculo'] : ''; ?>" <?php echo isset($user_data[$semana['semana']]) ? 'disabled' : ''; ?> required>
                        <?php if ($semana['semana'] == 0 || $semana['semana'] == 3): ?>
                            <input type="file" name="image" accept="image/jpeg,image/png,image/gif" <?php echo isset($user_data[$semana['semana']]) ? 'disabled' : ''; ?>>
                            <?php if (isset($user_data[$semana['semana']]['image']) && $user_data[$semana['semana']]['image']): ?>
                                <img src="Uploads/<?php echo htmlspecialchars($user_data[$semana['semana']]['image']); ?>" class="image-preview show">
                            <?php endif; ?>
                        <?php endif; ?>
                        <button type="submit" class="btn-<?php echo $semana['color']; ?> <?php echo isset($user_data[$semana['semana']]) ? 'btn-disabled' : ''; ?>" <?php echo isset($user_data[$semana['semana']]) ? 'disabled' : ''; ?>>
                            <?php echo isset($user_data[$semana['semana']]) ? 'DATOS ENVIADOS' : 'Enviar Datos'; ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="progress-container">
            <h2>Tu Progreso (Reto <?php echo $reto_id; ?>)</h2>
            <div id="progress-grid" class="progress-grid">
                <div class="progress-item">
                    <h3>Rango de Edad</h3>
                    <p><?php echo htmlspecialchars($progress['rango_edad']); ?></p>
                </div>
                <div class="progress-item">
                    <h3>Peso Ideal (kg)</h3>
                    <p><?php echo isset($progress['peso_ideal']) ? number_format($progress['peso_ideal'], 1) : 'No disponible'; ?></p>
                </div>
                <div class="progress-item">
                    <h3>Pérdida de Grasa</h3>
                    <p><?php echo isset($progress['avance_grasa_semana3']) ? number_format($progress['avance_grasa_semana3'], 4) : 'No disponible'; ?></p>
                </div>
                <div class="progress-item">
                    <h3>Ganancia de Músculo</h3>
                    <p><?php echo isset($progress['avance_musculo_semana3']) ? number_format($progress['avance_musculo_semana3'], 4) : 'No disponible'; ?></p>
                </div>
                <div class="progress-item">
                    <h3>Pérdida de Grasa Visceral</h3>
                    <p><?php echo isset($progress['avance_grasa_visceral_semana3']) ? number_format($progress['avance_grasa_visceral_semana3'], 4) : 'No disponible'; ?></p>
                </div>
                <div class="progress-item">
                    <h3>Avance Promedio</h3>
                    <p><?php echo isset($progress['promedio_avance_semana3']) ? number_format($progress['promedio_avance_semana3'], 4) : 'No disponible'; ?></p>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer-minimal">
        <p>© 2025 CAT21 - Todos los derechos reservados</p>
    </footer>

    <script>
     
        document.querySelectorAll('input[name="estatura"]').forEach(input => {
            input.addEventListener('change', () => {
                const value = parseFloat(input.value);
                if (value < 50 || value > 250) {
                    alert('La estatura debe estar entre 50 y 250 cm.');
                    input.value = '';
                }
            });
        });
        document.querySelectorAll('input[name="peso"]').forEach(input => {
            input.addEventListener('change', () => {
                const value = parseFloat(input.value);
                if (value < 20 || value > 300) {
                    alert('El peso debe estar entre 20 y 300 kg.');
                    input.value = '';
                }
            });
        });
        document.querySelectorAll('input[name="masa"]').forEach(input => {
            input.addEventListener('change', () => {
                const value = parseFloat(input.value);
                if (value < 0 || value > 50) {
                    alert('La grasa visceral debe estar entre 0 y 50.');
                    input.value = '';
                }
            });
        });
        document.querySelectorAll('input[name="grasa"]').forEach(input => {
            input.addEventListener('change', () => {
                const value = parseFloat(input.value);
                if (value < 0 || value > 100) {
                    alert('La grasa debe estar entre 0 y 100%.');
                    input.value = '';
                }
            });
        });
        document.querySelectorAll('input[name="musculo"]').forEach(input => {
            input.addEventListener('change', () => {
                const value = parseFloat(input.value);
                if (value < 0 || value > 100) {
                    alert('El músculo debe estar entre 0 y 100%.');
                    input.value = '';
                }
            });
        });

      
        document.querySelectorAll('form[action="#"]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                form.classList.add('disabled');
                form.querySelectorAll('input').forEach(input => input.disabled = true);
                const button = form.querySelector('button');
                button.classList.add('btn-disabled');
                button.textContent = 'DATOS ENVIADOS';
                const successDiv = document.createElement('div');
                successDiv.className = 'success-container';
                successDiv.textContent = 'Datos registrados (no guardados en la base de datos).';
                document.querySelector('.main-content').prepend(successDiv);
                setTimeout(() => successDiv.remove(), 3000);
            });
        });

        // Manejo de formularios reales
        document.querySelectorAll('form[action="submit_reto.php"]').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (form.classList.contains('disabled')) return;
                const formData = new FormData(form);
                const submitButton = form.querySelector('button');
                const imageInput = form.querySelector('input[type="file"]');
                const imagePreview = form.querySelector('.image-preview') || document.createElement('img');
                try {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Enviando...';
                    const response = await fetch('submit_reto.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        form.classList.add('disabled');
                        form.querySelectorAll('input').forEach(input => input.disabled = true);
                        submitButton.classList.add('btn-disabled');
                        submitButton.textContent = 'DATOS ENVIADOS';
                        if (result.imageUrl) {
                            imagePreview.src = 'Uploads/' + result.imageUrl;
                            imagePreview.className = 'image-preview show';
                            if (!imagePreview.parentNode) form.appendChild(imagePreview);
                        }
                        const successDiv = document.createElement('div');
                        successDiv.className = 'success-container';
                        successDiv.textContent = result.message;
                        document.querySelector('.main-content').prepend(successDiv);
                        setTimeout(() => location.reload(), 1000); // Reload to update progress
                        setTimeout(() => successDiv.remove(), 3000);
                    } else {
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'error-container';
                        errorDiv.textContent = result.message;
                        document.querySelector('.main-content').prepend(errorDiv);
                        setTimeout(() => errorDiv.remove(), 3000);
                    }
                } catch (error) {
                    console.error('Error al enviar datos:', error);
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-container';
                    errorDiv.textContent = 'Error al enviar datos. Por favor, intenta de nuevo.';
                    document.querySelector('.main-content').prepend(errorDiv);
                    setTimeout(() => errorDiv.remove(), 3000);
                } finally {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Enviar Datos';
                }
            });
        });
    </script>
</body>
</html>