<?php
// ============================================================
// Alta y edición de servicios (categorías). Solo administrador. No permite nombres repetidos.
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/validaciones.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Categoria.php';

exigirRol('admin');
$volver = '../presentacion/servicios.php';
exigirPost($volver);

$id     = trim($_POST['id'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');

try {
    $pdo = (new Conexion())->getConexion();
    $categoria = new Categoria($pdo);
    $existente = false;
    if ($id !== '') {
        $existente = ctype_digit($id) ? $categoria->buscarPorId($id) : false;
        if (!$existente) {
            guardarMensaje('El servicio no existe.', 'danger');
            redirigir($volver);
        }
    }
    $errores = validarServicio($nombre, $existente ? (int) $id : 0, $categoria);
    if ($errores) {
        $_SESSION['errores_form'] = $errores;
        $_SESSION['datos_form']   = ['id' => $id, 'nombre' => $nombre];
        redirigir($volver);
    }
    $categoria->id = $existente ? (int) $id : null;
    $categoria->nombre = $nombre;
    $categoria->guardar();
    registrarAccion($pdo, $existente ? 'servicio_editado' : 'servicio_creado',
        $existente ? '"' . $existente['nombre'] . '" → "' . $nombre . '"' : $nombre, 'servicio', $categoria->id);
    guardarMensaje($existente ? 'Servicio actualizado.' : 'Servicio creado.');
} catch (PDOException $e) {
    error_log('Error al guardar el servicio: ' . $e->getMessage());
    guardarMensaje('No se pudo guardar el servicio.', 'danger');
}
redirigir($volver);
