<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/permisos.php';

function renderSidebar(string $paginaActual = '')
{
?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM listo');

    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');

    if (!toggleBtn || !sidebar) return;
    console.log('toggleBtn:', toggleBtn);
    console.log('sidebar:', sidebar);

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('hidden');
        document.body.classList.toggle('sidebar-hidden');
    });

});
</script>
<link rel="stylesheet" href="/punto/css/sidebar.css">



<nav class="navbar navbar-expand-lg fixed-top shadow-sm">

    <div class="container-fluid">

        <button class="btn btn-outline-light me-3" id="toggleSidebar">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                <path d="M2.5 12.5h11v-1h-11v1zm0-4h11v-1h-11v1zm0-4h11v-1h-11v1z" />
            </svg>
        </button>

        <span class="navbar-brand fw-bold text-white"><?= $paginaActual ?></span>


        <div class="d-flex align-items-center ms-auto text-white">
            <span class="navbar-text me-3 text-white">
                <i class="bi bi-person-circle"></i>
                <?= $_SESSION['nombre'] ?? 'Usuario' ?>
            </span>
            <a href="/punto/logout.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-right"></i> Salir
            </a>
        </div>
    </div>
</nav>

<aside id="sidebar" class="bg-dark text-white">
    <div class="p-3">
        <h5 class="text-center mb-4">Menú</h5>

        <ul class="nav nav-pills flex-column gap-1">

            <?php if (puedeVerModulo('inicio')): ?>
            <li class="nav-item">
                <a href="/punto/inicio.php"
                    class="nav-link hoverbutton <?= $paginaActual == 'Inicio' ? 'active' : '' ?>">

                    <!-- 🏠 INICIO -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2"
                        viewBox="0 0 16 16">
                        <path d="M8 .5l6 6V15a1 1 0 0 1-1 1H9v-4H7v4H3a1 1 0 0 1-1-1V6.5l6-6z" />
                    </svg>
                    Inicio
                </a>
            </li>
            <?php endif; ?>

            <?php if (puedeVerModulo('ventas')): ?>
            <li class="nav-item">
                <a href="/punto/pantallas/ventas.php"
                    class="nav-link hoverbutton <?= $paginaActual == 'Ventas' ? 'active' : '' ?>">

                    <!-- 🧾 VENTAS -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2"
                        viewBox="0 0 16 16">
                        <path d="M4 1h8a1 1 0 0 1 1 1v12l-3-2-3 2-3-2-3 2V2a1 1 0 0 1 1-1z" />
                    </svg>
                    Ventas
                </a>
            </li>
            <?php endif; ?>

            <?php if (puedeVerModulo('almacen')): ?>
            <li class="nav-item">
                <a href="/punto/pantallas/almacen.php"
                    class="nav-link hoverbutton <?= $paginaActual == 'Almacén' ? 'active' : '' ?>">

                    <!-- 📦 ALMACÉN -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2"
                        viewBox="0 0 16 16">
                        <path d="M2 3l6-2 6 2v10l-6 2-6-2V3z" />
                        <path d="M8 1v14" />
                    </svg>
                    Almacén
                </a>
            </li>
            <?php endif; ?>

            <?php if (puedeVerModulo('caja')): ?>
            <li class="nav-item">
                <a href="/punto/pantallas/caja.php"
                    class="nav-link hoverbutton <?= $paginaActual == 'Caja' ? 'active' : '' ?>">

                    <!-- 💰 CAJA -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2"
                        viewBox="0 0 16 16">
                        <path d="M1 4h14v8H1z" />
                        <path d="M3 6h4v4H3z" />
                    </svg>
                    Caja
                </a>
            </li>
            <?php endif; ?>

            <?php if (puedeVerModulo('usuarios')): ?>
            <li class="nav-item">
                <a href="/punto/pantallas/usuarios.php"
                    class="nav-link hoverbutton <?= $paginaActual == 'Usuarios' ? 'active' : '' ?>">

                    <!-- 👥 USUARIOS -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2"
                        viewBox="0 0 16 16">
                        <path d="M5 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                        <path d="M11 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                        <path d="M2 14s0-3 3-3h2c3 0 3 3 3 3H2z" />
                        <path d="M9 14s0-3 3-3h1c2 0 2 3 2 3H9z" />
                    </svg>
                    Usuarios
                </a>
            </li>
            <?php endif; ?>

            <?php if (puedeVerModulo('empresa')): ?>
            <li class="nav-item">
                <a href="/punto/pantallas/empresa.php"
                    class="nav-link hoverbutton <?= $paginaActual == 'Empresa' ? 'active' : '' ?>">

                    <!-- 🏢 EMPRESA -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2"
                        viewBox="0 0 16 16">
                        <path d="M2 15V1h12v14" />
                        <path d="M5 3h2v2H5zM9 3h2v2H9zM5 7h2v2H5zM9 7h2v2H9z" />
                    </svg>
                    Empresa
                </a>
            </li>
            <?php endif; ?>
            <?php if (puedeVerModulo('gastos')): ?>
            <li class="nav-item">
                <a href="/punto/pantallas/gastos.php"
                    class="nav-link hoverbutton <?= $paginaActual == 'Gastos' ? 'active' : '' ?>">

                    <!-- 💸 GASTOS -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2"
                        viewBox="0 0 16 16">

                        <!-- Documento / Recibo -->
                        <path d="M3 0h7l3 3v13H3V0z" />
                        <path d="M10 0v3h3" />

                        <!-- Línea de gasto -->
                        <path d="M5 6h6v1H5zM5 8h6v1H5z" />

                        <!-- Moneda -->
                        <circle cx="8" cy="12" r="1.5" />
                    </svg>

                    Gastos
                </a>
            </li>
            <?php endif; ?>
            <?php if (puedeVerModulo('clientes')): ?>
            <li class="nav-item">
                <a href="/punto/pantallas/clientes.php"
                    class="nav-link hoverbutton <?= $paginaActual == 'Clientes' ? 'active' : '' ?>">

                    <!-- 💸 GASTOS -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path d="M16 11c1.66 0 3-1.79 3-4s-1.34-4-3-4-3 1.79-3 4 1.34 4 3 4z" />
                        <path d="M8 11c1.66 0 3-1.79 3-4S9.66 3 8 3 5 4.79 5 7s1.34 4 3 4z" />
                        <path d="M8 13c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                        <path d="M16 13c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45v2h7v-2c0-2.66-5.33-4-8-4z" />
                    </svg>

                    Clientes
                </a>
            </li>
            <?php endif; ?>
            <?php if (puedeVerModulo('finanzas')): ?>
            <li class="nav-item">
                <a href="/punto/pantallas/finanzas.php"
                    class="nav-link hoverbutton <?= $paginaActual === 'Finanzas' ? 'active' : '' ?>">

                    <!-- 💰 FINANZAS -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                        viewBox="0 0 16 16" class="me-2">
                        <path d="M0 4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V4z" />
                        <path d="M3 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" />
                    </svg>

                    Finanzas
                </a>
            </li>
            <?php endif; ?>


            <?php if (puedeVerModulo('facturacion')): ?>
            <li class="nav-item">
                <a href="/punto/pantallas/facturacion.php"
                    class="nav-link hoverbutton <?= $paginaActual === 'Facturacion' ? 'active' : '' ?>">

                    <!-- 🧾 FACTURACIÓN -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                        viewBox="0 0 16 16" class="me-2">
                        <path d="M4 0h8a2 2 0 0 1 2 2v14l-4-2-4 2-4-2-4 2V2a2 2 0 0 1 2-2z" />
                    </svg>

                    Facturación
                </a>
            </li>
            <?php endif; ?>


        </ul>
    </div>
</aside>



<?php
}