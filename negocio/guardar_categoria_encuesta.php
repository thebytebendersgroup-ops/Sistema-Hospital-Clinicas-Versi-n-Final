<?php
// ============================================================
// Alta y edición de categorías de encuestas. Pueden hacerlo TODOS los funcionarios.
// No permite nombres repetidos.
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/validaciones.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/CategoriaEncuesta.php';

exigirLogin();
$volver = '../presentacion/categorias_encuesta.php';
exigirPost($volver);

$id     = trim($_POST['id'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');

try {
    $pdo = (new Conexion())->getConexion();
    $categoria = new CategoriaEncuesta($pdo);
    $existente = false;
    if ($id !== '') {
        $existente = ctype_digit($id) ? $categoria->buscarPorId($id) : false;
        if (!$existente) {
            guardarMensaje('La categoría no existe.', 'danger');
            redirigir($volver);
        }
    }
    $errores = validarCategoriaEncuesta($nombre, $existente ? (int) $id : 0, $categoria);
    if ($errores) {
        $_SESSION['errores_form'] = $errores;
        $_SESSION['datos_form']   = ['id' => $id, 'nombre' => $nombre];
        redirigir($volver);
    }
    $categoria->id = $existente ? (int) $id : null;
    $categoria->nombre = $nombre;
    $categoria->guardar();
    registrarAccion($pdo, $existente ? 'categoria_encuesta_editada' : 'categoria_encuesta_creada',
        $existente ? '"' . $existente['nombre'] . '" → "' . $nombre . '"' : $nombre, 'categoria_encuesta', $categoria->id);
    guardarMensaje($existente ? 'Categoría actualizada.' : 'Categoría creada. Ya se puede elegir al crear una encuesta.');
} catch (PDOException $e) {
    error_log('Error al guardar la categoría de encuesta: ' . $e->getMessage());
    guardarMensaje('No se pudo guardar la categoría.', 'danger');
}
redirigir($volver);
