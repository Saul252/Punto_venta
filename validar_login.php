<?php
session_start();
require "conexion.php";

$usuario  = $_POST['usuario'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($usuario) || empty($password)) {
    echo "<script>alert('Complete todos los campos');window.location='login.php'</script>";
    exit();
}

$sql = "SELECT u.id, u.nombre, u.usuario, u.password, u.rol_id, r.nombre AS rol
        FROM usuarios u
        INNER JOIN roles r ON u.rol_id = r.id
        WHERE u.usuario = ? AND u.estado = 1
        LIMIT 1";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {

    $row = $resultado->fetch_assoc();

    if (password_verify($password, $row['password'])) {

        // 🔐 VARIABLES DE SESIÓN
        $_SESSION['user_id']   = $row['id'];
        $_SESSION['usuario']   = $row['usuario'];
        $_SESSION['nombre']    = $row['nombre'];
        $_SESSION['rol_id']    = $row['rol_id'];
        $_SESSION['rol']       = $row['rol'];
        $_SESSION['login']     = true;

        header("Location: inicio.php");
        exit();

    } else {
        echo "<script>alert('Contraseña incorrecta');window.location='login.php'</script>";
    }

} else {
    echo "<script>alert('Usuario no existe o está inactivo');window.location='login.php'</script>";
}
?>
