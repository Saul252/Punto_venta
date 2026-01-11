<?php


require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require_once __DIR__ . '/../includes/sidebar.php';
require "../conexion.php";
$productos = $conexion->query("
    SELECT p.*, c.nombre AS categoria
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    ORDER BY p.nombre
");

$categorias = $conexion->query("SELECT id, nombre FROM categorias ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Almacén</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<style>
    /* =====================
   ALMACÉN - TABLA SCROLL
===================== */
.tabla-almacen-scroll {
    max-height: 60vh;     /* Altura visible */
    overflow-y: auto;
}

/* Scroll elegante */
.tabla-almacen-scroll::-webkit-scrollbar {
    width: 8px;
}
.tabla-almacen-scroll::-webkit-scrollbar-thumb {
    background: #cfd6e4;
    border-radius: 10px;
}
.tabla-almacen-scroll::-webkit-scrollbar-thumb:hover {
    background: #b5c0d6;
}

/* Encabezado fijo */
.tabla-almacen thead th {
    position: sticky;
    top: 0;
    background: #f8f9fa;
    z-index: 2;
}

/* Filas alternadas */
.tabla-almacen tbody tr:nth-child(even) {
    background: #f9fbff;
}

/* Texto del producto más visible */
.tabla-almacen td:nth-child(2) {
    font-weight: 600;
    font-size: 15px;
}

</style>
<body class="bg-light">
<?php
// 👉 AQUI SE CARGA EL SIDEBAR
renderSidebar('Almacén');
?>

<div class="container mt-4">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>📦 Almacén</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProducto">
        ➕ Nuevo producto
    </button>
</div>

<div class="card shadow">
<div class="card-body">
<div class="tabla-almacen-scroll">
<table class="table table-sm table-hover">
<thead class="table-light">
<tr>
    <th>Código</th>
    <th>Producto</th>
    <th>Categoría</th>
    <th>Precio</th>
    <th>Stock</th>
</tr>
</thead>
<tbody>
<?php while ($p = $productos->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($p['codigo']) ?></td>
    <td><?= htmlspecialchars($p['nombre']) ?></td>
    <td><?= htmlspecialchars($p['categoria'] ?? '-') ?></td>
    <td>$<?= number_format($p['precio_venta'], 2) ?></td>
    <td><?= number_format($p['stock'], 3) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</div>
</div>
</div>
</div>

<!-- ================= MODAL ================= -->
<div class="modal fade" id="modalProducto">
<div class="modal-dialog modal-lg">
<div class="modal-content">

<div class="modal-header">
    <h5>➕ Nuevo producto</h5>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<form id="formProducto">

<div class="row g-3">

<div class="col-md-4">
    <label>Código</label>
    <input type="text" name="codigo" class="form-control">
</div>

<div class="col-md-8">
    <label>Nombre *</label>
    <input type="text" name="nombre" class="form-control" required>
</div>

<div class="col-md-4">
    <label>Precio compra</label>
    <input type="number" step="0.01" name="precio_compra" class="form-control">
</div>

<div class="col-md-4">
    <label>Precio venta *</label>
    <input type="number" step="0.01" name="precio_venta" class="form-control" required>
</div>

<div class="col-md-4">
    <label>Stock inicial</label>
    <input type="number" step="0.001" name="stock" class="form-control" value="0">
</div>

<div class="col-md-6">
    <label>Unidad</label>
    <select name="unidad_medida" class="form-select">
        <option value="PIEZA">Pieza</option>
        <option value="KILO">Kilo</option>
        <option value="GRAMO">Gramo</option>
        <option value="LITRO">Litro</option>
    </select>
</div>

<div class="col-md-6">
    <label>Categoría</label>
    <select name="categoria_id" class="form-select">
        <option value="">-- Seleccionar --</option>
        <?php while ($c = $categorias->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
        <?php endwhile; ?>
    </select>
</div>

<div class="col-md-12">
    <label>➕ Nueva categoría (si no existe)</label>
    <input type="text" name="categoria_nueva" class="form-control">
</div>

</div>

</form>
</div>

<div class="modal-footer">
    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    <button class="btn btn-success" onclick="guardarProducto()">Guardar</button>
</div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- 🔴 ESTO FALTABA -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function guardarProducto() {
    const form = document.getElementById('formProducto');
    const data = new FormData(form);

    fetch('/punto/acciones/guardar_producto_ajax.php', {
        method: 'POST',
        body: data
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.ok) {
            Swal.fire({
                icon: 'success',
                title: 'Guardado',
                text: resp.msg
            }).then(() => location.reload());
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: resp.msg
            });
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Error de conexión', 'error');
    });
}
</script>


</body>
</html>
