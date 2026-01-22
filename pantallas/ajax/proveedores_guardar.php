<?php
require_once __DIR__ . '/../../conexion.php';


header('Content-Type: application/json');

try {

    $nombre    = trim($_POST['nombre'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $rfc       = trim($_POST['rfc'] ?? null);
    $email     = trim($_POST['email'] ?? null);
    $direccion = trim($_POST['direccion'] ?? null);

    if ($nombre === '') {
        throw new Exception('El nombre del proveedor es obligatorio');
    }

    $stmt = $conexion->prepare("
        INSERT INTO proveedores
        (nombre, telefono, rfc, email, direccion)
        VALUES (?,?,?,?,?)
    ");

    if (!$stmt) {
        throw new Exception('Error al preparar la consulta');
    }

    $stmt->bind_param(
        "sssss",
        $nombre,
        $telefono,
        $rfc,
        $email,
        $direccion
    );

    if (!$stmt->execute()) {
        throw new Exception('No se pudo guardar el proveedor');
    }

    echo json_encode([
        'ok'     => true,
        'msg'    => 'Proveedor agregado correctamente',
        'id'     => $stmt->insert_id,
        'nombre' => $nombre
    ]);
    exit;

} catch (Exception $e) {

    http_response_code(400);
    echo json_encode([
        'ok'  => false,
        'msg' => $e->getMessage()
    ]);
    exit;
}

