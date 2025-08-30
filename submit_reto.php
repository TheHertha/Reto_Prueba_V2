<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Por favor, inicia sesión.']);
    error_log("submit_reto.php: Unauthorized access, no user_id");
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    error_log("submit_reto.php: Invalid request method");
    exit;
}

// Validate input data
$semana = isset($_POST['semana']) ? intval($_POST['semana']) : null;
$reto_id = isset($_POST['reto_id']) ? intval($_POST['reto_id']) : null;
$estatura = isset($_POST['estatura']) ? floatval($_POST['estatura']) : null;
$peso = isset($_POST['peso']) ? floatval($_POST['peso']) : null;
$masa = isset($_POST['masa']) ? floatval($_POST['masa']) : null;
$grasa = isset($_POST['grasa']) ? floatval($_POST['grasa']) : null;
$musculo = isset($_POST['musculo']) ? floatval($_POST['musculo']) : null;

if ($semana === null || $reto_id === null || $peso === null || $masa === null || $grasa === null || $musculo === null) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos.']);
    error_log("submit_reto.php: Missing required fields, semana=$semana, reto_id=$reto_id");
    exit;
}

// For weeks 1, 2, or 3, fetch estatura from Semana 0
if ($semana !== 0) {
    try {
        $stmt = $pdo->prepare("SELECT estatura FROM datos_semanales WHERE usuario_id = :usuario_id AND reto_id = :reto_id AND semana = 0");
        $stmt->execute(['usuario_id' => $_SESSION['user_id'], 'reto_id' => $reto_id]);
        $semana0_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$semana0_data) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No se encontró estatura de la Semana Inicial. Por favor, registra los datos de la Semana Inicial primero.']);
            error_log("submit_reto.php: No Semana 0 data found for user_id={$_SESSION['user_id']}, reto_id=$reto_id");
            exit;
        }
        $estatura = $semana0_data['estatura'];
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al obtener estatura: ' . $e->getMessage()]);
        error_log("submit_reto.php: Error fetching estatura: " . $e->getMessage());
        exit;
    }
}

// Validate ranges
if ($semana === 0 && ($estatura === null || $estatura < 50 || $estatura > 250)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'La estatura debe estar entre 50 y 250 cm.']);
    error_log("submit_reto.php: Invalid estatura=$estatura for semana=0");
    exit;
}
if ($semana !== 0 && $estatura === null) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener la estatura de la Semana Inicial.']);
    error_log("submit_reto.php: Estatura is null for semana=$semana");
    exit;
}
if ($peso < 20 || $peso > 300) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'El peso debe estar entre 20 y 300 kg.']);
    error_log("submit_reto.php: Invalid peso=$peso");
    exit;
}
if ($masa < 0 || $masa > 50) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'La grasa visceral debe estar entre 0 y 50.']);
    error_log("submit_reto.php: Invalid masa=$masa");
    exit;
}
if ($grasa < 0 || $grasa > 100) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'La grasa debe estar entre 0 y 100%.']);
    error_log("submit_reto.php: Invalid grasa=$grasa");
    exit;
}
if ($musculo < 0 || $musculo > 100) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'El músculo debe estar entre 0 y 100%.']);
    error_log("submit_reto.php: Invalid musculo=$musculo");
    exit;
}
if ($semana < 0 || $semana > 3) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Semana inválida.']);
    error_log("submit_reto.php: Invalid semana=$semana");
    exit;
}

// Check if data already exists for this user, reto, and semana
try {
    $stmt = $pdo->prepare("SELECT id FROM datos_semanales WHERE usuario_id = :usuario_id AND reto_id = :reto_id AND semana = :semana");
    $stmt->execute([
        'usuario_id' => $_SESSION['user_id'],
        'reto_id' => $reto_id,
        'semana' => $semana
    ]);
    if ($stmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ya has registrado datos para esta semana.']);
        error_log("submit_reto.php: Data already exists for user_id={$_SESSION['user_id']}, reto_id=$reto_id, semana=$semana");
        exit;
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error al verificar datos: ' . $e->getMessage()]);
    error_log("submit_reto.php: Error checking existing data: " . $e->getMessage());
    exit;
}

// Handle file upload for Semana 0 or 3
$image_url = null;
if (($semana === 0 || $semana === 3) && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($_FILES['image']['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido.']);
        error_log("submit_reto.php: Invalid file type for image upload");
        exit;
    }
    $image_name = uniqid() . '_' . basename($_FILES['image']['name']);
    $image_path = 'Uploads/' . $image_name;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al subir la imagen.']);
        error_log("submit_reto.php: Failed to move uploaded image");
        exit;
    }
    $image_url = $image_name;
}

// Insert data into datos_semanales
try {
    $stmt = $pdo->prepare("
        INSERT INTO datos_semanales (usuario_id, reto_id, semana, estatura, peso, masa, grasa, musculo, image)
        VALUES (:usuario_id, :reto_id, :semana, :estatura, :peso, :masa, :grasa, :musculo, :image)
    ");
    $stmt->execute([
        'usuario_id' => $_SESSION['user_id'],
        'reto_id' => $reto_id,
        'semana' => $semana,
        'estatura' => $estatura,
        'peso' => $peso,
        'masa' => $masa,
        'grasa' => $grasa,
        'musculo' => $musculo,
        'image' => $image_url
    ]);

    // If Semana 3 data is submitted, ensure user is enabled for ranking
    if ($semana === 3) {
        $stmt = $pdo->prepare("SELECT habilitado FROM usuarios WHERE id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user['habilitado'] == 0) {
            error_log("submit_reto.php: Semana 3 data submitted but user is disabled, user_id={$_SESSION['user_id']}, reto_id=$reto_id");
        } else {
            // Optionally trigger ranking update (not needed if ranking.php is dynamic)
            error_log("submit_reto.php: Semana 3 data submitted, eligible for ranking, user_id={$_SESSION['user_id']}, reto_id=$reto_id");
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Datos guardados correctamente.', 'imageUrl' => $image_url]);
    error_log("submit_reto.php: Data saved successfully for user_id={$_SESSION['user_id']}, reto_id=$reto_id, semana=$semana");
    exit;
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error al guardar datos: ' . $e->getMessage()]);
    error_log("submit_reto.php: Error saving data: " . $e->getMessage());
    exit;
}
?>