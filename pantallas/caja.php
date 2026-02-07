<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../conexion.php';

/* ===============================
   OBTENER VENTAS + PAGOS
================================ */
date_default_timezone_set('America/Mexico_City');

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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/punto/css/sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <style>
    body {
        background-color: #0d1b2a;

    }

    .page-content {
        background: linear-gradient(135deg, #1066bb, #065d7a, #00b3ff);
        padding: 25px;
        min-height: calc(100vh - 70px);
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

    /* ===== GLASS EFFECT ===== */
    .card,
    .venta-card {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
        color: #fff;
    }

    .venta-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.25);
        margin-bottom: 10px;
        font-weight: 600;
    }

    .venta-info span,
    .totales,
    .card-body {
        color: #f1f5f9;
    }

    .badge-abierta {
        background: rgba(40, 167, 69, 0.85);
        backdrop-filter: blur(6px);
        border-radius: 20px;
        padding: 6px 14px;
    }

    .badge-cerrada {
        background: rgba(13, 110, 253, 0.85);
        backdrop-filter: blur(6px);
        border-radius: 20px;
        padding: 6px 14px;
    }

    .detalle-venta {
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 12px;
        margin-top: 12px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .detalle-venta table {
        color: #fff;
    }

    .detalle-venta th {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    .venta-card {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .venta-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
    }

    .form-control,
    .form-select {
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #fff;
    }

    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }

    .form-control:focus,
    .form-select:focus {
        background: rgba(255, 255, 255, 0.25);
        color: #fff;
        border-color: #66b2ff;
        box-shadow: 0 0 0 0.2rem rgba(102, 178, 255, 0.25);
    }

    .texto-negro {
        color: #000;
    }

    .borde {
        background: rgba(255, 255, 255, 0.85);
        border: 2px solid rgba(13, 110, 253, 0.35);
        /* azul Bootstrap */
        border-radius: 10px;
        color: #212529;
        backdrop-filter: blur(4px);
        transition: all .2s ease-in-out;
    }

    .borde:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, .25);
        background: rgba(255, 255, 255, 0.95);
    }

    .borde::placeholder {
        color: #6c757d;
    }
    </style>
</head>

