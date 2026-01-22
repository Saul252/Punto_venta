<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();
require_once __DIR__ . '/../conexion.php';

/* ================= VALIDACIÓN ================= */
if (!isset($_GET['venta_id'])) {
    die('Venta no especificada');
}

$venta_id = (int)$_GET['venta_id'];

/* ================= EMPRESA ================= */
$empresa = $conexion->query("SELECT * FROM empresa LIMIT 1")->fetch_assoc();
if (!$empresa) die('Empresa no configurada');

/* ================= VENTA / CLIENTE ================= */
$venta = $conexion->query("
    SELECT v.*, 
           c.rfc AS rfc_cliente,
           c.razon_social AS razon_cliente,
           c.codigo_postal AS cp_cliente,
           c.regimen_fiscal AS regimen_cliente,
           c.uso_cfdi
    FROM ventas v
    LEFT JOIN clientes c ON c.id = v.cliente_id
    WHERE v.id = $venta_id
")->fetch_assoc();

if (!$venta) die('Venta no encontrada');

/* ================= CONCEPTOS ================= */
$conceptos  = [];
$subtotal   = 0.00;
$iva_total  = 0.00;

$res = $conexion->query("
    SELECT 
        vd.cantidad,
        vd.precio,
        p.nombre,
        p.clave_prod_serv,
        p.clave_unidad,
        p.objeto_impuesto
    FROM venta_detalle vd
    JOIN productos p ON p.id = vd.producto_id
    WHERE vd.venta_id = $venta_id
");

while ($c = $res->fetch_assoc()) {

    $cantidad = round((float)$c['cantidad'], 2);
    $precio   = round((float)$c['precio'], 2);
    $importe  = round($cantidad * $precio, 2);

    $iva = 0.00;
    if ((int)$c['objeto_impuesto'] === 2) {
        $iva = round($importe * 0.16, 2);
    }

    $subtotal  = round($subtotal + $importe, 2);
    $iva_total = round($iva_total + $iva, 2);

    $concepto = [
        "ClaveProdServ" => $c['clave_prod_serv'],
        "Cantidad"      => number_format($cantidad, 2, '.', ''),
        "ClaveUnidad"   => $c['clave_unidad'],
        "Descripcion"   => $c['nombre'],
        "ValorUnitario" => number_format($precio, 2, '.', ''),
        "Importe"       => number_format($importe, 2, '.', ''),
        "ObjetoImp"     => (string)$c['objeto_impuesto']
    ];

    if ($iva > 0) {
        $concepto["Impuestos"] = [
            "Traslados" => [
                [
                    "Base"       => number_format($importe, 2, '.', ''),
                    "Impuesto"   => "002",
                    "TipoFactor" => "Tasa",
                    "TasaOCuota" => "0.160000",
                    "Importe"    => number_format($iva, 2, '.', '')
                ]
            ]
        ];
    }

    $conceptos[] = $concepto;
}

$total = round($subtotal + $iva_total, 2);

/* ================= PAYLOAD CFDI 4.0 ================= */
$payload = [
    "Emisor" => [
        "Rfc"           => $empresa['rfc'],
        "Nombre"        => $empresa['razon_social'],
        "RegimenFiscal" => $empresa['regimen_fiscal'],
        "CodigoPostal"  => $empresa['codigo_postal']
    ],
    "Receptor" => [
        "Rfc"           => $venta['rfc_cliente'] ?: 'XAXX010101000',
        "Nombre"        => $venta['razon_cliente'] ?: 'PUBLICO EN GENERAL',
        "UsoCFDI"       => $venta['uso_cfdi'] ?: 'S01',
        "RegimenFiscal" => $venta['regimen_cliente'] ?: '616',
        "CodigoPostal"  => $venta['cp_cliente'] ?: $empresa['codigo_postal']
    ],
    "Conceptos" => $conceptos
];

/* ===== IMPUESTOS GLOBALES ===== */
if ($iva_total > 0) {
    $payload["Impuestos"] = [
        "TotalImpuestosTrasladados" => number_format($iva_total, 2, '.', ''),
        "Traslados" => [
            [
                "Impuesto"   => "002",
                "TipoFactor" => "Tasa",
                "TasaOCuota" => "0.160000",
                "Importe"    => number_format($iva_total, 2, '.', '')
            ]
        ]
    ];
}

/* ===== TOTALES (DEBEN IR DESPUÉS DE IMPUESTOS) ===== */
$payload["Totales"] = [
    "SubTotal"             => number_format($subtotal, 2, '.', ''),
    "ImpuestosTrasladados" => number_format($iva_total, 2, '.', ''),
    "Total"                => number_format($total, 2, '.', '')
];

/* ===== DATOS DE PAGO ===== */
$payload["FormaPago"]       = $venta['forma_pago'] ?: '01';
$payload["MetodoPago"]      = $venta['metodo_pago_sat'] ?: 'PUE';
$payload["Moneda"]          = "MXN";
$payload["LugarExpedicion"] = $empresa['codigo_postal'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>API Facturación CFDI 4.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        background: #eef2f7
    }

    .card {
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .08)
    }

    pre {
        background: #0f172a;
        color: #e5e7eb;
        padding: 20px;
        border-radius: 12px
    }
    </style>
</head>

<body>
    <div class="container py-4">
        <h4 class="mb-3">📤 Preparación de Factura CFDI 4.0</h4>

        <div class="card p-4 mb-4">
            <strong>Venta #<?= $venta_id ?></strong><br>
            Cliente: <?= htmlspecialchars($payload['Receptor']['Nombre']) ?><br>
            Total: <strong>$<?= number_format($total,2) ?></strong>
        </div>

        <div class="card p-4">
            <h6>JSON para API de Facturación</h6>
            <pre><?= json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
        </div>
    </div>
</body>

</html>