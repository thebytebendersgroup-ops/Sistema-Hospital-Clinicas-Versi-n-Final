<?php
// ============================================================
// Baja lógica de un documento (solo administrador). El archivo y el registro se conservan.
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Documento.php';

exigirRol('admin');
$volver = '../presentacion/documentos.php';
exigirPost($volver);

$id = $_POST['id'] ?? '';
try {
    $pdo = (new Conexion())->getConexion();
    $documento = new Documento($pdo);
    $existente = ctype_digit((string) $id) ? $documento->buscarPorId($id) : false;
    if (!$existente) {
        guardarMensaje('El documento no existe.', 'danger');
        redirigir($volver);
    }
    $documento->darDeBaja($id);
    registrarAccion($pdo, 'documento_eliminado', 'Baja de "' . mb_substr($existente['titulo'], 0, 80) . '"', 'documento', $id);
    guardarMensaje('Documento eliminado.');
} catch (PDOException $e) {
    error_log('Error al eliminar el documento: ' . $e->getMessage());
    guardarMensaje('No se pudo eliminar el documento.', 'danger');
}
redirigir($volver);
