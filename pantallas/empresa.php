<?php
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../conexion.php';

$empresa = $conexion->query("SELECT * FROM empresa LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Empresa</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body {
    background: #f5f5f7;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto;
}
.page-wrapper { max-width: 1000px; margin: auto; }
.card-apple {
    background: #fff;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0,0,0,.06);
}
.page-title {
    font-size: 26px;
    font-weight: 600;
    margin-bottom: 30px;
}
.form-control {
    border-radius: 12px;
    padding: 12px;
}
.section { margin-bottom: 30px; }
.btn-apple {
    background: #0071e3;
    color: #fff;
    border-radius: 14px;
    padding: 12px 30px;
    border: none;
}
</style>
</head>

<body>

<?php renderSidebar('Empresa'); ?>

<div class="page-wrapper mt-5">
<div class="card-apple">

<div class="page-title">🏢 Información fiscal de la empresa</div>

<form id="formEmpresa">

<!-- DATOS FISCALES -->
<div class="row g-4 section">
    <div class="col-md-6">
        <label class="form-label">Razón social *</label>
        <input name="razon_social" class="form-control" required
               value="<?= $empresa['razon_social'] ?? '' ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label">Nombre comercial</label>
        <input name="nombre_comercial" class="form-control"
               value="<?= $empresa['nombre_comercial'] ?? '' ?>">
    </div>

    <div class="col-md-4">
        <label class="form-label">RFC *</label>
        <input name="rfc" class="form-control" required
               value="<?= $empresa['rfc'] ?? '' ?>">
    </div>

    <div class="col-md-4">
        <label class="form-label">Régimen fiscal *</label>
        <input name="regimen_fiscal" class="form-control"
               value="<?= $empresa['regimen_fiscal'] ?? '' ?>">
    </div>

    <div class="col-md-4">
        <label class="form-label">Código postal *</label>
        <input name="codigo_postal" class="form-control"
               value="<?= $empresa['codigo_postal'] ?? '' ?>">
    </div>
</div>

<!-- DIRECCIÓN -->
<div class="row g-4 section">
    <div class="col-md-4">
        <label class="form-label">Lugar expedición</label>
        <input name="lugar_expedicion" class="form-control"
               value="<?= $empresa['lugar_expedicion'] ?? '' ?>">
    </div>

    <div class="col-md-4">
        <label class="form-label">País</label>
        <input name="pais" class="form-control"
               value="<?= $empresa['pais'] ?? 'México' ?>">
    </div>

    <div class="col-md-4">
        <label class="form-label">Estado</label>
        <input name="estado" class="form-control"
               value="<?= $empresa['estado'] ?? '' ?>">
    </div>

    <div class="col-md-4">
        <label class="form-label">Municipio</label>
        <input name="municipio" class="form-control"
               value="<?= $empresa['municipio'] ?? '' ?>">
    </div>

    <div class="col-md-4">
        <label class="form-label">Colonia</label>
        <input name="colonia" class="form-control"
               value="<?= $empresa['colonia'] ?? '' ?>">
    </div>

    <div class="col-md-2">
        <label class="form-label">No. Ext</label>
        <input name="numero_exterior" class="form-control"
               value="<?= $empresa['numero_exterior'] ?? '' ?>">
    </div>

    <div class="col-md-2">
        <label class="form-label">No. Int</label>
        <input name="numero_interior" class="form-control"
               value="<?= $empresa['numero_interior'] ?? '' ?>">
    </div>
</div>

<div class="section">
    <label class="form-label">Calle</label>
    <textarea name="direccion" class="form-control"><?= $empresa['direccion'] ?? '' ?></textarea>
</div>

<!-- CONTACTO -->
<div class="row g-4 section">
    <div class="col-md-6">
        <label class="form-label">Teléfono</label>
        <input name="telefono" class="form-control"
               value="<?= $empresa['telefono'] ?? '' ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input name="email" class="form-control"
               value="<?= $empresa['email'] ?? '' ?>">
    </div>
</div>

<div class="text-end">
    <button class="btn btn-apple">Guardar cambios</button>
</div>

</form>

</div>
</div>

<script>
document.getElementById('formEmpresa').addEventListener('submit', function(e){
    e.preventDefault();

    const formData = new FormData(this);

    Swal.fire({
        title: 'Guardando...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch('/punto/acciones/guardar_empresa_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if(res.ok){
            Swal.fire('Éxito', res.mensaje, 'success');
        } else {
            Swal.fire('Error', res.mensaje, 'error');
        }
    })
    .catch(() => {
        Swal.fire('Error', 'Error de conexión', 'error');
    });
});
</script>

</body>
</html>
