<?php
// ============================================================
// Alta y edición de usuarios (solo administrador). La contraseña se guarda con password_hash().
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/validaciones.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Usuario.php';

exigirRol('admin');
$volver = '../presentacion/usuarios.php';
exigirPost($volver);

$id = trim($_POST['id'] ?? '');
$datos = [
    'nombre'      => trim($_POST['nombre'] ?? ''),
    'email'       => trim($_POST['email'] ?? ''),
    'rol'         => $_POST['rol'] ?? '',
    'contrasenia' => is_string($_POST['contrasenia'] ?? '') ? ($_POST['contrasenia'] ?? '') : '',
];

try {
    $pdo = (new Conexion())->getConexion();
    $usuario = new Usuario($pdo);

    $existente = false;
    if ($id !== '') {
        $existente = ctype_digit($id) ? $usuario->buscarPorId($id) : false;
        if (!$existente) {
            guardarMensaje('El usuario no existe.', 'danger');
            redirigir($volver);
        }
    }

    $errores = validarUsuario($datos, $existente ? (int) $id : 0, !$existente, $usuario);

    // No se puede quitar el rol de administrador al último administrador activo
    if (!$errores && $existente && $existente['rol'] === 'admin' && $datos['rol'] !== 'admin'
        && $existente['activo'] && $usuario->contarAdministradoresActivos() <= 1) {
        $errores[] = 'Debe quedar al menos un administrador activo.';
    }

    if ($errores) {
        $datos['contrasenia'] = '';   // nunca se devuelve la contraseña al formulario
        $_SESSION['errores_form'] = $errores;
        $_SESSION['datos_form']   = $datos + ['id' => $id];
        redirigir($volver);
    }

    $usuario->id     = $existente ? (int) $id : null;
    $usuario->nombre = $datos['nombre'];
    $usuario->email  = $datos['email'];
    $usuario->rol    = $datos['rol'];
    $hash = $datos['contrasenia'] !== '' ? password_hash($datos['contrasenia'], PASSWORD_DEFAULT) : '';
    $usuario->guardar($hash);

    registrarAccion($pdo, $existente ? 'usuario_editado' : 'usuario_creado',
        ($existente ? describirCambios($existente, $datos, ['nombre' => 'nombre', 'email' => 'email', 'rol' => 'rol'])
                    : 'Usuario ' . $datos['email'] . ' (' . $datos['rol'] . ')')
        . ($existente && $hash !== '' ? '; contraseña cambiada' : ''),
        'usuario', $usuario->id);
    guardarMensaje($existente ? 'Usuario actualizado.' : 'Usuario creado.');
} catch (PDOException $e) {
    error_log('Error al guardar el usuario: ' . $e->getMessage());
    guardarMensaje('No se pudo guardar el usuario.', 'danger');
}
redirigir($volver);
