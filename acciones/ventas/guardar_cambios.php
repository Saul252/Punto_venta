<?php
require "../../conexion.php";
require_once __DIR__ . "/../../includes/auth.php";
protegerPagina();

header('Content-Type: application/json');

/* ================= VALIDACIÓN ================= */
$venta_id = (int)($_POST['venta_id'] ?? 0);
if ($venta_id <= 0) {
    echo json_encode(['ok'=>false,'msg'=>'Venta inválida']);
    exit;
}

/* ================= DATOS GENERALES ================= */
$cliente_id = ($_POST['cliente_id'] !== '') ? (int)$_POST['cliente_id'] : null;
$requiere_factura = isset($_POST['requiere_factura']) ? 1 : 0;

$tipo_factura = $cliente_id ? 'publico' : 'nombre';
$nombre_receptor  = $_POST['nombre_factura_publico'] ?? null;

$rfc              = $_POST['rfc'] ?? null;
$razon_social     = $_POST['razon_social'] ?? null;
$regimen_fiscal   = $_POST['regimen_fiscal'] ?? null;
$uso_cfdi         = $_POST['uso_cfdi'] ?? null;
$codigo_postal    = $_POST['codigo_postal'] ?? null;
$direccion_fiscal = $_POST['direccion_fiscal'] ?? null;

/* ================= PRODUCTOS ================= */
$editar_productos = isset($_POST['productos']['id']);

$conexion->begin_transaction();

try {

    /* ========= BLOQUEAR VENTA ========= */
    $venta = $conexion->query("
        SELECT total, total_pagado
        FROM ventas
        WHERE id = $venta_id
        FOR UPDATE
    ")->fetch_assoc();

    if (!$venta) {
        throw new Exception("Venta no encontrada");
    }

    $total_actual  = (float)$venta['total'];
    $total_pagado  = (float)$venta['total_pagado'];
    $total_nuevo   = $total_actual;

    /* =====================================================
       👉 SI SE EDITAN PRODUCTOS
    ===================================================== */
    if ($editar_productos) {

        $ids     = $_POST['productos']['id'] ?? [];
        $cants   = $_POST['productos']['cantidad'] ?? [];
        $precios = $_POST['productos']['precio'] ?? [];

        $total_nuevo = 0;

        for ($i = 0; $i < count($ids); $i++) {
            $pid    = (int)$ids[$i];
            $cant   = (float)$cants[$i];
            $precio = (float)$precios[$i];

            if ($pid <= 0 || $cant <= 0) continue;
            $total_nuevo += $cant * $precio;
        }

        /* ===== REEMPLAZAR DETALLE ===== */
        $conexion->query("DELETE FROM venta_detalle WHERE venta_id = $venta_id");

        $ins = $conexion->prepare("
            INSERT INTO venta_detalle
            (venta_id, producto_id, cantidad, precio, subtotal, iva)
            VALUES (?,?,?,?,?,0)
        ");

        for ($i = 0; $i < count($ids); $i++) {
            $pid    = (int)$ids[$i];
            $cant   = (float)$cants[$i];
            $precio = (float)$precios[$i];

            if ($pid <= 0 || $cant <= 0) continue;

            $sub = $cant * $precio;
            $ins->bind_param("iiddd", $venta_id, $pid, $cant, $precio, $sub);
            $ins->execute();
        }
        $ins->close();

        /* ===== ACTUALIZAR TOTAL ===== */
        $conexion->query("
            UPDATE ventas
            SET total = $total_nuevo
            WHERE id = $venta_id
        ");
    }

    /* =====================================================
       👉 CALCULAR SALDO REAL Y ESTADO
    ===================================================== */
    $res = $conexion->query("
        SELECT total, total_pagado
        FROM ventas
        WHERE id = $venta_id
    ")->fetch_assoc();

    $total_final  = (float)$res['total'];
    $pagado_final = (float)$res['total_pagado'];

    $saldo = $total_final - $pagado_final;

    $estado_nuevo = ($saldo == 0) ? 'CERRADA' : 'ABIERTA';

    /* =====================================================
       👉 ACTUALIZAR DATOS GENERALES
    ===================================================== */
    $upd = $conexion->prepare("
        UPDATE ventas SET
            cliente_id=?,
            requiere_factura=?,
            tipo_factura=?,
            nombre_receptor=?,
            rfc=?,
            razon_social=?,
            regimen_fiscal=?,
            uso_cfdi=?,
            codigo_postal=?,
            direccion_fiscal=?,
            estado=?
        WHERE id=?
    ");

    $upd->bind_param(
        "iisssssssssi",
        $cliente_id,
        $requiere_factura,
        $tipo_factura,
        $nombre_receptor,
        $rfc,
        $razon_social,
        $regimen_fiscal,
        $uso_cfdi,
        $codigo_postal,
        $direccion_fiscal,
        $estado_nuevo,
        $venta_id
    );

    $upd->execute();
    $upd->close();

    $conexion->commit();

    echo json_encode([
        'ok'     => true,
        'msg'    => 'Venta actualizada correctamente',
        'estado' => $estado_nuevo,
        'saldo'  => $saldo
    ]);

} catch (Throwable $e) {

    $conexion->rollback();

    echo json_encode([
        'ok'  => false,
        'msg' => 'Error al guardar cambios',
        'err' => $e->getMessage()
    ]);
}
