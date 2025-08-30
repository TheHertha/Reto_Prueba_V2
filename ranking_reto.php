<?php
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_GET['ciclo_id'])) {
    echo json_encode(['error' => true, 'message' => 'Ciclo no especificado']);
    exit;
}

try {
    $ciclo_id = (int)$_GET['ciclo_id'];
    $current_date = date('Y-m-d');

    // Verify cycle exists and check if it's active
    $stmt = $pdo->prepare("SELECT id, start_date, end_date FROM ciclos WHERE id = :ciclo_id");
    $stmt->execute(['ciclo_id' => $ciclo_id]);
    $ciclo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ciclo) {
        echo json_encode(['error' => true, 'message' => 'Ciclo no válido']);
        exit;
    }

    $is_active = $current_date >= $ciclo['start_date'] && $current_date <= $ciclo['end_date'];

    if ($is_active) {
        // Fetch live rankings from ranking table for active cycle
        $stmt = $pdo->prepare("
            SELECT r.usuario_id, u.nombre, u.apellido_paterno, r.avance_promedio
            FROM ranking r
            JOIN usuarios u ON r.usuario_id = u.id
            WHERE r.ciclo_id = :ciclo_id
            ORDER BY r.avance_promedio DESC
            LIMIT 3
        ");
        $stmt->execute(['ciclo_id' => $ciclo_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Assign positions
        $output = [];
        foreach ($results as $index => $row) {
            $output[] = [
                'puesto' => $index + 1,
                'usuario_id' => (int)$row['usuario_id'],
                'nombre' => $row['nombre'],
                'apellido_paterno' => $row['apellido_paterno'],
                'promedio_avance' => floatval($row['avance_promedio'])
            ];
        }
    } else {
        // Fetch archived rankings from ranking_historico for past cycles
        $stmt = $pdo->prepare("
            SELECT puesto, usuario_id, nombre, apellido_paterno, promedio_avance
            FROM ranking_historico
            WHERE ciclo_id = :ciclo_id
            ORDER BY puesto ASC
        ");
        $stmt->execute(['ciclo_id' => $ciclo_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format output
        $output = array_map(function($row) {
            return [
                'puesto' => (int)$row['puesto'],
                'usuario_id' => (int)$row['usuario_id'],
                'nombre' => $row['nombre'],
                'apellido_paterno' => $row['apellido_paterno'],
                'promedio_avance' => floatval($row['promedio_avance'])
            ];
        }, $results);
    }

    echo json_encode($output);
} catch (PDOException $e) {
    error_log("Error in ranking_reto.php: " . $e->getMessage());
    echo json_encode(['error' => true, 'message' => 'Error al obtener el ranking']);
}
?>