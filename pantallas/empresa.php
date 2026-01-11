<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../conexion.php';;

$empresa = $conexion->query("SELECT * FROM empresa LIMIT 1")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $empresa
        ? $conexion->prepare("UPDATE empresa SET razon_social=?, nombre_comercial=?, rfc=?, direccion=?, telefono=?, email=?, regimen_fiscal=? WHERE id=?")
        : $conexion->prepare("INSERT INTO empresa (razon_social, nombre_comercial, rfc, direccion, telefono, email, regimen_fiscal) VALUES (?,?,?,?,?,?,?)");

    $data = [
        $_POST['razon_social'],
        $_POST['nombre_comercial'],
        $_POST['rfc'],
        $_POST['direccion'],
        $_POST['telefono'],
        $_POST['email'],
        $_POST['regimen_fiscal']
    ];

    if ($empresa) {
        $data[] = $empresa['id'];
        $stmt->bind_param("sssssssi", ...$data);
    } else {
        $stmt->bind_param("sssssss", ...$data);
    }

    $stmt->execute();
    header("Location: empresa.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Empresa</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #f5f5f7;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial;
}

/* CONTENEDOR */
.page-wrapper {
    max-width: 900px;
    margin: auto;
}

/* CARD */
.card-apple {
    background: #ffffff;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.06);
    border: none;
}

/* TITULO */
.page-title {
    font-size: 26px;
    font-weight: 600;
    margin-bottom: 30px;
    color: #1d1d1f;
}

/* INPUTS */
.form-control {
    border-radius: 12px;
    padding: 12px 14px;
    border: 1px solid #e5e5ea;
    background: #fbfbfd;
}

.form-control:focus {
    border-color: #0071e3;
    box-shadow: 0 0 0 4px rgba(0,113,227,.15);
}

/* LABEL */
.form-label {
    font-weight: 500;
    color: #3a3a3c;
}

/* BOTON */
.btn-apple {
    background: #0071e3;
    border: none;
    border-radius: 14px;
    padding: 12px 30px;
    font-weight: 500;
}

.btn-apple:hover {
    background: #0060c9;
}

/* SEPARADOR */
.section {
    margin-bottom: 30px;
}
</style>
</head>

<body>

<?php renderSidebar('Empresa'); ?>

<div class="page-wrapper mt-5">

    <div class="card-apple">

        <div class="page-title">
            🏢 Información de la empresa
        </div>

        <form method="POST">

            <div class="row section g-4">

                <div class="col-md-6">
                    <label class="form-label">Razón social</label>
                    <input type="text" name="razon_social" class="form-control"
                           value="<?= $empresa['razon_social'] ?? '' ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Nombre comercial</label>
                    <input type="text" name="nombre_comercial" class="form-control"
                           value="<?= $empresa['nombre_comercial'] ?? '' ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">RFC</label>
                    <input type="text" name="rfc" class="form-control"
                           value="<?= $empresa['rfc'] ?? '' ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control"
                           value="<?= $empresa['telefono'] ?? '' ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= $empresa['email'] ?? '' ?>">
                </div>

            </div>

            <div class="section">
                <label class="form-label">Dirección fiscal</label>
                <textarea name="direccion" class="form-control" rows="3"><?= $empresa['direccion'] ?? '' ?></textarea>
            </div>

            <div class="section col-md-6">
                <label class="form-label">Régimen fiscal</label>
                <input type="text" name="regimen_fiscal" class="form-control"
                       value="<?= $empresa['regimen_fiscal'] ?? '' ?>">
            </div>

            <div class="text-end mt-4">
                <button class="btn btn-apple">
                    Guardar cambios
                </button>
            </div>

        </form>

    </div>

</div>

</body>
</html>
