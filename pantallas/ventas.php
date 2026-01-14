<?php
require "../conexion.php";
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();
require_once __DIR__ . '/../includes/sidebar.php';

$_SESSION['carrito'] ??= [];
$_SESSION['total']   ??= 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Ventas | POS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    body {
        background: #eef1f5;
        font-size: 14px;
    }

    .card-pos {
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, .08);
        border: none
    }

    .tabla-productos-scroll {
        max-height: 60vh;
        overflow-y: auto
    }

    .carrito-scroll {
        max-height: 45vh;
        overflow-y: auto
    }

    .carrito-item {
        background: #f8f9fc;
        border-radius: 10px;
        padding: 10px;
        margin-bottom: 8px
    }

    #total {
        font-size: 1.2rem;
        color: #198754
    }
    </style>
</head>

<body>
    <?php renderSidebar('Ventas'); ?>

    <div class="container-fluid mt-3">
        <div class="row g-3">

            <!-- PRODUCTOS -->
            <div class="col-lg-8">
                <div class="card card-pos">
                    <div class="card-body">

                        <div class="d-flex justify-content-between mb-2">
                            <h5>📦 Productos</h5>
                            <input type="text" id="buscar" class="form-control form-control-sm w-50"
                                placeholder="Buscar...">
                        </div>

                        <div class="tabla-productos-scroll">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th>Precio</th>
                                        <th>Stock</th>
                                        <th>Cant.</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-productos"></tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>

            <!-- CARRITO -->
            <div class="col-lg-4">
                <div class="card card-pos">
                    <div class="card-body">

                        <h6>Resumen</h6>
                        <div id="lista-carrito" class="carrito-scroll"></div>

                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span id="total">$0.00</span>
                        </div>

                        <button class="btn btn-success w-100 mt-2" id="btnFinalizar">
                            💳 Finalizar venta
                        </button>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ================= MODAL FINALIZAR VENTA ================= -->
    <div class="modal fade" id="modalFinalizarVenta" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <form action="/punto/acciones/guardar_venta.php" method="POST">

                    <div class="modal-header">
                        <h5 class="modal-title">Finalizar venta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <h6>Resumen</h6>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cant.</th>
                                    <th>Precio</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="resumenVenta"></tbody>
                        </table>

                        <div class="fw-bold text-end fs-4 mb-3">
                            Total: $<span id="totalModal">0.00</span>
                        </div>

                        <hr>

                        <label class="form-label">Cliente</label>
                        <div class="input-group mb-3">
                            <select class="form-select" name="cliente_id" id="cliente_id">
                                <option value="">Público en general</option>
                                <?php
$clientes = $conexion->query("SELECT id,nombre FROM clientes ORDER BY nombre");
while($c=$clientes->fetch_assoc()):
?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>

                            <button type="button" class="btn btn-outline-primary" id="btnNuevoCliente">
                                ➕ Nuevo
                            </button>
                        </div>
<!-- ================= FACTURA ================= -->
<div class="form-check mb-3">
    <input class="form-check-input"
           type="checkbox"
           id="requiereFactura"
           name="requiere_factura"
           value="1">
    <label class="form-check-label">
        Requiere factura
    </label>
</div>

<!-- SOLO PARA PÚBLICO GENERAL -->
<div id="nombrePublicoFactura" style="display:none">
    <label class="form-label">
        Nombre para la factura
        <small class="text-muted">(Público en general)</small>
    </label>
    <input type="text"
           class="form-control"
           name="nombre_factura_publico"
           placeholder="Ej. Juan Pérez">
</div>

                        <label class="form-label">Método de pago</label>
                        <select class="form-select mb-3" name="metodo_pago" required>
                            <option value="EFECTIVO">Efectivo</option>
                            <option value="TARJETA">Tarjeta</option>
                            <option value="TRANSFERENCIA">Transferencia</option>
                        </select>

                        <label class="form-label">Monto pagado</label>
                        <input type="number" step="0.01" class="form-control" name="monto_pago" id="montoPago" required>

                    </div>

                    <div class="modal-footer">
                       <button type="button"
        class="btn btn-secondary"
        data-bs-dismiss="modal">
    Cancelar
