<?php
require_once __DIR__ . '/db.php';

if (!isset($_FILES['archivo'])) die('No se recibió archivo.');
$tmp = $_FILES['archivo']['tmp_name'];
$ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));

$datos = [];
if ($ext === 'csv') {
    if (($h = fopen($tmp, 'r')) !== false) {
        $headers = fgetcsv($h);
        $map = array_change_key_case(array_flip($headers), CASE_LOWER);
        while (($row = fgetcsv($h)) !== false) {
            $sku = $row[$map['sku']] ?? '';
            $nombre = $row[$map['nombre']] ?? '';
            $precio = $row[$map['precio']] ?? 0;
            $stock = $row[$map['stock']] ?? 0;
            $datos[] = compact('sku','nombre','precio','stock');
        }
        fclose($h);
    }
}

$stmt = $mysqli->prepare("INSERT INTO fis_productos (sku, nombre, precio_unitario, stock)
VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), precio_unitario=VALUES(precio_unitario), stock=VALUES(stock)");
foreach ($datos as $d) {
    $stmt->bind_param("ssdi", $d['sku'], $d['nombre'], $d['precio'], $d['stock']);
    $stmt->execute();
}
$stmt->close();

header("Location: inventario.php");
