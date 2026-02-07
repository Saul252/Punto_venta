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

    <style>
    .tabla-almacen-scroll {
        max-height: 60vh;
        overflow-y: auto;
    }

    .tabla-almacen-scroll::-webkit-scrollbar {
        width: 8px;
    }

    .tabla-almacen-scroll::-webkit-scrollbar-thumb {
        background: #cfd6e4;
        border-radius: 10px;
    }

    .tabla-almacen thead th {
        position: sticky;
        top: 0;
        background: #f8f9fa;
        z-index: 2;
    }

    .tabla-almacen tbody tr:nth-child(even) {
        background: #f9fbff;
    }

    .tabla-almacen td {
        font-size: 15px;
    }

    .tabla-almacen td:nth-child(2) {
        font-weight: 600;
    }
    </style>
</head>

<body class="bg-light">
    <?php renderSidebar('Almacén'); ?>

    <div class="container mt-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>📦 Almacén</h4>
            <button class="btn btn-primary" onclick="nuevoProducto()">
                ➕ Nuevo producto
            </button>
        </div>

        <!-- 🔍 BUSCADOR -->
        <div class="mb-3">
            <input type="text" id="buscador" class="form-control"
                placeholder="🔍 Buscar por código, nombre o categoría...">
        </div>

        <div class="card shadow">
            <div class="card-body p-0">
                <div class="tabla-almacen-scroll">
                    <table class="table table-sm table-hover tabla-almacen" id="tablaProductos">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th class="text-center">Acciones</th>
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
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning"
                                        onclick='editarProducto(<?= json_encode($p) ?>)'>
                                        ✏️
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarProducto(<?= $p['id'] ?>)">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= MODAL ================= -->
    <div class="modal fade" id="modalProducto" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 id="tituloModal">➕ Nuevo producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form id="formProducto">
                        <input type="hidden" name="id" id="producto_id">

                        <!-- CAMPOS SAT OCULTOS -->
                        <input type="hidden" name="clave_unidad" id="clave_unidad">
                        <input type="hidden" name="objeto_impuesto" value="02">

                        <div class="row g-3">

                            <div class="col-md-4">
                                <label>Código</label>
                                <input type="text" name="codigo" id="codigo" class="form-control">
                            </div>

                            <div class="col-md-8">
                                <label>Nombre *</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label>Descripción</label>
                                <textarea name="descripcion" id="descripcion" class="form-control" rows="2"
                                    placeholder="Ej. Refresco botella 600 ml"></textarea>
                            </div>

                            <div class="col-md-4">
                                <label>Precio compra</label>
                                <input type="number" step="0.01" name="precio_compra" id="precio_compra"
                                    class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label>Precio venta *</label>
                                <input type="number" step="0.01" name="precio_venta" id="precio_venta"
                                    class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label>Stock</label>
                                <input type="number" step="0.001" name="stock" id="stock" class="form-control">
                            </div>

                            <!-- UNIDAD DEL SISTEMA -->
                            <div class="col-md-6">
                                <label>Unidad (sistema)</label>
                                <select name="unidad_medida" id="unidad_medida" class="form-select"
                                    onchange="asignarClaveSAT()">
                                    <option value="PIEZA">Pieza</option>
                                    <option value="KILO">Kilo</option>
                                    <option value="GRAMO">Gramo</option>
                                    <option value="LITRO">Litro</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label>Categoría</label>
                                <select name="categoria_id" id="categoria_id" class="form-select">
                                    <option value="">-- Seleccionar --</option>
                                    <?php
                $categorias->data_seek(0);
                while ($c = $categorias->fetch_assoc()):
                ?>
                                    <option value="<?= $c['id'] ?>">
                                        <?= htmlspecialchars($c['nombre']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- DATOS FISCALES -->
                            <div class="col-md-6">
                                <label>Clave producto/servicio SAT *</label>
                               <input type="text" name="clave_prod_serv" id="clave_prod_serv" class="form-control" required>

                            </div>

                            <div class="col-md-6">
                                <label>IVA *</label>
                               <select name="tasa_iva" id="tasa_iva" class="form-select">

                                    <option value="0.1600">16%</option>
                                    <option value="0.0000">0%</option>
                                </select>
                            </div>

                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-success" onclick="guardarProducto()">
                        Guardar
                    </button>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    /* 🔍 FILTRO EN TIEMPO REAL */
    document.getElementById('buscador').addEventListener('keyup', function() {
        let filtro = this.value.toLowerCase();
        document.querySelectorAll('#tablaProductos tbody tr').forEach(tr => {
            tr.style.display = tr.innerText.toLowerCase().includes(filtro) ? '' : 'none';
        });
    });

    /* ➕ NUEVO */
    function nuevoProducto() {
        document.getElementById('formProducto').reset();
        document.getElementById('producto_id').value = '';
        document.getElementById('tituloModal').innerText = '➕ Nuevo producto';
        new bootstrap.Modal(document.getElementById('modalProducto')).show();
    }

    /* ✏️ EDITAR */
  function editarProducto(p) {
    document.getElementById('tituloModal').innerText = '✏️ Editar producto';

    producto_id.value   = p.id;
    codigo.value        = p.codigo;
    nombre.value        = p.nombre;
    descripcion.value   = p.descripcion ?? '';
    precio_compra.value= p.precio_compra;
    precio_venta.value = p.precio_venta;
    stock.value         = p.stock;
    unidad_medida.value= p.unidad_medida;
    categoria_id.value = p.categoria_id;

    // ✅ CAMPOS SAT
    document.getElementById('clave_prod_serv').value = p.clave_prod_serv;
    document.getElementById('clave_unidad').value    = p.clave_unidad;
    document.getElementById('tasa_iva').value        = p.tasa_iva;

    new bootstrap.Modal(document.getElementById('modalProducto')).show();
}

    /* 💾 GUARDAR */
    function guardarProducto() {

        const form = document.getElementById('formProducto');
        const data = new FormData(form);
        const id = document.getElementById('producto_id').value;

        const url = id ?
            '/punto/acciones/actualizar_producto_ajax.php' :
            '/punto/acciones/guardar_producto_ajax.php';

        fetch(url, {
                method: 'POST',
                body: data
            })
            .then(r => r.json())
            .then(resp => {
                Swal.fire(
                    resp.ok ? 'Éxito' : 'Error',
                    resp.msg,
                    resp.ok ? 'success' : 'error'
                ).then(() => {
                    if (resp.ok) location.reload();
                });
            });
    }


    /* 🗑️ ELIMINAR */
    function eliminarProducto(id) {
        Swal.fire({
            title: '¿Eliminar producto?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar'
        }).then(res => {
            if (res.isConfirmed) {
                fetch('/punto/acciones/eliminar_producto_ajax.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'id=' + id
                    })
                    .then(r => r.json())
                    .then(resp => {
                        Swal.fire(resp.ok ? 'Eliminado' : 'Error', resp.msg,
                                resp.ok ? 'success' : 'error')
                            .then(() => resp.ok && location.reload());
                    });
            }
        });
    }
    </script>

</body>

</html>