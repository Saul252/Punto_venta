<?php
session_start();
require_once __DIR__ . '/../conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    echo json_encode(['ok' => false, 'msg' => 'Sesión inválida']);
    exit;
}

$nombre = $_POST['nombre'] ?? '';
$razon_social = $_POST['razon_social'] ?? '';
$rfc = $_POST['rfc'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$email = $_POST['email'] ?? '';
$direccion = $_POST['direccion_fiscal'] ?? '';
$cp = $_POST['codigo_postal'] ?? '';
$regimen = $_POST['regimen_fiscal'] ?? '';
$uso_cfdi = $_POST['uso_cfdi'] ?? '';

if (!$nombre || !$razon_social || !$rfc || !$cp || !$regimen || !$uso_cfdi) {
    echo json_encode(['ok' => false, 'msg' => 'Faltan datos obligatorios']);
    exit;
}

$stmt = $conexion->prepare("
    INSERT INTO clientes
    (nombre, razon_social, rfc, telefono, email, direccion_fiscal,
     codigo_postal, regimen_fiscal, uso_cfdi)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssssssss",
    $nombre,
    $razon_social,
    $rfc,
    $telefono,
    $email,
    $direccion,
    $cp,
    $regimen,
    $uso_cfdi
);

if ($stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'id' => $stmt->insert_id,
        'nombre' => $razon_social
    ]);
} else {
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al guardar cliente'
    ]);
}
