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

if ($monto_pago < 0 || $monto_pago > $total) {
    alertError("Monto de pago inválido");
}

$estado_venta = ($monto_pago >= $total) ? 'CERRADA' : 'ABIERTA';

/* ========= TRANSACCIÓN ========= */
$conexion->begin_transaction();

try {

    /* ========= VENTA ========= */
    $stmt = $conexion->prepare("
        INSERT INTO ventas
        (cliente_id, usuario_id, caja_id, total, total_pagado, metodo_pago, estado)
        VALUES (?,?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        "iiiddss",
        $cliente_id,
        $usuario_id,
        $caja_id,
        $total,
        $monto_pago,
        $metodo,
        $estado_venta
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

    /* ========= PAGOS ========= */
    if ($monto_pago > 0) {

        /* ventas_pagos */
        $stmtVP = $conexion->prepare("
            INSERT INTO ventas_pagos
            (venta_id, caja_id, usuario_id, monto, metodo_pago)
            VALUES (?,?,?,?,?)
        ");
        $stmtVP->bind_param("iiids", $venta_id, $caja_id, $usuario_id, $monto_pago, $metodo);
        if (!$stmtVP->execute()) {
            throw new Exception("Error en ventas_pagos");
        }
        $stmtVP->close();

        /* pagos (tabla general) */
        $stmtPago = $conexion->prepare("
            INSERT INTO pagos
            (tipo, referencia_id, caja_id, usuario_id, monto, metodo_pago, referencia)
            VALUES ('VENTA', ?, ?, ?, ?, ?, ?)
        ");
        $referencia = "Venta #{$venta_id}";
        $stmtPago->bind_param(
            "iiidss",
            $venta_id,
            $caja_id,
            $usuario_id,
            $monto_pago,
            $metodo,
            $referencia
        );

        if (!$stmtPago->execute()) {
            throw new Exception("Error al registrar en pagos");
        }
        $stmtPago->close();

        /* movimiento caja */
        $stmtMov = $conexion->prepare("
            INSERT INTO movimientos_caja
            (caja_id, tipo, descripcion, monto)
            VALUES (?, 'INGRESO', ?, ?)
        ");
        $stmtMov->bind_param("isd", $caja_id, $referencia, $monto_pago);
        if (!$stmtMov->execute()) {
            throw new Exception("Error en movimiento de caja");
        }
        $stmtMov->close();
    }

    /* ========= CONFIRMAR ========= */
    $conexion->commit();
    unset($_SESSION['carrito'], $_SESSION['total']);

    alertSuccess($venta_id, $estado_venta);

} catch (Exception $e) {
    $conexion->rollback();
    alertError($e->getMessage());
}
