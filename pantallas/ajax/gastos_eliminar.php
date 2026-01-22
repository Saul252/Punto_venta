<?php
session_start();
require_once '../../conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok'=>false,'msg'=>'Sesión inválida']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$gasto_id = (int)($data['id'] ?? 0);

if ($gasto_id <= 0) {
    echo json_encode(['ok'=>false,'msg'=>'ID inválido']);
    exit;
}

try {

    $stmt = $conexion->prepare("
        DELETE FROM gastos
        WHERE id = ?
    ");

    $stmt->bind_param("i", $gasto_id);

    if (!$stmt->execute()) {
        throw new Exception('No se pudo eliminar el gasto');
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception('El gasto no existe');
    }

    echo json_encode(['ok'=>true]);

} catch (Exception $e) {

    echo json_encode([
        'ok' => false,
        'msg' => $e->getMessage()
    ]);
}
