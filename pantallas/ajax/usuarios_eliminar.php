<?php
session_start();

require_once __DIR__ . '/../../includes/auth.php';
protegerPagina();

require_once __DIR__ . '/../../conexion.php';

/* =========================
   VALIDAR ID
========================= */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /punto/pantallas/usuarios.php");
    exit;
}

$id = (int) $_GET['id'];

/* =========================
   ELIMINAR
========================= */
$eliminado = false;

$stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $eliminado = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Eliminar Usuario</title>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<script>
<?php if ($eliminado): ?>
    Swal.fire({
        icon: 'success',
        title: 'Usuario eliminado',
        text: 'El usuario fue eliminado correctamente',
        confirmButtonText: 'Aceptar'
    }).then(() => {
        window.location.href = '/punto/pantallas/usuarios.php';
    });
<?php else: ?>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'No se pudo eliminar el usuario',
        confirmButtonText: 'Aceptar'
    }).then(() => {
        window.location.href = '/punto/pantallas/usuarios.php';
    });
<?php endif; ?>
</script>

</body>
</html>
