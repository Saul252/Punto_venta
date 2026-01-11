<?php
session_start();
require_once __DIR__ . '/../../conexion.php';


$nombre   = $_POST['nombre'] ?? '';
$usuario  = $_POST['usuario'] ?? '';
$password = $_POST['password'] ?? '';
$rol_id   = $_POST['rol_id'] ?? 0;

if (!$nombre || !$usuario || !$password || !$rol_id) {
    $_SESSION['alert'] = ['type'=>'error','msg'=>'Datos incompletos'];
    header("Location: usuarios.php");
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conexion->prepare("
    INSERT INTO usuarios (nombre, usuario, password, rol_id, estado)
    VALUES (?, ?, ?, ?, 1)
");
$stmt->bind_param("sssi", $nombre, $usuario, $hash, $rol_id);
$stmt->execute();

$_SESSION['alert'] = ['type'=>'success','msg'=>'Usuario agregado'];
header("Location: /punto/pantallas/usuarios.php");
