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
        // usar desde / hasta
        break;
    default: // HOY
        $desde = $hasta = $hoy;
}

/* =========================
   VENTAS PARA FACTURACIÓN
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
WHERE v.estado = 'CERRADA'
AND DATE(v.fecha) BETWEEN '$desde' AND '$hasta'
ORDER BY v.fecha DESC
");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Facturación</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    body {
        background: #eef2f7
    }

    .glass {
        background: rgba(255, 255, 255, .85);
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, .4);
        box-shadow: 0 15px 35px rgba(0, 0, 0, .1)
    }

    .borde {
        background: #fff;
        border: 2px solid rgba(13, 110, 253, .35);
        border-radius: 12px;
        color: #212529 !important;
        -webkit-text-fill-color: #212529;
    }
    </style>
</head>

<body>
    <?php renderSidebar('Facturacion'); ?>

    <div class="container py-4">

        <h3 class="fw-bold mb-3">🧾 Facturación</h3>

        <!-- ================= FILTROS ================= -->
        <form method="GET" class="glass p-4 mb-4">
            <div class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label">Rango</label>
                    <select name="rango" id="fRango" class="form-select borde">
                        <?php foreach(['HOY','AYER','SEMANA','MES','PERSONALIZADO'] as $op): ?>
                        <option value="<?= $op ?>" <?= $rango==$op?'selected':'' ?>>
                            <?= $op ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Desde</label>
                    <input type="date" name="desde" id="fDesde" class="form-control borde" value="<?= $desde ?>"
                        <?= $rango!='PERSONALIZADO'?'disabled':'' ?>>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="hasta" id="fHasta" class="form-control borde" value="<?= $hasta ?>"
                        <?= $rango!='PERSONALIZADO'?'disabled':'' ?>>
                </div>

                <div class="col-md-3">
                    <button class="btn btn-primary w-100">🔍 Aplicar</button>
                </div>

            </div>
        </form>

        <!-- ================= TABLA ================= -->
        <div class="glass p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th># Venta</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php if($ventas->num_rows == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No hay ventas</td>
                        </tr>
                        <?php endif; ?>

                        <?php while($v = $ventas->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $v['id'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                            <td>
                                <?= htmlspecialchars($v['cliente'] ?? 'PÚBLICO EN GENERAL') ?><br>
                                <small class="text-muted"><?= $v['rfc'] ?></small>
                            </td>
                            <td class="text-success">$<?= number_format($v['total'],2) ?></td>
                            <td class="text-primary">$<?= number_format($v['pagado'],2) ?></td>
                            <td class="<?= $v['saldo']>0?'text-danger':'text-success' ?>">
                                $<?= number_format($v['saldo'],2) ?>
                            </td>
                            <td>
                                <a href="/punto/acciones/apifacturacion.php?venta_id=<?= $v['id'] ?>"
                                    class="btn btn-outline-primary btn-sm">
                                    📤 Facturar
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>

                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
    document.getElementById('fRango').addEventListener('change', function() {
        const personal = this.value === 'PERSONALIZADO';
        document.getElementById('fDesde').disabled = !personal;
        document.getElementById('fHasta').disabled = !personal;
    });
    </script>

</body>

</html>