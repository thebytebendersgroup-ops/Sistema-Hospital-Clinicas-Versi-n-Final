<?php
// ============================================================
// Resultados de las encuestas: resumen por encuesta, y detalle con porcentajes,
// gráficos de barras, comentarios de texto y exportación a CSV.
// Filtros: texto, servicio y rango de fechas. Todo es anónimo.
// ============================================================
require_once __DIR__ . '/_vista.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Encuesta.php';
require_once __DIR__ . '/../datos/Pregunta.php';
require_once __DIR__ . '/../datos/Categoria.php';
require_once __DIR__ . '/../datos/Resultado.php';
exigirLogin();

$buscar = trim($_GET['q'] ?? '');
$servicioPedido = $_GET['servicio'] ?? '';
$idCat  = $servicioPedido === 'general' ? 'general' : (enteroPositivo($servicioPedido) ? (string) enteroPositivo($servicioPedido) : '');
$desde  = fechaDeFiltro($_GET['desde'] ?? '');
$hasta  = fechaDeFiltro($_GET['hasta'] ?? '');
$idEnc  = enteroPositivo($_GET['encuesta'] ?? '');
$pagTexto = max(1, enteroPositivo($_GET['ptexto'] ?? '1'));

$servicios = []; $resumen = []; $enc = false; $detalle = null;
try {
    $pdo = (new Conexion())->getConexion();
    $servicios = (new Categoria($pdo))->listar(false);
    $modelo = new Resultado($pdo);
    if ($idEnc) {
        $enc = (new Encuesta($pdo))->buscarPorId($idEnc);
    }
    if ($enc) {
        $detalle = $modelo->detalle($idEnc, $desde, $hasta, $pagTexto, 10);
    } else {
        $resumen = $modelo->resumenEncuestas($buscar, $idCat, $desde, $hasta);
    }
} catch (PDOException $e) {
    error_log('Error en resultados: ' . $e->getMessage());
    guardarMensaje('No se pudieron leer los resultados.', 'danger');
}
if ($idEnc && !$enc && !isset($_SESSION['mensaje'])) {
    guardarMensaje('La encuesta no existe.', 'danger');
}

$tituloPagina = 'Resultados';
$paginaActiva = 'resultados';
require __DIR__ . '/_cabecera.php';
?>
<h5 class="fw-semibold mb-3">Resultados de encuestas</h5>

<form method="GET" class="row g-2 mb-3">
    <?php if ($enc): ?>
        <input type="hidden" name="encuesta" value="<?= (int) $enc['id'] ?>">
    <?php else: ?>
        <div class="col-12 col-md-3"><input type="search" name="q" value="<?= e($buscar) ?>" class="form-control" placeholder="Buscar encuesta..." maxlength="100"></div>
        <div class="col-12 col-md-3">
            <select name="servicio" class="form-select">
                <option value="">Todos los servicios</option>
                <option value="general" <?= $idCat === 'general' ? 'selected' : '' ?>>Generales</option>
                <?php foreach ($servicios as $s): ?><option value="<?= (int) $s['id'] ?>" <?= (string) $s['id'] === $idCat ? 'selected' : '' ?>><?= e($s['nombre']) ?></option><?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div class="col-6 col-md-2"><input type="date" name="desde" value="<?= e($desde) ?>" class="form-control" title="Desde"></div>
    <div class="col-6 col-md-2"><input type="date" name="hasta" value="<?= e($hasta) ?>" class="form-control" title="Hasta"></div>
    <div class="col-12 col-md-2 d-flex gap-2">
        <button class="btn btn-outline-primary flex-fill">Filtrar</button>
        <a href="<?= $enc ? 'resultados.php?encuesta=' . (int) $enc['id'] : 'resultados.php' ?>" class="btn btn-outline-secondary" title="Limpiar"><i class="bi bi-x-lg"></i></a>
    </div>
</form>

<?php if (!$enc): ?>
    <div class="card border shadow-sm"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead><tr><th>Encuesta</th><th>Servicio</th><th>Estado</th><th>Respuestas</th><th>Promedio (1–5)</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($resumen as $r): ?>
            <tr>
                <td><?= e($r['titulo']) ?> <span class="text-secondary small">v<?= (int) $r['version'] ?></span></td>
                <td><?= $r['servicio'] ? e($r['servicio']) : '<span class="text-secondary">General</span>' ?></td>
                <td><?= etiquetaEstado($r['estado']) ?></td>
                <td><?= (int) $r['envios'] ?></td>
                <td><?= $r['promedio'] !== null ? e($r['promedio']) : '—' ?></td>
                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(urlCon(['encuesta' => $r['id']])) ?>">Ver detalle</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$resumen): ?><tr><td colspan="6" class="text-center text-secondary py-4">No hay encuestas para mostrar.</td></tr><?php endif; ?>
        </tbody>
    </table></div></div>

