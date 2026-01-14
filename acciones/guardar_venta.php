<?php
session_start();
require "../conexion.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ========= SWEET ALERT ========= */
function alertError($msg) {
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: ".json_encode($msg).",
                confirmButtonText: 'Volver'
            }).then(() => history.back());
        </script>
    </body>
    </html>";
    exit;
}

function alertSuccess($venta_id, $estado) {
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Venta registrada',
                html: `<b>Venta #{$venta_id}</b><br>Estado: <b>{$estado}</b>`,
                confirmButtonText: 'Continuar'
            }).then(() => {
                window.location.href = '/punto/pantallas/ventas.php';
            });
        </script>
    </body>
    </html>";
    exit;
}

/* ========= VALIDACIONES ========= */
if (!isset($_SESSION['login'], $_SESSION['user_id'])) {
    alertError("Sesión inválida");
}

if (empty($_SESSION['carrito']) || empty($_SESSION['total'])) {
    alertError("Carrito vacío");
}

if (!isset($_POST['metodo_pago'], $_POST['monto_pago'])) {
    alertError("Datos de pago incompletos");
}

/* ========= DATOS ========= */
$usuario_id = (int)$_SESSION['user_id'];
$caja_id    = 2; // TODO: dinámico
$total      = (float)$_SESSION['total'];
$monto_pago = (float)$_POST['monto_pago'];
$metodo     = $_POST['metodo_pago'];
$carrito    = $_SESSION['carrito'];

$cliente_id = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
$requiereFactura = isset($_POST['requiere_factura']) ? 1 : 0;

if ($monto_pago < 0 || $monto_pago > $total) {
    alertError("Monto de pago inválido");
}

$estado_venta = ($monto_pago >= $total) ? 'CERRADA' : 'ABIERTA';

/* ========= DATOS FISCALES ========= */
$tipoFactura = null;
$nombre_receptor = null;
$rfc = null;
$razon_social = null;
$regimen_fiscal = null;
$uso_cfdi = null;
$codigo_postal = null;
$direccion_fiscal = null;

if ($requiereFactura) {

    if ($cliente_id) {
        // CLIENTE REGISTRADO
        $stmt = $conexion->prepare("
            SELECT
                nombre,
                rfc,
                razon_social,
                regimen_fiscal,
                uso_cfdi,
                codigo_postal,
                direccion_fiscal
            FROM clientes
            WHERE id = ?
        ");
        $stmt->bind_param("i", $cliente_id);
        $stmt->execute();
        $cli = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$cli) {
            alertError("Cliente no encontrado");
        }

        $tipoFactura      = 'publico';
        $nombre_receptor  = $cli['nombre'];
        $rfc              = $cli['rfc'];
        $razon_social     = $cli['razon_social'];
        $regimen_fiscal   = $cli['regimen_fiscal'];
        $uso_cfdi         = $cli['uso_cfdi'];
        $codigo_postal    = $cli['codigo_postal'];
        $direccion_fiscal = $cli['direccion_fiscal'];

    } else {
        // PÚBLICO EN GENERAL CON NOMBRE
        $nombreFactura = trim($_POST['nombre_factura_publico'] ?? '');

        if ($nombreFactura === '') {
            alertError("Debes capturar el nombre para la factura");
        }

        $empresa = $conexion->query("
            SELECT codigo_postal FROM empresa LIMIT 1
        ")->fetch_assoc();

        $tipoFactura      = 'nombre';
        $nombre_receptor  = $nombreFactura;
        $rfc              = 'XAXX010101000';
        $razon_social     = $nombreFactura;
        $regimen_fiscal   = '616';
        $uso_cfdi         = 'P01';
        $codigo_postal    = $empresa['codigo_postal'];
        $direccion_fiscal = null;
    }
}

/* ========= TRANSACCIÓN ========= */
$conexion->begin_transaction();

try {

    /* ========= VENTA ========= */
    $stmt = $conexion->prepare("
        INSERT INTO ventas (
            cliente_id,
            usuario_id,
            caja_id,
            total,
            total_pagado,
            metodo_pago,
            estado,
            requiere_factura,
            tipo_factura,
            nombre_receptor,
            rfc,
            razon_social,
            regimen_fiscal,
            uso_cfdi,
            codigo_postal,
            direccion_fiscal
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "iiiddssissssssss",
        $cliente_id,
        $usuario_id,
        $caja_id,
        $total,
        $monto_pago,
        $metodo,
        $estado_venta,
        $requiereFactura,
        $tipoFactura,
        $nombre_receptor,
        $rfc,
        $razon_social,
        $regimen_fiscal,
        $uso_cfdi,
        $codigo_postal,
        $direccion_fiscal
    );

    if (!$stmt->execute()) {
        throw new Exception("No se pudo guardar la venta");
    }

    $venta_id = $conexion->insert_id;
    $stmt->close();

    /* ========= DETALLE + STOCK ========= */
    $stmtDet = $conexion->prepare("
        INSERT INTO venta_detalle
        (venta_id, producto_id, cantidad, precio, subtotal)
        VALUES (?,?,?,?,?)
    ");

    $stmtStock = $conexion->prepare("
        UPDATE productos
        SET stock = stock - ?
        WHERE id = ? AND stock >= ?
    ");

    foreach ($carrito as $item) {

        $producto_id = (int)$item['id'];
        $cantidad    = (float)$item['cantidad'];
        $precio      = (float)$item['precio'];
        $subtotal    = $cantidad * $precio;

        $stmtDet->bind_param("iiddd", $venta_id, $producto_id, $cantidad, $precio, $subtotal);
        if (!$stmtDet->execute()) {
            throw new Exception("Error al guardar detalle");
        }

        $stmtStock->bind_param("did", $cantidad, $producto_id, $cantidad);
        if (!$stmtStock->execute() || $stmtStock->affected_rows === 0) {
            throw new Exception("Stock insuficiente (producto ID $producto_id)");
        }
    }

    $stmtDet->close();
    $stmtStock->close();

    /* ========= PAGOS / CAJA ========= */
    if ($monto_pago > 0) {

        $stmtVP = $conexion->prepare("
            INSERT INTO ventas_pagos
            (venta_id, caja_id, usuario_id, monto, metodo_pago)
            VALUES (?,?,?,?,?)
        ");
        $stmtVP->bind_param("iiids", $venta_id, $caja_id, $usuario_id, $monto_pago, $metodo);
        $stmtVP->execute();
        $stmtVP->close();

        $stmtPago = $conexion->prepare("
            INSERT INTO pagos
            (tipo, referencia_id, caja_id, usuario_id, monto, metodo_pago, referencia)
            VALUES ('VENTA', ?, ?, ?, ?, ?, ?)
        ");
        $referencia = "Venta #{$venta_id}";
        $stmtPago->bind_param("iiidss", $venta_id, $caja_id, $usuario_id, $monto_pago, $metodo, $referencia);
        $stmtPago->execute();
        $stmtPago->close();

        $stmtMov = $conexion->prepare("
            INSERT INTO movimientos_caja
            (caja_id, tipo, descripcion, monto)
            VALUES (?, 'INGRESO', ?, ?)
        ");
        $stmtMov->bind_param("isd", $caja_id, $referencia, $monto_pago);
        $stmtMov->execute();
        $stmtMov->close();
    }

    $conexion->commit();
    unset($_SESSION['carrito'], $_SESSION['total']);

    alertSuccess($venta_id, $estado_venta);

} catch (Exception $e) {
    $conexion->rollback();
    alertError($e->getMessage());
}
