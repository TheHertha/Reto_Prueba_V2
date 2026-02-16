<?php
session_start();
require_once 'config.php';

// Seguridad básica
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'coach'])) {
    die("Acceso denegado");
}

// Filtro opcional por reto
$reto_id = isset($_GET['reto_id']) ? (int)$_GET['reto_id'] : null;

$filename = 'Reto_' . ($reto_id ?: 'Todos') . '_Participantes_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// BOM para acentos en Excel Windows
echo chr(0xEF) . chr(0xBB) . chr(0xBF);

$output = fopen('php://output', 'w');

// Encabezados con las dos columnas de fotos (URLs)
$encabezados = [
    'Usuario ID',
    'Nombre Completo',
    'Email',
    'Reto ID',
    'Estatura (cm)',
    'Peso Inicial (kg)',
    'Grasa Inicial (%)',
    'Músculo Inicial (%)',
    'Masa Inicial',
    'Foto Inicial (URL)',              
    'Peso Final (kg)',
    'Grasa Final (%)',
    'Músculo Final (%)',
    'Masa Final',
    'Foto Final (URL)',                 
    'Avance Grasa (%)',
    'Avance Músculo (%)',
    'Avance Grasa Visceral (%)',
    'Promedio Avance (%)',
];

fputcsv($output, $encabezados);


$base_url = 'http://localhost/cat21/';  


$sql = "
    SELECT 
        u.id AS usuario_id,
        CONCAT(u.nombre, ' ', u.apellido_paterno, ' ', COALESCE(u.apellido_materno, '')) AS nombre_completo,
        u.email,
        ds0.reto_id,
        ds0.estatura,
        ds0.peso AS peso_inicial,
        ds0.grasa AS grasa_inicial,
        ds0.musculo AS musculo_inicial,
        ds0.masa AS masa_inicial,
        ds0.image AS foto_inicial,
        ds3.peso AS peso_final,
        ds3.grasa AS grasa_final,
        ds3.musculo AS musculo_final,
        ds3.masa AS masa_final,
        ds3.image AS foto_final,
        ds3.avance_grasa,
        ds3.avance_musculo,
        ds3.avance_grasa_visceral,
        ds3.promedio_avance
    FROM usuarios u
    INNER JOIN datos_semanales ds0 ON u.id = ds0.usuario_id AND ds0.semana = 0
    INNER JOIN datos_semanales ds3 ON u.id = ds3.usuario_id AND ds3.semana = 3 
                                   AND ds3.reto_id = ds0.reto_id
    WHERE 1=1
";

$params = [];
if ($reto_id) {
    $sql .= " AND ds0.reto_id = :reto_id";
    $params[':reto_id'] = $reto_id;
}

$sql .= " ORDER BY COALESCE(ds3.promedio_avance, -999999) DESC, u.apellido_paterno, u.nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  
    $foto_inicial_url = $row['foto_inicial'] ? rtrim($base_url, '/') . '/Uploads/' . $row['foto_inicial'] : '';
    $foto_final_url   = $row['foto_final']   ? rtrim($base_url, '/') . '/Uploads/' . $row['foto_final']   : '';

   
    $avance_grasa          = $row['avance_grasa']          !== null ? number_format($row['avance_grasa'], 3, '.', '') : '';
    $avance_musculo        = $row['avance_musculo']        !== null ? number_format($row['avance_musculo'], 3, '.', '') : '';
    $avance_grasa_visceral = $row['avance_grasa_visceral'] !== null ? number_format($row['avance_grasa_visceral'], 3, '.', '') : '';
    $promedio_avance       = $row['promedio_avance']       !== null ? number_format($row['promedio_avance'], 3, '.', '') : '';

    $fila = [
        $row['usuario_id'],
        $row['nombre_completo'],
        $row['email'],
        $row['reto_id'],
        $row['estatura'],
        $row['peso_inicial'],
        $row['grasa_inicial'],
        $row['musculo_inicial'],
        $row['masa_inicial'],
        $foto_inicial_url,          
        $row['peso_final'],
        $row['grasa_final'],
        $row['musculo_final'],
        $row['masa_final'],
        $foto_final_url,           
        $avance_grasa,
        $avance_musculo,
        $avance_grasa_visceral,
        $promedio_avance,
    ];

    fputcsv($output, $fila);
}

fclose($output);
exit;