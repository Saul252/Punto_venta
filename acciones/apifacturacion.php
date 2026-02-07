<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();
require_once __DIR__ . '/../conexion.php';

/* ================= VALIDACIÓN ================= */
if (!isset($_GET['venta_id'])) {
    die('Venta no especificada');
}
$venta_id = (int)$_GET['venta_id'];

/* =========================
   CONSULTAR CLIENTES SANDBOX
========================= */

$clientesSandbox = [];

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => 'https://facturaonline.com.mx/v1/clients',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'F-PLUGIN: 9d4095c8f7ed5785cb14c0e3b033eeb8252416ed',
        'F-Api-Key: JDJ5JDEwJDJ5YW16MVNacHFHUkxHODZBQnptQWVWN00wdFJ0b1loNVlQWlBWOXBHdlJ4Y2xGNzdiOEcy',
'F-Secret-Key: JDJ5JDEwJGQ3WXd5d212dzVoQTdBUy9ILzAzUmVHVEJkd3AvRFZZdmZBNkNidmp1VkU2WVJac0RLenVx'  ],
]);

$response = curl_exec($curl);

if ($response !== false) {
    $json = json_decode($response, true);
    if (!empty($json['data'])) {
        $clientesSandbox = $json['data'];
    }
}

curl_close($curl);



/* ================= EMPRESA ================= */
$empresa = $conexion->query("SELECT * FROM empresa LIMIT 1")->fetch_assoc();
if (!$empresa) {
    die('Empresa no configurada');
}

/* ================= VENTA ================= */
$venta = $conexion->query("
    SELECT *
    FROM ventas
    WHERE id = $venta_id
")->fetch_assoc();

if (!$venta) {
    die('Venta no encontrada');
}

/* ================= CONCEPTOS ================= */
$conceptos = [];

$res = $conexion->query("
    SELECT 
        vd.cantidad,
        vd.precio,
        p.nombre,
        p.clave_prod_serv,
        p.clave_unidad,
        p.objeto_impuesto,
        p.tasa_iva
    FROM venta_detalle vd
    JOIN productos p ON p.id = vd.producto_id
    WHERE vd.venta_id = $venta_id
");

while ($c = $res->fetch_assoc()) {

    $cantidad = round((float)$c['cantidad'], 3); // kilos
    $precioConIVA = round((float)$c['precio'], 2);
    $tasaIVA = (float)$c['tasa_iva'];

    // Carnicería: precio ya es final
    $precioSinIVA = $precioConIVA;
    $importe = round($cantidad * $precioSinIVA, 2);

    $concepto = [
        "ClaveProdServ" => $c['clave_prod_serv'],
        "Cantidad"      => $cantidad,
        "ClaveUnidad"   => $c['clave_unidad'],
        "Unidad"        => "Kilo",
        "Descripcion"   => $c['nombre'],
        "ValorUnitario" => $precioSinIVA,
        "Importe"       => $importe,
        "ObjetoImp"     => ((int)$c['objeto_impuesto'] === 2) ? "02" : "01"
    ];

    // 👇 SOLO si es objeto de impuesto
    if ((int)$c['objeto_impuesto'] === 2) {

        $concepto["Impuestos"] = [
            "Traslados" => [[
                "Base"       => $importe,
                "Impuesto"   => "002",
                "TipoFactor" => "Tasa",
                "TasaOCuota" => number_format($tasaIVA, 6, '.', ''),
                "Importe"    => round($importe * $tasaIVA, 2) // 0.00 si tasa 0
            ]]
        ];
    }

    $conceptos[] = $concepto;
}


/* ================= PAYLOAD (IGUAL AL SANDBOX) ================= */
$payload = [
  "Receptor" => [
    "UID" => $_POST['cliente_uid']
],
    "TipoDocumento"   => "factura",
    "RegimenFiscal"   => 626,            // 👈 número
    "UsoCFDI"         => "S01",
    "Serie"           => 41955,         // 👈 número
    "FormaPago"       => $venta['forma_pago'] ?: "01",
    "MetodoPago"      => $venta['metodo_pago_sat'] ?: "PUE",
    "Moneda"          => "MXN",
    "LugarExpedicion" => $empresa['codigo_postal'],
    "EnviarCorreo"    => false,           // 👈 boolean real
    "Conceptos"       => $conceptos
];

/* ================= TIMBRADO (SOLO AL PRESIONAR BOTÓN) ================= */
$respuesta = null;
$httpcode  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://facturaonline.com.mx/v4/cfdi40/create',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'F-PLUGIN: 9d4095c8f7ed5785cb14c0e3b033eeb8252416ed',
        'F-Api-Key: JDJ5JDEwJDJ5YW16MVNacHFHUkxHODZBQnptQWVWN00wdFJ0b1loNVlQWlBWOXBHdlJ4Y2xGNzdiOEcy',
'F-Secret-Key: JDJ5JDEwJGQ3WXd5d212dzVoQTdBUy9ILzAzUmVHVEJkd3AvRFZZdmZBNkNidmp1VkU2WVJac0RLenVx'],
    ]);

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $respuesta = json_decode($response, true);
    

  

}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Factura CFDI 4.0 | Público en General</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background: #eef2f7;
}
.card {
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(0,0,0,.08);
}
pre {
    background: #0f172a;
    color: #e5e7eb;
    padding: 20px;
    border-radius: 12px;
    font-size: 14px;
}
</style>
</head>

<body>
<div class="container py-4">

    <h4 class="mb-3">🧾 Facturación CFDI 4.0 – Público en General</h4>

    <div class="card p-4 mb-4">
        <strong>Venta #<?= $venta_id ?></strong><br>
        Moneda: MXN<br>
        Forma de pago: <?= htmlspecialchars($payload['FormaPago']) ?><br>
        Método de pago: <?= htmlspecialchars($payload['MetodoPago']) ?>
    </div>

    <div class="card p-4 mb-4">
        <h6>📤 Payload (Sandbox Factura.com)</h6>
        <pre><?= json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>

        <form method="POST" class="mt-3">
               <!-- ================= CLIENTE FACTURA ================= -->

            <div class="card p-3 mb-4">
                <h5>Cliente para la factura</h5>

                <select name="cliente_uid" class="form-select" required>
                    <option value="">Selecciona un cliente</option>

                    <?php foreach($clientesSandbox as $c): ?>
                    <option value="<?= $c['UID'] ?>" <?= $c['RFC'] === 'XAXX010101000' ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['RazonSocial']) ?>
                        (<?= $c['RFC'] ?>)
                    </option>
                    <?php endforeach; ?>
                </select>

                <small class="text-muted">
                    Para factura global SAT debe seleccionarse <b>PÚBLICO EN GENERAL</b>.
                </small>
            </div>
            <button class="btn btn-success btn-lg">
                🚀 Generar factura
            </button>
        </form>
    </div>

    <?php if ($respuesta): ?>
        <div class="card p-4">
            <h6>📥 Respuesta Factura.com</h6>
            <strong>HTTP:</strong> <?= $httpcode ?>
            <pre><?= json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
        </div>
    <?php endif; ?>

</div>
<?php if ($httpcode >= 200 && $httpcode < 300 && isset($respuesta['data']['uuid'])): ?>

<script>
Swal.fire({
    icon: 'success',
    title: 'Factura generada',
    html: `
        <strong>UUID:</strong><br>
        <code><?= $respuesta['data']['uuid'] ?></code>
    `,
    confirmButtonText: 'Aceptar'
}).then(() => {
    window.location.href = '/punto/pantallas/facturacion.php';
});
</script>
<?php endif; ?>

</body>
</html>
