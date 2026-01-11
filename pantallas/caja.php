<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../conexion.php';

/* ===============================
   OBTENER VENTAS + PAGOS
================================ */
$sql = "
SELECT 
    v.id AS venta_id,
    v.total,
    v.fecha,
    v.estado,
    c.id AS caja_id,
    u.nombre AS usuario,
    COALESCE(cl.nombre, 'Público general') AS cliente,
    IFNULL(SUM(p.monto),0) AS total_pagado
FROM ventas v
INNER JOIN cajas c ON v.caja_id = c.id
INNER JOIN usuarios u ON v.usuario_id = u.id
LEFT JOIN clientes cl ON v.cliente_id = cl.id
LEFT JOIN pagos p ON p.referencia_id = v.id AND p.tipo='VENTA'
GROUP BY v.id
ORDER BY v.fecha DESC
";
$ventas = $conexion->query($sql);

/* ===============================
   DETALLE DE PRODUCTOS POR VENTA
================================ */
$sqlDetalle = "
SELECT 
    vd.venta_id,
    p.nombre AS producto,
    vd.cantidad,
    vd.precio,
    vd.subtotal
FROM venta_detalle vd
INNER JOIN productos p ON p.id = vd.producto_id
ORDER BY vd.venta_id
";

$resDetalle = $conexion->query($sqlDetalle);

$detalles = [];
while ($row = $resDetalle->fetch_assoc()) {
    $detalles[$row['venta_id']][] = $row;
}


?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Caja | Punto de Venta</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/punto/css/sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <style>
    .page-content {
        background: #858b93ff;
        padding: 20px;
        min-height: calc(100vh - 70px)
    }

    .venta-card {
        background: #fff;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .08)
    }

    .venta-header {
        display: flex;
        justify-content: space-between;
        border-bottom: 1px solid #ddd;
        margin-bottom: 10px
    }

    .venta-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
        font-size: 14px
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 10px
    }

    .badge-abierta {
        background: #28a745;
        color: #fff;
        padding: 5px 14px;
        border-radius: 20px
    }

    .badge-cerrada {
        background: #0d6efd;
        color: #fff;
        padding: 5px 14px;
        border-radius: 20px
    }


    .detalle-venta {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 10px;
        margin-top: 12px;
    }

    .detalle-venta table {
        width: 100%;
        font-size: 13px;
    }

    .detalle-venta th {
        background: #dee2e6;
        padding: 6px;
    }

    .detalle-venta td {
        padding: 6px;
    }
    </style>
</head>

<body>
    <?php renderSidebar('Caja'); ?>

    <main class="page-content">
        <h4 class="mb-3">📦 Ventas por Caja</h4>

        <!-- FILTROS -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <input class="form-control col" id="fVenta" placeholder="Venta">
                    <input class="form-control col" id="fCaja" placeholder="Caja">
                    <input class="form-control col" id="fCliente" placeholder="Cliente">
                    <select class="form-select col" id="fEstado">
                        <option value="">Estado</option>
                        <option value="ABIERTA">ABIERTA</option>
                        <option value="CERRADA">CERRADA</option>
                    </select>
                    <input type="date" class="form-control col" id="fDesde">
                    <input type="date" class="form-control col" id="fHasta">
                    <button class="btn btn-outline-secondary col" id="btnReset">🔄</button>
                </div>
                
            </div>
<div class="d-flex gap-2 mb-3 justify-content-end">
            <button class="btn btn-outline-success btn-sm" id="btnCajaExcel">📊 Importar a Excel</button>
            <button class="btn btn-outline-danger btn-sm" id="btnCajaPdf">📄 Importar a PDF</button>
        </div>
        </div>

        

        <?php while($v=$ventas->fetch_assoc()):
$saldo=$v['total']-$v['total_pagado'];
?>
        <div class="venta-card" data-venta="<?= $v['venta_id'] ?>" data-caja="<?= $v['caja_id'] ?>"
            data-cliente="<?= strtolower($v['cliente']) ?>" data-estado="<?= $v['estado'] ?>"
            data-fecha="<?= date('Y-m-d',strtotime($v['fecha'])) ?>">

            <div class="venta-header">
                <strong>Venta #<?= $v['venta_id'] ?> | Caja <?= $v['caja_id'] ?></strong>
                <span><?= date('d/m/Y H:i',strtotime($v['fecha'])) ?></span>
            </div>

            <div class="venta-info">
                <span>👤 <?= $v['usuario'] ?></span>
                <span class="cliente">🧾 <?= $v['cliente'] ?></span>
                <span>💰 Pagado: $<?= number_format($v['total_pagado'],2) ?></span>
                <span><?= $v['estado']=='ABIERTA'