<body>
    <?php renderSidebar('Caja'); ?>

    <main class="page-content">
        <h4 class="mb-3">📦 Ventas por Caja</h4>

        <!-- FILTROS -->
        <!-- FILTROS -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-2">
                        <label class="form-label">Venta #</label>
                        <input class="form-control" id="fVenta" placeholder="Ej. 123">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Caja</label>
                        <input class="form-control" id="fCaja" placeholder="Ej. 1">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Cliente</label>
                        <input class="form-control" id="fCliente" placeholder="Nombre del cliente">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <select class="form-select" id="fEstado">
                            <option class="texto-negro" value="">Todos</option>
                            <option class="texto-negro" value="ABIERTA">Abierta</option>
                            <option class="texto-negro" value="CERRADA">Cerrada</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Rango de fechas</label>
                        <select class="form-select" id="fRango">
                            <option class="texto-negro" value="HOY">Hoy</option>
                            <option class="texto-negro" value="AYER">Ayer</option>
                            <option class="texto-negro" value="SEMANA">Esta semana</option>
                            <option class="texto-negro" value="MES">Este mes</option>
                            <option class="texto-negro" value="PERSONALIZADO">Personalizado</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Fecha inicio</label>
                        <input type="date" class="form-control" id="fDesde" disabled>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Fecha fin</label>
                        <input type="date" class="form-control" id="fHasta" disabled>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary " id="btnReset">Restear filtros 🔄</button>
                    </div>

                </div>
            </div>

            <div class="d-flex gap-2 mb-3 justify-content-end px-3">
                <button class="btn btn-success btn-sm" id="btnCajaExcel">📊 Exportar Excel</button>
                <button class="btn btn-danger btn-sm" id="btnCajaPdf">📄 Exportar PDF</button>
            </div>
        </div>


        <!-- TOTALES GENERALES -->
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Totales (según filtros)</h6>
                <div>
                    <span class="me-3">
                        💰 <strong>Total:</strong> $
                        <span id="totalVentas">0.00</span>
                    </span>
                    <span class="text-danger">
                        ⚠ <strong>Saldo:</strong> $
                        <span id="totalSaldo">0.00</span>
                    </span>
                </div>
            </div>
        </div>


        <?php while($v=$ventas->fetch_assoc()):
        $saldo=$v['total']-$v['total_pagado'];?>
        <div class="venta-card" data-venta="<?= $v['venta_id'] ?>" data-caja="<?= $v['caja_id'] ?>"
            data-cliente="<?= strtolower($v['cliente']) ?>" data-estado="<?= $v['estado'] ?>"
            data-fecha="<?= date('Y-m-d',strtotime($v['fecha'])) ?>" data-total="<?= $v['total'] ?>"
            data-saldo="<?= $saldo ?>">
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
                    <a href="/punto/pantallas/ventas/editar_venta.php?id=<?= $v['venta_id'] ?>"
                        class="btn btn-warning btn-sm">
                        ✏️ Editar
                    </a>

                    <button onclick="eliminarVenta(<?= $v['venta_id'] ?>)" class="btn btn-danger">
                        Eliminar venta
                    </button>
                    <a href="/punto/acciones/imprimir_venta.php?id=<?= $v['venta_id'] ?>" target="_blank"
                        class="btn btn-primary btn-sm">🖨 Imprimir</a>
                    <?php if($v['estado']=='ABIERTA'): ?>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalPago"
                        data-venta="<?= $v['venta_id'] ?>" data-total="<?= $v['total'] ?>"
                        data-pagado="<?= $v['total_pagado'] ?>" data-saldo="<?= $saldo ?>">
                        💳
                    </button>

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
        <div class="modal-content">

            <div class="modal-header">
                <h5>Abonar pago</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body bg-light text-dark rounded-3">

                <!-- FORMULARIO NORMAL DE PAGO -->
                <form method="POST" action="/punto/acciones/guardar_pago.php" id="formPago">

                    <input type="hidden" name="venta_id" id="pagoVentaId">

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="p-2 border rounded text-center bg-white">
                                <small class="text-muted">Total</small>
                                <div class="fw-bold text-primary">
                                    $<span id="pTotal"></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="p-2 border rounded text-center bg-white">
                                <small class="text-muted">Pagado</small>
                                <div class="fw-bold text-success">
                                    $<span id="pPagado"></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="p-2 border rounded text-center bg-white">
                                <small class="text-muted">Saldo</small>
                                <div class="fw-bold text-danger">
                                    $<span id="pSaldo"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Monto a pagar</label>
                        <input type="number"
                               class="form-control form-control-lg borde text-dark"
                               name="monto"
                               id="pMonto"
                               step="0.01">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Método de pago</label>
                        <select class="form-select form-select-lg borde" name="metodo_pago">
                            <option class="texto-negro" value="EFECTIVO">💵 Efectivo</option>
                            <option class="texto-negro" value="TARJETA">💳 Tarjeta</option>
                            <option class="texto-negro" value="TRANSFERENCIA">🏦 Transferencia</option>
                        </select>
                    </div>

                    <div class="modal-footer px-0">
                        <button class="btn btn-success w-100" id="btnGuardarPago">
                            Guardar pago
                        </button>
                    </div>

                </form>

                <!-- BLOQUE SALDO A FAVOR (SE ACTIVA SOLO SI SALDO < 0) -->
                <div id="bloqueSaldoFavor" class="mt-3 d-none">

                    <hr>

                    <label class="form-label fw-semibold text-danger">
                        Saldo a favor del cliente
                    </label>

                    <input type="number"
                           class="form-control borde text-dark mb-2"
                           id="saldoFavor"
                           readonly>

                    <!-- FORMULARIO PHP PURO -->
                    <form method="POST" action="/punto/acciones/ventas/ajustar_saldo.php">
                        <input type="hidden" name="venta_id" id="ajusteVentaId">
                        <button type="submit" class="btn btn-outline-danger w-100">
                            🔁 Devolver saldo a favor
                        </button>
                    </form>

                </div>

            </div>

        </div>
    </div>
