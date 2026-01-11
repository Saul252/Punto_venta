
<?php

require_once __DIR__ . '/includes/auth.php';
protegerPagina();

require_once __DIR__ . '/includes/sidebar.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Punto de Venta</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Iconos Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/punto/css/inicio.css">
   
</head>

<body>


<?php
// 👉 AQUI SE CARGA EL SIDEBAR
renderSidebar('Inicio');
?>

<!-- CONTENIDO -->
<div class="container my-5">

    <h4 class="mb-4 text-secondary">Menú principal</h4>

    <div class="row g-4">

        <!-- VENTAS -->
        <div class="col-md-4">
            <a href="/punto/pantallas/ventas.php" class="text-decoration-none">
                <div class="card card-dashboard shadow-sm text-center p-4">
                    <div class="icon-circle">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Ventas</h5>
                    <p class="text-muted mb-0">Registrar ventas</p>
                </div>
            </a>
        </div>

        <!-- ALMACÉN -->
        <div class="col-md-4">
            <a href="/punto/pantallas/almacen.php" class="text-decoration-none">
                <div class="card card-dashboard shadow-sm text-center p-4">
                    <div class="icon-circle">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Almacén</h5>
                    <p class="text-muted mb-0">Productos e inventario</p>
                </div>
            </a>
        </div>

        <!-- CAJA -->
        <div class="col-md-4">
            <a href="/punto/pantallas/caja.php" class="text-decoration-none">
                <div class="card card-dashboard shadow-sm text-center p-4">
                    <div class="icon-circle">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Caja</h5>
                    <p class="text-muted mb-0">Apertura y cierre</p>
                </div>
            </a>
        </div>

        <!-- USUARIOS (ADMIN) -->
        <?php if ($_SESSION['rol_id'] == 1): ?>
        <div class="col-md-4">
            <a href="usuarios.php" class="text-decoration-none">
                <div class="card card-dashboard shadow-sm text-center p-4">
                    <div class="icon-circle">
                        <i class="bi bi-people"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Usuarios</h5>
                    <p class="text-muted mb-0">Gestión de usuarios</p>
                </div>
            </a>
        </div>
        <?php endif; ?>

        <!-- EMPRESA -->
        <div class="col-md-4">
            <a href="empresa.php" class="text-decoration-none">
                <div class="card card-dashboard shadow-sm text-center p-4">
                    <div class="icon-circle">
                        <i class="bi bi-building"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Empresa</h5>
                    <p class="text-muted mb-0">Datos fiscales</p>
                </div>
            </a>
        </div>

    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


</body>
</html>
