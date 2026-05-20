<?php
include 'conexion.php';

require_post_method();

if (!verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    set_flash_message('error', 'La sesion vencio o la solicitud no es valida.');
    app_redirect('index.php');
}

$data = get_equipo_form_data($_POST);
$errors = validate_equipo_form_data($data);

if ($errors) {
    set_flash_message('error', $errors[0]);
    app_redirect('index.php');
}

$stmt = mysqli_prepare(
    $conexion,
    'INSERT INTO equipos (
        numero_caja,
        marca_pc,
        nombre_pc,
        modelo_pc,
        serial_pc,
        modelo_cargador,
        serial_cargador,
        estado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
;

if (!$stmt) {
    set_flash_message('error', 'No fue posible preparar el guardado del equipo.');
    app_redirect('index.php');
}

$ok = mysqli_stmt_bind_param(
    $stmt,
    'ssssssss',
    $data['numero_caja'],
    $data['marca_pc'],
    $data['nombre_pc'],
    $data['modelo_pc'],
    $data['serial_pc'],
    $data['modelo_cargador'],
    $data['serial_cargador'],
    $data['estado']
);

if (!$ok || !mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    set_flash_message('error', 'No fue posible guardar el equipo.');
    app_redirect('index.php');
}

mysqli_stmt_close($stmt);
set_flash_message('success', 'Equipo guardado correctamente.');
app_redirect('index.php');
?>
