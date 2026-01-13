<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "../conexion.php";
header('Content-Type: application/json');

try {

    if (empty($_POST)) {
        throw new Exception("No llegaron datos POST");
    }

    /* ================= DATOS OBLIGATORIOS ================= */
    $nombre = trim($_POST['nombre'] ?? '');
    $precio = $_POST['precio_venta'] ?? null;

    if ($nombre === '' || $precio === null) {
        throw new Exception("Nombre y precio obligatorios");
    }

    /* ================= DATOS EXISTENTES ================= */
    $codigo = $_POST['codigo'] ?? null;
    $descripcion = trim($_POST['descripcion'] ?? null);
    $precio_compra = $_POST['precio_compra'] ?? null;
    $stock = $_POST['stock'] ?? 0;
    $unidad = $_POST['unidad_medida'] ?? 'PIEZA';

    $categoria_id = $_POST['categoria_id'] ?: null;
    $categoria_nueva = trim($_POST['categoria_nueva'] ?? '');

    /* ================= DATOS FISCALES ================= */
    $clave_prod_serv = trim($_POST['clave_prod_serv'] ?? '');
    if ($clave_prod_serv === '') {
        throw new Exception("Clave producto/servicio SAT obligatoria");
    }

    // ===== MAPEO AUTOMÁTICO UNIDAD → CLAVE SAT =====
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
    $tasa_iva = $_POST['tasa_iva'] ?? 0.1600;

    /* ================= NORMALIZAR NÚMEROS ================= */
    $precio_compra = ($precio_compra === '' || $precio_compra === null)
        ? null
        : (float)$precio_compra;

    $precio = (float)$precio;
    $stock = (float)$stock;
    $tasa_iva = (float)$tasa_iva;

    $conexion->begin_transaction();

    /* ================= CATEGORÍA NUEVA ================= */
    if ($categoria_nueva !== '') {

        $stmt = $conexion->prepare(
            "SELECT id FROM categorias WHERE nombre = ?"
        );
        $stmt->bind_param("s", $categoria_nueva);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $categoria_id = $res->fetch_assoc()['id'];
        } else {
            $stmt = $conexion->prepare(
                "INSERT INTO categorias (nombre) VALUES (?)"
            );
            $stmt->bind_param("s", $categoria_nueva);
            $stmt->execute();
            $categoria_id = $conexion->insert_id;
        }
    }

    /* ================= INSERT PRODUCTO ================= */
    $stmt = $conexion->prepare("
        INSERT INTO productos (
            codigo,
            nombre,
            descripcion,
            clave_prod_serv,
            clave_unidad,
            objeto_impuesto,
            tasa_iva,
            precio_compra,
            precio_venta,
            stock,
            categoria_id,
            unidad_medida
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    // 🔥 bind_param CORRECTO (12 CAMPOS)
    $stmt->bind_param(
        "ssssssddddis",
        $codigo,
        $nombre,
        $descripcion,
        $clave_prod_serv,
        $clave_unidad,
        $objeto_impuesto,
        $tasa_iva,
        $precio_compra,
        $precio,
        $stock,
        $categoria_id,
        $unidad
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $conexion->commit();

    echo json_encode([
        'ok' => true,
        'msg' => 'Producto guardado correctamente'
    ]);
    exit;

} catch (Exception $e) {

    if (isset($conexion)) {
        @$conexion->rollback();
    }

    echo json_encode([
        'ok' => false,
        'msg' => $e->getMessage()
    ]);
    exit;
}
