<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../conexion.php';

$usuarios = $conexion->query("
    SELECT u.id, u.nombre, u.usuario, u.estado, r.nombre AS rol
    FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    ORDER BY u.id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Usuarios</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/punto/css/sidebar.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

<?php renderSidebar('Usuarios'); ?>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>👥 Usuarios</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregar">
            ➕ Agregar usuario
        </button>
    </div>

    <div class="card shadow-sm">
        <table class="table table-striped mb-0">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th width="140">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($u = $usuarios->fetch_assoc()): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['nombre']) ?></td>
                    <td><?= htmlspecialchars($u['usuario']) ?></td>
                    <td><?= $u['rol'] ?></td>
                    <td>
                        <span class="badge <?= $u['estado'] ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $u['estado'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td>
                        <a href="ajax/usuarios_editar.php?id=<?= $u['id'] ?>"
                           class="btn btn-sm btn-warning">✏️</a>

                        <?php if ($u['id'] != 1): ?>
                        <a href="ajax/usuarios_eliminar.php?id=<?= $u['id'] ?>"
                           onclick="return confirm('¿Eliminar usuario?')"
                           class="btn btn-sm btn-danger">🗑</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAgregar">
  <div class="modal-dialog">
    <form method="POST" action="ajax/usuarios_guardar.php" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">➕ Nuevo usuario</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="mb-2">
            <label>Nombre</label>
            <input name="nombre" class="form-control" required>
        </div>

        <div class="mb-2">
            <label>Usuario</label>
            <input name="usuario" class="form-control" required>
        </div>

        <div class="mb-2">
            <label>Contraseña</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="mb-2">
            <label>Rol</label>
            <select name="rol_id" class="form-select">
                <option value="1">Administrador</option>
                <option value="2">Usuario</option>
            </select>
        </div>

        <div class="mb-2">
            <label>Estado</label>
            <select name="estado" class="form-select">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
            </select>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary">Guardar</button>
      </div>

    </form>
  </div>
</div>

<?php if (!empty($_SESSION['alert'])): ?>
<script>
Swal.fire({
    icon: '<?= $_SESSION['alert']['type'] ?>',
    text: '<?= $_SESSION['alert']['msg'] ?>'
});
</script>
<?php unset($_SESSION['alert']); endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
