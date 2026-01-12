<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require_once __DIR__ . '/../includes/sidebar.php';
require "../conexion.php";

$sql = "
SELECT 
    c.*,
    COUNT(v.id) AS total_ventas,
    IFNULL(SUM(
        CASE 
            WHEN v.estado='ABIERTA' 
            THEN v.total - IFNULL(v.total_pagado,0) 
            ELSE 0 
        END
    ),0) AS adeudo
FROM clientes c
LEFT JOIN ventas v ON v.cliente_id = c.id
GROUP BY c.id
ORDER BY c.nombre
";

$clientes = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Clientes</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

<style>
.tabla-scroll{max-height:65vh;overflow-y:auto}
.tabla-scroll thead th{position:sticky;top:0;background:#fff}
.tabla-scroll tbody tr:nth-child(even){background:#f9fbff}
.nombre{font-weight:600;font-size:15px}
</style>
</head>

<body class="bg-light">
<?php renderSidebar('Clientes'); ?>

<div class="container mt-4">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>👥 Clientes</h4>
    <button class="btn btn-primary" onclick="nuevoCliente()">➕ Nuevo cliente</button>
</div>

<input type="text" id="buscador" class="form-control mb-3"
placeholder="🔍 Buscar por nombre, RFC, teléfono o email">

<div class="card shadow">
<div class="card-body p-0 tabla-scroll">

<table class="table table-sm table-hover align-middle" id="tablaClientes">
<thead class="table-light">
<tr>
    <th>Cliente</th>
    <th>RFC</th>
    <th>Teléfono</th>
    <th>Email</th>
    <th>Ventas</th>
    <th>Adeudo</th>
    <th class="text-center">Acciones</th>
</tr>
</thead>
<tbody>

<?php while($c=$clientes->fetch_assoc()): ?>
<tr>
    <td class="nombre"><?= htmlspecialchars($c['nombre']) ?></td>
    <td><?= $c['rfc'] ?: '-' ?></td>
    <td><?= $c['telefono'] ?: '-' ?></td>
    <td><?= $c['email'] ?: '-' ?></td>
    <td><?= $c['total_ventas'] ?></td>
    <td>
        <?= $c['adeudo']>0
            ? '<span class="badge bg-danger">$'.number_format($c['adeudo'],2).'</span>'
            : '<span class="badge bg-success">Sin adeudo</span>' ?>
    </td>
    <td class="text-center">
        <button class="btn btn-sm btn-warning"
            onclick="editarCliente(<?= $c['id'] ?>)">✏️</button>
        <button class="btn btn-sm btn-danger"
            onclick="eliminarCliente(<?= $c['id'] ?>)">🗑️</button>
    </td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

</div>
</div>
</div>

<!-- ================= MODAL ================= -->
<div class="modal fade" id="modalCliente">
<div class="modal-dialog modal-lg">
<div class="modal-content">

<div class="modal-header">
    <h5 id="tituloModal">Nuevo cliente</h5>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<form id="formCliente">
<input type="hidden" name="id" id="cliente_id">

<div class="row g-3">

<div class="col-md-6">
<label class="form-label">Nombre *</label>
<input type="text" name="nombre" id="nombre" class="form-control" required>
</div>

<div class="col-md-6">
<label class="form-label">RFC</label>
<input type="text" name="rfc" id="rfc" class="form-control">
</div>

<div class="col-md-6">
<label class="form-label">Teléfono</label>
<input type="text" name="telefono" id="telefono" class="form-control">
</div>

<div class="col-md-6">
<label class="form-label">Email</label>
<input type="email" name="email" id="email" class="form-control">
</div>

<div class="col-md-6">
<label class="form-label">Régimen fiscal *</label>
<select name="regimen_fiscal" id="regimen_fiscal" class="form-select" required>
<option value="">Selecciona régimen fiscal</option>
<option value="601">601 – General de Ley Personas Morales</option>
<option value="603">603 – Personas Morales sin Fines Lucrativos</option>
<option value="605">605 – Sueldos y Salarios</option>
<option value="606">606 – Arrendamiento</option>
<option value="612">612 – Actividades Empresariales</option>
<option value="626">626 – RESICO</option>
</select>
</div>

<div class="col-md-6">
<label class="form-label">Uso de CFDI *</label>
<select name="uso_cfdi" id="uso_cfdi" class="form-select" required>
<option value="">Selecciona uso CFDI</option>
<option value="G01">G01 – Adquisición de mercancías</option>
<option value="G03">G03 – Gastos en general</option>
<option value="P01">P01 – Por definir</option>
</select>
</div>

</div>
</form>
</div>

<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
<button class="btn btn-success" onclick="guardarCliente()">Guardar</button>
</div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
buscador.addEventListener('keyup',()=>{
    let f = buscador.value.toLowerCase()
    document.querySelectorAll('#tablaClientes tbody tr').forEach(tr=>{
        tr.style.display = tr.innerText.toLowerCase().includes(f) ? '' : 'none'
    })
})

function nuevoCliente(){
    document.getElementById('formCliente').reset()
    cliente_id.value=''
    tituloModal.innerText='➕ Nuevo cliente'
    new bootstrap.Modal(modalCliente).show()
}

function editarCliente(id){
fetch('/punto/acciones/editar_cliente_ajax.php?id='+id)
.then(r=>r.json())
.then(resp=>{
    if(!resp.ok){
        Swal.fire('Error',resp.msg,'error')
        return
    }
    const c=resp.data
    cliente_id.value=c.id
    nombre.value=c.nombre
    rfc.value=c.rfc
    telefono.value=c.telefono
    email.value=c.email
    regimen_fiscal.value=c.regimen_fiscal
    uso_cfdi.value=c.uso_cfdi
    tituloModal.innerText='✏️ Editar cliente'
    new bootstrap.Modal(modalCliente).show()
})
}

function guardarCliente(){
const data=new FormData(formCliente)
fetch('/punto/acciones/guardar_cliente_ajax.php',{
method:'POST',
body:data
})
.then(r=>r.json())
.then(resp=>{
Swal.fire(resp.ok?'Éxito':'Error',resp.msg,resp.ok?'success':'error')
.then(()=>resp.ok && location.reload())
})
}

function eliminarCliente(id){
Swal.fire({
title:'¿Eliminar cliente?',
icon:'warning',
showCancelButton:true,
confirmButtonColor:'#dc3545'
}).then(r=>{
if(r.isConfirmed){
fetch('/punto/acciones/eliminar_cliente_ajax.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:'id='+id
})
.then(r=>r.json())
.then(resp=>{
Swal.fire(resp.ok?'Eliminado':'Error',resp.msg,resp.ok?'success':'error')
.then(()=>resp.ok && location.reload())
})
}
})
}
</script>

</body>
</html>
