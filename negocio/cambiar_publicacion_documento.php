<?php
// ============================================================
// Publica u oculta un documento (el paciente solo ve los publicados).
// ============================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/ayudas.php';
require_once __DIR__ . '/auditoria.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Documento.php';

exigirLogin();
$volver = '../presentacion/documentos.php';
exigirPost($volver);

$id = $_POST['id'] ?? '';
$publicar = ($_POST['publicado'] ?? '') === '1';
try {
    $pdo = (new Conexion())->getConexion();
    $documento = new Documento($pdo);
    $existente = ctype_digit((string) $id) ? $documento->buscarPorId($id) : false;
    if (!$existente) {
        guardarMensaje('El documento no existe.', 'danger');
        redirigir($volver);
    }
    $documento->cambiarPublicado($id, $publicar);
    registrarAccion($pdo, $publicar ? 'documento_publicado' : 'documento_oculto', '"' . mb_substr($existente['titulo'], 0, 80) . '"', 'documento', $id);
    guardarMensaje($publicar ? 'Documento publicado: ya lo ven los pacientes.' : 'Documento oculto: los pacientes ya no lo ven.');
} catch (PDOException $e) {
    error_log('Error al cambiar la publicación: ' . $e->getMessage());
    guardarMensaje('No se pudo cambiar el estado.', 'danger');
}
redirigir($volver);
