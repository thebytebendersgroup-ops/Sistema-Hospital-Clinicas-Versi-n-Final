<?php
// ============================================================
// Alta y edición de preguntas de una encuesta (con sus opciones).
// Solo se permite en encuestas en BORRADOR (así las respuestas ya recibidas no se rompen).
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/validaciones.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Encuesta.php';
require_once __DIR__ . '/../datos/Pregunta.php';

exigirLogin();
exigirPost('../presentacion/encuestas.php');

$idEncuesta = $_POST['id_encuesta'] ?? '';
$id         = trim($_POST['id'] ?? '');
$volver     = '../presentacion/encuesta_editar.php?id=' . (int) $idEncuesta;

$datos = [
    'texto'    => trim($_POST['texto'] ?? ''),
    'tipo'     => $_POST['tipo'] ?? '',
    'opciones' => is_string($_POST['opciones'] ?? '') ? ($_POST['opciones'] ?? '') : '',
];

try {
    $pdo = (new Conexion())->getConexion();
    $encuesta = ctype_digit((string) $idEncuesta) ? (new Encuesta($pdo))->buscarPorId($idEncuesta) : false;
    if (!$encuesta) {
        guardarMensaje('La encuesta no existe.', 'danger');
        redirigir('../presentacion/encuestas.php');
    }
    if ($encuesta['estado'] !== 'borrador') {
        guardarMensaje('Solo se pueden cambiar las preguntas de una encuesta en borrador.', 'danger');
        redirigir($volver);
    }

    $pregunta = new Pregunta($pdo);
    $existente = false;
    if ($id !== '') {
        $existente = ctype_digit($id) ? $pregunta->buscarPorId($id) : false;
        // La pregunta tiene que pertenecer a ESTA encuesta
        if (!$existente || (int) $existente['id_encuesta'] !== (int) $idEncuesta) {
            guardarMensaje('La pregunta no pertenece a esta encuesta.', 'danger');
            redirigir($volver);
        }
    }

    $resultado = validarPregunta($datos);
    if ($resultado['errores']) {
        $_SESSION['errores_form'] = $resultado['errores'];
        $_SESSION['datos_form']   = $datos + ['id' => $id, 'obligatoria' => isset($_POST['obligatoria']) ? 1 : 0];
        redirigir($volver . '#preguntas');
    }

    $pregunta->id          = $existente ? (int) $id : null;
    $pregunta->id_encuesta = (int) $idEncuesta;
    $pregunta->texto       = $datos['texto'];
    $pregunta->tipo        = $datos['tipo'];
    $pregunta->obligatoria = isset($_POST['obligatoria']) ? 1 : 0;
    $pregunta->opciones    = $resultado['opciones'];
    $pregunta->guardar();

    registrarAccion($pdo, $existente ? 'pregunta_editada' : 'pregunta_creada',
        mb_substr($datos['texto'], 0, 100), 'encuesta', $idEncuesta);
    guardarMensaje($existente ? 'Pregunta actualizada.' : 'Pregunta agregada.');
} catch (PDOException $e) {
    error_log('Error al guardar la pregunta: ' . $e->getMessage());
    guardarMensaje('No se pudo guardar la pregunta.', 'danger');
}
redirigir($volver . '#preguntas');
