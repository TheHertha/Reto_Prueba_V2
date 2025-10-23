<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $producto_id = isset($_POST['producto_id']) ? intval($_POST['producto_id']) : 0;
  $cantidad    = isset($_POST['cantidad']) ? max(1, intval($_POST['cantidad'])) : 1;

  try {
    $pdo->beginTransaction();

    $st = $pdo->prepare("SELECT id, precio_unitario, stock FROM fis_productos WHERE id=:id FOR UPDATE");
    $st->execute([':id'=>$producto_id]);
    $prod = $st->fetch(PDO::FETCH_ASSOC);
    if (!$prod) { throw new Exception('Producto no encontrado'); }
    if ($prod['stock'] < $cantidad) { throw new Exception('Stock insuficiente'); }

    $nuevo = $prod['stock'] - $cantidad;
    $total = floatval($prod['precio_unitario']) * $cantidad;

    $st = $pdo->prepare("UPDATE fis_productos SET stock=:s WHERE id=:id");
    $st->execute([':s'=>$nuevo, ':id'=>$producto_id]);

    $st = $pdo->prepare("INSERT INTO fis_ventas (producto_id, cantidad, precio_unitario, total)
                         VALUES (:pid,:cant,:precio,:total)");
    $st->execute([
      ':pid'=>$producto_id,
      ':cant'=>$cantidad,
      ':precio'=>$prod['precio_unitario'],
      ':total'=>$total
    ]);

    $pdo->commit();
    header("Location: inventario.php");
  } catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo "Error: " . htmlspecialchars($e->getMessage());
  }
} else {
  header("Location: inventario.php");
}
