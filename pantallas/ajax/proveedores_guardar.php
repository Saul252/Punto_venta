<?php
require_once __DIR__ . '/../../conexion.php';

$nombre = $_POST['nombre'] ?? '';

if(!$nombre){
    echo json_encode(['ok'=>false,'msg'=>'Nombre requerido']);
    exit;
}

$stmt = $conexion->prepare("INSERT INTO proveedores (nombre, telefono) VALUES (?,?)");
$stmt->bind_param("ss", $nombre, $_POST['telefono']);
$stmt->execute();

echo json_encode([
    'ok'=>true,
    'id'=>$stmt->insert_id,
    'nombre'=>$nombre
]);
