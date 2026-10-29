<?php
// ============================================================
// Baja lógica / reactivación de una categoría de encuestas (todos los funcionarios).
// Una categoría de baja ya no se ofrece al crear encuestas; las encuestas que la tenían la conservan.
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/CategoriaEncuesta.php';

exigirLogin();
$volver = '../presentacion/categorias_encuesta.php';
exigirPost($volver);

$id = $_POST['id'] ?? '';
$activar = ($_POST['activo'] ?? '') === '1';
try {
    $pdo = (new Conexion())->getConexion();
    $categoria = new CategoriaEncuesta($pdo);
    $existente = ctype_digit((string) $id) ? $categoria->buscarPorId($id) : false;
    if (!$existente) {
        guardarMensaje('La categoría no existe.', 'danger');
        redirigir($volver);
    }
    $categoria->cambiarEstado($id, $activar);
    registrarAccion($pdo, $activar ? 'categoria_encuesta_reactivada' : 'categoria_encuesta_baja', $existente['nombre'], 'categoria_encuesta', $id);
    guardarMensaje($activar ? 'Categoría reactivada.' : 'Categoría dada de baja: ya no se ofrece al crear encuestas.');
} catch (PDOException $e) {
    error_log('Error al cambiar el estado de la categoría: ' . $e->getMessage());
    guardarMensaje('No se pudo cambiar el estado.', 'danger');
}
redirigir($volver);
