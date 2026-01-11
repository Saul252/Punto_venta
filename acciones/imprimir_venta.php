<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require_once __DIR__ . '/../conexion.php';

$venta_id = intval($_GET['id'] ?? 0);
if ($venta_id <= 0) die('Venta inválida');

/* ===============================
   DATOS DE VENTA
================================ */
$q = $conexion->prepare("
SELECT v.*, 
       u.nombre AS usuario,
       c.id AS caja,
       cl.nombre AS cliente,
       cl.rfc,
       cl.email
FROM ventas v
INNER JOIN usuarios u ON v.usuario_id = u.id
INNER JOIN cajas c ON v.caja_id = c.id
LEFT JOIN clientes cl ON v.cliente_id = cl.id
WHERE v.id = ?
");
$q->bind_param("i", $venta_id);
$q->execute();
$venta = $q->get_result()->fetch_assoc();

if (!$venta) die('Venta no encontrada');

/* ===============================
   DETALLE
================================ */
$det = $conexion->prepare("
SELECT vd.cantidad, vd.precio, vd.subtotal,
       p.nombre, p.unidad_medida
FROM venta_detalle vd
INNER JOIN productos p ON vd.producto_id = p.id
WHERE vd.venta_id = ?
");
$det->bind_param("i", $venta_id);
$det->execute();
$detalle = $det->get_result();

/* ===============================
   PAGOS
================================ */
$p = $conexion->prepare("
SELECT metodo_pago, monto, fecha
FROM pagos
WHERE tipo='VENTA' AND referencia_id=?
");
$p->bind_param("i", $venta_id);
$p->execute();
$pagos = $p->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Venta #<?= $venta_id ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { background:#f5f5f5; }
.ticket {
    max-width: 380px;
    margin: auto;
    background:#fff;
    padding:20px;
    font-size:13px;
}
hr { margin: 6px 0; }
@media print {
    .no-print { display:none; }
    body { background:#fff; }
}
</style>
</head>

<body>

<div class="ticket">

<h5 class="text-center">🧾 COMPROBANTE DE VENTA</h5>
<hr>

<p>
<strong>Venta:</strong> #<?= $venta_id ?><br>
<strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?><br>
<strong>Caja:</strong> <?= $venta['caja'] ?><br>
<strong>Usuario:</strong> <?= $venta['usuario'] ?>
</p>

<hr>

<p>
<strong>Cliente:</strong><br>
<?= $venta['cliente'] ?: 'Público en general' ?><br>
<?= $venta['rfc'] ? "RFC: {$venta['rfc']}<br>" : '' ?>
<?= $venta['email'] ? "Email: {$venta['email']}" : '' ?>
</p>

<hr>

<table class="table table-sm">
<thead>
<tr>
<th>Prod.</th>
<th>Cant</th>
<th>$</th>
</tr>
</thead>
<tbody>
<?php while ($d = $detalle->fetch_assoc()): ?>
<tr>
<td><?= $d['nombre'] ?></td>
<td><?= $d['cantidad'].' '.$d['unidad_medida'] ?></td>
<td>$<?= number_format($d['subtotal'],2) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<hr>

<p>
<strong>Total:</strong> $<?= number_format($venta['total'],2) ?><br>
</p>

<hr>

<p>
<strong>Pagos:</strong><br>
<?php while ($pg = $pagos->fetch_assoc()): ?>
<?= $pg['metodo_pago'] ?> - $<?= number_format($pg['monto'],2) ?><br>
<?php endwhile; ?>
</p>

<hr>

<p class="text-center">
Gracias por su compra 🙏
</p>

<div class="no-print text-center mt-3">
    <button onclick="window.print()" class="btn btn-primary btn-sm">
        🖨 Imprimir ticket
    </button>
</div>

</div>

</body>
</html>
