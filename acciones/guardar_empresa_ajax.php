<?php

require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json');

/* ========= VALIDAR MÉTODO ========= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido'
    ]);
    exit;
}

/* ========= SANITIZAR DATOS ========= */
function limpiar($valor) {
    return trim($valor ?? '');
}

$razon_social        = limpiar($_POST['razon_social']);
$nombre_comercial    = limpiar($_POST['nombre_comercial']);
$rfc                 = limpiar($_POST['rfc']);
$codigo_postal       = limpiar($_POST['codigo_postal']);
$lugar_expedicion    = limpiar($_POST['lugar_expedicion']);
$direccion           = limpiar($_POST['direccion']);
$telefono            = limpiar($_POST['telefono']);
$email               = limpiar($_POST['email']);
$regimen_fiscal      = limpiar($_POST['regimen_fiscal']);

$pais                = limpiar($_POST['pais'] ?? 'México');
$estado              = limpiar($_POST['estado']);
$municipio           = limpiar($_POST['municipio']);
$colonia             = limpiar($_POST['colonia']);
$numero_exterior     = limpiar($_POST['numero_exterior']);
$numero_interior     = limpiar($_POST['numero_interior']);

$logo                = limpiar($_POST['logo']);
$certificado_csd     = limpiar($_POST['certificado_csd']);
$llave_privada_csd   = limpiar($_POST['llave_privada_csd']);
$password_csd        = limpiar($_POST['password_csd']);

/* ========= VALIDACIONES BÁSICAS ========= */
if ($razon_social === '' || $rfc === '' || $codigo_postal === '') {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Razón social, RFC y código postal son obligatorios'
    ]);
    exit;
}

/* ========= VERIFICAR SI YA EXISTE ========= */
$empresa = $conexion->query("SELECT id FROM empresa LIMIT 1")->fetch_assoc();

try {

    if ($empresa) {

        /* ===== UPDATE ===== */
        $stmt = $conexion->prepare("
            UPDATE empresa SET
                razon_social = ?,
                nombre_comercial = ?,
                rfc = ?,
                codigo_postal = ?,
                lugar_expedicion = ?,
                direccion = ?,
                telefono = ?,
                email = ?,
                regimen_fiscal = ?,
                pais = ?,
                estado = ?,
                municipio = ?,
                colonia = ?,
                numero_exterior = ?,
                numero_interior = ?,
                logo = ?,
                certificado_csd = ?,
                llave_privada_csd = ?,
                password_csd = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "sssssssssssssssssssi",
            $razon_social,
            $nombre_comercial,
            $rfc,
            $codigo_postal,
            $lugar_expedicion,
            $direccion,
            $telefono,
            $email,
            $regimen_fiscal,
            $pais,
            $estado,
            $municipio,
            $colonia,
            $numero_exterior,
            $numero_interior,
            $logo,
            $certificado_csd,
            $llave_privada_csd,
            $password_csd,
            $empresa['id']
        );

        $accion = 'actualizada';

    } else {

        /* ===== INSERT ===== */
        $stmt = $conexion->prepare("
            INSERT INTO empresa (
                razon_social,
                nombre_comercial,
                rfc,
                codigo_postal,
                lugar_expedicion,
                direccion,
                telefono,
                email,
                regimen_fiscal,
                pais,
                estado,
                municipio,
                colonia,
                numero_exterior,
                numero_interior,
                logo,
                certificado_csd,
                llave_privada_csd,
                password_csd
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "sssssssssssssssssss",
            $razon_social,
            $nombre_comercial,
            $rfc,
            $codigo_postal,
            $lugar_expedicion,
            $direccion,
            $telefono,
            $email,
            $regimen_fiscal,
            $pais,
            $estado,
            $municipio,
            $colonia,
            $numero_exterior,
            $numero_interior,
            $logo,
            $certificado_csd,
            $llave_privada_csd,
            $password_csd
        );

        $accion = 'creada';
    }

    $stmt->execute();

    echo json_encode([
        'ok' => true,
        'mensaje' => "Empresa {$accion} correctamente"
    ]);

} catch (Exception $e) {

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al guardar empresa',
        'error' => $e->getMessage()
    ]);
}
