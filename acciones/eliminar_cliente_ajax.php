<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require "../conexion.php";

header('Content-Type: application/json');

/* ========= VALIDACIÓN ========= */
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode([
        'ok' => false,
        'msg' => 'ID de cliente inválido'
    ]);
    exit;
}

$cliente_id = (int)$_POST['id'];

/* ========= VERIFICAR VENTAS ========= */
$check = $conexion->prepare("
    SELECT COUNT(*) 
    FROM ventas 
    WHERE cliente_id = ?
");
$check->bind_param("i", $cliente_id);
$check->execute();
$check->bind_result($totalVentas);
$check->fetch();
$check->close();

if ($totalVentas > 0) {
    echo json_encode([
        'ok' => false,
        'msg' => 'No se puede eliminar el cliente porque tiene ventas registradas'
    ]);
    exit;
}

/* ========= ELIMINAR ========= */
$stmt = $conexion->prepare("DELETE FROM clientes WHERE id = ?");
$stmt->bind_param("i", $cliente_id);

if ($stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'msg' => 'Cliente eliminado correctamente'
    ]);
} else {
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al eliminar el cliente'
    ]);
}

$stmt->close();
