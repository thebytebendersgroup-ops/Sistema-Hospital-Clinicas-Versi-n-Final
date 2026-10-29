<?php
// ============================================================
// Sube o baja una pregunta en el orden de la encuesta (solo en borrador).
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Encuesta.php';
require_once __DIR__ . '/../datos/Pregunta.php';

exigirLogin();
exigirPost('../presentacion/encuestas.php');

$id = $_POST['id'] ?? '';
$direccion = ($_POST['direccion'] ?? '') === 'arriba' ? 'arriba' : 'abajo';
try {
    $pdo = (new Conexion())->getConexion();
    $pregunta = new Pregunta($pdo);
    $p = ctype_digit((string) $id) ? $pregunta->buscarPorId($id) : false;
    if (!$p) {
        redirigir('../presentacion/encuestas.php');
    }
    $volver = '../presentacion/encuesta_editar.php?id=' . (int) $p['id_encuesta'] . '#preguntas';
    $encuesta = (new Encuesta($pdo))->buscarPorId($p['id_encuesta']);
    if ($encuesta && $encuesta['estado'] === 'borrador') {
        $pregunta->mover($id, $direccion);
    } else {
        guardarMensaje('Solo se puede reordenar una encuesta en borrador.', 'danger');
    }
} catch (PDOException $e) {
    error_log('Error al mover la pregunta: ' . $e->getMessage());
    redirigir('../presentacion/encuestas.php');
}
redirigir($volver);