<?php elseif ($detalle): ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <a href="resultados.php" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Volver al resumen</a>
            <h6 class="fw-semibold mb-0 mt-1"><?= e($enc['titulo']) ?> <small class="text-secondary">v<?= (int) $enc['version'] ?></small> <?= etiquetaEstado($enc['estado']) ?></h6>
            <span class="text-secondary" style="font-size:13px;"><?= $enc['servicio'] ? e($enc['servicio']) : 'General' ?> · <?= (int) $detalle['total'] ?> respuesta(s)<?= ($desde || $hasta) ? ' en el período elegido' : '' ?></span>
        </div>
        <a class="btn btn-outline-success btn-sm" href="<?= e('../negocio/exportar_resultados.php?' . http_build_query(array_filter(['id' => $enc['id'], 'desde' => $desde, 'hasta' => $hasta]))) ?>"><i class="bi bi-filetype-csv"></i> Exportar CSV</a>
    </div>

    <?php if ($detalle['total'] === 0): ?><div class="alert alert-light border">Todavía no hay respuestas para este filtro.</div><?php endif; ?>

    <?php foreach ($detalle['preguntas'] as $i => $q): ?>
        <div class="card border shadow-sm mb-3"><div class="card-body">
            <div class="fw-semibold" style="font-size:15px;"><?= $i + 1 ?>. <?= e($q['texto']) ?></div>
            <div class="text-secondary mb-2" style="font-size:12px;"><?= (int) $q['cantidad'] ?> respuesta(s)<?= $q['tipo'] === 'valoracion' ? ' · promedio ' . e($q['promedio']) . ' / 5' : '' ?></div>

            <?php if ($q['tipo'] === 'valoracion'): ?>
                <?php foreach ($q['por_nota'] as $nota => $d): ?>
                    <div class="d-flex align-items-center gap-2 mb-1" style="font-size:13px;">
                        <span style="width:48px;"><?= (int) $nota ?> ★</span>
                        <div class="barra flex-grow-1"><div style="width:<?= (int) $d['porcentaje'] ?>%;"></div></div>
                        <span style="width:84px;" class="text-end"><?= (int) $d['porcentaje'] ?>% (<?= (int) $d['cantidad'] ?>)</span>
                    </div>
                <?php endforeach; ?>

            <?php elseif ($q['tipo'] === 'unica' || $q['tipo'] === 'multiple'): ?>
                <?php foreach ($q['opciones'] as $o): ?>
                    <div class="d-flex align-items-center gap-2 mb-1" style="font-size:13px;">
                        <span style="width:35%;" class="text-truncate" title="<?= e($o['texto']) ?>"><?= e($o['texto']) ?></span>
                        <div class="barra flex-grow-1"><div style="width:<?= (int) $o['porcentaje'] ?>%;"></div></div>
                        <span style="width:84px;" class="text-end"><?= (int) $o['porcentaje'] ?>% (<?= (int) $o['cantidad'] ?>)</span>
                    </div>
                <?php endforeach; ?>
                <?php if ($q['tipo'] === 'multiple'): ?><div class="text-secondary" style="font-size:11px;">Se pueden marcar varias opciones: los porcentajes pueden sumar más de 100%.</div><?php endif; ?>

            <?php else: ?>
                <?php foreach ($q['textos'] as $t): ?>
                    <div class="border-start border-3 ps-2 mb-2" style="font-size:13px;"><?= e($t['texto']) ?> <span class="text-secondary">· <?= e(date('d/m/Y', strtotime($t['fecha']))) ?></span></div>
                <?php endforeach; ?>
                <?php if (!$q['textos']): ?><span class="text-secondary" style="font-size:13px;">Sin comentarios.</span><?php endif; ?>
                <?php $pt = paginar($q['total_textos'], 10, $pagTexto); if ($pt['paginas'] > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="text-secondary small">Comentarios <?= (int) $pt['desplazamiento'] + 1 ?>–<?= min($pt['desplazamiento'] + 10, $pt['total']) ?> de <?= (int) $pt['total'] ?></span>
                        <div class="btn-group btn-group-sm">
                            <?php if ($pt['pagina'] > 1): ?><a class="btn btn-outline-secondary" href="<?= e(urlCon(['ptexto' => $pt['pagina'] - 1])) ?>">Anterior</a><?php endif; ?>
                            <?php if ($pt['pagina'] < $pt['paginas']): ?><a class="btn btn-outline-secondary" href="<?= e(urlCon(['ptexto' => $pt['pagina'] + 1])) ?>">Siguiente</a><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div></div>
    <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/_pie.php'; ?>
