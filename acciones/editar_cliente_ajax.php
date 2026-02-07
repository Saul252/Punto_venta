<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require "../conexion.php";
header('Content-Type: application/json');

/* ================= VALIDAR ID ================= */
$id = $_POST['id'] ?? $_GET['id'] ?? '';

if ($id === '' || !is_numeric($id)) {
    echo json_encode([
        'ok' => false,
        'msg' => 'ID de cliente inválido'
    ]);
    exit;
}

/* ================= DETECTAR UPDATE ================= */
$esUpdate = isset($_POST['nombre']);

/* ===================================================
   =============== ACTUALIZAR CLIENTE ================
   =================================================== */
if ($esUpdate) {

    $nombre        = $_POST['nombre'] ?? '';
    $razon_social  = $_POST['razon_social'] ?? '';
    $rfc           = $_POST['rfc'] ?? '';
    $telefono      = $_POST['telefono'] ?? '';
    $email         = $_POST['email'] ?? '';
    $direccion     = $_POST['direccion_fiscal'] ?? '';
    $cp            = $_POST['codigo_postal'] ?? '';
    $regimen       = $_POST['regimen_fiscal'] ?? '';
    $uso_cfdi      = $_POST['uso_cfdi'] ?? '';

    if (!$nombre || !$razon_social || !$rfc || !$cp || !$regimen || !$uso_cfdi) {
        echo json_encode([
            'ok' => false,
            'msg' => 'Faltan datos obligatorios'
        ]);
        exit;
    }

    $stmt = $conexion->prepare("
        UPDATE clientes SET
            nombre = ?,
            razon_social = ?,
            rfc = ?,
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
        "sssssssssi",
        $nombre,
        $razon_social,
        $rfc,
        $telefono,
        $email,
        $direccion,
        $cp,
        $regimen,
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
            'msg' => 'Error al actualizar cliente'
        ]);
    }

    exit;
}

/* ===================================================
   ================= CONSULTAR CLIENTE ===============
   =================================================== */

$stmt = $conexion->prepare("
    SELECT 
        id,
        nombre,
        razon_social,
        rfc,
        telefono,
        email,
        direccion_fiscal,
        codigo_postal,
        regimen_fiscal,
        uso_cfdi
    FROM clientes
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'ok' => false,
        'msg' => 'Cliente no encontrado'
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'data' => $result->fetch_assoc()
]);

$stmt->close();
