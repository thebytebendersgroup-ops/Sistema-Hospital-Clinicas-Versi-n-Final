<?php
// ============================================================
// Recibe las respuestas del paciente (formulario público presentacion/encuesta.php).
// El envío es ANÓNIMO: solo se guarda la fecha. Protecciones:
//   - campo trampa + tiempo mínimo (anti-robots)
//   - token de un solo uso (si se recarga o se reenvía, no se guarda dos veces)
//   - la encuesta tiene que estar publicada y su servicio activo
//   - se validan las respuestas contra las preguntas y opciones de ESA encuesta
//   - todo se guarda en una transacción
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/validaciones.php';
require_once __DIR__ . '/antibots.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Encuesta.php';
require_once __DIR__ . '/../datos/Pregunta.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('../presentacion/portal-paciente.php');
}

$idEncuesta = $_POST['id_encuesta'] ?? '';
$formulario = '../presentacion/encuesta.php?id=' . (int) $idEncuesta;
$nombreForm = 'encuesta_' . (int) $idEncuesta;

// 1) Robots: se descarta en silencio (no se le da pistas)
if (pareceAutomatico($nombreForm, $_POST)) {
    guardarMensaje('No pudimos enviar su respuesta. Espere unos segundos y vuelva a intentarlo.', 'danger');
    redirigir($formulario);
}

// 2) Token de un solo uso: evita duplicados al recargar o reenviar
if (!usarTokenUnico($nombreForm, $_POST['token_unico'] ?? '')) {
    guardarMensaje('Esta encuesta ya fue enviada o el formulario venció. Gracias por participar.', 'warning');
    redirigir('../presentacion/portal-paciente.php');
}

try {
    $pdo = (new Conexion())->getConexion();
    $encuesta = ctype_digit((string) $idEncuesta) ? (new Encuesta($pdo))->cargarCompleta($idEncuesta) : false;

    // 3) La encuesta debe existir, estar publicada y (si tiene servicio) este activo
    if (!$encuesta || $encuesta['estado'] !== 'publicada' || ($encuesta['id_categoria'] !== null && !$encuesta['servicio_activo'])) {
        guardarMensaje('La encuesta ya no está disponible.', 'warning');
        redirigir('../presentacion/portal-paciente.php');
    }

    // 4) Validar respuestas
    $resultado = validarRespuestasEnvio($encuesta, $_POST);
    if ($resultado['errores']) {
        $_SESSION['errores_form'] = $resultado['errores'];
        $_SESSION['datos_form']   = array_filter($_POST, function ($k) { return strpos($k, 'p_') === 0; }, ARRAY_FILTER_USE_KEY);
        redirigir($formulario);
    }

    // 5) Guardar (transacción) y volver con mensaje (PRG: recargar no reenvía)
    (new Encuesta($pdo))->guardarEnvio($encuesta['id'], $resultado['respuestas']);
    guardarMensaje('¡Gracias! Su opinión fue enviada.');
    redirigir('../presentacion/portal-paciente.php');

} catch (PDOException $e) {
    error_log('Error al guardar el envío de la encuesta: ' . $e->getMessage());
    guardarMensaje('Ocurrió un error al guardar su respuesta. Intente nuevamente.', 'danger');
    redirigir($formulario);
}
