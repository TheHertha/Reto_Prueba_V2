<?php
header('Content-Type: application/json');
require_once 'config.php';

// Fetch coaches from the database with optional search
try {
    $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
    $sql = "SELECT name FROM coaches WHERE name LIKE :search ORDER BY name ASC LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['search' => $search]);
    $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($coaches)) {
        echo json_encode([]);
    } else {
        echo json_encode($coaches);
    }
} catch (Exception $e) {
    error_log("Error al obtener coaches: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar coaches']);
}
?>