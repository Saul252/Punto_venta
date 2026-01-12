<?php



require "../conexion.php";
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require_once __DIR__ . '/../includes/sidebar.php';

unset($_SESSION['carrito']);
unset($_SESSION['total']);

?>
<!DOCTYPE html>
<html lang="en">
<!-- 🔴 IMPORTANTE: lang en para punto decimal -->

<head>
    <meta charset="UTF-8">
    <title>Ventas | Punto de Venta</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    body {
        background: #eef1f5;
    }

    .card-pos {
        border-radius: 14px;
        border: none;
        box-shadow: 0 8px 20px rgba(0, 0, 0, .08);
    }

    .carrito-item {
        padding: 10px;
        border-bottom: 1px solid #eaeaea;
    }

    /* =====================
   ESTILO GENERAL POS
===================== */
    body {
        background: #eef1f5;
        font-size: 14px;
    }

    /* TARJETAS */
    .card-pos {
        border-radius: 16px;
        border: none;
        box-shadow: 0 10px 25px rgba(0, 0, 0, .08);
    }

    /* TÍTULOS */
    .titulo-seccion {
        font-weight: 600;
        color: #343a40;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 0;
    }

    .titulo-seccion .icono {
        font-size: 1.2rem;
    }

    /* BUSCADOR */
    #buscar {
        border-radius: 10px;
        padding-left: 12px;
    }

    /* =====================
   TABLA POS
===================== */
    .table-pos {
        border-collapse: separate;
        border-spacing: 0 6px;
    }

    .table-pos thead th {
        background: #f8f9fa;
        font-size: 13px;
        text-transform: uppercase;
        color: #6c757d;
        border: none;
        padding: 10px;
    }

    .table-pos tbody tr {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .05);
    }

    .table-pos tbody td {
        padding: 10px;
        border: none;
    }

    .table-pos tbody tr:hover {
        background: #f1f5ff;
    }

    /* INPUT CANTIDAD */
    .table-pos input.cantidad {
        width: 90px;
        text-align: center;
        border-radius: 8px;
    }

    /* BOTÓN AGREGAR */
    .btn-agregar {
        border-radius: 8px;
        padding: 4px 10px;
    }

    /* =====================
   CARRITO
===================== */
    .carrito-item {
        padding: 10px;
        border-bottom: 1px dashed #ddd;
    }

    .carrito-item strong {
        font-size: 14px;
    }

    #total {
        font-size: 1.2rem;
        color: #198754;
    }

    /* BOTÓN FINALIZAR */
    .btn-success {
        border-radius: 12px;
        font-weight: 600;
        padding: 10px;
    }

    /* =====================
   TABLA POS - ZEBRA
===================== */
    .table-pos tbody tr:nth-child(odd) {
        background: #ffffff;
    }

    .table-pos tbody tr:nth-child(even) {
        background: #f2f6ff;
    }

    .table-pos tbody tr:hover {
        background: #e6eeff;
    }

    /* BORDES SUAVES */
    .table-pos tbody tr {
        border-radius: 12px;
    }

    /* =====================
   TEXTO PRODUCTOS
===================== */
    .table-pos td.nombre-producto {
        font-size: 1.05rem;
        /* ⬅ MÁS GRANDE */
        font-weight: 600;
        color: #212529;
    }

    /* =====================
   SCROLL PRODUCTOS POS
===================== */
    .tabla-productos-scroll {
        max-height: 60vh;
        /* altura visible */
        overflow-y: auto;
        border-radius: 12px;
    }

    /* HEADER FIJO */
    .tabla-productos-scroll thead th {
        position: sticky;
        top: 0;
        background: #ffffff;
        z-index: 2;
        border-bottom: 2px solid #dee2e6;
    }

    /* SCROLL BONITO */
    .tabla-productos-scroll::-webkit-scrollbar {
        width: 8px;
    }

    .tabla-productos-scroll::-webkit-scrollbar-thumb {
        background: #cfd6e4;
        border-radius: 10px;
    }

    .tabla-productos-scroll::-webkit-scrollbar-thumb:hover {
        background: #b5c0d6;
    }

    /* =====================
   SCROLL CARRITO POS
===================== */
    .carrito-scroll {
        max-height: 45vh;
        /* altura visible del carrito */
        overflow-y: auto;
        margin-bottom: 10px;
    }

    /* Scroll bonito */
    .carrito-scroll::-webkit-scrollbar {
        width: 8px;
    }

    .carrito-scroll::-webkit-scrollbar-thumb {
        background: #cfd6e4;
        border-radius: 10px;
    }

    .carrito-scroll::-webkit-scrollbar-thumb:hover {
        background: #b5c0d6;
    }

    /* Items más claros */
    .carrito-item {
        background: #f8f9fc;
        border-radius: 10px;
        margin-bottom: 8px;
    }
    </style>
