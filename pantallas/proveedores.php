<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../conexion.php';

$proveedores = $conexion->query("SELECT * FROM proveedores WHERE estado = 1 ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Proveedores</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
    body {
        background: #f4f6f9;
    }

    .card {
        border-radius: 15px;
    }

    .btn-primary {
        border-radius: 25px;
    }

    .table thead {
        background: #1f2937;
        color: white;
    }
    </style>
</head>

<body>
    <?php renderSidebar('Gastos'); ?>
    <div class="container mt-5">
        <div class="card shadow-lg">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold">📦 Gastos/Proveedores</h3>
                    <button class="btn btn-primary px-4" onclick="nuevoProveedor()">➕ Agregar proveedor</button>
                </div>

                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>RFC</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th width="140">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($p = $proveedores->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['nombre']) ?></td>
                            <td><?= $p['rfc'] ?></td>
                            <td><?= $p['telefono'] ?></td>
                            <td><?= $p['email'] ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning"
                                    onclick='editar(<?= json_encode($p) ?>)'>✏️</button>
                                <button class="btn btn-sm btn-danger" onclick="eliminar(<?= $p['id'] ?>)">🗑️</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL -->
    <div class="modal fade" id="modalProveedor">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" method="POST" action="/punto/acciones/proveedores/guardar.php">
                <div class="modal-header">
                    <h5 class="modal-title">Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" name="id" id="id">
                    <div class="col-md-6">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label>RFC</label>
                        <input type="text" name="rfc" id="rfc" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" id="telefono" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label>Email</label>
                        <input type="email" name="email" id="email" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label>Dirección</label>
                        <textarea name="direccion" id="direccion" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    const modal = new bootstrap.Modal(document.getElementById('modalProveedor'));

    function nuevoProveedor() {
        document.querySelector("form").reset();
        document.getElementById("id").value = "";
        modal.show();
    }

    function editar(p) {
        for (let k in p) {
            if (document.getElementById(k)) {
                document.getElementById(k).value = p[k];
            }
        }
        modal.show();
    }

    function eliminar(id) {
        Swal.fire({
            title: '¿Eliminar proveedor?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar'
        }).then(r => {
            if (r.isConfirmed) {
                window.location = '/punto/acciones/proveedores/eliminar.php?id=' + id;
            }
        });
    }
    </script>
    <?php
$successMsg = null;
$errorMsg   = null;

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'creado':
            $successMsg = 'Proveedor agregado correctamente';
            break;
        case 'actualizado':
            $successMsg = 'Proveedor actualizado correctamente';
            break;
        case 'eliminado':
            $successMsg = 'Proveedor eliminado correctamente';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'guardar':
            $errorMsg = 'No se pudo guardar el proveedor';
            break;
        case 'eliminar':
            $errorMsg = 'No se pudo eliminar el proveedor';
            break;
    }
}
?>

    <?php if ($successMsg): ?>
    <script>
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: <?= json_encode($successMsg) ?>,
        timer: 2500,
        showConfirmButton: false
    });
    </script>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
    <script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: <?= json_encode($errorMsg) ?>,
        confirmButtonText: 'Aceptar'
    });
    </script>
    <?php endif; ?>

</body>

</html>