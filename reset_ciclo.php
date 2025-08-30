<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'C:\xampp\php\logs\php_errors.log');

require_once 'config.php';

try {
    // Check if current cycle has ended
    $sql = "SELECT id, end_date FROM ciclos WHERE end_date < CURDATE() ORDER BY end_date DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $ciclo = $stmt->fetch();

    if ($ciclo) {
        $ciclo_id = $ciclo['id'];

        // Check if rankings already exist for this cycle
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ranking WHERE ciclo_id = :ciclo_id");
        $stmt->execute(['ciclo_id' => $ciclo_id]);
        if ($stmt->fetchColumn() > 0) {
            // Rankings already stored, proceed to create new cycle
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime('+20 days'));
            $sql = "INSERT INTO ciclos (start_date, end_date) VALUES (:start_date, :end_date)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
            $new_ciclo_id = $pdo->lastInsertId();

            error_log("New cycle created: ID=$new_ciclo_id, Start=$start_date, End=$end_date");
            echo json_encode(['success' => true, 'new_ciclo_id' => $new_ciclo_id]);
            exit;
        }

        // Calculate top 3 rankings
        $query = "
            SELECT 
                u.id AS usuario_id,
                u.nombre,
                u.apellido_paterno,
                COALESCE(
                    (
                        ((SELECT grasa FROM datos_semanales WHERE usuario_id = u.id AND semana = 0 AND ciclo_id = :ciclo_id) - 
                         (SELECT grasa FROM datos_semanales WHERE usuario_id = u.id AND semana = 3 AND ciclo_id = :ciclo_id)) +
                        ((SELECT musculo FROM datos_semanales WHERE usuario_id = u.id AND semana = 3 AND ciclo_id = :ciclo_id) - 
                         (SELECT musculo FROM datos_semanales WHERE usuario_id = u.id AND semana = 0 AND ciclo_id = :ciclo_id))
                    ) / 2, 
                    0
                ) AS promedio_avance
            FROM usuarios u
            WHERE EXISTS (
                SELECT 1 FROM datos_semanales WHERE usuario_id = u.id AND semana = 0 AND ciclo_id = :ciclo_id
            ) AND EXISTS (
                SELECT 1 FROM datos_semanales WHERE usuario_id = u.id AND semana = 3 AND ciclo_id = :ciclo_id
            )
            ORDER BY promedio_avance DESC
            LIMIT 3
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute(['ciclo_id' => $ciclo_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Log data for debugging
        error_log("Ranking calculation for ciclo_id $ciclo_id: " . json_encode($results));

        // Store rankings
        $stmt = $pdo->prepare("
            INSERT INTO ranking (ciclo_id, usuario_id, nombre, apellido_paterno, promedio_avance, puesto, created_at)
            VALUES (:ciclo_id, :usuario_id, :nombre, :apellido_paterno, :promedio_avance, :puesto, NOW())
        ");

        foreach ($results as $index => $row) {
            $stmt->execute([
                'ciclo_id' => $ciclo_id,
                'usuario_id' => $row['usuario_id'],
                'nombre' => strtoupper($row['nombre']),
                'apellido_paterno' => strtoupper($row['apellido_paterno']),
                'promedio_avance' => number_format($row['promedio_avance'], 2),
                'puesto' => $index + 1
            ]);
        }

        // Create new cycle
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+20 days'));
        $sql = "INSERT INTO ciclos (start_date, end_date) VALUES (:start_date, :end_date)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
        $new_ciclo_id = $pdo->lastInsertId();

        error_log("New cycle created: ID=$new_ciclo_id, Start=$start_date, End=$end_date");
        echo json_encode(['success' => true, 'new_ciclo_id' => $new_ciclo_id]);
    } else {
        echo json_encode(['success' => true, 'message' => 'No cycle needs reset']);
    }
} catch (PDOException $e) {
    error_log("Error in reset_ciclo: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos', 'message' => $e->getMessage()]);
}
?>