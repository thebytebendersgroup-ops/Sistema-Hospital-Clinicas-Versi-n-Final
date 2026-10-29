<?php
// ============================================================
// Baja lógica de una encuesta (solo administrador). Las respuestas se conservan en la base.
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Encuesta.php';
require_once __DIR__ . '/../datos/Pregunta.php';

exigirRol('admin');
$volver = '../presentacion/encuestas.php';
exigirPost($volver);

$id = $_POST['id'] ?? '';
try {
    $pdo = (new Conexion())->getConexion();
    $encuesta = new Encuesta($pdo);
    $e = ctype_digit((string) $id) ? $encuesta->buscarPorId($id) : false;
    if (!$e) {
        guardarMensaje('La encuesta no existe.', 'danger');
        redirigir($volver);
    }
    $encuesta->darDeBaja($id);
    registrarAccion($pdo, 'encuesta_eliminada', '"' . mb_substr($e['titulo'], 0, 80) . '" v' . $e['version'], 'encuesta', $id);
    guardarMensaje('Encuesta eliminada. Sus respuestas se conservan.');
} catch (PDOException $ex) {
    error_log('Error al eliminar la encuesta: ' . $ex->getMessage());
    guardarMensaje('No se pudo eliminar la encuesta.', 'danger');
}
redirigir($volver);
