<?php
// Ensure no output before JSON
ob_start();
require_once 'config.php';

// Set JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // Get the new cycle ID
    $new_ciclo_id = isset($_GET['new_ciclo_id']) ? (int)$_GET['new_ciclo_id'] : 0;
    if (!$new_ciclo_id) {
        throw new Exception('ID del nuevo ciclo no especificado');
    }

    // Verify the new cycle exists
    $stmt = $pdo->prepare("SELECT id FROM ciclos WHERE id = :ciclo_id");
    $stmt->execute(['ciclo_id' => $new_ciclo_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Ciclo no válido');
    }

    // Fetch datos_semanales
    $stmt = $pdo->prepare("
        SELECT ds.*, u.nombre, u.apellido_paterno
        FROM datos_semanales ds
        JOIN usuarios u ON ds.usuario_id = u.id
        WHERE ds.ciclo_id != :new_ciclo_id
    ");
    $stmt->execute(['new_ciclo_id' => $new_ciclo_id]);
    $datos_semanales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch ranking
    $stmt = $pdo->prepare("
        SELECT r.*, u.nombre, u.apellido_paterno
        FROM ranking r
        JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.ciclo_id != :new_ciclo_id
    ");
    $stmt->execute(['new_ciclo_id' => $new_ciclo_id]);
    $ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare JSON data
    $backup_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ciclo_id_excluded' => $new_ciclo_id,
        'datos_semanales' => $datos_semanales,
        'ranking' => $ranking
    ];

    // Delete data
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("DELETE FROM datos_semanales WHERE ciclo_id != :new_ciclo_id");
    $stmt->execute(['new_ciclo_id' => $new_ciclo_id]);
    $stmt = $pdo->prepare("DELETE FROM ranking WHERE ciclo_id != :new_ciclo_id");
    $stmt->execute(['new_ciclo_id' => $new_ciclo_id]);
    $pdo->commit();

    // Force JSON download
    $timestamp = date('Ymd_His');
    $filename = "backup_cycle_data_{$timestamp}_except_ciclo_{$new_ciclo_id}.json";
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("backup_cycle_data.php: Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Error al crear backup: ' . $e->getMessage()]);
    ob_end_flush();
    exit;
}
?>