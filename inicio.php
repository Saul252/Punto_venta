<?php
require_once __DIR__ . '/includes/auth.php';
protegerPagina();

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/permisos.php';

$paginaActual = 'Inicio';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Inicio</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
    background:#eef2f7;
}

.main{
    margin-left:260px;
    padding-top:90px;
    padding-left:25px;
    padding-right:25px;
}

.card-modulo{
    border-radius:18px;
    border:none;
    background:#fff;
    box-shadow:0 15px 30px rgba(0,0,0,.08);
    transition:.25s;
    height:100%;
}

.card-modulo:hover{
    transform:translateY(-5px);
    box-shadow:0 20px 45px rgba(0,0,0,.12);
}

.card-modulo i{
    font-size:2.8rem;
}

.main{
    margin-left:260px;      /* ancho del sidebar */
    padding-top:90px;
    padding-left:24px;
    padding-right:24px;

    max-width:1200px;
    margin-right:auto;
}
@media (max-width: 1200px){
    .main{
        max-width:100%;
    }
}
@media (max-width: 992px){
    .main{
        margin-left:0;      /* sidebar ya no empuja */
        padding-left:16px;
        padding-right:16px;
    }
}
@media (max-width: 576px){
    .main{
        padding-top:80px;
        padding-left:12px;
        padding-right:12px;
    }
}

</style>
</head>

<body>

<?php renderSidebar($paginaActual); ?>

<div class="main">

<h3 class="fw-bold mb-4">🏠 Inicio</h3>

<div class="row g-4">

    <?php if (puedeVerModulo('ventas')): ?>
    <div class="col-md-4 col-lg-3">
        <a href="/punto/pantallas/ventas.php" class="text-decoration-none text-dark">
            <div class="card card-modulo p-4 text-center">
                <i class="bi bi-receipt text-primary mb-3"></i>
                <h6 class="fw-bold">Ventas</h6>
                <small class="text-muted">Registrar y consultar ventas</small>
            </div>
        </a>
    </div>
    <?php endif; ?>

    <?php if (puedeVerModulo('almacen')): ?>
    <div class="col-md-4 col-lg-3">
        <a href="/punto/pantallas/almacen.php" class="text-decoration-none text-dark">
            <div class="card card-modulo p-4 text-center">
                <i class="bi bi-box-seam text-warning mb-3"></i>
                <h6 class="fw-bold">Almacén</h6>
                <small class="text-muted">Productos e inventario</small>
            </div>
        </a>
    </div>
    <?php endif; ?>

    <?php if (puedeVerModulo('caja')): ?>
    <div class="col-md-4 col-lg-3">
        <a href="/punto/pantallas/caja.php" class="text-decoration-none text-dark">
            <div class="card card-modulo p-4 text-center">
                <i class="bi bi-cash-stack text-success mb-3"></i>
                <h6 class="fw-bold">Caja</h6>
                <small class="text-muted">Ingresos y egresos</small>
            </div>
        </a>
    </div>
    <?php endif; ?>

    <?php if (puedeVerModulo('gastos')): ?>
    <div class="col-md-4 col-lg-3">
        <a href="/punto/pantallas/gastos.php" class="text-decoration-none text-dark">
            <div class="card card-modulo p-4 text-center">
                <i class="bi bi-wallet2 text-danger mb-3"></i>
                <h6 class="fw-bold">Gastos</h6>
                <small class="text-muted">Control de gastos</small>
            </div>
        </a>
    </div>
    <?php endif; ?>

    <?php if (puedeVerModulo('clientes')): ?>
    <div class="col-md-4 col-lg-3">
        <a href="/punto/pantallas/clientes.php" class="text-decoration-none text-dark">
            <div class="card card-modulo p-4 text-center">
                <i class="bi bi-people-fill text-info mb-3"></i>
                <h6 class="fw-bold">Clientes</h6>
                <small class="text-muted">Gestión de clientes</small>
            </div>
        </a>
    </div>
    <?php endif; ?>

    <?php if (puedeVerModulo('finanzas')): ?>
    <div class="col-md-4 col-lg-3">
        <a href="/punto/pantallas/finanzas.php" class="text-decoration-none text-dark">
            <div class="card card-modulo p-4 text-center">
                <i class="bi bi-graph-up-arrow text-secondary mb-3"></i>
                <h6 class="fw-bold">Finanzas</h6>
                <small class="text-muted">Resumen financiero</small>
            </div>
        </a>
    </div>
    <?php endif; ?>

    <?php if (puedeVerModulo('facturacion')): ?>
    <div class="col-md-4 col-lg-3">
        <a href="/punto/pantallas/facturacion.php" class="text-decoration-none text-dark">
            <div class="card card-modulo p-4 text-center">
                <i class="bi bi-file-earmark-text text-primary mb-3"></i>
                <h6 class="fw-bold">Facturación</h6>
                <small class="text-muted">Facturar ventas</small>
            </div>
        </a>
    </div>
    <?php endif; ?>

    <?php if (puedeVerModulo('usuarios')): ?>
    <div class="col-md-4 col-lg-3">
        <a href="/punto/pantallas/usuarios.php" class="text-decoration-none text-dark">
            <div class="card card-modulo p-4 text-center">
                <i class="bi bi-person-badge text-dark mb-3"></i>
                <h6 class="fw-bold">Usuarios</h6>
                <small class="text-muted">Control de usuarios</small>
            </div>
        </a>
    </div>
    <?php endif; ?>

    <?php if (puedeVerModulo('empresa')): ?>
    <div class="col-md-4 col-lg-3">
        <a href="/punto/pantallas/empresa.php" class="text-decoration-none text-dark">
            <div class="card card-modulo p-4 text-center">
                <i class="bi bi-building text-muted mb-3"></i>
                <h6 class="fw-bold">Empresa</h6>
                <small class="text-muted">Datos de la empresa</small>
            </div>
        </a>
    </div>
    <?php endif; ?>

</div>

</div>

</body>
</html>
