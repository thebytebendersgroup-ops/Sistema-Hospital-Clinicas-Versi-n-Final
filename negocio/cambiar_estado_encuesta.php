<?php
// ============================================================
// Publica o cierra una encuesta.
//   publicar: borrador → publicada (necesita al menos una pregunta y un servicio activo).
//             Si es una nueva versión, la versión anterior se cierra (su historial se conserva).
//   cerrar:   publicada → cerrada (deja de recibir respuestas; los resultados se conservan).
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Encuesta.php';
require_once __DIR__ . '/../datos/Pregunta.php';

exigirLogin();
exigirPost('../presentacion/encuestas.php');

$id     = $_POST['id'] ?? '';
$accion = $_POST['accion'] ?? '';
$volver = '../presentacion/' . (($_POST['volver'] ?? '') === 'editar' ? 'encuesta_editar.php?id=' . (int) $id : 'encuestas.php');

try {
    $pdo = (new Conexion())->getConexion();
    $encuesta = new Encuesta($pdo);
    $e = ctype_digit((string) $id) ? $encuesta->buscarPorId($id) : false;
    if (!$e) {
        guardarMensaje('La encuesta no existe.', 'danger');
        redirigir('../presentacion/encuestas.php');
    }

    if ($accion === 'publicar') {
        if ($e['estado'] !== 'borrador') {
            guardarMensaje('Solo se puede publicar una encuesta en borrador.', 'danger');
            redirigir($volver);
        }
        if ((int) $e['preguntas'] < 1) {
            guardarMensaje('Agregue al menos una pregunta antes de publicar.', 'danger');
            redirigir($volver);
        }
        if ($e['id_categoria'] !== null && !$e['servicio_activo']) {
            guardarMensaje('El servicio de esta encuesta está dado de baja.', 'danger');
            redirigir($volver);
        }
        $pdo->beginTransaction();
        try {
            // Una nueva versión reemplaza a la anterior: la anterior se cierra
            if ($e['id_origen']) {
                $pdo->prepare("UPDATE encuesta SET estado = 'cerrada' WHERE id = ? AND estado = 'publicada'")->execute([$e['id_origen']]);
            }
            $encuesta->cambiarEstado($id, 'publicada');
            $pdo->commit();
        } catch (PDOException $ex) {
            $pdo->rollBack();
            throw $ex;
        }
        registrarAccion($pdo, 'encuesta_publicada', '"' . mb_substr($e['titulo'], 0, 80) . '" v' . $e['version'], 'encuesta', $id);
        guardarMensaje('Encuesta publicada: ya pueden responderla los pacientes.');

    } elseif ($accion === 'cerrar') {
        if ($e['estado'] !== 'publicada') {
            guardarMensaje('Solo se puede cerrar una encuesta publicada.', 'danger');
            redirigir($volver);
        }
        $encuesta->cambiarEstado($id, 'cerrada');
        registrarAccion($pdo, 'encuesta_cerrada', '"' . mb_substr($e['titulo'], 0, 80) . '" v' . $e['version'], 'encuesta', $id);
        guardarMensaje('Encuesta cerrada: ya no recibe respuestas.');

    } else {
        guardarMensaje('Acción no válida.', 'danger');
    }
} catch (PDOException $ex) {
    error_log('Error al cambiar el estado de la encuesta: ' . $ex->getMessage());
    guardarMensaje('No se pudo cambiar el estado de la encuesta.', 'danger');
}
redirigir($volver);
