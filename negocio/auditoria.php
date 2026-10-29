<?php
// ============================================================
// Registro de acciones importantes: quién hizo qué y cuándo.
// Si guardar el registro falla, la acción principal NO se interrumpe.
// ============================================================

require_once __DIR__ . '/../datos/Registro.php';

// $entidad / $idEntidad: sobre qué se actuó (ej.: 'documento', 5).
// $idUsuario: si no se indica, se usa el funcionario que tiene la sesión iniciada.
function registrarAccion($pdo, $accion, $detalle, $entidad = null, $idEntidad = null, $idUsuario = null) {
    try {
        if ($idUsuario === null) {
            $idUsuario = $_SESSION['usuario']['id'] ?? null;
        }
        (new Registro($pdo))->registrar($accion, $detalle, $idUsuario, $entidad, $idEntidad);
    } catch (PDOException $e) {
        error_log('No se pudo guardar en el registro de acciones: ' . $e->getMessage());
    }
}
