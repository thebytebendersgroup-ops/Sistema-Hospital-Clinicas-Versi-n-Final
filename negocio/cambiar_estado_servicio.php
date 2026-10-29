<?php
// ============================================================
// Baja lógica / reactivación de un servicio (solo administrador).
// Al darlo de baja, sus documentos y encuestas dejan de verse en el portal del paciente,
// pero NO se borran: si se reactiva el servicio, todo vuelve a aparecer.
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Categoria.php';

exigirRol('admin');
$volver = '../presentacion/servicios.php';
exigirPost($volver);

$id = $_POST['id'] ?? '';
$activar = ($_POST['activo'] ?? '') === '1';
try {
    $pdo = (new Conexion())->getConexion();
    $categoria = new Categoria($pdo);
    $existente = ctype_digit((string) $id) ? $categoria->buscarPorId($id) : false;
    if (!$existente) {
        guardarMensaje('El servicio no existe.', 'danger');
        redirigir($volver);
    }
    $categoria->cambiarEstado($id, $activar);
    registrarAccion($pdo, $activar ? 'servicio_reactivado' : 'servicio_baja', $existente['nombre'], 'servicio', $id);
    guardarMensaje($activar ? 'Servicio reactivado.' : 'Servicio dado de baja: sus documentos y encuestas se ocultan del portal.');
} catch (PDOException $e) {
    error_log('Error al cambiar el estado del servicio: ' . $e->getMessage());
    guardarMensaje('No se pudo cambiar el estado.', 'danger');
}
redirigir($volver);
