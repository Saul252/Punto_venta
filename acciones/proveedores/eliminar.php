<?php
session_start();
require "../../conexion.php";
require_once __DIR__ . "/../../includes/auth.php";

try {
    $id = (int)$_GET['id'];

    if ($id <= 0) {
        throw new Exception("ID inválido");
    }

    $conexion->query("DELETE FROM proveedores  WHERE id = $id");

    header("Location: /punto/pantallas/proveedores.php?success=eliminado");
    exit;

} catch (Exception $e) {
    header("Location: /punto/pantallas/proveedores.php?error=eliminar");
    exit;
}
