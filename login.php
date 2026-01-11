<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login | Punto de Venta</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Fuente moderna -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

<style>
/* =======================
   BASE
======================= */
body {
    font-family: 'Inter', sans-serif;
    min-height: 100vh;
    overflow: hidden;
}

/* =======================
   FONDO ANIMADO ELEGANTE
======================= */
.bg-login {
    position: fixed;
    inset: 0;
    background: linear-gradient(
        120deg,
        #0d6efd,
        #198754,
        #0dcaf0,
        #6610f2
    );
    background-size: 300% 300%;
    animation: gradientFlow 14s ease infinite;
    z-index: -2;
}

@keyframes gradientFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* =======================
   OVERLAY SUAVE
======================= */
.bg-login::after {
    content: "";
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.08);
}

/* =======================
   GLASS CARD
======================= */
.glass-login {
    background: rgba(255,255,255,0.18);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-radius: 18px;
    border: 1px solid rgba(255,255,255,0.3);
    box-shadow: 0 18px 40px rgba(0,0,0,0.18);
}

/* =======================
   TEXTOS
======================= */
.login-title {
    font-weight: 600;
    letter-spacing: 0.3px;
}

.login-subtitle {
    font-size: 13px;
    color: rgba(255,255,255,0.8);
}

/* =======================
   INPUTS
======================= */
.form-control {
    border-radius: 10px;
    border: none;
    padding: 12px 14px;
}

.form-control:focus {
    box-shadow: 0 0 0 3px rgba(13,110,253,.25);
}

/* =======================
   BOTÓN
======================= */
.btn-login {
    border-radius: 12px;
    font-weight: 600;
    padding: 12px;
}

/* =======================
   FOOTER
======================= */
.login-footer {
    font-size: 12px;
    color: rgba(255,255,255,0.75);
}
</style>
</head>

<body>

<div class="bg-login"></div>

<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh">
    <div class="glass-login p-4" style="max-width:420px; width:100%">

        <div class="text-center text-white mb-4">
            <div style="font-size:42px">Punto de Venta</div>
            <h4 class="login-title mt-2">Acceso al Sistema</h4>
            
        </div>

        <form method="POST" action="validar_login.php">
            <div class="mb-3">
                <label class="form-label text-white">Usuario</label>
                <input type="text" name="usuario" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label text-white">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button class="btn btn-primary btn-login w-100">
                🔐 Ingresar
            </button>
        </form>

        <div class="login-footer text-center mt-3">
            Sistema interno • Acceso restringido
        </div>

    </div>
</div>

</body>
</html>
