<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require "../conexion.php";

header('Content-Type: application/json');

/* ================= VALIDAR ID ================= */
$id = $_GET['id'] ?? $_POST['id'] ?? '';

if ($id === '' || !is_numeric($id)) {
    echo json_encode([
        'ok' => false,
        'msg' => 'ID de cliente inválido'
    ]);
    exit;
}

/* ================= CONSULTAR ================= */
$stmt = $conexion->prepare("
    SELECT 
        id,
        nombre,
        rfc,
        razon_social,
        documento,
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

$cliente = $result->fetch_assoc();

/* ================= RESPUESTA ================= */
echo json_encode([
    'ok' => true,
    'data' => $cliente
]);

$stmt->close();
