-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 14-01-2026 a las 02:16:51
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12
<?php
session_start();
require "../conexion.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ========= SWEET ALERT ========= */
function alertError($msg) {
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: ".json_encode($msg).",
                confirmButtonText: 'Volver'
            }).then(() => history.back());
        </script>
    </body>
    </html>";
    exit;
}

function alertSuccess($venta_id, $estado) {
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Venta registrada',
                html: `<b>Venta #{$venta_id}</b><br>Estado: <b>{$estado}</b>`,
                confirmButtonText: 'Continuar'
            }).then(() => {
                window.location.href = '/punto/pantallas/ventas.php';
            });
        </script>
    </body>
    </html>";
    exit;
}

/* ========= VALIDACIONES ========= */
if (!isset($_SESSION['login'], $_SESSION['user_id'])) {
    alertError("Sesión inválida");
}

if (empty($_SESSION['carrito']) || empty($_SESSION['total'])) {
    alertError("Carrito vacío");
}

if (!isset($_POST['metodo_pago'], $_POST['monto_pago'])) {
    alertError("Datos de pago incompletos");
}

/* ========= DATOS ========= */
$usuario_id = (int)$_SESSION['user_id'];
$caja_id    = 2; // TODO: dinámico
$total      = (float)$_SESSION['total'];
$monto_pago = (float)$_POST['monto_pago'];
$metodo     = $_POST['metodo_pago'];
$carrito    = $_SESSION['carrito'];

$cliente_id = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
$requiereFactura = isset($_POST['requiere_factura']) ? 1 : 0;

if ($monto_pago < 0 || $monto_pago > $total) {
    alertError("Monto de pago inválido");
}

$estado_venta = ($monto_pago >= $total) ? 'CERRADA' : 'ABIERTA';

/* ========= DATOS FISCALES ========= */
$tipoFactura = null;
$nombre_receptor = null;
$rfc = null;
$razon_social = null;
$regimen_fiscal = null;
$uso_cfdi = null;
$codigo_postal = null;
$direccion_fiscal = null;

