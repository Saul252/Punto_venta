<?php
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// ✅ VARIABLE CORRECTA
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'ok' => false,
        'msg' => 'Sesión inválida'
    ]);
    exit;
}

$usuario_id = (int) $_SESSION['user_id'];

// 📥 DATOS
$proveedor_id = !empty($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null;
$concepto     = trim($_POST['concepto'] ?? '');
$descripcion  = trim($_POST['descripcion'] ?? '');
$monto        = (float) $_POST['monto'];
$metodo_pago  = $_POST['metodo_pago'];
$fecha        = date('Y-m-d');

// 🔒 VALIDACIONES
if ($concepto === '' || $monto <= 0) {
    echo json_encode([
        'ok' => false,
        'msg' => 'Datos incompletos'
    ]);
    exit;
}

// 🧾 INSERT REAL (SEGÚN TU TABLA)
$stmt = $conexion->prepare("
    INSERT INTO gastos (
        proveedor_id,
        usuario_id,
        concepto,
        descripcion,
        monto,
        metodo_pago,
        fecha
    ) VALUES (?,?,?,?,?,?,?)
");

$stmt->bind_param(
    "iissdss",
    $proveedor_id,
    $usuario_id,
    $concepto,
    $descripcion,
    $monto,
    $metodo_pago,
    $fecha
);

if ($stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'msg' => 'Gasto registrado correctamente'
    ]);
} else {
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al guardar gasto',
        'error' => $stmt->error
    ]);
}
