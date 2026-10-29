<?php
// ============================================================
// Encuestas: listado con búsqueda, filtros por servicio y estado, y paginación.
// Desde acá se crea, edita, duplica, publica, cierra y elimina.
// ============================================================
require_once __DIR__ . '/_vista.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Encuesta.php';
require_once __DIR__ . '/../datos/Pregunta.php';
require_once __DIR__ . '/../datos/Categoria.php';
require_once __DIR__ . '/../datos/CategoriaEncuesta.php';
exigirLogin();

$buscar = trim($_GET['q'] ?? '');
$servicioPedido = $_GET['servicio'] ?? '';
$idCat = $servicioPedido === 'general' ? 'general' : (enteroPositivo($servicioPedido) ? (string) enteroPositivo($servicioPedido) : '');
$estado = in_array($_GET['estado'] ?? '', ['borrador', 'publicada', 'cerrada'], true) ? $_GET['estado'] : '';

$categoriaPedida = $_GET['categoria'] ?? '';
$idTipo = $categoriaPedida === 'sin' ? 'sin' : (enteroPositivo($categoriaPedida) ? (string) enteroPositivo($categoriaPedida) : '');

$encuestas = []; $servicios = []; $categoriasEnc = [];
$p = paginar(0, POR_PAGINA, 1);
try {
    $pdo = (new Conexion())->getConexion();
    $modelo = new Encuesta($pdo);
    $servicios = (new Categoria($pdo))->listar(true);
    $categoriasEnc = (new CategoriaEncuesta($pdo))->listar(false);
    $p = paginar($modelo->contarPanel($buscar, $idCat, $estado, $idTipo), POR_PAGINA, $_GET['pagina'] ?? 1);
    $encuestas = $modelo->listarPanel($buscar, $idCat, $estado, $p['por_pagina'], $p['desplazamiento'], $idTipo);
} catch (PDOException $e) {
    error_log('Error al listar encuestas: ' . $e->getMessage());
    guardarMensaje('No se pudieron leer las encuestas.', 'danger');
}
$datosForm = datosForm();
$reabrir = !empty($_SESSION['errores_form']);

$tituloPagina = 'Encuestas';
$paginaActiva = 'encuestas';
require __DIR__ . '/_cabecera.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h5 class="fw-semibold mb-0">Encuestas</h5>
    <div class="d-flex gap-2">
        <a href="categorias_encuesta.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-tags"></i> Categorías</a>
        <button class="btn btn-hc btn-sm" data-bs-toggle="modal" data-bs-target="#modalEncuesta"><i class="bi bi-plus-lg"></i> Nueva encuesta</button>
    </div>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-12 col-md-3"><input type="search" name="q" value="<?= e($buscar) ?>" class="form-control" placeholder="Buscar por título..." maxlength="100"></div>
    <div class="col-6 col-md-2">
        <select name="categoria" class="form-select">
            <option value="">Todas las categorías</option>
            <option value="sin" <?= $idTipo === 'sin' ? 'selected' : '' ?>>Sin categoría</option>
            <?php foreach ($categoriasEnc as $ce): ?>
                <option value="<?= (int) $ce['id'] ?>" <?= (string) $ce['id'] === $idTipo ? 'selected' : '' ?>><?= e($ce['nombre']) ?><?= $ce['activo'] ? '' : ' (de baja)' ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-2">
        <select name="servicio" class="form-select">
            <option value="">Todos los servicios</option>
            <option value="general" <?= $idCat === 'general' ? 'selected' : '' ?>>Generales (sin servicio)</option>
            <?php foreach ($servicios as $s): ?>
                <option value="<?= (int) $s['id'] ?>" <?= (string) $s['id'] === $idCat ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-2">
        <select name="estado" class="form-select">
            <option value="">Todos los estados</option>
            <?php foreach (['borrador' => 'Borrador', 'publicada' => 'Publicada', 'cerrada' => 'Cerrada'] as $k => $v): ?>
                <option value="<?= $k ?>" <?= $estado === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12 col-md-3 d-flex gap-2">
        <button class="btn btn-outline-primary flex-fill">Filtrar</button>
        <a href="encuestas.php" class="btn btn-outline-secondary" title="Limpiar"><i class="bi bi-x-lg"></i></a>
    </div>
</form>

