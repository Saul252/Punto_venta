<?php
function puedeVerModulo(string $modulo): bool
{
    if (!isset($_SESSION['rol_id'])) {
        return false;
    }

    // permisos por rol
    $permisos = [
        1 => [ // ADMIN
            'inicio',
            'ventas',
            'almacen',
            'caja',
            'usuarios',
            'empresa',
            'gastos',
            'clientes',
        ],
        2 => [ // CAJERO
            'inicio',
            'ventas',
            'caja'
        ],
        3 => [ // ALMACEN
            'inicio',
            'almacen'
        ]
    ];

    $rol = $_SESSION['rol_id'];

    return isset($permisos[$rol]) && in_array($modulo, $permisos[$rol]);
}
