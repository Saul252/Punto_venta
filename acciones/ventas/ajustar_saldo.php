<?php
session_start();
require "../../conexion.php";
require_once __DIR__ . "/../../includes/auth.php";

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

function alertSuccess($venta_id) {
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
                title: 'Saldo ajustado',
                html: '<b>Venta #{$venta_id}</b><br>Saldo corregido correctamente',
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

if (empty($_POST['venta_id'])) {
    alertError("Venta no especificada");
}

$venta_id   = (int)$_POST['venta_id'];
$usuario_id = (int)$_SESSION['user_id'];

/* ========= VENTA + PAGOS ========= */
$stmt = $conexion->prepare("
    SELECT 
        v.id,
        v.caja_id,
        v.total,
        IFNULL(SUM(p.monto), 0) AS total_pagado_real
    FROM ventas v
    LEFT JOIN pagos p 
        ON p.tipo = 'VENTA'
       AND p.referencia_id = v.id
       AND p.estado = 'APLICADO'
    WHERE v.id = ?
    GROUP BY v.id
");
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$venta) {
    alertError("Venta no encontrada");
}

/* ========= SALDO REAL ========= */
$saldo = $venta['total'] - $venta['total_pagado_real'];

if ($saldo >= 0) {
    alertError("Esta venta no tiene saldo a favor");
}

$monto_devolver = abs($saldo);
$monto_negativo = -$monto_devolver;
$caja_id = (int)$venta['caja_id'];

/* ========= TRANSACCIÓN ========= */
$conexion->begin_transaction();

try {

    /* 1️⃣ AJUSTAR VENTA */
    $stmt = $conexion->prepare("
        UPDATE ventas
        SET total_pagado = total,
            estado = 'CERRADA'
        WHERE id = ?
    ");
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    $stmt->close();

    /* 2️⃣ ventas_pagos (NEGATIVO) */
    $stmtVP = $conexion->prepare("
        INSERT INTO ventas_pagos
        (venta_id, caja_id, usuario_id, monto, metodo_pago)
        VALUES (?, ?, ?, ?, 'EFECTIVO')
    ");
    $stmtVP->bind_param(
        "iiid",
        $venta_id,
        $caja_id,
        $usuario_id,
        $monto_negativo
    );
    $stmtVP->execute();
    $stmtVP->close();

    /* 3️⃣ pagos (NEGATIVO) */
    $referencia = "Devolución Venta #{$venta_id}";
    $stmtPago = $conexion->prepare("
        INSERT INTO pagos
        (tipo, referencia_id, caja_id, usuario_id, monto, metodo_pago, referencia)
        VALUES ('VENTA', ?, ?, ?, ?, 'EFECTIVO', ?)
    ");
    $stmtPago->bind_param(
        "iiids",
        $venta_id,
        $caja_id,
        $usuario_id,
        $monto_negativo,
        $referencia
    );
    $stmtPago->execute();
    $stmtPago->close();

    /* 4️⃣ CAJA (EGRESO POSITIVO) */
    $stmtMov = $conexion->prepare("
        INSERT INTO movimientos_caja
        (caja_id, tipo, descripcion, monto)
        VALUES (?, 'EGRESO', ?, ?)
    ");
    $stmtMov->bind_param(
        "isd",
        $caja_id,
        $referencia,
        $monto_devolver
    );
    $stmtMov->execute();
    $stmtMov->close();

    $conexion->commit();
    alertSuccess($venta_id);

} catch (Exception $e) {
    $conexion->rollback();
    alertError($e->getMessage());
}
