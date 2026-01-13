<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "../conexion.php";
header('Content-Type: application/json');

try {

    if (empty($_POST['id'])) {
        throw new Exception("ID de producto no recibido");
    }

    /* ================= ID ================= */
    $id = (int)$_POST['id'];

    /* ================= DATOS GENERALES ================= */
    $codigo        = $_POST['codigo'] ?? null;
    $nombre        = trim($_POST['nombre'] ?? '');
    $descripcion   = trim($_POST['descripcion'] ?? null);
    $precio_compra = $_POST['precio_compra'] ?? null;
    $precio_venta  = $_POST['precio_venta'] ?? null;
    $stock         = $_POST['stock'] ?? 0;
    $unidad        = $_POST['unidad_medida'] ?? 'PIEZA';
    $categoria_id  = $_POST['categoria_id'] ?: null;

    if ($nombre === '' || $precio_venta === null) {
        throw new Exception("Nombre y precio de venta son obligatorios");
    }

    /* ================= DATOS FISCALES ================= */
    $clave_prod_serv = trim($_POST['clave_prod_serv'] ?? '');
    if ($clave_prod_serv === '') {
        throw new Exception("Clave producto/servicio SAT obligatoria");
    }

    // MAPEO UNIDAD → CLAVE SAT
    $mapa_unidades = [
        'PIEZA' => 'H87',
        'KILO'  => 'KGM',
        'GRAMO' => 'GRM',
        'LITRO' => 'LTR'
    ];

    if (!isset($mapa_unidades[$unidad])) {
        throw new Exception("Unidad de medida no válida");
    }

    $clave_unidad = $mapa_unidades[$unidad];

    $objeto_impuesto = $_POST['objeto_impuesto'] ?? '02';
    $tasa_iva        = $_POST['tasa_iva'] ?? 0.1600;

    /* ================= NORMALIZAR ================= */
    $precio_compra = ($precio_compra === '' || $precio_compra === null)
        ? null
        : (float)$precio_compra;

    $precio_venta = (float)$precio_venta;
    $stock        = (float)$stock;
    $tasa_iva     = (float)$tasa_iva;

    /* ================= UPDATE ================= */
    $stmt = $conexion->prepare("
        UPDATE productos SET
            codigo = ?,
            nombre = ?,
            descripcion = ?,
            clave_prod_serv = ?,
            clave_unidad = ?,
            objeto_impuesto = ?,
            tasa_iva = ?,
            precio_compra = ?,
            precio_venta = ?,
            stock = ?,
            categoria_id = ?,
            unidad_medida = ?
        WHERE id = ?
    ");

    // 🔥 bind_param CORRECTO
    $stmt->bind_param(
        "ssssssddddisi",
        $codigo,
        $nombre,
        $descripcion,
        $clave_prod_serv,
        $clave_unidad,
        $objeto_impuesto,
        $tasa_iva,
        $precio_compra,
        $precio_venta,
        $stock,
        $categoria_id,
        $unidad,
        $id
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $stmt->close();

    echo json_encode([
        'ok' => true,
        'msg' => 'Producto actualizado correctamente'
    ]);
    exit;

} catch (Exception $e) {

    echo json_encode([
        'ok' => false,
        'msg' => $e->getMessage()
    ]);
    exit;
}
