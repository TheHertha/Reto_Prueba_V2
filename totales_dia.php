<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

$fecha = isset($_GET['fecha']) ? trim($_GET['fecha']) : '';

if ($fecha !== '') {
  $st = $pdo->prepare("SELECT COALESCE(SUM(total),0) ingresos, COALESCE(SUM(cantidad),0) piezas
                       FROM fis_ventas WHERE DATE(created_at)=:f");
  $st->execute([':f'=>$fecha]);
  $f = $fecha;
} else {
  $st = $pdo->query("SELECT COALESCE(SUM(total),0) ingresos, COALESCE(SUM(cantidad),0) piezas
                     FROM fis_ventas WHERE DATE(created_at)=CURDATE()");
  $f = date('Y-m-d');
}
$tot = $st->fetch(PDO::FETCH_ASSOC) ?: ['ingresos'=>0,'piezas'=>0];

echo json_encode([
  'fecha'    => $f,
  'ingresos' => (float)$tot['ingresos'],
  'piezas'   => (int)$tot['piezas'],
], JSON_UNESCAPED_UNICODE);
