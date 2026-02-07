<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();
require_once __DIR__ . '/../conexion.php';

/* ================= VALIDAR ================= */

if(empty($_POST['ventas'])) die("No hay ventas seleccionadas");

$ids = array_map('intval', $_POST['ventas']);
$lista = implode(',', $ids);

/* ================= RECIBE DE facturaglobal.php ================= */

$periodicidad = $_POST['periodicidad'] ?? '05';
$meses        = $_POST['meses'] ?? date('m');
$anio         = $_POST['anio'] ?? date('Y');
$formaPago    = $_POST['forma_pago'];
$metodoPago   = $_POST['metodo_pago'];

/* ================= EMPRESA ================= */

$empresa = $conexion->query("SELECT * FROM empresa LIMIT 1")->fetch_assoc();
if(!$empresa) die("Empresa no configurada");

/* ================= DETECTAR FORMA / METODO PAGO AUTOMATICO ================= */

/* ================= CONCEPTOS AGRUPADOS ================= */

$res = $conexion->query("
SELECT 
p.id,
p.nombre,
p.clave_prod_serv,
p.clave_unidad,
p.objeto_impuesto,
p.tasa_iva,
SUM(vd.cantidad) cantidad,
SUM(vd.cantidad * vd.precio) total_con_iva
FROM venta_detalle vd
JOIN productos p ON p.id = vd.producto_id
WHERE vd.venta_id IN ($lista)
GROUP BY p.id

");

$conceptos = [];

while($c = $res->fetch_assoc()){

    $cantidad = round((float)$c['cantidad'], 3); // kilos
    $totalConIVA = round((float)$c['total_con_iva'], 2);
    $tasaIVA = (float)$c['tasa_iva'];

    // Carnicería: el precio ya es final
    if ($tasaIVA > 0) {
        $importe = round($totalConIVA / (1 + $tasaIVA), 2);
        $iva = round($importe * $tasaIVA, 2);
    } else {
        $importe = $totalConIVA;
        $iva = 0;
    }

    $valorUnitario = round($importe / $cantidad, 6);

    $concepto = [
        "ClaveProdServ" => $c['clave_prod_serv'],
        "Cantidad" => $cantidad,
        "ClaveUnidad" => $c['clave_unidad'],
        "Unidad" => "Kilo",
        "Descripcion" => $c['nombre']." — Venta Global",
        "ValorUnitario" => $valorUnitario,
        "Importe" => $importe,
        "ObjetoImp" => ((int)$c['objeto_impuesto'] === 2) ? "02" : "01"
    ];

    // 👇 Impuestos SOLO si es objeto
    if ((int)$c['objeto_impuesto'] === 2) {
        $concepto["Impuestos"] = [
            "Traslados" => [[
                "Base" => $importe,
                "Impuesto" => "002",
                "TipoFactor" => "Tasa",
                "TasaOCuota" => number_format($tasaIVA, 6, '.', ''),
                "Importe" => $iva
            ]]
        ];
    }

    $conceptos[] = $concepto;
}


/* ================= INFORMACION GLOBAL SAT ================= */

$infoGlobal = [
    "Periodicidad" => $periodicidad,
    "Meses" => $meses,
    "Año" => $anio
];

/* ================= PAYLOAD GLOBAL CFDI 4.0 ================= */

$payload = [

"Receptor" => [
    "UID" => $_POST['cliente_uid']
],

"TipoDocumento" => "factura",   // ← CORREGIDO

"RegimenFiscal" => 626,

"InformacionGlobal" => $infoGlobal,

"UsoCFDI" => "S01",             // ← CORREGIDO CFDI 4.0

"Serie" => 41955,

"FormaPago" => $formaPago,
"MetodoPago" => $metodoPago,

"Moneda" => "MXN",
"LugarExpedicion" => 12000,
"EnviarCorreo" => false,

"Conceptos" => $conceptos

];

/* ================= TIMBRADO ================= */

$curl = curl_init();

curl_setopt_array($curl, [
CURLOPT_URL => 'https://facturaonline.com.mx/api/v4/cfdi40/create',
CURLOPT_RETURNTRANSFER => true,
CURLOPT_POST => true,
CURLOPT_POSTFIELDS => json_encode($payload),
CURLOPT_HTTPHEADER => [
'Content-Type: application/json',
'F-PLUGIN: 9d4095c8f7ed5785cb14c0e3b033eeb8252416ed',
'F-Api-Key: JDJ5JDEwJDJ5YW16MVNacHFHUkxHODZBQnptQWVWN00wdFJ0b1loNVlQWlBWOXBHdlJ4Y2xGNzdiOEcy',
'F-Secret-Key: JDJ5JDEwJGQ3WXd5d212dzVoQTdBUy9ILzAzUmVHVEJkd3AvRFZZdmZBNkNidmp1VkU2WVJac0RLenVx'
]
]);

$response = curl_exec($curl);
$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

/* ================= RESPUESTA ================= */

echo "<h3>📤 Payload enviado</h3>";
echo "<pre>".json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."</pre>";

echo "<h3>📥 Respuesta API</h3>";
echo "<pre>".$response."</pre>";
