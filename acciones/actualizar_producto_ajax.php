<?php
require "../conexion.php";

header('Content-Type: application/json');

try {

    if (empty($_POST['id'])) {
        throw new Exception("ID de producto no recibido");
    }

    $id             = (int)$_POST['id'];
    $codigo         = $_POST['codigo'] ?? null;
    $nombre         = trim($_POST['nombre'] ?? '');
    $precio_compra  = $_POST['precio_compra'] ?? null;
    $precio_venta   = $_POST['precio_venta'] ?? null;
    $stock          = $_POST['stock'] ?? 0;
    $unidad         = $_POST['unidad_medida'] ?? 'PIEZA';
    $categoria_id   = $_POST['categoria_id'] ?: null;

    if ($nombre === '' || $precio_venta === null) {
        throw new Exception("Nombre y precio de venta son obligatorios");
    }

    $stmt = $conexion->prepare("
        UPDATE productos SET
            codigo = ?,
            nombre = ?,
            precio_compra = ?,
            precio_venta = ?,
            stock = ?,
            unidad_medida = ?,
            categoria_id = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssdddssi",
        $codigo,
        $nombre,
        $precio_compra,
        $precio_venta,
        $stock,
        $unidad,
        $categoria_id,
        $id
    );

    if (!$stmt->execute()) {
        throw new Exception("No se pudo actualizar el producto");
    }

    $stmt->close();

    echo json_encode([
        'ok' => true,
        'msg' => 'Producto actualizado correctamente'
    ]);

} catch (Exception $e) {

    echo json_encode([
        'ok' => false,
        'msg' => $e->getMessage()
    ]);
}
