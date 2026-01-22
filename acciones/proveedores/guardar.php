<?php
session_start();
require "../../conexion.php";
require_once __DIR__ . "/../../includes/auth.php";



try {

    $id = $_POST['id'] ?? null;
    $nombre = $_POST['nombre'];
    $rfc = $_POST['rfc'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $direccion = $_POST['direccion'];

    if ($id) {
        $stmt = $conexion->prepare("
            UPDATE proveedores
            SET nombre=?, rfc=?, telefono=?, email=?, direccion=?
            WHERE id=?
        ");
        $stmt->bind_param("sssssi", $nombre, $rfc, $telefono, $email, $direccion, $id);
        $accion = "actualizado";
    } else {
        $stmt = $conexion->prepare("
            INSERT INTO proveedores (nombre, rfc, telefono, email, direccion)
            VALUES (?,?,?,?,?)
        ");
        $stmt->bind_param("sssss", $nombre, $rfc, $telefono, $email, $direccion);
        $accion = "creado";
    }

    if (!$stmt->execute()) {
        throw new Exception("Error al guardar proveedor");
    }

    header("Location: /punto/pantallas/proveedores.php?success=$accion");
    exit;

} catch (Exception $e) {
    header("Location: /punto/pantallas/proveedores.php?error=guardar");
    exit;
}

