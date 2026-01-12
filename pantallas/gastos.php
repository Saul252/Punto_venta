<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../conexion.php';

/* ===============================
   GASTOS
================================ */
$gastos = $conexion->query("
    SELECT g.*, p.nombre AS proveedor
    FROM gastos g
    LEFT JOIN proveedores p ON g.proveedor_id = p.id
    ORDER BY g.fecha DESC
");

/* ===============================
   PROVEEDORES
================================ */
$proveedores = $conexion->query("SELECT * FROM proveedores ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gastos</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.card-soft{
    background:#fff;
    border-radius:14px;
    box-shadow:0 10px 25px rgba(0,0,0,.06);
}
.table th{
    font-weight:600;
    color:#555;
}
.table tbody tr:nth-child(even){
    background:#fafafa;
}
</style>
</head>

<body class="bg-light">

<?php renderSidebar('Gastos'); ?>

<div class="container-fluid p-4">

<!-- ================= HEADER ================= -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">💸 Gastos</h3>
    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalGasto">
        + Nuevo gasto
    </button>
</div>

<!-- ================= FILTROS ================= -->
<div class="card card-soft mb-4">
<div class="card-body">

<div class="row g-3 align-items-end">

    <div class="col-md-3">
        <label class="form-label">Proveedor</label>
        <input type="text" id="fProveedor" class="form-control" placeholder="Buscar proveedor">
    </div>

    <div class="col-md-3">
        <label class="form-label">Concepto</label>
        <input type="text" id="fConcepto" class="form-control" placeholder="Buscar concepto">
    </div>

    <div class="col-md-2">
        <label class="form-label">Método</label>
        <select id="fMetodo" class="form-select">
            <option value="">Todos</option>
            <option value="EFECTIVO">EFECTIVO</option>
            <option value="TRANSFERENCIA">TRANSFERENCIA</option>
            <option value="TARJETA">TARJETA</option>
        </select>
    </div>

    <div class="col-md-2">
        <label class="form-label">Desde</label>
        <input type="date" id="fDesde" class="form-control">
    </div>

    <div class="col-md-2">
        <label class="form-label">Hasta</label>
        <input type="date" id="fHasta" class="form-control">
    </div>

    <div class="col-md-1 d-grid">
        <button class="btn btn-outline-secondary" id="btnReset">🔄</button>
    </div>

</div>

<div class="d-flex justify-content-end gap-2 mt-3">
    <button class="btn btn-outline-success btn-sm" id="btnExcel">📊 Excel</button>
    <button class="btn btn-outline-danger btn-sm" id="btnPdf">📄 PDF</button>
</div>

</div>
</div>

<!-- ================= TABLA ================= -->
<div class="card card-soft">
<div class="card-body">

<div class="table-responsive" style="max-height:60vh; overflow:auto;">
<table class="table table-hover align-middle mb-0">
<thead class="table-light">
<tr>
    <th>Fecha</th>
    <th>Proveedor</th>
    <th>Concepto</th>
    <th>Método</th>
    <th class="text-end">Monto</th>
</tr>
</thead>
<tbody>

<?php while($g = $gastos->fetch_assoc()): ?>
<tr class="gasto-row"
    data-proveedor="<?= strtolower($g['proveedor'] ?? '') ?>"
    data-concepto="<?= strtolower($g['concepto']) ?>"
    data-metodo="<?= $g['metodo_pago'] ?>"
    data-fecha="<?= date('Y-m-d', strtotime($g['fecha'])) ?>">

    <td><?= date('d/m/Y', strtotime($g['fecha'])) ?></td>
    <td><?= $g['proveedor'] ?? '—' ?></td>
    <td><?= htmlspecialchars($g['concepto']) ?></td>
    <td><?= $g['metodo_pago'] ?></td>
    <td class="text-end fw-semibold">$<?= number_format($g['monto'],2) ?></td>
</tr>
<?php endwhile; ?>

</tbody>
</table>
</div>

</div>
</div>

</div>

<!-- ================= MODAL GASTO ================= -->
<div class="modal fade" id="modalGasto" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered">
<form id="formGasto" class="modal-content">

<div class="modal-header">
    <h5 class="modal-title">Nuevo gasto</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<div class="row g-3">

    <div class="col-md-6">
        <label class="form-label">Proveedor</label>
        <select name="proveedor_id" class="form-select">
            <option value="">— Sin proveedor —</option>
            <?php while($p = $proveedores->fetch_assoc()): ?>
                <option value="<?= $p['id'] ?>"><?= $p['nombre'] ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Método</label>
        <select name="metodo_pago" class="form-select" required>
            <option value="EFECTIVO">EFECTIVO</option>
            <option value="TRANSFERENCIA">TRANSFERENCIA</option>
            <option value="TARJETA">TARJETA</option>
        </select>
    </div>

    <div class="col-md-8">
        <label class="form-label">Concepto</label>
        <input type="text" name="concepto" class="form-control" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Monto</label>
        <input type="number" step="0.01" name="monto" class="form-control" required>
    </div>

    <div class="col-md-12">
        <label class="form-label">Descripción</label>
        <textarea name="descripcion" class="form-control"></textarea>
    </div>

</div>
</div>

<div class="modal-footer">
    <button class="btn btn-dark">Guardar gasto</button>
</div>

</form>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- ================= FILTROS JS ================= -->
<script>
const fProveedor = document.getElementById('fProveedor')
const fConcepto  = document.getElementById('fConcepto')
const fMetodo    = document.getElementById('fMetodo')
const fDesde     = document.getElementById('fDesde')
const fHasta     = document.getElementById('fHasta')
const btnReset   = document.getElementById('btnReset')
const filas      = document.querySelectorAll('.gasto-row')

function filtrar() {
    filas.forEach(row => {
        let ok = true

        if (fProveedor.value &&
            !row.dataset.proveedor.includes(fProveedor.value.toLowerCase()))
            ok = false

        if (fConcepto.value &&
            !row.dataset.concepto.includes(fConcepto.value.toLowerCase()))
            ok = false

        if (fMetodo.value && row.dataset.metodo !== fMetodo.value)
            ok = false

        if (fDesde.value && row.dataset.fecha < fDesde.value)
            ok = false

        if (fHasta.value && row.dataset.fecha > fHasta.value)
            ok = false

        row.style.display = ok ? '' : 'none'
    })
}

[fProveedor, fConcepto, fMetodo, fDesde, fHasta]
    .forEach(el => el.addEventListener('input', filtrar))

btnReset.addEventListener('click', () => {
    fProveedor.value = ''
    fConcepto.value  = ''
    fMetodo.value    = ''
    fDesde.value     = ''
    fHasta.value     = ''

    filas.forEach(row => row.style.display = '')
})
</script>

<!-- ================= EXPORTAR ================= -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.5.29/dist/jspdf.plugin.autotable.min.js"></script>

<script>
document.getElementById('btnExcel').onclick = () => {
    const rows = [...document.querySelectorAll('.gasto-row')]
        .filter(r => r.style.display !== 'none')
        .map(r => [...r.children].map(c => c.innerText))

    if (!rows.length)
        return Swal.fire('Sin datos', 'No hay registros visibles', 'info')

    rows.unshift(['Fecha','Proveedor','Concepto','Método','Monto'])

    const wb = XLSX.utils.book_new()
    const ws = XLSX.utils.aoa_to_sheet(rows)
    XLSX.utils.book_append_sheet(wb, ws, 'Gastos')
    XLSX.writeFile(wb, 'gastos.xlsx')
}

document.getElementById('btnPdf').onclick = () => {
    const rows = [...document.querySelectorAll('.gasto-row')]
        .filter(r => r.style.display !== 'none')
        .map(r => [...r.children].map(c => c.innerText))

    if (!rows.length)
        return Swal.fire('Sin datos', 'No hay registros visibles', 'info')

    const { jsPDF } = window.jspdf
    const doc = new jsPDF()
    doc.text('Reporte de Gastos', 14, 15)

    doc.autoTable({
        startY: 20,
        head: [['Fecha','Proveedor','Concepto','Método','Monto']],
        body: rows,
        styles:{ fontSize:9 }
    })

    doc.save('gastos.pdf')
}
</script>

</body>
</html>
