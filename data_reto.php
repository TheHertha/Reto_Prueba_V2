<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'C:\xampp\php\logs\php_errors.log');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado', 'message' => 'Debes iniciar sesión para acceder a los datos.']);
    exit;
}

require_once 'config.php';

try {
    if (!extension_loaded('pdo_mysql')) {
        throw new Exception('El módulo PDO MySQL no está habilitado en el servidor.');
    }

    $usuario_id = $_SESSION['user_id'];
    $ciclo_id = isset($_GET['ciclo_id']) ? intval($_GET['ciclo_id']) : 0;

    // Validate cycle
    $stmt = $pdo->prepare("SELECT id FROM ciclos WHERE id = :ciclo_id");
    $stmt->execute(['ciclo_id' => $ciclo_id]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Ciclo inválido', 'message' => 'El ciclo especificado no existe.']);
        exit;
    }

    // Check if table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'datos_semanales'");
    if ($table_check->rowCount() === 0) {
        throw new Exception('La tabla datos_semanales no existe en la base de datos retofitcat21.');
    }

    // Fetch data for current cycle
    $stmt = $pdo->prepare("SELECT semana, estatura, peso, masa, grasa, musculo, image FROM datos_semanales WHERE usuario_id = :usuario_id AND ciclo_id = :ciclo_id");
    $stmt->execute(['usuario_id' => $usuario_id, 'ciclo_id' => $ciclo_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($records);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error en la base de datos',
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error general',
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
} finally {
    $pdo = null;
}
?>