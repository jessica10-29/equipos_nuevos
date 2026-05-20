<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'seguridad.php';

$defaultConfig = array(
    'DB_HOST' => 'localhost',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'DB_NAME' => 'inventario_equipos',
);

$configPath = __DIR__ . DIRECTORY_SEPARATOR . 'secure' . DIRECTORY_SEPARATOR . 'config.php';
$productionConfig = file_exists($configPath) ? require $configPath : array();

if (!is_array($productionConfig)) {
    $productionConfig = array();
}

$config = array_merge($defaultConfig, array_filter($productionConfig, function ($value) {
    return $value !== null && $value !== '';
}));

$usesInsecureDefaults = $config['DB_USER'] === 'root' && $config['DB_PASS'] === '';
if (!app_is_local_environment() && $usesInsecureDefaults) {
    http_response_code(500);
    die('Configura credenciales de base de datos seguras antes de publicar este sitio.');
}

$conexion = mysqli_connect(
    $config['DB_HOST'],
    $config['DB_USER'],
    $config['DB_PASS'],
    $config['DB_NAME']
);

if (!$conexion) {
    http_response_code(500);
    die('Error de conexion a la base de datos.');
}

mysqli_set_charset($conexion, 'utf8mb4');
?>