</div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.5.29/dist/jspdf.plugin.autotable.min.js"></script>  
    <script>
    function eliminarVenta(id) {
        Swal.fire({
            title: '¿Eliminar venta?',
            text: 'Esta acción NO se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('/punto/acciones/eliminar_venta.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'venta_id=' + id
                    })
                    .then(r => r.text())
                    .then(resp => {
                        if (resp.trim() === 'OK') {
                            Swal.fire('Eliminada', '', 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error', resp, 'error');
                        }
                    });
            }
        });
    }
    </script>

    <script>
    const cards = [...document.querySelectorAll('.venta-card')]
    const totalVentas = document.getElementById('totalVentas')
    const totalSaldo = document.getElementById('totalSaldo')

    function recalcularTotales() {
        let t = 0
        let s = 0

        cards.forEach(c => {
            if (c.offsetParent !== null) {
                t += Number(c.dataset.total)
                s += Number(c.dataset.saldo)
            }
        })

        totalVentas.textContent = t.toFixed(2)
        totalSaldo.textContent = s.toFixed(2)
    }

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

        recalcularTotales()
    }

    [fVenta, fCaja, fCliente, fEstado, fDesde, fHasta].forEach(i => {
        i.addEventListener('input', filtrar)
    })

    btnReset.onclick = () => {
        fVenta.value = fCaja.value = fCliente.value = ''
        fEstado.value = ''
        fRango.value = 'HOY'
        setRango('HOY')
    }


    // cálculo inicial
    recalcularTotales()
    </script>



    <script>
       btnCajaExcel.onclick = () => {

    const filas = []

    // ENCABEZADOS
    filas.push([
        'Venta',
        'Caja',
        'Cliente',
        'Producto',
        'Cantidad',
        'Precio',
        'Subtotal',
        'Total venta'
    ])

    cards
        .filter(c => c.style.display !== 'none')
        .forEach(c => {

            const venta   = c.dataset.venta
            const caja    = c.dataset.caja
            const cliente = c.querySelector('.cliente')
                .innerText.replace('🧾', '').trim()
            const totalVenta = c.dataset.total

            const tbody = c.querySelector('.detalle-venta table tbody')

            if (!tbody) return

            let primeraFila = true

            tbody.querySelectorAll('tr').forEach(tr => {
                const tds = tr.querySelectorAll('td')

                filas.push([
                    primeraFila ? venta : '',
                    primeraFila ? caja : '',
                    primeraFila ? cliente : '',
                    tds[0].innerText, // Producto
                    tds[1].innerText, // Cantidad
                    tds[2].innerText, // Precio
                    tds[3].innerText, // Subtotal
                    primeraFila ? totalVenta : ''
                ])

                primeraFila = false
            })
        })

    const wb = XLSX.utils.book_new()
    const ws = XLSX.utils.aoa_to_sheet(filas)

    XLSX.utils.book_append_sheet(wb, ws, 'Ventas detalladas')
    XLSX.writeFile(wb, 'caja_ventas_detallado.xlsx')
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

<script>
modalPago.addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget
    const t = parseFloat(b.dataset.total)
    const p = parseFloat(b.dataset.pagado)
    const saldo = parseFloat(b.dataset.saldo)

    pagoVentaId.value = b.dataset.venta
    pTotal.textContent = t.toFixed(2)
    pPagado.textContent = p.toFixed(2)
    pSaldo.textContent = saldo.toFixed(2)

    const bloque = document.getElementById('bloqueSaldoFavor')
    const saldoInput = document.getElementById('saldoFavor')
    const ajusteVentaId = document.getElementById('ajusteVentaId')

    if (saldo < 0) {
        bloque.classList.remove('d-none')
        saldoInput.value = Math.abs(saldo).toFixed(2)
        ajusteVentaId.value = b.dataset.venta
        pMonto.disabled = true
    } else {
        bloque.classList.add('d-none')
        pMonto.disabled = false
    }
})
</script>

<script>
const fRango = document.getElementById('fRango')

function hoyMX() {
    const d = new Date()
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset())
    return d.toISOString().split('T')[0]
}

function setRango(tipo) {
    const hoy = new Date()
    let desde = null
    let hasta = null

    switch (tipo) {
        case 'HOY':
            desde = hasta = hoy
            break

        case 'AYER':
            desde = hasta = new Date(hoy.setDate(hoy.getDate() - 1))
            break

        case 'SEMANA':
            const dia = hoy.getDay() || 7
            desde = new Date(hoy)
            desde.setDate(hoy.getDate() - dia + 1)
            hasta = new Date()
            break

        case 'MES':
            desde = new Date(hoy.getFullYear(), hoy.getMonth(), 1)
            hasta = new Date()
            break

        case 'PERSONALIZADO':
            fDesde.disabled = false
            fHasta.disabled = false
            return
    }

    fDesde.disabled = true
    fHasta.disabled = true

    fDesde.value =
    desde.getFullYear() + '-' +
    String(desde.getMonth() + 1).padStart(2, '0') + '-' +
    String(desde.getDate()).padStart(2, '0')

fHasta.value =
    hasta.getFullYear() + '-' +
    String(hasta.getMonth() + 1).padStart(2, '0') + '-' +
    String(hasta.getDate()).padStart(2, '0')


    filtrar()
}

fRango.addEventListener('change', e => {
    setRango(e.target.value)
})

fDesde.addEventListener('change', filtrar)
fHasta.addEventListener('change', filtrar)

// 🔥 Inicializar en HOY
setRango('HOY')
</script>
</body>
</html>