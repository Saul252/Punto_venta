<?php
session_start();
require '../conexion.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ===============================
   SWEET ALERT HELPERS
================================ */
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
        }).then(() => {
            window.location.href = '/punto/pantallas/caja.php';
        });
    </script>
    </body>
    </html>";
    exit;
}

function alertSuccess($msg) {
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
            title: 'Pago registrado',
            text: ".json_encode($msg).",
            confirmButtonText: 'Aceptar'
        }).then(() => {
            window.location.href = '/punto/pantallas/caja.php';
        });
    </script>
    </body>
    </html>";
    exit;
}

/* ===============================
   VALIDACIONES
================================ */
if (!isset($_SESSION['login'], $_SESSION['user_id'])) {
    alertError('Sesión inválida');
}

$venta_id    = intval($_POST['venta_id'] ?? 0);
$monto       = floatval($_POST['monto'] ?? 0);
$metodo_pago = $_POST['metodo_pago'] ?? '';

$usuario_id = (int)$_SESSION['user_id'];
$caja_id    = 2;

if ($venta_id <= 0 || $monto <= 0 || !$metodo_pago) {
    alertError('Datos incompletos');
}

/* ===============================
   TRANSACCIÓN
================================ */
$conexion->begin_transaction();

try {

    // Total venta
    $q = $conexion->prepare("SELECT total FROM ventas WHERE id=? FOR UPDATE");
    $q->bind_param("i", $venta_id);
    $q->execute();
    $total_venta = $q->get_result()->fetch_assoc()['total'];

    // Total pagado
    $q = $conexion->prepare("
        SELECT IFNULL(SUM(monto),0) total_pagado
        FROM pagos
        WHERE tipo='VENTA' AND referencia_id=?
    ");
    $q->bind_param("i", $venta_id);
    $q->execute();
    $total_pagado = $q->get_result()->fetch_assoc()['total_pagado'];

    $saldo = $total_venta - $total_pagado;

    if ($monto > $saldo) {
        throw new Exception('El monto excede el saldo pendiente');
    }

    // Insertar pago
    $ref = "VENTA #{$venta_id}";
    $stmt = $conexion->prepare("
        INSERT INTO pagos
        (tipo, referencia_id, caja_id, usuario_id, monto, metodo_pago, referencia)
        VALUES ('VENTA', ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "iiidss",
        $venta_id,
        $caja_id,
        $usuario_id,
        $monto,
        $metodo_pago,
        $ref
    );

    if (!$stmt->execute()) {
        throw new Exception('No se pudo guardar el pago');
    }

    // Recalcular saldo
    $q->execute();
    $nuevo_pagado = $q->get_result()->fetch_assoc()['total_pagado'];
    $nuevo_saldo  = $total_venta - $nuevo_pagado;

    // Cerrar venta
    if ($nuevo_saldo <= 0) {
        $up = $conexion->prepare("UPDATE ventas SET estado='CERRADA' WHERE id=?");
        $up->bind_param("i", $venta_id);
        $up->execute();
    }

    $conexion->commit();

    alertSuccess(
        $nuevo_saldo <= 0
        ? 'Pago completo. La venta fue cerrada.'
        : 'Pago registrado correctamente'
    );

} catch (Exception $e) {
    $conexion->rollback();
    alertError($e->getMessage());
}
