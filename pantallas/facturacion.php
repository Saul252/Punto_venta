<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../conexion.php';
date_default_timezone_set('America/Mexico_City');

/* =========================
   RANGO
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
   VENTAS
========================= */
$ventas = $conexion->query("
SELECT 
    v.id,
    v.fecha,
    v.total,
    IFNULL(v.total_pagado,0) pagado,
    (v.total - IFNULL(v.total_pagado,0)) saldo,
    v.factura_uuid,
    c.nombre cliente,
    c.rfc
FROM ventas v
LEFT JOIN clientes c ON c.id = v.cliente_id
WHERE v.estado='CERRADA'
AND DATE(v.fecha) BETWEEN '$desde' AND '$hasta'
ORDER BY v.fecha DESC

");
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Facturación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php renderSidebar('Facturacion'); ?>

    <div class="container py-4">

        <h3>🧾 Facturación</h3>

        <!-- ================= FILTROS ================= -->

        <form method="GET" class="card p-3 mb-4">
            <div class="row g-3">

                <div class="col-md-3">
                    <label>Rango</label>
                    <select name="rango" id="fRango" class="form-select">
                        <?php foreach(['HOY','AYER','SEMANA','MES','PERSONALIZADO'] as $op): ?>
                        <option value="<?= $op ?>" <?= $rango==$op?'selected':'' ?>><?= $op ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Desde</label>
                    <input type="date" name="desde" id="fDesde" value="<?= $desde ?>" class="form-control"
                        <?= $rango!='PERSONALIZADO'?'disabled':'' ?>>
                </div>

                <div class="col-md-3">
                    <label>Hasta</label>
                    <input type="date" name="hasta" id="fHasta" value="<?= $hasta ?>" class="form-control"
                        <?= $rango!='PERSONALIZADO'?'disabled':'' ?>>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Filtrar</button>
                </div>

            </div>
        </form>

        <!-- ================= TABLA ================= -->

        <form method="POST" action="/punto/pantallas/facturaglobal.php">

            <button class="btn btn-success mb-3" id="btnGlobal" disabled>
                🧾 Factura Global
            </button>

            <table class="table table-bordered">

                <thead>
                    <tr>
                        <th><input type="checkbox" id="checkAll"></th>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Pagado</th>
                        <th>Saldo</th>
                        <th>Factura</th>

                        
                    </tr>
                </thead>

                <tbody>

                    <?php while($v=$ventas->fetch_assoc()): ?>
                    <tr>

                        <td>
                            <input type="checkbox" name="ventas[]" value="<?= $v['id'] ?>" class="checkVenta">
                        </td>

                        <td><?= $v['id'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>

                        <td>
                            <?= htmlspecialchars($v['cliente'] ?? 'PUBLICO GENERAL') ?><br>
                            <small><?= $v['rfc'] ?></small>
                        </td>

                        <td>$<?= number_format($v['total'],2) ?></td>
                        <td>$<?= number_format($v['pagado'],2) ?></td>
                        <td>$<?= number_format($v['saldo'],2) ?></td>


                        <td>
                            <?php if($facturada): ?>
                            <span class="badge bg-success">
                                <?= substr($v['factura_uuid'],0,8) ?>…
                            </span>
                            <?php else: ?>
                            <a href="/punto/acciones/apifacturacion.php?venta_id=<?= $v['id'] ?>"
                                class="btn btn-sm btn-outline-primary">
                                Facturar
                            </a>
                            <?php endif; ?>
                        </td>


                    </tr>
                    <?php endwhile; ?>

                </tbody>
            </table>

        </form>

    </div>

    <script>
    document.getElementById('fRango').addEventListener('change', function() {
        const p = this.value === 'PERSONALIZADO';
        document.getElementById('fDesde').disabled = !p;
        document.getElementById('fHasta').disabled = !p;
    });

    const checkAll = document.getElementById('checkAll');
    const checks = document.querySelectorAll('.checkVenta');
    const btn = document.getElementById('btnGlobal');

    function validar() {
        btn.disabled = document.querySelectorAll('.checkVenta:checked').length == 0;
    }

    checkAll.addEventListener('change', e => {
        checks.forEach(c => c.checked = e.target.checked);
        validar();
    });

    checks.forEach(c => c.addEventListener('change', validar));
    </script>

</body>

</html>