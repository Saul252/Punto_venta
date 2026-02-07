<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../conexion.php';
date_default_timezone_set('America/Mexico_City');
/* =========================
   CONSULTAR CLIENTES SANDBOX
========================= */

$clientesSandbox = [];

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => 'https://facturaonline.com.mx/api/v1/clients',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'F-PLUGIN: 9d4095c8f7ed5785cb14c0e3b033eeb8252416ed',        
'F-Api-Key: JDJ5JDEwJDJ5YW16MVNacHFHUkxHODZBQnptQWVWN00wdFJ0b1loNVlQWlBWOXBHdlJ4Y2xGNzdiOEcy',
'F-Secret-Key: JDJ5JDEwJGQ3WXd5d212dzVoQTdBUy9ILzAzUmVHVEJkd3AvRFZZdmZBNkNidmp1VkU2WVJac0RLenVx' ],
]);

$response = curl_exec($curl);

if ($response !== false) {
    $json = json_decode($response, true);
    if (!empty($json['data'])) {
        $clientesSandbox = $json['data'];
    }
}

curl_close($curl);


/* =========================
   VALIDAR IDS RECIBIDOS
========================= */

if(empty($_POST['ventas'])) {
    die("No se recibieron ventas para factura global");
}

$ids = array_map('intval', $_POST['ventas']);
$lista = implode(',', $ids);

/* =========================
   CONSULTA VENTAS
========================= */

$ventas = $conexion->query("
SELECT 
    v.id,
    v.fecha,
    v.total,
    IFNULL(v.total_pagado,0) pagado,
    (v.total - IFNULL(v.total_pagado,0)) saldo,
    c.nombre cliente,
    c.rfc
FROM ventas v
LEFT JOIN clientes c ON c.id = v.cliente_id
WHERE v.id IN ($lista)
ORDER BY v.fecha DESC
");

/* =========================
   TOTALES
========================= */

$total = 0;
while($r = $ventas->fetch_assoc()){
    $total += $r['total'];
    $rows[] = $r;
}

$ventas->data_seek(0);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Factura Global</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php renderSidebar('Factura Global'); ?>

    <div class="container py-4">

        <h3>🧾 Factura Global CFDI 4.0</h3>

        <div class="alert alert-info">
            Se generará una factura global con las ventas seleccionadas.
        </div>

        <!-- ================= RESUMEN ================= -->

        <div class="card p-3 mb-4">
            <h5>Total Global:</h5>
            <h3 class="text-success">$<?= number_format($total,2) ?></h3>
        </div>

        <!-- ================= FORM ENVIO API ================= -->

        <form method="POST" action="/punto/acciones/apifacturacionGlobal.php">

            <input type="hidden" name="factura_global" value="1">

            <?php foreach($ids as $id): ?>
            <input type="hidden" name="ventas[]" value="<?= $id ?>">
            <?php endforeach; ?>
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


            <!-- ================= PERIODICIDAD SAT ================= -->

            <div class="card p-3 mb-4">
                <h5>Periodicidad CFDI Global</h5>

                <div class="row g-3">

                    <div class="col-md-4">
                        <label>Periodicidad</label>
                        <select name="periodicidad" class="form-select" required>
                            <option value="01">Diario</option>
                            <option value="02">Semanal</option>
                            <option value="03">Quincenal</option>
                            <option value="04">Mensual</option>
                            <option value="05">Bimestral</option>
                        </select>
                    </div>
                    <select name="forma_pago" class="form-select" required>
                        <option value="01">Efectivo</option>
                        <option value="03">Transferencia</option>
                        <option value="04">Tarjeta</option>
                        <option value="99">Por definir</option>
                    </select>

                    <select name="metodo_pago" class="form-select" required>
                        <option value="PUE">Pago en una exhibición</option>
                        <option value="PPD">Pago en parcialidades</option>
                    </select>

                    <div class="col-md-4">
                        <label>Meses</label>
                        <select name="meses" class="form-select" required>
                            <?php for($i=1;$i<=18;$i++): ?>
                            <option value="<?= str_pad($i,2,'0',STR_PAD_LEFT) ?>">
                                <?= $i ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Año</label>
                        <input type="number" name="anio" class="form-control" value="<?= date('Y') ?>" required>
                    </div>

                </div>
            </div>

            <!-- ================= TABLA VENTAS ================= -->

            <div class="card p-3 mb-4">
                <h5>Ventas incluidas</h5>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach($rows as $v): ?>
                        <tr>
                            <td><?= $v['id'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                            <td>
                                <?= htmlspecialchars($v['cliente'] ?? 'PUBLICO GENERAL') ?><br>
                                <small><?= $v['rfc'] ?></small>
                            </td>
                            <td>$<?= number_format($v['total'],2) ?></td>
                            <td>$<?= number_format($v['pagado'],2) ?></td>
                            <td>$<?= number_format($v['saldo'],2) ?></td>
                        </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

            <button class="btn btn-success btn-lg">
                🚀 Generar Factura Global
            </button>

        </form>

    </div>
</body>

</html>