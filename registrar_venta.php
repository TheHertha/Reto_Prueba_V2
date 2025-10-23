<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_id = intval($_POST['producto_id'] ?? 0);
    $cantidad = max(1, intval($_POST['cantidad'] ?? 1));

    $mysqli->begin_transaction();
    try {
        $stmt = $mysqli->prepare("SELECT id, precio_unitario, stock FROM fis_productos WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$prod) throw new Exception('Producto no encontrado');
        if ($prod['stock'] < $cantidad) throw new Exception('Stock insuficiente');

        $nuevo_stock = $prod['stock'] - $cantidad;
        $total = $prod['precio_unitario'] * $cantidad;

        $stmt = $mysqli->prepare("UPDATE fis_productos SET stock = ? WHERE id = ?");
        $stmt->bind_param("ii", $nuevo_stock, $producto_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $mysqli->prepare("INSERT INTO fis_ventas (producto_id, cantidad, precio_unitario, total) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iidd", $producto_id, $cantidad, $prod['precio_unitario'], $total);
        $stmt->execute();
        $stmt->close();

        $mysqli->commit();
        header("Location: inventario.php");
    } catch (Exception $e) {
        $mysqli->rollback();
        echo "Error: " . htmlspecialchars($e->getMessage());
    }
} else {
    header("Location: inventario.php");
}