</button>
 
                        <button class="btn btn-success">💾 Confirmar venta</button>
                    </div>

                </form>
            </div>
        </div>
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

          <!-- NOMBRE -->
          <div class="mb-3">
            <label class="form-label">Nombre *</label>
            <input type="text" name="nombre" class="form-control" required>
          </div>

          <!-- RFC -->
          <div class="mb-3">
            <label class="form-label">RFC</label>
            <input type="text" name="rfc" class="form-control">
          </div>

          <!-- RAZÓN SOCIAL -->
          <div class="mb-3">
            <label class="form-label">Razón social</label>
            <input type="text" name="razon_social" class="form-control">
          </div>

          <!-- DOCUMENTO -->
          <div class="mb-3">
            <label class="form-label">Documento</label>
            <input type="text" name="documento" class="form-control"
                   placeholder="INE / CURP / RFC">
          </div>

          <div class="row">
            <!-- TELÉFONO -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Teléfono</label>
              <input type="text" name="telefono" class="form-control">
            </div>

            <!-- EMAIL -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control">
            </div>
          </div>

          <!-- DIRECCIÓN FISCAL -->
          <div class="mb-3">
            <label class="form-label">Dirección fiscal</label>
            <textarea name="direccion_fiscal" class="form-control" rows="2"></textarea>
          </div>

          <div class="row">
            <!-- CP -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Código postal</label>
              <input type="text" name="codigo_postal" class="form-control">
            </div>

            <!-- RÉGIMEN -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Régimen fiscal</label>
              <input type="text" name="regimen_fiscal" class="form-control">
            </div>
          </div>

          <!-- USO CFDI -->
          <div class="mb-3">
            <label class="form-label">Uso CFDI</label>
            <input type="text" name="uso_cfdi" class="form-control"
                   placeholder="G01, G03, P01...">
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Cancelar
          </button>
          <button class="btn btn-primary">
            💾 Guardar cliente
          </button>
        </div>

      </form>

    </div>
  </div>
</div>


    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    /* ================== PRODUCTOS ================== */
    const tabla = document.getElementById('tabla-productos');
    fetch('ajax_buscar_productos.php')
        .then(r => r.text()).then(h => tabla.innerHTML = h);

    /* ================== CARRITO ================== */
    let carrito = [];
    let total = 0;

    document.addEventListener('click', e => {
        if (!e.target.classList.contains('agregar')) return;
        const tr = e.target.closest('tr');

        const item = {
            id: tr.dataset.id,
            nombre: tr.dataset.nombre,
            precio: parseFloat(tr.dataset.precio),
            cantidad: parseFloat(tr.querySelector('.cantidad').value)
        };

        if (item.cantidad <= 0) return alert('Cantidad inválida');

        const existe = carrito.find(p => p.id === item.id);
        existe ? existe.cantidad += item.cantidad : carrito.push(item);
        render();
    });

    function render() {
        const lista = document.getElementById('lista-carrito');
        lista.innerHTML = '';
        total = 0;

        carrito.forEach((p, i) => {
            const sub = p.precio * p.cantidad;
            total += sub;
            lista.innerHTML += `
<div class="carrito-item">
<strong>${p.nombre}</strong>
<div class="d-flex justify-content-between">
<span>$${sub.toFixed(2)}</span>
<button class="btn btn-sm btn-danger eliminar" data-i="${i}">✖</button>
</div>
</div>`;
        });

        document.getElementById('total').textContent = '$' + total.toFixed(2);
    }

    document.addEventListener('click', e => {
        if (!e.target.classList.contains('eliminar')) return;
        carrito.splice(e.target.dataset.i, 1);
        render();
    });

    /* ================== FINALIZAR ================== */
    const modalVenta = new bootstrap.Modal('#modalFinalizarVenta', {
        backdrop: 'static'
    });
    const modalCliente = new bootstrap.Modal('#modalCliente', {
        backdrop: 'static'
    });

    document.getElementById('btnFinalizar').onclick = () => {
        if (!carrito.length) return alert('Carrito vacío');

        fetch('guardar_carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    carrito,
                    total
                })
            })
            .then(r => r.json()).then(ok => {
                if (!ok.ok) return alert('Error');
                document.getElementById('resumenVenta').innerHTML =
                    carrito.map(p => `
<tr><td>${p.nombre}</td><td>${p.cantidad}</td><td>$${p.precio}</td><td>$${(p.precio*p.cantidad).toFixed(2)}</td></tr>`)
                    .join('');
                document.getElementById('totalModal').textContent = total.toFixed(2);
                document.getElementById('montoPago').value = total.toFixed(2);
                modalVenta.show();
            });
    };

    /* ================== CLIENTE ================== */
    document.getElementById('btnNuevoCliente').onclick = () => modalCliente.show();

    document.getElementById('modalCliente').addEventListener('hidden.bs.modal', () => {
        document.body.classList.add('modal-open');
    });
    </script>
<script>
    const chkFactura = document.getElementById('requiereFactura');
const clienteSelect = document.getElementById('cliente_id');
const campoPublico = document.getElementById('nombrePublicoFactura');

function validarFactura() {
    // Si requiere factura y NO hay cliente seleccionado
    if (chkFactura.checked && !clienteSelect.value) {
        campoPublico.style.display = 'block';
    } else {
        campoPublico.style.display = 'none';
    }
}

chkFactura.addEventListener('change', validarFactura);
clienteSelect.addEventListener('change', validarFactura);

</script>
</body>

</html>