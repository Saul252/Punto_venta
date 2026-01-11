<?php
require "../conexion.php";

$q = $_GET['q'] ?? '';

$sql = "SELECT 
            id,
            codigo,
            nombre,
            precio_venta,
            stock,
            unidad_medida
        FROM productos
        WHERE estado = 1
          AND (nombre LIKE ? OR codigo LIKE ?)
        ORDER BY nombre";

$stmt = $conexion->prepare($sql);
$like = "%$q%";
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$result = $stmt->get_result();

while ($p = $result->fetch_assoc()):
    $esPieza = ($p['unidad_medida'] === 'PIEZA');
?>
<tr
    data-id="<?= $p['id'] ?>"
    data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
    data-precio="<?= $p['precio_venta'] ?>"
    data-unidad="<?= $p['unidad_medida'] ?>"
>

    <td><?= htmlspecialchars($p['codigo']) ?></td>
    <td><?= htmlspecialchars($p['nombre']) ?></td>
    <td>$<?= number_format($p['precio_venta'], 2) ?></td>
    <td><?= number_format($p['stock'], 3) ?></td>

    <td style="width:110px">
        <?php if ($esPieza): ?>
            <!-- PIEZA -->
            <input type="number"
                   class="form-control form-control-sm cantidad"
                   value="1"
                   min="1"
                   step="1">
        <?php else: ?>
            <!-- GRAMO / KILO / LITRO -->
            <input type="number"
                   class="form-control form-control-sm cantidad"
                   value="1"
                   min="0.0"
                   step="0.1"
                   placeholder="Ej: 0.250">
        <?php endif; ?>
    </td>

    <td>
        <button class="btn btn-success btn-sm  agregar" style="    background-color: #e55300;
    border-color: #e55300 !important;
    color: #fff;
">Agregar</button>
    </td>
</tr>

<?php endwhile; ?>
