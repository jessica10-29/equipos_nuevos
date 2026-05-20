<?php
include 'conexion.php';

require_post_method();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if (!verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    set_flash_message('error', 'La sesion vencio o la solicitud no es valida.');
    app_redirect('index.php');
}

if ($id > 0) {
    $stmt = mysqli_prepare($conexion, 'DELETE FROM equipos WHERE id = ?');

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        $deleted = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($deleted) {
            set_flash_message('success', 'Equipo eliminado correctamente.');
        } else {
            set_flash_message('error', 'No fue posible eliminar el equipo.');
        }
    } else {
        set_flash_message('error', 'No fue posible eliminar el equipo.');
    }
} else {
    set_flash_message('error', 'El equipo que intentas eliminar no es valido.');
}

app_redirect('index.php');
?>
