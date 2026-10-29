<?php
// ============================================================
// Cierre de sesión. Se pide por POST con token CSRF (así otra página no puede cerrar la sesión).
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';

exigirPost('../presentacion/index.php');

try {
    registrarAccion((new Conexion())->getConexion(), 'logout', 'Cierre de sesión', 'usuario', $_SESSION['usuario']['id'] ?? null);
} catch (Throwable $e) {
    error_log('logout: ' . $e->getMessage());
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}
session_destroy();

redirigir('../presentacion/login.php');
