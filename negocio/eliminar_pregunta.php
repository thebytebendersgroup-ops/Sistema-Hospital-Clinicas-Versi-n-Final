<?php
// ============================================================
// Elimina una pregunta (y sus opciones). Solo en encuestas en borrador.
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Encuesta.php';
require_once __DIR__ . '/../datos/Pregunta.php';

exigirLogin();
exigirPost('../presentacion/encuestas.php');

$id = $_POST['id'] ?? '';
try {
    $pdo = (new Conexion())->getConexion();
    $pregunta = new Pregunta($pdo);
    $p = ctype_digit((string) $id) ? $pregunta->buscarPorId($id) : false;
    if (!$p) {
        guardarMensaje('La pregunta no existe.', 'danger');
        redirigir('../presentacion/encuestas.php');
    }
    $volver = '../presentacion/encuesta_editar.php?id=' . (int) $p['id_encuesta'] . '#preguntas';
    $encuesta = (new Encuesta($pdo))->buscarPorId($p['id_encuesta']);
    if (!$encuesta || $encuesta['estado'] !== 'borrador') {
        guardarMensaje('Solo se pueden eliminar preguntas de una encuesta en borrador.', 'danger');
        redirigir($volver);
    }
    $pregunta->eliminar($id);
    registrarAccion($pdo, 'pregunta_eliminada', mb_substr($p['texto'], 0, 100), 'encuesta', $p['id_encuesta']);
    guardarMensaje('Pregunta eliminada.');
} catch (PDOException $e) {
    error_log('Error al eliminar la pregunta: ' . $e->getMessage());
    guardarMensaje('No se pudo eliminar la pregunta.', 'danger');
    redirigir('../presentacion/encuestas.php');
}
redirigir($volver);
