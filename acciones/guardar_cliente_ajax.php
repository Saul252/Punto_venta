<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require "../conexion.php";

header('Content-Type: application/json');

/* ================= VALIDAR ================= */
$nombre = trim($_POST['nombre'] ?? '');
$id     = $_POST['id'] ?? '';

if ($nombre === '') {
    echo json_encode([
        'ok' => false,
        'msg' => 'El nombre del cliente es obligatorio'
    ]);
    exit;
}

/* ================= CAMPOS ================= */
$rfc               = $_POST['rfc'] ?? null;
$razon_social      = $_POST['razon_social'] ?? null;
$documento         = $_POST['documento'] ?? null;
$telefono          = $_POST['telefono'] ?? null;
$email             = $_POST['email'] ?? null;
$direccion_fiscal  = $_POST['direccion_fiscal'] ?? null;
$codigo_postal     = $_POST['codigo_postal'] ?? null;
$regimen_fiscal    = $_POST['regimen_fiscal'] ?? null;
$uso_cfdi          = $_POST['uso_cfdi'] ?? null;

/* ================= EDITAR ================= */
if (!empty($id)) {

    $stmt = $conexion->prepare("
        UPDATE clientes SET
            nombre = ?,
            rfc = ?,
            razon_social = ?,
            documento = ?,
            telefono = ?,
            email = ?,
            direccion_fiscal = ?,
            codigo_postal = ?,
            regimen_fiscal = ?,
            uso_cfdi = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssssssssssi",
        $nombre,
        $rfc,
        $razon_social,
        $documento,
        $telefono,
        $email,
        $direccion_fiscal,
        $codigo_postal,
        $regimen_fiscal,
        $uso_cfdi,
        $id
    );

    if ($stmt->execute()) {
        echo json_encode([
            'ok' => true,
            'msg' => 'Cliente actualizado correctamente'
        ]);
    } else {
        echo json_encode([
            'ok' => false,
            'msg' => 'Error al actualizar el cliente'
        ]);
    }

    $stmt->close();
    exit;
}

/* ================= NUEVO ================= */
$stmt = $conexion->prepare("
    INSERT INTO clientes
    (nombre, rfc, razon_social, documento, telefono, email,
     direccion_fiscal, codigo_postal, regimen_fiscal, uso_cfdi)
    VALUES (?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
    "ssssssssss",
    $nombre,
    $rfc,
    $razon_social,
    $documento,
    $telefono,
    $email,
    $direccion_fiscal,
    $codigo_postal,
    $regimen_fiscal,
    $uso_cfdi
);

if ($stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'msg' => 'Cliente registrado correctamente'
    ]);
} else {
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al registrar el cliente'
    ]);
}

$stmt->close();
