<?php
include 'conexion.php';

mysqli_set_charset($conexion, 'utf8mb4');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$flash = consume_flash_message();
$datos = null;

if ($id > 0) {
    $stmt = mysqli_prepare($conexion, 'SELECT * FROM equipos WHERE id = ? LIMIT 1');

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        $datos = $resultado ? mysqli_fetch_assoc($resultado) : null;
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar equipo</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body class="app-body">
    <div class="bg-shape bg-shape-one"></div>
    <div class="bg-shape bg-shape-two"></div>

    <main class="page-shell page-shell-narrow">
        <section class="hero-panel hero-panel-compact">
            <div class="hero-copy">
                <span class="eyebrow">Edicion</span>
                <h1>Actualizar equipo</h1>
                <p>Modifica la informacion del registro y regresa al panel principal cuando termines.</p>
            </div>

            <a href="index.php" class="btn btn-secondary btn-inline">Volver al inventario</a>
        </section>

        <article class="panel-card">
            <?php if ($flash) { ?>
                <div class="flash-banner flash-<?php echo e($flash['type']); ?>">
                    <?php echo e($flash['message']); ?>
                </div>
            <?php } ?>

            <?php if (!$datos) { ?>
                <div class="empty-state">
                    <h2>Equipo no encontrado</h2>
                    <p>El registro solicitado no existe o fue eliminado.</p>
                    <a href="index.php" class="btn btn-primary btn-inline">Regresar</a>
                </div>
            <?php } else { ?>
                <div class="section-heading">
                    <div>
                        <span class="section-tag">Formulario</span>
                        <h2>Datos del equipo</h2>
                    </div>
                    <p>Actualiza los campos necesarios y guarda los cambios.</p>
                </div>

                <form action="actualizar.php" method="POST" class="inventory-form" id="formularioEdicion">
                    <input type="hidden" name="id" value="<?php echo (int) $datos['id']; ?>">
                    <?php echo csrf_input(); ?>

                    <div class="form-grid">
                        <label class="field">
                            <span>Numero de caja</span>
                            <input type="text" name="numero_caja" value="<?php echo e($datos['numero_caja']); ?>" required>
                        </label>

                        <label class="field">
                            <span>Marca del PC</span>
                            <input type="text" name="marca_pc" value="<?php echo e($datos['marca_pc']); ?>" required>
                        </label>

                        <label class="field">
                            <span>Nombre del PC o portatil</span>
                            <input type="text" name="nombre_pc" value="<?php echo e(isset($datos['nombre_pc']) ? $datos['nombre_pc'] : ''); ?>" placeholder="Ej: Laptop-oficina-01" required>
                        </label>

                        <label class="field">
                            <span>Modelo del PC</span>
                            <input type="text" name="modelo_pc" value="<?php echo e($datos['modelo_pc']); ?>" required>
                        </label>

                        <label class="field">
                            <span>Serial del PC</span>
                            <input type="text" name="serial_pc" value="<?php echo e($datos['serial_pc']); ?>" required>
                        </label>

                        <label class="field">
                            <span>Modelo del cargador</span>
                            <input type="text" name="modelo_cargador" value="<?php echo e($datos['modelo_cargador']); ?>" required>
                        </label>

                        <label class="field">
                            <span>Serial del cargador</span>
                            <input type="text" name="serial_cargador" value="<?php echo e($datos['serial_cargador']); ?>" required>
                        </label>

                        <label class="field">
                            <span>Estado</span>
                            <select name="estado" required>
                                <option value="Disponible" <?php echo $datos['estado'] === 'Disponible' ? 'selected' : ''; ?>>Disponible</option>
                                <option value="Asignado" <?php echo $datos['estado'] === 'Asignado' ? 'selected' : ''; ?>>Asignado</option>
                            </select>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Actualizar equipo</button>
                        <a href="index.php" class="btn btn-secondary btn-inline">Cancelar</a>
                    </div>
                </form>
            <?php } ?>
        </article>
    </main>

</body>
</html>