?'<span class="badge-abierta">ABIERTA</span>'
:'<span class="badge-cerrada">CERRADA</span>' ?></span>
            </div>

            <div class="total-row">
                <div class="totales">
                    <strong>Total:</strong> $<?= number_format($v['total'],2) ?><br>
                    <strong>Saldo:</strong> $<?= number_format($saldo,2) ?>
                </div>

                <div class="d-flex gap-2">
                    <a href="/punto/acciones/imprimir_venta.php?id=<?= $v['venta_id'] ?>" target="_blank"
                        class="btn btn-outline-primary btn-sm">🖨 Imprimir</a>

                    <?php if($v['estado']=='ABIERTA'): ?>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalPago"
                        data-venta="<?= $v['venta_id'] ?>" data-total="<?= $v['total'] ?>"
                        data-pagado="<?= $v['total_pagado'] ?>">💳</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($detalles[$v['venta_id']])): ?>
            <div class="detalle-venta">
                <strong>🧾 Productos vendidos</strong>

                <table class="table table-sm table-bordered mt-2 mb-0">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-end">Cantidad</th>
                            <th class="text-end">Precio</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles[$v['venta_id']] as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['producto']) ?></td>
                            <td class="text-end"><?= number_format($d['cantidad'],3) ?></td>
                            <td class="text-end">$<?= number_format($d['precio'],2) ?></td>
                            <td class="text-end">$<?= number_format($d['subtotal'],2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </div>
        <?php endwhile; ?>
    </main>

    <!-- MODAL PAGO -->
    <div class="modal fade" id="modalPago" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="/punto/acciones/guardar_pago.php">
                <div class="modal-header">
                    <h5>Abonar pago</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="venta_id" id="pagoVentaId">
                    <p>Total: $<span id="pTotal"></span></p>
                    <p>Pagado: $<span id="pPagado"></span></p>
                    <p class="text-danger">Saldo: $<span id="pSaldo"></span></p>
                    <input type="number" class="form-control" name="monto" id="pMonto" step="0.01" required>
                    <select class="form-select mt-2" name="metodo_pago">
                        <option>EFECTIVO</option>
                        <option>TARJETA</option>
                        <option>TRANSFERENCIA</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.5.29/dist/jspdf.plugin.autotable.min.js"></script>

    <script>
    const cards = [...document.querySelectorAll('.venta-card')]

    function filtrar() {
        cards.forEach(c => {
            let ok = true
            if (fVenta.value && !c.dataset.venta.includes(fVenta.value)) ok = false
            if (fCaja.value && !c.dataset.caja.includes(fCaja.value)) ok = false
            if (fCliente.value && !c.dataset.cliente.includes(fCliente.value.toLowerCase())) ok = false
            if (fEstado.value && c.dataset.estado !== fEstado.value) ok = false
            if (fDesde.value && c.dataset.fecha < fDesde.value) ok = false
            if (fHasta.value && c.dataset.fecha > fHasta.value) ok = false
            c.style.display = ok ? '' : 'none'
        })
    }
    [fVenta, fCaja, fCliente, fEstado, fDesde, fHasta].forEach(i => i.oninput = filtrar)
    btnReset.onclick = () => {
        fVenta.value = fCaja.value = fCliente.value = fEstado.value = fDesde.value = fHasta.value = '';
        filtrar()
    }
    </script>

    <script>
    btnCajaExcel.onclick = () => {
        const filas = cards.filter(c => c.style.display !== 'none').map(c => {
            const t = c.querySelector('.totales').innerText.split('\n')
            return [
                c.dataset.venta,
                c.dataset.caja,
                c.querySelector('.cliente').innerText.replace('🧾', '').trim(),
                c.dataset.estado,
                t[0].replace('Total:', '').trim(),
                t[1].replace('Saldo:', '').trim()
            ]
        })
        filas.unshift(['Venta', 'Caja', 'Cliente', 'Estado', 'Total', 'Saldo'])
        const wb = XLSX.utils.book_new()
        XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(filas), 'Caja')
        XLSX.writeFile(wb, 'caja_ventas.xlsx')
    }
    </script>

    <script>
    btnCajaPdf.onclick = () => {
        const {
            jsPDF
        } = window.jspdf
        const doc = new jsPDF('l')
        const filas = cards.filter(c => c.style.display !== 'none').map(c => {
            const t = c.querySelector('.totales').innerText.split('\n')
            return [
                c.dataset.venta,
                c.dataset.caja,
                c.querySelector('.cliente').innerText.replace('🧾', '').trim(),
                c.dataset.estado,
                t[0].replace('Total:', '').trim(),
                t[1].replace('Saldo:', '').trim()
            ]
        })
        doc.text('Reporte de Caja', 14, 15)
        doc.autoTable({
            startY: 20,
            head: [
                ['Venta', 'Caja', 'Cliente', 'Estado', 'Total', 'Saldo']
            ],
            body: filas
        })
        doc.save('caja_ventas.pdf')
    }
    </script>

    <script>
    modalPago.addEventListener('show.bs.modal', e => {
        const b = e.relatedTarget
        const t = parseFloat(b.dataset.total)
        const p = parseFloat(b.dataset.pagado)
        pagoVentaId.value = b.dataset.venta
        pTotal.textContent = t.toFixed(2)
        pPagado.textContent = p.toFixed(2)
        pSaldo.textContent = (t - p).toFixed(2)
        pMonto.max = (t - p).toFixed(2)
        pMonto.value = ''
    })
    </script>

</body>

</html>