<div class="card border shadow-sm"><div class="table-responsive">
<table class="table table-hover mb-0">
    <thead><tr><th>Título</th><th>Categoría</th><th>Servicio</th><th>Versión</th><th>Estado</th><th class="d-none d-md-table-cell">Preg.</th><th class="d-none d-md-table-cell">Resp.</th><th class="text-end">Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($encuestas as $en): ?>
        <tr>
            <td><a href="encuesta_editar.php?id=<?= (int) $en['id'] ?>" class="text-decoration-none fw-medium"><?= e($en['titulo']) ?></a>
                <div class="text-secondary" style="font-size:12px;">Creada <?= e(date('d/m/Y', strtotime($en['fecha_creacion']))) ?> por <?= e($en['autor']) ?></div></td>
            <td><?= $en['categoria_encuesta'] ? '<span class="badge bg-primary bg-opacity-10 text-primary">' . e($en['categoria_encuesta']) . '</span>' : '<span class="text-secondary">—</span>' ?></td>
            <td><?= $en['servicio'] ? e($en['servicio']) . (!$en['servicio_activo'] ? ' <span class="badge bg-warning text-dark">de baja</span>' : '') : '<span class="text-secondary">General</span>' ?></td>
            <td>v<?= (int) $en['version'] ?></td>
            <td><?= etiquetaEstado($en['estado']) ?></td>
            <td class="d-none d-md-table-cell"><?= (int) $en['preguntas'] ?></td>
            <td class="d-none d-md-table-cell"><?= (int) $en['envios'] ?></td>
            <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="encuesta_editar.php?id=<?= (int) $en['id'] ?>" title="<?= $en['estado'] === 'borrador' ? 'Editar' : 'Ver' ?>"><i class="bi bi-<?= $en['estado'] === 'borrador' ? 'pencil' : 'eye' ?>"></i></a>
                <a class="btn btn-sm btn-outline-secondary" href="resultados.php?encuesta=<?= (int) $en['id'] ?>" title="Resultados"><i class="bi bi-bar-chart"></i></a>
                <?php
                $id = $en['id'];
                if ($en['estado'] === 'borrador') {
                    botonPost('../negocio/cambiar_estado_encuesta.php', ['id' => $id, 'accion' => 'publicar'], '<i class="bi bi-send"></i>', 'btn btn-sm btn-outline-success', '¿Publicar la encuesta? Desde ese momento no se podrán cambiar sus preguntas.');
                } elseif ($en['estado'] === 'publicada') {
                    botonPost('../negocio/cambiar_estado_encuesta.php', ['id' => $id, 'accion' => 'cerrar'], '<i class="bi bi-lock"></i>', 'btn btn-sm btn-outline-dark', '¿Cerrar la encuesta? Ya no recibirá respuestas.');
                }
                if ($en['estado'] !== 'borrador') {
                    botonPost('../negocio/duplicar_encuesta.php', ['id' => $id, 'modo' => 'version'], '<i class="bi bi-layers"></i>', 'btn btn-sm btn-outline-secondary', 'Se creará una nueva versión en borrador para poder modificarla. ¿Continuar?');
                }
                botonPost('../negocio/duplicar_encuesta.php', ['id' => $id, 'modo' => 'duplicar'], '<i class="bi bi-copy"></i>', 'btn btn-sm btn-outline-secondary', '');
                if (esAdmin()) {
                    botonPost('../negocio/eliminar_encuesta.php', ['id' => $id], '<i class="bi bi-trash"></i>', 'btn btn-sm btn-outline-danger', '¿Eliminar la encuesta "' . $en['titulo'] . '"? Sus respuestas se conservan.');
                }
                ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$encuestas): ?><tr><td colspan="8" class="text-center text-secondary py-4">No hay encuestas para mostrar.</td></tr><?php endif; ?>
    </tbody>
</table></div></div>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
    <span class="text-secondary" style="font-size:13px;"><?= (int) $p['total'] ?> encuesta(s)</span>
    <?php mostrarPaginacion($p); ?>
</div>

<!-- Ventana: nueva encuesta -->
<div class="modal fade" id="modalEncuesta" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="../negocio/guardar_encuesta.php" id="formEncuesta" novalidate>
    <?= campoCsrf() ?>
    <div class="modal-header"><h6 class="modal-title">Nueva encuesta</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <?php mostrarErroresForm(); ?>
        <div class="mb-3"><label class="form-label">Título *</label><input class="form-control" name="titulo" id="encTitulo" maxlength="120" value="<?= e($datosForm['titulo'] ?? '') ?>" required></div>
        <div class="mb-3"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion" rows="2" maxlength="300"><?= e($datosForm['descripcion'] ?? '') ?></textarea></div>
        <div class="mb-2"><label class="form-label">Servicio</label>
            <select class="form-select" name="id_categoria">
                <option value="">General (para todo el hospital)</option>
                <?php foreach ($servicios as $s): ?><option value="<?= (int) $s['id'] ?>" <?= (string) ($datosForm['id_categoria'] ?? '') === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="mb-2"><label class="form-label">Categoría de la encuesta</label>
            <select class="form-select" name="id_categoria_encuesta">
                <option value="">Sin categoría</option>
                <?php foreach ($categoriasEnc as $ce): if (!$ce['activo']) { continue; } ?><option value="<?= (int) $ce['id'] ?>" <?= (string) ($datosForm['id_categoria_encuesta'] ?? '') === (string) $ce['id'] ? 'selected' : '' ?>><?= e($ce['nombre']) ?></option><?php endforeach; ?>
            </select>
            <a href="categorias_encuesta.php" class="small text-decoration-none">¿Falta una categoría? Agregarla</a></div>
        <div class="text-danger small d-none" id="encErrorJs"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-hc">Crear borrador</button></div>
</form></div></div>
<?php
$scriptExtra = '<script>
document.getElementById("formEncuesta").addEventListener("submit", function (ev) {
    var caja = document.getElementById("encErrorJs");
    var mal = document.getElementById("encTitulo").value.trim().length < 3;
    caja.textContent = mal ? "El título debe tener al menos 3 caracteres." : "";
    caja.classList.toggle("d-none", !mal);
    if (mal) ev.preventDefault();
});
' . ($reabrir ? 'new bootstrap.Modal(document.getElementById("modalEncuesta")).show();' : '') . '
</script>';
require __DIR__ . '/_pie.php'; ?>
