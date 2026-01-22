<?php
session_start();
require "../../conexion.php";
require_once __DIR__ . '/../../includes/auth.php';
protegerPagina();

$venta_id = (int)($_GET['id'] ?? 0);
if ($venta_id <= 0) die("ID inválido");

/* ================= VENTA ================= */
$stmt = $conexion->prepare("SELECT * FROM ventas WHERE id = ?");
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$venta) die("La venta no existe o no se puede editar");

/* ================= DETALLE ================= */
$detalle = $conexion->query("
    SELECT vd.*, p.nombre
    FROM venta_detalle vd
    JOIN productos p ON p.id = vd.producto_id
    WHERE vd.venta_id = $venta_id
")->fetch_all(MYSQLI_ASSOC);

/* ================= CLIENTES ================= */
$clientes = $conexion->query("SELECT id,nombre FROM clientes ORDER BY nombre");

/* ================= PRODUCTOS ================= */
$productos = $conexion->query("
    SELECT id,nombre,precio_venta
    FROM productos
    WHERE estado = 1
");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar venta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-4">
        <h4>✏️ Editar venta #<?= $venta_id ?></h4>

        <form action="/punto/acciones/ventas/guardar_cambios.php" method="POST" id="formVenta">
            <input type="hidden" name="venta_id" value="<?= $venta_id ?>">

            <!-- ================= CLIENTE ================= -->
            <div class="card mb-3">
                <div class="card-body">

                    <label class="form-label">Cliente</label>
                    <div class="input-group mb-2">
                        <select class="form-select" name="cliente_id" id="cliente_id">
                            <option value="">Público en general</option>
                            <?php while($c=$clientes->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>" <?= $venta['cliente_id']==$c['id']?'selected':'' ?>>
                                <?= htmlspecialchars($c['nombre']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        <button type="button" class="btn btn-outline-primary" id="btnNuevoCliente">➕ Nuevo</button>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="requiereFactura" name="requiere_factura"
                            value="1" <?= $venta['requiere_factura'] ? 'checked':'' ?>>
                        <label class="form-check-label">Requiere factura</label>
                    </div>

                    <div id="nombrePublicoFactura" style="display:none">
                        <label class="form-label">Nombre para la factura</label>
                        <input type="text" class="form-control" name="nombre_factura_publico"
                            value="<?= htmlspecialchars($venta['nombre_factura_publico'] ?? '') ?>">
                    </div>

                </div>
            </div>

            <!-- ================= PRODUCTOS ================= -->
            <div class="card mb-3">
                <div class="card-body">

                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cant.</th>
                                <th>Precio</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="tablaDetalle">
                            <?php foreach($detalle as $d): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['nombre']) ?>
                                    <input type="hidden" name="productos[id][]" value="<?= $d['producto_id'] ?>">
                                </td>
                                <td><input type="number" step="0.001" class="form-control cantidad"
                                        name="productos[cantidad][]" value="<?= $d['cantidad'] ?>"></td>
                                <td><input type="number" step="0.01" class="form-control precio"
                                        name="productos[precio][]" value="<?= $d['precio'] ?>"></td>
                                <td class="subtotal">$0.00</td>
                                <td><button type="button" class="btn btn-danger btn-sm eliminar">✖</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <select id="productoNuevo" class="form-select">
                        <option value="">➕ Agregar producto</option>
                        <?php while($p=$productos->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>" data-precio="<?= $p['precio_venta'] ?>">
                            <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>

                </div>
            </div>

            <!-- ================= TOTAL ================= -->
            <div class="card mb-3">
                <div class="card-body text-end">
                    <h5>Total: $<span id="total">0.00</span></h5>
                    <input type="hidden" name="total" id="totalInput">
                </div>
            </div>

            <button class="btn btn-success">💾 Guardar cambios</button>
            <a href="/punto/pantallas/ventas.php" class="btn btn-secondary">Cancelar</a>

        </form>
    </div>

    <!-- ================= MODAL CLIENTE ================= -->
    <div class="modal fade" id="modalCliente" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <form id="formCliente">
                    <div class="modal-header">
                        <h5 class="modal-title">Nuevo cliente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>

                        <div class="mb-3"><label>RFC</label><input name="rfc" class="form-control"></div>
                        <div class="mb-3"><label>Razón social</label><input name="razon_social" class="form-control">
                        </div>
                        <div class="mb-3"><label>Documento</label><input name="documento" class="form-control"></div>

                        <div class="row">
                            <div class="col-md-6 mb-3"><label>Teléfono</label><input name="telefono"
                                    class="form-control"></div>
                            <div class="col-md-6 mb-3"><label>Email</label><input name="email" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3"><label>Dirección fiscal</label><textarea name="direccion_fiscal"
                                class="form-control"></textarea></div>

                        <div class="row">
                            <div class="col-md-6 mb-3"><label>CP</label><input name="codigo_postal"
                                    class="form-control"></div>
                            <div class="col-md-6 mb-3"><label>Régimen fiscal</label><input name="regimen_fiscal"
                                    class="form-control"></div>
                        </div>

                        <div class="mb-3"><label>Uso CFDI</label><input name="uso_cfdi" class="form-control"></div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-primary">💾 Guardar cliente</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/* ====== GUARDAR CAMBIOS CON SWEET ALERT ====== */
document.getElementById('formVenta').addEventListener('submit', function (e) {
    e.preventDefault(); // ⛔ evita recarga

    const form = this;
    const datos = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: datos
    })
    .then(r => r.json())
    .then(resp => {

        if (resp.ok) {
            Swal.fire({
                icon: 'success',
                title: 'Ajuste realizado',
                text: resp.msg,
                confirmButtonText: 'Aceptar'
            }).then(() => {
                window.location.href = '/punto/pantallas/caja.php';
            });

        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: resp.msg
            });
        }

    })
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo guardar la venta'
        });
    });
});
</script>

    <script>
    /* ====== TOTALES ====== */
    function recalcular() {
        let total = 0;
        document.querySelectorAll('#tablaDetalle tr').forEach(tr => {
            let c = +tr.querySelector('.cantidad').value || 0;
            let p = +tr.querySelector('.precio').value || 0;
            let s = c * p;
            tr.querySelector('.subtotal').textContent = '$' + s.toFixed(2);
            total += s;
        });
        totalInput.value = total.toFixed(2);
        document.getElementById('total').textContent = total.toFixed(2);
    }
    document.addEventListener('input', recalcular);
    document.addEventListener('click', e => {
        if (e.target.classList.contains('eliminar')) {
            e.target.closest('tr').remove();
            recalcular();
        }
    });
    document.getElementById('productoNuevo').onchange = function() {
        if (!this.value) return;
        let tr = document.createElement('tr');
        tr.innerHTML = `<td>${this.options[this.selectedIndex].text}
<input type="hidden" name="productos[id][]" value="${this.value}"></td>
<td><input type="number" step="0.001" class="form-control cantidad" name="productos[cantidad][]" value="1"></td>
<td><input type="number" step="0.01" class="form-control precio" name="productos[precio][]" value="${this.selectedOptions[0].dataset.precio}"></td>
<td class="subtotal">$0.00</td>
<td><button type="button" class="btn btn-danger btn-sm eliminar">✖</button></td>`;
        tablaDetalle.appendChild(tr);
        this.value = '';
        recalcular();
    }
    recalcular();

    /* ====== FACTURA ====== */
    const chk = document.getElementById('requiereFactura');
    const sel = document.getElementById('cliente_id');
    const pub = document.getElementById('nombrePublicoFactura');

    function validarFactura() {
        pub.style.display = (chk.checked && !sel.value) ? 'block' : 'none';
    }
    chk.onchange = sel.onchange = validarFactura;
    validarFactura();

    /* ====== CLIENTE ====== */
    const modalCliente = new bootstrap.Modal('#modalCliente');
    btnNuevoCliente.onclick = () => modalCliente.show();

    formCliente.onsubmit = e => {
        e.preventDefault();
        fetch('/punto/acciones/guardar_cliente.php', {
                method: 'POST',
                body: new FormData(formCliente)
            })
            .then(r => r.json()).then(resp => {
                if (!resp.ok) return alert(resp.msg);
                let op = new Option(resp.nombre, resp.id, true, true);
                cliente_id.appendChild(op);
                modalCliente.hide();
                formCliente.reset();
            });
    };
    </script>

</body>

</html>