<?php
require_once 'config.php';

try {
    $current_date = date('Y-m-d');

    // Find cycles that ended and have no archived rankings
    $stmt = $pdo->prepare("
        SELECT id
        FROM ciclos
        WHERE end_date < :current_date
        AND id NOT IN (SELECT DISTINCT ciclo_id FROM ranking_historico)
    ");
    $stmt->execute(['current_date' => $current_date]);
    $ended_cycles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($ended_cycles as $ciclo_id) {
        // Fetch top 3 rankings for the cycle
        $stmt = $pdo->prepare("
            SELECT r.usuario_id, u.nombre, u.apellido_paterno, r.avance_promedio
            FROM ranking r
            JOIN usuarios u ON r.usuario_id = u.id
            WHERE r.ciclo_id = :ciclo_id
            ORDER BY r.avance_promedio DESC
            LIMIT 3
        ");
        $stmt->execute(['ciclo_id' => $ciclo_id]);
        $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Insert into ranking_historico
        $stmt_insert = $pdo->prepare("
            INSERT INTO ranking_historico (ciclo_id, puesto, usuario_id, nombre, apellido_paterno, promedio_avance)
            VALUES (:ciclo_id, :puesto, :usuario_id, :nombre, :apellido_paterno, :promedio_avance)
        ");

        foreach ($rankings as $index => $rank) {
            $stmt_insert->execute([
                'ciclo_id' => $ciclo_id,
                'puesto' => $index + 1,
                'usuario_id' => $rank['usuario_id'],
                'nombre' => $rank['nombre'],
                'apellido_paterno' => $rank['apellido_paterno'],
                'promedio_avance' => $rank['avance_promedio']
            ]);
        }

        error_log("archive_rankings.php: Archived rankings for ciclo_id=$ciclo_id");
    }

    echo json_encode(['success' => true, 'message' => 'Rankings archivados correctamente']);
} catch (PDOException $e) {
    error_log("archive_rankings.php: Error: " . $e->getMessage());
    echo json_encode(['error' => true, 'message' => 'Error al archivar rankings: ' . $e->getMessage()]);
}
?>