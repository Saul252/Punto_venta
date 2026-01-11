<?php
session_start();

if (!isset($_SESSION['login'])) {
    header("Location: /punto/login.php");
    exit;
}

if (empty($_SESSION['carrito'])) {
    header("Location: /punto/pantallas/ventas.php");
    exit;
}

require "../conexion.php";

$carrito = $_SESSION['carrito'];
$total   = $_SESSION['total'];

$clientes = $conexion->query("SELECT id, nombre FROM clientes ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Finalizar Venta</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    body {
        background: #eef1f5;
    }

    .card-pos {
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
    }

    .total {
        font-size: 28px;
        font-weight: bold;
        color: #0d6efd;
    }
    </style>
</head>

<body>

    <div class="container my-4">
        <div class="row g-4">

            <!-- 🧾 RESUMEN -->
            <div class="col-lg-7">
                <div class="card card-pos">
                    <div class="card-body">

                        <h5 class="mb-3">Resumen de productos</h5>

                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($carrito as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><?= number_format($item['cantidad'], 3) ?></td>
                                    <td>$<?= number_format($item['precio'], 2) ?></td>
                                    <td>$<?= number_format($item['subtotal'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <hr>
                        <div class="d-flex justify-content-between total">
                            <span>Total</span>
                            <span>$<?= number_format($total, 2) ?></span>
                        </div>

                    </div>
                </div>
            </div>

            <!-- 💳 FINALIZAR -->
            <div class="col-lg-5">
                <div class="card card-pos">
                    <div class="card-body">

                        <h5 class="mb-3">Finalizar venta</h5>

                        <form action="/punto/acciones/guardar_venta.php" method="POST">

                            <!-- CLIENTE -->
                            <label class="form-label">Cliente</label>

                            <div class="input-group mb-3">
                                <select class="form-select" name="cliente_id" id="cliente_id">
                                    <option value="">Público en general</option>
                                    <?php while ($c = $clientes->fetch_assoc()): ?>
                                    <option value="<?= $c['id'] ?>">
                                        <?= htmlspecialchars($c['nombre']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>

                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#modalCliente">
                                    ➕ Nuevo
                                </button>
                            </div>


                            <!-- MÉTODO -->
                            <label class="form-label">Método de pago</label>
                            <select class="form-select mb-3" name="metodo_pago" required>
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TARJETA">Tarjeta</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                            </select>

                            <!-- MONTO -->
                            <label class="form-label">Monto a pagar</label>
                            <input type="number" step="0.01" class="form-control mb-3" name="monto_pago"
                                value="<?= number_format($total, 2, '.', '') ?>" required>

                            <!-- FACTURA (solo visual por ahora) -->
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" disabled>
                                <label class="form-check-label text-muted">
                                    Facturación (próximamente)
                                </label>
                            </div>

                            <button class="btn btn-success w-100">
                                💾 Confirmar venta
                            </button>

                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="modal fade" id="modalCliente" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <form id="formCliente">

                    <div class="modal-header">
                        <h5 class="modal-title">Agregar cliente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Razón social</label>
                            <input type="text" name="razon_social" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">RFC</label>
                            <input type="text" name="rfc" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Código postal</label>
                            <input type="text" name="codigo_postal" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Dirección fiscal</label>
                            <input type="text" name="direccion_fiscal" class="form-control">
                        </div>

                        <label class="form-label">Régimen fiscal</label>
                        <select name="regimen_fiscal" class="form-select" required>
                            <option value="">Selecciona régimen fiscal</option>
                            <option value="601">601 – General de Ley Personas Morales</option>
                            <option value="603">603 – Personas Morales con Fines no Lucrativos</option>
                            <option value="605">605 – Sueldos y Salarios e Ingresos Asimilados a Salarios</option>
                            <option value="606">606 – Arrendamiento</option>
                            <option value="607">607 – Régimen de Enajenación o Adquisición de Bienes</option>
                            <option value="608">608 – Demás ingresos</option>
                            <option value="609">609 – Consolidación</option>
                            <option value="610">610 – Residentes en el Extranjero sin Establecimiento Permanente en
                                México</option>
                            <option value="611">611 – Ingresos por Dividendos (socios y accionistas)</option>
                            <option value="612">612 – Personas Fís<icas con Activida>des Empresariales y Profesionales
                            </option>
                            <option value="614">614 – Ingresos por intereses</option>
                            <option value="615">615 – Régimen de los ingresos por obtención de premios</option>
                            <option value="616">616 – Sin obligaciones fiscales</option>
                            <option value="620">620 – Sociedades Cooperativas de Producción que optan por diferir sus
                                ingresos</option>
                            <option value="621">621 – Incorporación Fiscal</option>
                            <option value="622">622 – Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras</option>
                            <option value="623">623 – Opcional para Grupos de Sociedades</option>
                            <option value="624">624 – Coordinados</option>
                            <option value="625">625 – Régimen de las Actividades Empresariales con ingresos a través de
                                Plataformas Tecnológicas</option>
                            <option value="626">626 – Régimen Simplificado de Confianza (RESICO)</option>
                        </select>


                        <label class="form-label">Uso de CFDI</label>
                        <select name="uso_cfdi" class="form-select" required>
                            <option value="">Selecciona uso de CFDI</option>
                            <option value="G01">G01 – Adquisición de mercancías</option>
                            <option value="G02">G02 – Devoluciones, descuentos o bonificaciones</option>
                            <option value="G03">G03 – Gastos en general</option>
                            <option value="I01">I01 – Construcciones</option>
                            <option value="I02">I02 – Mobiliario y equipo de oficina por inversiones</option>
                            <option value="I03">I03 – Equipo de transporte</option>
                            <option value="I04">I04 – Equipo de cómputo y accesorios</option>
                            <option value="I05">I05 – Dados, troqueles, moldes, matrices y herramental</option>
                            <option value="I06">I06 – Comunicaciones telefónicas</option>
                            <option value="I07">I07 – Comunicaciones satelitales</option>
                            <option value="I08">I08 – Otra maquinaria y equipo</option>
                            <option value="D01">D01 – Honorarios médicos, dentales y gastos hospitalarios</option>
                            <option value="D02">D02 – Gastos médicos por incapacidad o discapacidad</option>
                            <option value="D03">D03 – Gastos funerales</option>
                            <option value="D04">D04 – Donativos</option>
                            <option value="D05">D05 – Intereses reales efectivamente pagados</option>
                            <option value="D06">D06 – Aportaciones voluntarias al SAR</option>
                            <option value="D07">D07 – Primas por seguros de gastos médicos</option>
                            <option value="D08">D08 – Gastos de transportación escolar obligatoria</option>
                            <option value="D09">D09 – Depósitos en cuentas para el ahorro, primas de planes de pensiones
                            </option>
                            <option value="D10">D10 – Pagos por servicios educativos (colegiaturas)</option>
                            <option value="P01">P01 – Por definir</option>
                        </select>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button class="btn btn-primary">
                            Guardar cliente
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $('#formCliente').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: '/punto/acciones/guardar_cliente.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp) {
                if (resp.ok) {
                    $('#cliente_id').append(
                        `<option value="${resp.id}" selected>${resp.nombre}</option>`
                    );

                    $('#modalCliente').modal('hide');
                    $('#formCliente')[0].reset();
                } else {
                    alert(resp.msg);
                }
            },
            error: function() {
                alert('Error de conexión');
            }
        });
    });
    </script>

</body>

</html>