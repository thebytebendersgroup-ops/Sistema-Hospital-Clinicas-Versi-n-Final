<?php
// ============================================================
// Baja lógica / reactivación de un usuario (solo administrador).
// Protecciones: no puede darse de baja a sí mismo ni al último administrador activo.
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Usuario.php';

exigirRol('admin');
$volver = '../presentacion/usuarios.php';
exigirPost($volver);

$id = $_POST['id'] ?? '';
$activar = ($_POST['activo'] ?? '') === '1';
try {
    $pdo = (new Conexion())->getConexion();
    $usuario = new Usuario($pdo);
    $existente = ctype_digit((string) $id) ? $usuario->buscarPorId($id) : false;
    if (!$existente) {
        guardarMensaje('El usuario no existe.', 'danger');
        redirigir($volver);
    }
    if (!$activar) {
        if ((int) $id === (int) $_SESSION['usuario']['id']) {
            guardarMensaje('No puede darse de baja a sí mismo.', 'danger');
            redirigir($volver);
        }
        if ($existente['rol'] === 'admin' && $usuario->contarAdministradoresActivos() <= 1) {
            guardarMensaje('Debe quedar al menos un administrador activo.', 'danger');
            redirigir($volver);
        }
    }
    $usuario->cambiarEstado($id, $activar);
    registrarAccion($pdo, $activar ? 'usuario_reactivado' : 'usuario_baja', $existente['email'], 'usuario', $id);
    guardarMensaje($activar ? 'Usuario reactivado.' : 'Usuario dado de baja: ya no puede iniciar sesión.');
} catch (PDOException $e) {
    error_log('Error al cambiar el estado del usuario: ' . $e->getMessage());
    guardarMensaje('No se pudo cambiar el estado.', 'danger');
}
redirigir($volver);
