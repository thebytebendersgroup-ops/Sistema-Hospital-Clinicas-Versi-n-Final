<?php
// ============================================================
// Alta y edición de los datos generales de una encuesta (título, descripción, servicio).
// Siempre nace como BORRADOR. Solo se puede editar mientras es borrador.
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/validaciones.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Encuesta.php';
require_once __DIR__ . '/../datos/Pregunta.php';
require_once __DIR__ . '/../datos/Categoria.php';
require_once __DIR__ . '/../datos/CategoriaEncuesta.php';

exigirLogin();
exigirPost('../presentacion/encuestas.php');

$id = trim($_POST['id'] ?? '');
$datos = [
    'titulo'       => trim($_POST['titulo'] ?? ''),
    'descripcion'  => trim($_POST['descripcion'] ?? ''),
    'id_categoria' => trim($_POST['id_categoria'] ?? ''),   // vacío = encuesta general
    'id_categoria_encuesta' => trim($_POST['id_categoria_encuesta'] ?? ''),   // vacío = sin categoría
];
$volverError = $id !== '' ? '../presentacion/encuesta_editar.php?id=' . urlencode($id) : '../presentacion/encuestas.php';

try {
    $pdo = (new Conexion())->getConexion();
    $encuesta = new Encuesta($pdo);
    $existente = false;
    if ($id !== '') {
        $existente = ctype_digit($id) ? $encuesta->buscarPorId($id) : false;
        if (!$existente) {
            guardarMensaje('La encuesta no existe.', 'danger');
            redirigir('../presentacion/encuestas.php');
        }
        if ($existente['estado'] !== 'borrador') {
            guardarMensaje('Solo se puede editar una encuesta en borrador. Cree una nueva versión para cambiarla.', 'danger');
            redirigir('../presentacion/encuesta_editar.php?id=' . (int) $id);
        }
    }

    $errores = validarEncuesta($datos, new Categoria($pdo), new CategoriaEncuesta($pdo), $existente ? (string) $existente['id_categoria_encuesta'] : '');
    if ($errores) {
        $_SESSION['errores_form'] = $errores;
        $_SESSION['datos_form']   = $datos + ['id' => $id];
        redirigir($volverError);
    }

    $encuesta->id           = $existente ? (int) $id : null;
    $encuesta->titulo       = $datos['titulo'];
    $encuesta->descripcion  = $datos['descripcion'] !== '' ? $datos['descripcion'] : null;
    $encuesta->id_categoria = $datos['id_categoria'] !== '' ? (int) $datos['id_categoria'] : null;
    $encuesta->id_categoria_encuesta = $datos['id_categoria_encuesta'] !== '' ? (int) $datos['id_categoria_encuesta'] : null;
    $encuesta->id_usuario   = $_SESSION['usuario']['id'];
    $encuesta->guardar();

    registrarAccion($pdo, $existente ? 'encuesta_editada' : 'encuesta_creada',
        'Encuesta "' . mb_substr($datos['titulo'], 0, 80) . '"', 'encuesta', $encuesta->id);
    guardarMensaje($existente ? 'Encuesta actualizada.' : 'Encuesta creada como borrador. Ahora agregue las preguntas.');
    redirigir('../presentacion/encuesta_editar.php?id=' . (int) $encuesta->id);

} catch (PDOException $e) {
    error_log('Error al guardar la encuesta: ' . $e->getMessage());
    guardarMensaje('No se pudo guardar la encuesta.', 'danger');
    redirigir($volverError);
}
