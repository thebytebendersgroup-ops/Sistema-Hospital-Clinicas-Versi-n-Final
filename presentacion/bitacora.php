<?php
// ============================================================
// Bitácora de acciones (solo administrador): quién hizo qué y cuándo.
// Con ?entidad=documento&id=5 se ve el historial de modificaciones de un documento (o encuesta, etc.).
// ============================================================
require_once __DIR__ . '/_vista.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Bitacora.php';
exigirRol('admin');

$accion  = trim($_GET['accion'] ?? '');
$entidad = in_array($_GET['entidad'] ?? '', ['documento', 'encuesta', 'usuario', 'servicio'], true) ? $_GET['entidad'] : '';
$idEnt   = enteroPositivo($_GET['id'] ?? '');

$filas = []; $acciones = [];
$p = paginar(0, 20, 1);
try {
    $modelo = new Bitacora((new Conexion())->getConexion());
    $acciones = $modelo->listarAcciones();
    $p = paginar($modelo->contar($accion, $entidad, $idEnt), 20, $_GET['pagina'] ?? 1);
    $filas = $modelo->listar($accion, $entidad, $idEnt, $p['por_pagina'], $p['desplazamiento']);
} catch (PDOException $e) {
    error_log('Error en la bitácora: ' . $e->getMessage());
    guardarMensaje('No se pudo leer la bitácora.', 'danger');
}
$tituloPagina = 'Bitácora';
$paginaActiva = 'bitacora';
require __DIR__ . '/_cabecera.php';
?>
<h5 class="fw-semibold mb-3">Bitácora de acciones</h5>
<?php if ($entidad && $idEnt): ?>
    <div class="alert alert-info py-2" style="font-size:14px;">Historial de <?= e($entidad) ?> #<?= (int) $idEnt ?>. <a href="bitacora.php">Ver toda la bitácora</a></div>
<?php endif; ?>
<form method="GET" class="row g-2 mb-3">
    <?php if ($entidad && $idEnt): ?><input type="hidden" name="entidad" value="<?= e($entidad) ?>"><input type="hidden" name="id" value="<?= (int) $idEnt ?>"><?php endif; ?>
    <div class="col-8 col-md-4">
        <select name="accion" class="form-select">
            <option value="">Todas las acciones</option>
            <?php foreach ($acciones as $a): $nombre = is_array($a) ? reset($a) : $a; ?>
                <option value="<?= e($nombre) ?>" <?= $nombre === $accion ? 'selected' : '' ?>><?= e($nombre) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-4 col-md-3 d-flex gap-2"><button class="btn btn-outline-primary flex-fill">Filtrar</button><a href="bitacora.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a></div>
</form>
<div class="card border shadow-sm"><div class="table-responsive">
<table class="table table-sm table-hover mb-0">
    <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Detalle</th></tr></thead>
    <tbody>
    <?php foreach ($filas as $f): ?>
        <tr><td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($f['fecha']))) ?></td><td><?= e($f['usuario'] ?? '—') ?></td><td><code><?= e($f['accion']) ?></code></td><td><?= e($f['detalle']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$filas): ?><tr><td colspan="4" class="text-center text-secondary py-4">No hay registros.</td></tr><?php endif; ?>
    </tbody>
</table></div></div>
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-2">
    <span class="text-secondary" style="font-size:13px;"><?= (int) $p['total'] ?> registro(s)</span>
    <?php mostrarPaginacion($p); ?>
</div>
<?php require __DIR__ . '/_pie.php'; ?>
