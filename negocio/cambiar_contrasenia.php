<?php
// ============================================================
// Cambio de la contraseña propia (cualquier funcionario).
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/validaciones.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Usuario.php';

exigirLogin();
$volver = '../presentacion/perfil.php';
exigirPost($volver);

$actual    = is_string($_POST['actual'] ?? '') ? $_POST['actual'] : '';
$nueva     = is_string($_POST['nueva'] ?? '') ? $_POST['nueva'] : '';
$repetida  = is_string($_POST['repetida'] ?? '') ? $_POST['repetida'] : '';
$idUsuario = $_SESSION['usuario']['id'];

try {
    $pdo = (new Conexion())->getConexion();
    $usuario = new Usuario($pdo);
    $errores = validarCambioContrasenia($actual, $nueva, $repetida, $usuario, $idUsuario);
    if ($errores) {
        $_SESSION['errores_form'] = $errores;
        redirigir($volver);
    }
    $usuario->cambiarContrasenia($idUsuario, password_hash($nueva, PASSWORD_DEFAULT));
    session_regenerate_id(true);
    registrarAccion($pdo, 'contrasenia_cambiada', 'El usuario cambió su contraseña', 'usuario', $idUsuario);
    guardarMensaje('Contraseña cambiada correctamente.');
} catch (PDOException $e) {
    error_log('Error al cambiar la contraseña: ' . $e->getMessage());
    guardarMensaje('No se pudo cambiar la contraseña.', 'danger');
}
redirigir($volver);
