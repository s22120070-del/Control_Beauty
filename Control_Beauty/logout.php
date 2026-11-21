<?php
include('conexion.php');

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se usó una cookie de sesión, borrarla también
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión por completo
session_destroy();

// Redirigir al usuario de vuelta al login
header("Location: index.php");
exit();
?>