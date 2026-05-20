<?php
// Comprueba que secure/config.php existe y prueba la conexion a la base de datos
$configPath = __DIR__ . '/secure/config.php';
if (!file_exists($configPath)) {
    echo "secure/config.php no existe. Asegurate de que el workflow lo generó y se subió.\n";
    exit;
}

$config = require $configPath;
$mysqli = @mysqli_connect($config['DB_HOST'], $config['DB_USER'], $config['DB_PASS'], $config['DB_NAME']);
if (!$mysqli) {
    echo "Error de conexion a la base de datos: " . mysqli_connect_error();
} else {
    echo "Conexion OK a la base de datos: " . htmlspecialchars($config['DB_NAME']);
    mysqli_close($mysqli);
}
