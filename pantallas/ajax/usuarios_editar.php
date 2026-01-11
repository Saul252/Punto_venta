<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/auth.php';
protegerPagina();

require_once __DIR__ . '/../../conexion.php';

/* ===========================
   VALIDAR ID
=========================== */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /punto/pantallas/usuarios.php");
    exit;
}

$id = (int) $_GET['id'];

/* ===========================
   GUARDAR CAMBIOS
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre  = $_POST['nombre'];
    $usuario = $_POST['usuario'];
    $rol_id  = $_POST['rol_id'];

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $conexion->prepare("
            UPDATE usuarios
            SET nombre = ?, usuario = ?, password = ?, rol_id = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssii", $nombre, $usuario, $password, $rol_id, $id);
    } else {
        $stmt = $conexion->prepare("
            UPDATE usuarios
            SET nombre = ?, usuario = ?, rol_id = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssii", $nombre, $usuario, $rol_id, $id);
    }

    $stmt->execute();
    header("Location: /punto/pantallas/usuarios.php");
    exit;
}

/* ===========================
   OBTENER USUARIO
=========================== */
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    header("Location: /punto/pantallas/usuarios.php");
    exit;
}

/* ===========================
   ROLES
=========================== */
$roles = $conexion->query("SELECT id, nombre FROM roles");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Usuario</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header fw-bold">
            ✏️ Editar Usuario
        </div>

        <div class="card-body">
            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control"
                           value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="usuario" class="form-control"
                           value="<?= htmlspecialchars($usuario['usuario']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nueva contraseña (opcional)</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="Dejar vacío para no cambiar">
                </div>

                <div class="mb-3">
                    <label class="form-label">Rol</label>
                    <select name="rol_id" class="form-select" required>
                        <?php while ($r = $roles->fetch_assoc()): ?>
                            <option value="<?= $r['id'] ?>"
                                <?= $usuario['rol_id'] == $r['id'] ? 'selected' : '' ?>>
                                <?= $r['nombre'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="/punto/pantallas/usuarios.php" class="btn btn-secondary">
                        ⬅ Volver
                    </a>

                    <button class="btn btn-primary">
                        💾 Guardar cambios
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>
