<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require "../conexion.php";
header('Content-Type: application/json');

/* ================= CAMPOS ================= */
$id               = $_POST['id'] ?? '';
$nombre           = trim($_POST['nombre'] ?? '');
$rfc              = $_POST['rfc'] ?? '';
$razon_social     = $_POST['razon_social'] ?? '';
$documento        = $_POST['documento'] ?? '';
$telefono         = $_POST['telefono'] ?? '';
$email            = $_POST['email'] ?? '';
$direccion_fiscal = $_POST['direccion_fiscal'] ?? '';
$codigo_postal    = $_POST['codigo_postal'] ?? '';
$regimen_fiscal   = $_POST['regimen_fiscal'] ?? '';
$uso_cfdi         = $_POST['uso_cfdi'] ?? '';

/* ================= VALIDAR ================= */
if ($nombre === '') {
    echo json_encode([
        'ok' => false,
        'msg' => 'El nombre del cliente es obligatorio'
    ]);
    exit;
}

if (!$codigo_postal || !$regimen_fiscal || !$uso_cfdi) {
    echo json_encode([
        'ok' => false,
        'msg' => 'Faltan datos fiscales obligatorios'
    ]);
    exit;
}

/* ===================================================
   ===================== EDITAR ======================
   =================================================== */
if ($id !== '') {

    if (!is_numeric($id)) {
        echo json_encode([
            'ok' => false,
            'msg' => 'ID inválido'
        ]);
        exit;
    }

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
        LIMIT 1
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
            'msg' => 'Cliente actualizado correctamente',
            'id' => $id,
            'nombre' => $razon_social ?: $nombre
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

/* ===================================================
   ===================== NUEVO =======================
   =================================================== */
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
        'msg' => 'Cliente registrado correctamente',
        'id' => $stmt->insert_id,
        'nombre' => $razon_social ?: $nombre
    ]);
} else {
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al registrar el cliente'
    ]);
}

$stmt->close();
