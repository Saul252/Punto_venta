<?php
require "../conexion.php";

header('Content-Type: application/json');

try {

    if (empty($_POST['id'])) {
        throw new Exception("ID de producto no recibido");
    }

    $id = (int)$_POST['id'];

    /* 🔒 VALIDAR QUE EXISTA */
    $check = $conexion->prepare("SELECT id FROM productos WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        throw new Exception("El producto no existe");
    }
    $check->close();

    /* 🚫 VALIDAR QUE NO TENGA VENTAS */
    $checkVentas = $conexion->prepare("
        SELECT 1 FROM venta_detalle WHERE producto_id = ? LIMIT 1
    ");
    $checkVentas->bind_param("i", $id);
    $checkVentas->execute();
    $checkVentas->store_result();

    if ($checkVentas->num_rows > 0) {
        throw new Exception("No se puede eliminar: el producto tiene ventas registradas");
    }
    $checkVentas->close();

    /* 🗑️ ELIMINAR */
    $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        throw new Exception("No se pudo eliminar el producto");
    }

    $stmt->close();

    echo json_encode([
        'ok' => true,
        'msg' => 'Producto eliminado correctamente'
    ]);

} catch (Exception $e) {

    echo json_encode([
        'ok' => false,
        'msg' => $e->getMessage()
    ]);
}
