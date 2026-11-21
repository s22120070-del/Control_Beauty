<?php
// Ocultar errores técnicos en pantalla para evitar divulgación de información
ini_set('display_errors', 0);
error_reporting(0);

// Configuración segura de la cookie de sesión antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// Generar Token Anti-CSRF único para la sesión
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Encabezados de seguridad HTTP para respuestas PHP
if (!headers_sent()) {
    header_remove('X-Powered-By');
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com data:; img-src 'self' data: https://via.placeholder.com; form-action 'self'; frame-ancestors 'self';");
}

// Datos de conexión a MySQL en WampServer
$servidor = "localhost";
$usuario_bd = "root";
$password_bd = "";
$base_datos = "control_inventario";

$conexion = new mysqli($servidor, $usuario_bd, $password_bd, $base_datos);

if ($conexion->connect_error) {
    die("Error de conexión con la base de datos.");
}

$conexion->set_charset("utf8mb4");
date_default_timezone_set('America/Mexico_City');
?>