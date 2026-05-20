<?php

function app_is_https()
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443';
}

function app_is_local_environment()
{
    $host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
    $host = explode(':', $host)[0];

    return $host === '' || in_array($host, array('localhost', '127.0.0.1', '::1'), true);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('inventario_session');
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => '/',
        'secure' => app_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    session_start();
}

function csp_nonce()
{
    static $nonce = null;

    if ($nonce === null) {
        $nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }

    return $nonce;
}

if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header(
        "Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'; img-src 'self' data:; connect-src 'self'; script-src 'self' 'nonce-" . csp_nonce() . "'; style-src 'self' 'unsafe-inline';"
    );
}

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

function app_redirect($path)
{
    header('Location: ' . $path);
    exit;
}

function require_post_method()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Metodo no permitido.');
    }
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token)
{
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
}

function csrf_input()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function set_flash_message($type, $message)
{
    $_SESSION['flash_message'] = array(
        'type' => $type === 'success' ? 'success' : 'error',
        'message' => normalize_text($message, 240),
    );
}

function consume_flash_message()
{
    if (empty($_SESSION['flash_message']) || !is_array($_SESSION['flash_message'])) {
        return null;
    }

    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);

    return $flash;
}

function normalize_text($value, $maxLength = 100)
{
    $value = trim((string) $value);
    $value = str_replace("\0", '', $value);

    if ($maxLength > 0) {
        if (function_exists('mb_substr')) {
            $value = mb_substr($value, 0, $maxLength, 'UTF-8');
        } else {
            $value = substr($value, 0, $maxLength);
        }
    }

    return $value;
}

function get_equipo_form_data($source)
{
    return array(
        'numero_caja' => normalize_text(isset($source['numero_caja']) ? $source['numero_caja'] : '', 10),
        'marca_pc' => normalize_text(isset($source['marca_pc']) ? $source['marca_pc'] : '', 100),
        'nombre_pc' => normalize_text(isset($source['nombre_pc']) ? $source['nombre_pc'] : '', 100),
        'modelo_pc' => normalize_text(isset($source['modelo_pc']) ? $source['modelo_pc'] : '', 100),
        'serial_pc' => normalize_text(isset($source['serial_pc']) ? $source['serial_pc'] : '', 100),
        'modelo_cargador' => normalize_text(isset($source['modelo_cargador']) ? $source['modelo_cargador'] : '', 100),
        'serial_cargador' => normalize_text(isset($source['serial_cargador']) ? $source['serial_cargador'] : '', 100),
        'estado' => isset($source['estado']) && $source['estado'] === 'Asignado' ? 'Asignado' : 'Disponible',
    );
}

function validate_equipo_form_data($data)
{
    $labels = array(
        'numero_caja' => 'El numero de caja',
        'marca_pc' => 'La marca del PC',
        'nombre_pc' => 'El nombre del PC o portatil',
        'modelo_pc' => 'El modelo del PC',
        'serial_pc' => 'El serial del PC',
        'modelo_cargador' => 'El modelo del cargador',
        'serial_cargador' => 'El serial del cargador',
    );

    $errors = array();

    foreach ($labels as $field => $label) {
        if (!isset($data[$field]) || $data[$field] === '') {
            $errors[] = $label . ' es obligatorio.';
        }
    }

    return $errors;
}

