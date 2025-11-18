<?php
// export_datos_semanales.php

// 1) Incluir tu conexión PDO
require_once 'config.php';  // Ajusta la ruta según tu proyecto

// 2) Encabezados para forzar descarga como archivo de Excel
$filename = 'datos_semanales_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// 3) Abrir salida estándar como archivo
$output = fopen('php://output', 'w');

// 4) Agregar BOM para que Excel reconozca UTF-8 (acentos, etc.)
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// 5) Escribir fila de encabezados (columnas)
$encabezados = [
    'id',
    'usuario_id',
    'reto_id',
    'semana',
    'estatura',
    'peso',
    'masa',
    'grasa',
    'musculo',
    'image',
    'created_at'
];

fputcsv($output, $encabezados);

// 6) Obtener datos de la tabla
$sql = "SELECT id, usuario_id, reto_id, semana, estatura, peso, masa, grasa, musculo, image, created_at
        FROM datos_semanales
        ORDER BY usuario_id, reto_id, semana";

$stmt = $pdo->query($sql);

// 7) Escribir cada registro como una fila del CSV
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

// 8) Cerrar y terminar
fclose($output);
exit;
