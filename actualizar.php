<?php
include 'conexion.php';

require_post_method();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if (!verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    set_flash_message('error', 'La sesion vencio o la solicitud no es valida.');
    app_redirect($id > 0 ? 'editar.php?id=' . $id : 'index.php');
}

$data = get_equipo_form_data($_POST);
$errors = validate_equipo_form_data($data);

if ($id <= 0) {
    $errors[] = 'El equipo que intentas actualizar no es valido.';
}

if ($errors) {
    set_flash_message('error', $errors[0]);
    app_redirect($id > 0 ? 'editar.php?id=' . $id : 'index.php');
}

$stmt = mysqli_prepare(
    $conexion,
    'UPDATE equipos SET
        numero_caja = ?,
        marca_pc = ?,
        nombre_pc = ?,
        modelo_pc = ?,
        serial_pc = ?,
        modelo_cargador = ?,
        serial_cargador = ?,
        estado = ?
    WHERE id = ?'
);

if (!$stmt) {
    set_flash_message('error', 'No fue posible preparar la actualizacion del equipo.');
    app_redirect('editar.php?id=' . $id);
}

$ok = mysqli_stmt_bind_param(
    $stmt,
    'ssssssssi',
    $data['numero_caja'],
    $data['marca_pc'],
    $data['nombre_pc'],
    $data['modelo_pc'],
    $data['serial_pc'],
    $data['modelo_cargador'],
    $data['serial_cargador'],
    $data['estado'],
    $id
);

if (!$ok || !mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    set_flash_message('error', 'No fue posible actualizar el equipo.');
    app_redirect('editar.php?id=' . $id);
}

mysqli_stmt_close($stmt);
set_flash_message('success', 'Equipo actualizado correctamente.');
app_redirect('index.php');
?>
