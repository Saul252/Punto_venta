<?php

require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../conexion.php';
date_default_timezone_set('America/Mexico_City');

/* =========================
   RANGO DE FECHAS
========================= */
$rango = $_GET['rango'] ?? 'HOY';
$desde = $_GET['desde'] ?? date('Y-m-d');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$hoy   = date('Y-m-d');

switch ($rango) {
    case 'AYER':
        $desde = $hasta = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'SEMANA':
        $desde = date('Y-m-d', strtotime('monday this week'));
        $hasta = $hoy;
        break;
    case 'MES':
        $desde = date('Y-m-01');
        $hasta = $hoy;
        break;
    case 'PERSONALIZADO':
        break;
    default:
        $desde = $hasta = $hoy;
}

/* =========================
   TOTALES
========================= */
$totalVentas = $conexion->query("
    SELECT IFNULL(SUM(total),0) total
    FROM ventas
    WHERE estado='CERRADA'
    AND DATE(fecha) BETWEEN '$desde' AND '$hasta'
")->fetch_assoc()['total'];

$totalGastos = $conexion->query("
    SELECT IFNULL(SUM(monto),0) total
    FROM gastos
    WHERE DATE(fecha) BETWEEN '$desde' AND '$hasta'
")->fetch_assoc()['total'];

/* 🔴 ADEUDOS = ventas ABIERTAS */
$totalAdeudos = $conexion->query("
    SELECT IFNULL(SUM(total - total_pagado),0) total
    FROM ventas
    WHERE estado='ABIERTA'
    AND DATE(fecha) BETWEEN '$desde' AND '$hasta'
")->fetch_assoc()['total'];

$balance = $totalVentas - ($totalGastos + $totalAdeudos);

/* =========================
   MOVIMIENTOS
========================= */
$movimientos = $conexion->query("
(
    SELECT 
        fecha,
        'VENTA' tipo,
        CONCAT('Venta #',id) descripcion,
        metodo_pago metodo,
        total monto
    FROM ventas
    WHERE estado='CERRADA'
    AND DATE(fecha) BETWEEN '$desde' AND '$hasta'
)
UNION ALL
(
    SELECT 
        fecha,
        'GASTO',
        descripcion,
        metodo_pago,
        monto * -1
    FROM gastos
    WHERE DATE(fecha) BETWEEN '$desde' AND '$hasta'
)
UNION ALL
(
    SELECT
        fecha,
        'ADEUDO',
        CONCAT('Venta #',id,' (saldo pendiente)'),
        metodo_pago,
        (total - total_pagado) * -1
    FROM ventas
    WHERE estado='ABIERTA'
    AND DATE(fecha) BETWEEN '$desde' AND '$hasta'
)
ORDER BY fecha DESC
");

/* =========================
   DATOS PARA GRÁFICAS
========================= */
$labels = [];
$ventasDia = [];
$gastosDia = [];
$adeudosDia = [];

$graf = $conexion->query("
SELECT d,
SUM(ventas) ventas,
SUM(gastos) gastos,
SUM(adeudos) adeudos
FROM (
    SELECT DATE(fecha) d, total ventas, 0 gastos, 0 adeudos
    FROM ventas
    WHERE estado='CERRADA'
    AND DATE(fecha) BETWEEN '$desde' AND '$hasta'

    UNION ALL
    SELECT DATE(fecha), 0, monto, 0
    FROM gastos
    WHERE DATE(fecha) BETWEEN '$desde' AND '$hasta'

    UNION ALL
    SELECT DATE(fecha), 0, 0, (total - total_pagado)
    FROM ventas
    WHERE estado='ABIERTA'
    AND DATE(fecha) BETWEEN '$desde' AND '$hasta'
) t
GROUP BY d
ORDER BY d
");

while($g = $graf->fetch_assoc()){
    $labels[]     = $g['d'];
    $ventasDia[]  = $g['ventas'];
    $gastosDia[]  = $g['gastos'];
    $adeudosDia[] = $g['adeudos'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Finanzas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
    body {
        background: #eef2f7
    }

    .glass {
        background: rgba(255, 255, 255, .8);
        backdrop-filter: blur(14px);
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, .4);
        box-shadow: 0 15px 35px rgba(0, 0, 0, .1)
    }

    .borde {
        background: #fff;
        border: 2px solid rgba(13, 110, 253, .35);
        border-radius: 12px;
        color: #212529 !important;
        -webkit-text-fill-color: #212529
    }
    </style>
</head>

<body>
    <?php renderSidebar(paginaActual: 'Finanzas'); ?>
    <div class="container py-4">

        <h3 class="fw-bold mb-3">📊 Finanzas / Balance</h3>

        <!-- ================= FILTROS ================= -->
        <form class="glass p-4 mb-4" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Rango</label>
                    <select name="rango" class="form-select borde">
                        <?php foreach(['HOY','AYER','SEMANA','MES','PERSONALIZADO'] as $op): ?>
                        <option value="<?= $op ?>" <?= $rango==$op?'selected':'' ?>><?= $op ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Desde</label>
                    <input type="date" name="desde" class="form-control borde" value="<?= $desde ?>"
                        <?= $rango!='PERSONALIZADO'?'disabled':'' ?>>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="hasta" class="form-control borde" value="<?= $hasta ?>"
                        <?= $rango!='PERSONALIZADO'?'disabled':'' ?>>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100">🔍 Aplicar</button>
                </div>
            </div>
        </form>

        <!-- ================= TARJETAS ================= -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="glass p-3 text-center">
                    <h6 class="text-muted">💰 Ventas</h6>
                    <h3 class="text-success">$<?= number_format($totalVentas,2) ?></h3>
                </div>
            </div>

            <div class="col-md-3">
                <div class="glass p-3 text-center">
                    <h6 class="text-muted">💸 Gastos</h6>
                    <h3 class="text-danger">$<?= number_format($totalGastos,2) ?></h3>
                </div>
            </div>

            <div class="col-md-3">
                <div class="glass p-3 text-center">
                    <h6 class="text-muted">📉 Adeudos</h6>
                    <h3 class="text-warning">$<?= number_format($totalAdeudos,2) ?></h3>
                </div>
            </div>

            <div class="col-md-3">
                <div class="glass p-3 text-center">
                    <h6 class="text-muted">📊 Balance</h6>
                    <h3 class="<?= $balance>=0?'text-success':'text-danger' ?>">
                        $<?= number_format($balance,2) ?>
                    </h3>
                </div>
            </div>
        </div>

        <!-- ================= GRAFICAS ================= -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="glass p-3"><canvas id="chartBar"></canvas></div>
            </div>
            <div class="col-md-6">
                <div class="glass p-3"><canvas id="chartLine"></canvas></div>
            </div>
        </div>

        <!-- ================= TABLA ================= -->
        <div class="glass p-4">
            <h5 class="mb-3">🧾 Movimientos</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Método</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($m=$movimientos->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i',strtotime($m['fecha'])) ?></td>
                            <td><?= $m['tipo'] ?></td>
                            <td><?= htmlspecialchars($m['descripcion']) ?></td>
                            <td><?= $m['metodo'] ?></td>
                            <td class="text-end <?= $m['monto']<0?'text-danger':'text-success' ?>">
                                $<?= number_format($m['monto'],2) ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
    new Chart(chartBar, {
        type: 'bar',
        data: {
            labels: ['Ventas', 'Gastos', 'Adeudos', 'Balance'],
            datasets: [{
                data: [
                    <?= $totalVentas ?>,
                    <?= $totalGastos ?>,
                    <?= $totalAdeudos ?>,
                    <?= $balance ?>
                ],
                backgroundColor: ['#198754', '#dc3545', '#ffc107', '#0d6efd']
            }]
        }
    });

    new Chart(chartLine, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                    label: 'Ventas',
                    data: <?= json_encode($ventasDia) ?>,
                    borderColor: '#198754'
                },
                {
                    label: 'Gastos',
                    data: <?= json_encode($gastosDia) ?>,
                    borderColor: '#dc3545'
                },
                {
                    label: 'Adeudos',
                    data: <?= json_encode($adeudosDia) ?>,
                    borderColor: '#ffc107'
                }
            ]
        }
    });
    </script>

</body>

</html>