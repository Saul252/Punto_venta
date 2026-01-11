<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "../conexion.php";
header('Content-Type: application/json');

try {

    if (empty($_POST)) {
        throw new Exception("No llegaron datos POST");
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $precio = $_POST['precio_venta'] ?? null;

    if ($nombre === '' || $precio === null) {
        throw new Exception("Nombre y precio obligatorios");
    }

    $codigo = $_POST['codigo'] ?? null;
    $precio_compra = $_POST['precio_compra'] ?? null;
    $stock = $_POST['stock'] ?? 0;
    $unidad = $_POST['unidad_medida'] ?? 'PIEZA';

    $categoria_id = $_POST['categoria_id'] ?: null;
    $categoria_nueva = trim($_POST['categoria_nueva'] ?? '');

    $conexion->begin_transaction();

    /* ===== CATEGORÍA NUEVA ===== */
    if ($categoria_nueva !== '') {
        $stmt = $conexion->prepare(
            "SELECT id FROM categorias WHERE nombre = ?"
        );
        $stmt->bind_param("s", $categoria_nueva);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $categoria_id = $res->fetch_assoc()['id'];
        } else {
            $stmt = $conexion->prepare(
                "INSERT INTO categorias (nombre) VALUES (?)"
            );
            $stmt->bind_param("s", $categoria_nueva);
            $stmt->execute();
            $categoria_id = $conexion->insert_id;
        }
    }

    /* ===== INSERT PRODUCTO ===== */
    $stmt = $conexion->prepare("
        INSERT INTO productos
        (codigo, nombre, precio_compra, precio_venta, stock, categoria_id, unidad_medida)
        VALUES (?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "ssdddis",
        $codigo,
        $nombre,
        $precio_compra,
        $precio,
        $stock,
        $categoria_id,
        $unidad
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $conexion->commit();

    echo json_encode([
        'ok' => true,
        'msg' => 'Producto guardado correctamente'
    ]);
    exit;

} catch (Exception $e) {

    if ($conexion->errno === 0) {
        @$conexion->rollback();
    }

    echo json_encode([
        'ok' => false,
        'msg' => $e->getMessage()
    ]);
    exit;
}
