<?php
// ============================================================
// Duplica una encuesta o crea una NUEVA VERSIÓN de ella.
//   modo=duplicar: copia independiente ("Copia de ...").
//   modo=version:  mismo título, versión + 1. Sirve para cambiar una encuesta que ya tiene
//                  respuestas sin perder el historial (la versión vieja queda como estaba).
// Siempre se crea como borrador.
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Encuesta.php';
require_once __DIR__ . '/../datos/Pregunta.php';

exigirLogin();
$volver = '../presentacion/encuestas.php';
exigirPost($volver);

$id = $_POST['id'] ?? '';
$comoVersion = ($_POST['modo'] ?? '') === 'version';
try {
    $pdo = (new Conexion())->getConexion();
    $encuesta = new Encuesta($pdo);
    $e = ctype_digit((string) $id) ? $encuesta->buscarPorId($id) : false;
    if (!$e) {
        guardarMensaje('La encuesta no existe.', 'danger');
        redirigir($volver);
    }
    if ($comoVersion && $e['estado'] === 'borrador') {
        guardarMensaje('Una encuesta en borrador se puede editar directamente.', 'warning');
        redirigir('../presentacion/encuesta_editar.php?id=' . (int) $id);
    }
    $idNueva = $encuesta->duplicar($id, $_SESSION['usuario']['id'], $comoVersion);
    registrarAccion($pdo, $comoVersion ? 'encuesta_version' : 'encuesta_duplicada',
        '"' . mb_substr($e['titulo'], 0, 80) . '" (origen #' . (int) $id . ')', 'encuesta', $idNueva);
    guardarMensaje($comoVersion ? 'Nueva versión creada como borrador. Edítela y publíquela cuando esté lista.'
                                : 'Encuesta duplicada como borrador.');
    redirigir('../presentacion/encuesta_editar.php?id=' . (int) $idNueva);
} catch (PDOException $ex) {
    error_log('Error al duplicar la encuesta: ' . $ex->getMessage());
    guardarMensaje('No se pudo duplicar la encuesta.', 'danger');
    redirigir($volver);
}
