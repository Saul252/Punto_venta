<?php
session_start();
require "../conexion.php";

if (!isset($_POST['venta_id'])) {
    die("ID inválido");
}

$venta_id = (int)$_POST['venta_id'];

$conexion->begin_transaction();

try {

    /* 1️⃣ Revertir stock */
    $det = $conexion->query("
        SELECT producto_id, cantidad
        FROM venta_detalle
        WHERE venta_id = $venta_id
    ");

    while ($d = $det->fetch_assoc()) {
        $conexion->query("
            UPDATE productos
            SET stock = stock + {$d['cantidad']}
            WHERE id = {$d['producto_id']}
        ");
    }

    /* 2️⃣ Eliminar movimientos de caja */
    $conexion->query("
        DELETE FROM movimientos_caja
        WHERE descripcion = 'Venta #{$venta_id}'
    ");

    /* 3️⃣ Eliminar pagos */
    $conexion->query("
        DELETE FROM pagos
        WHERE tipo = 'VENTA' AND referencia_id = $venta_id
    ");

    /* 4️⃣ Eliminar ventas_pagos */
    $conexion->query("
        DELETE FROM ventas_pagos WHERE venta_id = $venta_id
    ");

    /* 5️⃣ Eliminar detalle */
    $conexion->query("
        DELETE FROM venta_detalle WHERE venta_id = $venta_id
    ");

    /* 6️⃣ Eliminar facturas */
    $conexion->query("
        DELETE FROM facturas WHERE venta_id = $venta_id
    ");

    /* 7️⃣ Eliminar venta */
    $conexion->query("
        DELETE FROM ventas WHERE id = $venta_id
    ");

    $conexion->commit();
    echo "OK";

} catch (Exception $e) {
    $conexion->rollback();
    echo "ERROR: " . $e->getMessage();
}
