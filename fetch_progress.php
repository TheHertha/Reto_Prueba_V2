<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['ciclo_id'])) {
    echo json_encode(['error' => true, 'message' => 'Usuario o ciclo no especificado.']);
    exit;
}

try {
    $ciclo_id = (int)$_GET['ciclo_id'];
    $user_id = $_SESSION['user_id'];

    // Obtener edad
    $stmt = $pdo->prepare("SELECT fecha_nacimiento FROM usuarios WHERE id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch();
    if (!$user) {
        echo json_encode(['error' => true, 'message' => 'Usuario no encontrado.']);
        exit;
    }
    $birthDate = new DateTime($user['fecha_nacimiento']);
    $age = (new DateTime())->diff($birthDate)->y;
    $rango_edad = $age < 40 ? '20-39' : ($age < 60 ? '40-59' : '+60');

    // Obtener datos de la semana inicial y semana 3
    $stmt = $pdo->prepare("SELECT * FROM datos_semanales WHERE usuario_id = :usuario_id AND ciclo_id = :ciclo_id AND semana IN (0, 3)");
    $stmt->execute(['usuario_id' => $user_id, 'ciclo_id' => $ciclo_id]);
    $data = [];
    while ($row = $stmt->fetch()) {
        $data[$row['semana']] = $row;
    }

    if (!isset($data[0]) || !isset($data[3])) {
        echo json_encode([
            'error' => false,
            'rango_edad' => $rango_edad,
            'peso_ideal' => null,
            'avance_grasa_semana3' => null,
            'avance_musculo_semana3' => null,
            'promedio_avance_semana3' => null
        ]);
        exit;
    }

    $initial = $data[0];
    $week3 = $data[3];

    $pesoIdeal = floatval($initial['estatura'] - 100);
    $grasaIdeal = $age < 40 ? 23 : ($age < 60 ? 25 : 27);
    $musculoIdeal = $age < 40 ? 41.7 : ($age < 60 ? 41.5 : 41.3);
    $grasaIdealKg = floatval(($grasaIdeal * $pesoIdeal) / 100);
    $musculoIdealKg = floatval(($musculoIdeal * $pesoIdeal) / 100);
    $grasaVisceralIdeal = 7;

    $grasaInicialKg = floatval(($initial['peso'] * $initial['grasa']) / 100);
    $grasaSemana3Kg = floatval(($week3['peso'] * $week3['grasa']) / 100);
    $diferenciaGrasa = floatval($grasaInicialKg - $grasaSemana3Kg);
    $avanceGrasa = $grasaIdealKg != 0 ? floatval($diferenciaGrasa / $grasaIdealKg) : 0;

    $musculoInicialKg = floatval(($initial['peso'] * $initial['musculo']) / 100);
    $musculoSemana3Kg = floatval(($week3['peso'] * $week3['musculo']) / 100);
    $diferenciaMusculo = floatval($musculoSemana3Kg - $musculoInicialKg);
    $avanceMusculo = $musculoInicialKg != 0 ? floatval($diferenciaMusculo / $musculoInicialKg) : 0;

    $grasaVisceralDiferenciaIdeal = floatval($initial['masa'] - $grasaVisceralIdeal);
    $diferenciaGrasaVisceral = floatval($initial['masa'] - $week3['masa']);
    $avanceGrasaVisceral = $grasaVisceralDiferenciaIdeal != 0 ? floatval($diferenciaGrasaVisceral / $grasaVisceralDiferenciaIdeal) : 0;

    $promedioAvance = floatval(($avanceGrasaVisceral + $avanceGrasa + $avanceMusculo) / 3);

    echo json_encode([
        'error' => false,
        'rango_edad' => $rango_edad,
        'peso_ideal' => $pesoIdeal,
        'avance_grasa_semana3' => $avanceGrasa,
        'avance_musculo_semana3' => $avanceMusculo,
        'promedio_avance_semana3' => $promedioAvance
    ]);
} catch (PDOException $e) {
    error_log("Error in fetch_progress.php: " . $e->getMessage());
    echo json_encode(['error' => true, 'message' => 'Error al calcular progreso.']);
}
?>