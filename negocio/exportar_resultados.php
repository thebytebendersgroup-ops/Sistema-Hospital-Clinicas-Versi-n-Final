<?php
// ============================================================
// Exporta a CSV las respuestas de una encuesta (con los mismos filtros de fecha que la pantalla).
// Se abre con Excel. Los textos que empiezan con = + - @ se neutralizan (evita "inyección de fórmulas").
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Encuesta.php';
require_once __DIR__ . '/../datos/Pregunta.php';
require_once __DIR__ . '/../datos/Resultado.php';

exigirLogin();

$id    = $_GET['id'] ?? '';
$desde = fechaDeFiltro($_GET['desde'] ?? '');
$hasta = fechaDeFiltro($_GET['hasta'] ?? '');

function celdaCsv($valor) {
    $valor = (string) $valor;
    if ($valor !== '' && strpos("=+-@\t\r", $valor[0]) !== false) {
        $valor = "'" . $valor;
    }
    return $valor;
}

try {
    $pdo = (new Conexion())->getConexion();
    $e = ctype_digit((string) $id) ? (new Encuesta($pdo))->buscarPorId($id) : false;
    if (!$e) {
        guardarMensaje('La encuesta no existe.', 'danger');
        redirigir('../presentacion/resultados.php');
    }
    $filas = (new Resultado($pdo))->filasParaExportar($id, $desde, $hasta);
    registrarAccion($pdo, 'resultados_exportados', 'CSV de "' . mb_substr($e['titulo'], 0, 80) . '"', 'encuesta', $id);
} catch (PDOException $ex) {
    error_log('Error al exportar: ' . $ex->getMessage());
    guardarMensaje('No se pudo exportar.', 'danger');
    redirigir('../presentacion/resultados.php');
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="encuesta_' . (int) $id . '_resultados.csv"');
echo "\xEF\xBB\xBF";   // marca para que Excel lea bien los acentos
$salida = fopen('php://output', 'w');
fputcsv($salida, ['Envío', 'Fecha', 'Pregunta', 'Tipo', 'Respuesta'], ';');
foreach ($filas as $f) {
    if ($f['tipo'] === 'valoracion') {
        $respuesta = $f['valor_escala'];
    } elseif ($f['tipo'] === 'texto') {
        $respuesta = $f['valor_texto'];
    } else {
        $respuesta = $f['opcion'];
    }
    fputcsv($salida, [$f['envio'], substr($f['fecha'], 0, 10), celdaCsv($f['pregunta']), $f['tipo'], celdaCsv($respuesta)], ';');
}
fclose($salida);