if ($requiereFactura) {

    if ($cliente_id) {
        // CLIENTE REGISTRADO
        $stmt = $conexion->prepare("
            SELECT
                nombre,
                rfc,
                razon_social,
                regimen_fiscal,
                uso_cfdi,
                codigo_postal,
                direccion_fiscal
            FROM clientes
            WHERE id = ?
        ");
        $stmt->bind_param("i", $cliente_id);
        $stmt->execute();
        $cli = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$cli) {
            alertError("Cliente no encontrado");
        }

        $tipoFactura      = 'publico';
        $nombre_receptor  = $cli['nombre'];
        $rfc              = $cli['rfc'];
        $razon_social     = $cli['razon_social'];
        $regimen_fiscal   = $cli['regimen_fiscal'];
        $uso_cfdi         = $cli['uso_cfdi'];
        $codigo_postal    = $cli['codigo_postal'];
        $direccion_fiscal = $cli['direccion_fiscal'];

    } else {
        // PÚBLICO EN GENERAL CON NOMBRE
        $nombreFactura = trim($_POST['nombre_factura_publico'] ?? '');

        if ($nombreFactura === '') {
            alertError("Debes capturar el nombre para la factura");
        }

        $empresa = $conexion->query("
            SELECT codigo_postal FROM empresa LIMIT 1
        ")->fetch_assoc();

        $tipoFactura      = 'nombre';
        $nombre_receptor  = $nombreFactura;
        $rfc              = 'XAXX010101000';
        $razon_social     = $nombreFactura;
        $regimen_fiscal   = '616';
        $uso_cfdi         = 'P01';
        $codigo_postal    = $empresa['codigo_postal'];
        $direccion_fiscal = null;
    }
}

/* ========= TRANSACCIÓN ========= */
$conexion->begin_transaction();

try {

    /* ========= VENTA ========= */
    $stmt = $conexion->prepare("
        INSERT INTO ventas (
            cliente_id,
            usuario_id,
            caja_id,
            total,
            total_pagado,
            metodo_pago,
            estado,
            requiere_factura,
            tipo_factura,
            nombre_receptor,
            rfc,
            razon_social,
            regimen_fiscal,
            uso_cfdi,
            codigo_postal,
            direccion_fiscal
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "iiiddssissssssss",
        $cliente_id,
        $usuario_id,
        $caja_id,
        $total,
        $monto_pago,
        $metodo,
        $estado_venta,
        $requiereFactura,
        $tipoFactura,
        $nombre_receptor,
        $rfc,
        $razon_social,
        $regimen_fiscal,
        $uso_cfdi,
        $codigo_postal,
        $direccion_fiscal
    );

    if (!$stmt->execute()) {
        throw new Exception("No se pudo guardar la venta");
    }

    $venta_id = $conexion->insert_id;
    $stmt->close();

    /* ========= DETALLE + STOCK ========= */
    $stmtDet = $conexion->prepare("
        INSERT INTO venta_detalle
        (venta_id, producto_id, cantidad, precio, subtotal)
        VALUES (?,?,?,?,?)
    ");

    $stmtStock = $conexion->prepare("
        UPDATE productos
        SET stock = stock - ?
        WHERE id = ? AND stock >= ?
    ");

    foreach ($carrito as $item) {

        $producto_id = (int)$item['id'];
        $cantidad    = (float)$item['cantidad'];
        $precio      = (float)$item['precio'];
        $subtotal    = $cantidad * $precio;

        $stmtDet->bind_param("iiddd", $venta_id, $producto_id, $cantidad, $precio, $subtotal);
        if (!$stmtDet->execute()) {
            throw new Exception("Error al guardar detalle");
        }

        $stmtStock->bind_param("did", $cantidad, $producto_id, $cantidad);
        if (!$stmtStock->execute() || $stmtStock->affected_rows === 0) {
            throw new Exception("Stock insuficiente (producto ID $producto_id)");
        }
    }

    $stmtDet->close();
    $stmtStock->close();

    /* ========= PAGOS / CAJA ========= */
    if ($monto_pago > 0) {

        $stmtVP = $conexion->prepare("
            INSERT INTO ventas_pagos
            (venta_id, caja_id, usuario_id, monto, metodo_pago)
            VALUES (?,?,?,?,?)
        ");
        $stmtVP->bind_param("iiids", $venta_id, $caja_id, $usuario_id, $monto_pago, $metodo);
        $stmtVP->execute();
        $stmtVP->close();

        $stmtPago = $conexion->prepare("
            INSERT INTO pagos
            (tipo, referencia_id, caja_id, usuario_id, monto, metodo_pago, referencia)
            VALUES ('VENTA', ?, ?, ?, ?, ?, ?)
        ");
        $referencia = "Venta #{$venta_id}";
        $stmtPago->bind_param("iiidss", $venta_id, $caja_id, $usuario_id, $monto_pago, $metodo, $referencia);
        $stmtPago->execute();
        $stmtPago->close();

        $stmtMov = $conexion->prepare("
            INSERT INTO movimientos_caja
            (caja_id, tipo, descripcion, monto)
            VALUES (?, 'INGRESO', ?, ?)
        ");
        $stmtMov->bind_param("isd", $caja_id, $referencia, $monto_pago);
        $stmtMov->execute();
        $stmtMov->close();
    }

    $conexion->commit();
    unset($_SESSION['carrito'], $_SESSION['total']);

    alertSuccess($venta_id, $estado_venta);

} catch (Exception $e) {
    $conexion->rollback();
    alertError($e->getMessage());
}

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `punto_venta`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cajas`
--

CREATE TABLE `cajas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_apertura` datetime DEFAULT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `monto_inicial` decimal(10,2) DEFAULT NULL,
  `monto_final` decimal(10,2) DEFAULT NULL,
  `estado` enum('ABIERTA','CERRADA') DEFAULT 'ABIERTA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) DEFAULT NULL,
  `rfc` varchar(20) DEFAULT NULL,
  `razon_social` varchar(150) DEFAULT NULL,
  `documento` varchar(50) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion_fiscal` text DEFAULT NULL,
  `codigo_postal` varchar(10) DEFAULT NULL,
  `regimen_fiscal` varchar(100) DEFAULT NULL,
  `uso_cfdi` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa`
--

CREATE TABLE `empresa` (
  `id` int(11) NOT NULL,
  `razon_social` varchar(150) NOT NULL,
  `nombre_comercial` varchar(150) DEFAULT NULL,
  `rfc` varchar(20) NOT NULL,
  `codigo_postal` varchar(5) NOT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `regimen_fiscal` varchar(100) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `rfc` varchar(13) NOT NULL,
  `razon_social` varchar(255) NOT NULL,
  `uso_cfdi` varchar(5) NOT NULL,
  `regimen_fiscal` varchar(5) NOT NULL,
  `codigo_postal` varchar(5) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `impuestos` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `forma_pago` varchar(5) NOT NULL,
  `metodo_pago` varchar(5) NOT NULL,
  `moneda` varchar(5) DEFAULT 'MXN',
  `uuid` varchar(50) DEFAULT NULL,
  `folio` varchar(50) DEFAULT NULL,
  `xml` longtext DEFAULT NULL,
  `pdf` longtext DEFAULT NULL,
  `estado` enum('PENDIENTE','TIMBRADA','CANCELADA') DEFAULT 'PENDIENTE',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura_conceptos`
--

CREATE TABLE `factura_conceptos` (
  `id` int(11) NOT NULL,
  `factura_id` int(11) NOT NULL,
  `clave_prod_serv` varchar(10) NOT NULL,
  `clave_unidad` varchar(5) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `importe` decimal(10,2) NOT NULL,
  `iva` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gastos`
--

CREATE TABLE `gastos` (
  `id` int(11) NOT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `tipo_gasto_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `caja_id` int(11) DEFAULT NULL,
  `concepto` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('EFECTIVO','TARJETA','TRANSFERENCIA') NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('REGISTRADO','CANCELADO') DEFAULT 'REGISTRADO',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_caja`
--

CREATE TABLE `movimientos_caja` (
  `id` int(11) NOT NULL,
  `caja_id` int(11) NOT NULL,
  `tipo` enum('INGRESO','EGRESO') NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `tipo` enum('VENTA','GASTO') NOT NULL,
  `referencia_id` int(11) NOT NULL,
  `caja_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('EFECTIVO','TARJETA','TRANSFERENCIA') NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `estado` enum('APLICADO','CANCELADO') DEFAULT 'APLICADO',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `clave_prod_serv` varchar(10) NOT NULL COMMENT 'Clave SAT',
  `clave_unidad` varchar(5) NOT NULL COMMENT 'Clave SAT',
  `objeto_impuesto` char(2) NOT NULL DEFAULT '02' COMMENT '01 no objeto, 02 sí objeto',
  `tasa_iva` decimal(5,4) NOT NULL DEFAULT 0.1600 COMMENT 'IVA SAT',
  `precio_compra` decimal(10,2) DEFAULT NULL,
  `precio_venta` decimal(10,2) NOT NULL,
  `stock` decimal(10,3) NOT NULL DEFAULT 0.000,
  `categoria_id` int(11) DEFAULT NULL,
  `estado` tinyint(4) DEFAULT 1,
  `unidad_medida` enum('PIEZA','KILO','GRAMO','LITRO') DEFAULT 'PIEZA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `rfc` varchar(20) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `estado` tinyint(4) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_gasto`
--

CREATE TABLE `tipos_gasto` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `estado` tinyint(4) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `caja_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `total_pagado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `metodo_pago` enum('EFECTIVO','TARJETA','TRANSFERENCIA') DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo` enum('MOSTRADOR','FACTURA') DEFAULT 'MOSTRADOR',
  `estado` enum('ABIERTA','CERRADA','CANCELADA') DEFAULT 'ABIERTA',
  `requiere_factura` tinyint(4) DEFAULT 0,
  `tipo_factura` enum('publico','nombre') DEFAULT 'publico',
  `nombre_receptor` varchar(150) DEFAULT NULL,
  `forma_pago` varchar(5) DEFAULT NULL COMMENT 'Clave SAT (01,03,04, etc)',
  `metodo_pago_sat` varchar(5) DEFAULT 'PUE' COMMENT 'PUE o PPD',
  `rfc` varchar(13) DEFAULT NULL,
  `razon_social` varchar(255) DEFAULT NULL,
  `regimen_fiscal` varchar(10) DEFAULT NULL,
  `uso_cfdi` varchar(5) DEFAULT NULL,
  `codigo_postal` varchar(10) DEFAULT NULL,
  `direccion_fiscal` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas_pagos`
--

CREATE TABLE `ventas_pagos` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `caja_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('EFECTIVO','TARJETA','TRANSFERENCIA') NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta_detalle`
--

CREATE TABLE `venta_detalle` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `iva` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cajas`
--
ALTER TABLE `cajas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `empresa`
--
ALTER TABLE `empresa`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`);

--
-- Indices de la tabla `factura_conceptos`
--
ALTER TABLE `factura_conceptos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_factura_conceptos` (`factura_id`);

--
-- Indices de la tabla `gastos`
--
ALTER TABLE `gastos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_gastos_proveedor` (`proveedor_id`),
  ADD KEY `fk_gastos_tipo` (`tipo_gasto_id`),
  ADD KEY `fk_gastos_usuario` (`usuario_id`),
  ADD KEY `fk_gastos_caja` (`caja_id`);

--
-- Indices de la tabla `movimientos_caja`
--
ALTER TABLE `movimientos_caja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `caja_id` (`caja_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tipo_ref` (`tipo`,`referencia_id`),
  ADD KEY `idx_caja` (`caja_id`),
  ADD KEY `idx_usuario` (`usuario_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tipos_gasto`
--
ALTER TABLE `tipos_gasto`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `rol_id` (`rol_id`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `caja_id` (`caja_id`);

--
-- Indices de la tabla `ventas_pagos`
--
ALTER TABLE `ventas_pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`),
  ADD KEY `caja_id` (`caja_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `venta_detalle`
--
ALTER TABLE `venta_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cajas`
--
ALTER TABLE `cajas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empresa`
--
ALTER TABLE `empresa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `factura_conceptos`
--
ALTER TABLE `factura_conceptos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `gastos`
--
ALTER TABLE `gastos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimientos_caja`
--
ALTER TABLE `movimientos_caja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipos_gasto`
--
ALTER TABLE `tipos_gasto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ventas_pagos`
--
ALTER TABLE `ventas_pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `venta_detalle`
--
ALTER TABLE `venta_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cajas`
--
ALTER TABLE `cajas`
  ADD CONSTRAINT `cajas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD CONSTRAINT `facturas_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`);

--
-- Filtros para la tabla `factura_conceptos`
--
ALTER TABLE `factura_conceptos`
  ADD CONSTRAINT `fk_factura_conceptos` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `gastos`
--
ALTER TABLE `gastos`
  ADD CONSTRAINT `fk_gastos_caja` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`),
  ADD CONSTRAINT `fk_gastos_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`),
  ADD CONSTRAINT `fk_gastos_tipo` FOREIGN KEY (`tipo_gasto_id`) REFERENCES `tipos_gasto` (`id`),
  ADD CONSTRAINT `fk_gastos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `movimientos_caja`
--
ALTER TABLE `movimientos_caja`
  ADD CONSTRAINT `movimientos_caja_ibfk_1` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`);

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pagos_caja` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`),
  ADD CONSTRAINT `fk_pagos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `ventas_ibfk_3` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`);

--
-- Filtros para la tabla `ventas_pagos`
--
ALTER TABLE `ventas_pagos`
  ADD CONSTRAINT `ventas_pagos_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`),
  ADD CONSTRAINT `ventas_pagos_ibfk_2` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`),
  ADD CONSTRAINT `ventas_pagos_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `venta_detalle`
--
ALTER TABLE `venta_detalle`
  ADD CONSTRAINT `venta_detalle_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`),
  ADD CONSTRAINT `venta_detalle_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;