</head>

<body>
    <?php
// 👉 AQUI SE CARGA EL SIDEBAR
renderSidebar('Ventas');
?>


    <div class="container-fluid mt-3">
        <div class="row g-3">

            <!-- PRODUCTOS -->
            <div class="col-lg-8">
                <div class="card card-pos">
                    <div class="card-body">

                        <div class="d-flex justify-content-between mb-2">
                            <h5 class="titulo-seccion">
                                <span class="icono">📦</span> Productos
                            </h5>

                            <input type="text" id="buscar" class="form-control form-control-sm w-50"
                                placeholder="Buscar...">
                        </div>
                        <div class="tabla-productos-scroll">
                            <table class="table table-pos align-middle">


                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th>Precio</th>
                                        <th>Stock</th>
                                        <th>Cant.</th>
                                        <th>Agregar</th>
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
                        <div class="carrito-scroll" id="lista-carrito"></div>

                        <hr>

                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span id="total">$0.00</span>
                        </div>

                        <button class="btn btn-success w-100 mt-3" onclick="finalizarVenta()">
                            Finalizar venta
                        </button>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
    /* 🔍 BUSCAR PRODUCTOS */
    const buscar = document.getElementById('buscar');
    const tabla = document.getElementById('tabla-productos');

    function cargarProductos(q = '') {
        fetch('ajax_buscar_productos.php?q=' + q)
            .then(res => res.text())
            .then(html => tabla.innerHTML = html);
    }

    buscar.addEventListener('keyup', () => cargarProductos(buscar.value));
    cargarProductos();

    /* 🛒 CARRITO */
    let carrito = [];
    let total = 0;

    /* ➕ AGREGAR */
    document.addEventListener('click', e => {
        if (!e.target.classList.contains('agregar')) return;

        const fila = e.target.closest('tr');

        const id = fila.dataset.id;
        const nombre = fila.dataset.nombre;
        const precio = parseFloat(fila.dataset.precio);

        let cantidad = fila.querySelector('.cantidad').value;
        cantidad = cantidad.replace(',', '.');
        cantidad = parseFloat(cantidad);

        if (isNaN(cantidad) || cantidad <= 0) {
            alert('Cantidad inválida');
            return;
        }

        const item = carrito.find(p => p.id === id);

        if (item) {
            item.cantidad += cantidad;
        } else {
            carrito.push({
                id,
                nombre,
                precio,
                cantidad
            });
        }

        renderCarrito();
    });

    /* 💰 SUBTOTAL */
    function calcularSubtotal(item) {
        return item.precio * item.cantidad;
    }

    /* 🔄 RENDER */
    function renderCarrito() {
        const lista = document.getElementById('lista-carrito');
        const totalSpan = document.getElementById('total');

        lista.innerHTML = '';
        total = 0;

        carrito.forEach((item, i) => {
            const subtotal = calcularSubtotal(item);
            total += subtotal;

            lista.innerHTML += `
            <div class="carrito-item">
                <strong>${item.nombre}</strong>

                <input type="number"
                       step="0.001"
                       min="0.001"
                       lang="en"
                       class="form-control form-control-sm cantidad-edit mt-1"
                       data-index="${i}"
                       value="${item.cantidad}">

                <div class="d-flex justify-content-between mt-2">
                    <span>$${subtotal.toFixed(2)}</span>
                    <button class="btn btn-sm btn-outline-danger eliminar"
                            data-index="${i}">✖</button>
                </div>
            </div>
        `;
        });

        totalSpan.textContent = '$' + total.toFixed(2);
    }

    /* ✏️ EDITAR */
    document.addEventListener('input', e => {
        if (!e.target.classList.contains('cantidad-edit')) return;

        const i = e.target.dataset.index;

        let valor = e.target.value.replace(',', '.');
        valor = parseFloat(valor);

        if (!isNaN(valor) && valor > 0) {
            carrito[i].cantidad = valor;
            renderCarrito();
        }
    });

    /* ❌ ELIMINAR */
    document.addEventListener('click', e => {
        if (!e.target.classList.contains('eliminar')) return;
        carrito.splice(e.target.dataset.index, 1);
        renderCarrito();
    });

    /* ✅ FINALIZAR */
    function finalizarVenta() {
        if (carrito.length === 0) {
            alert("El carrito está vacío");
            return;
        }

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
            .then(r => r.json())
            .then(resp => {
                if (resp.ok) {
                    location.href = 'finalizar_venta.php';
                } else {
                    alert('Error al guardar venta');
                }
            });
    }
    </script>

</body>

</html>