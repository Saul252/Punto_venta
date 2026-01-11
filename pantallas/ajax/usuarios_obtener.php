<?php
require_once __DIR__ . '/../ajax/conexion.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID no enviado']);
    exit;
}

$id = (int) $_GET['id'];

$sql = "
SELECT 
    id,
    nombre,
    usuario,
    rol_id,
    estado
FROM usuarios
WHERE id = ?
LIMIT 1
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$res = $stmt->get_result();

if ($res->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit;
}

header('Content-Type: application/json');
echo json_encode($res->fetch_assoc());
