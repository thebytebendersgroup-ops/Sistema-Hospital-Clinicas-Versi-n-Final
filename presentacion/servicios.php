<?php
// ============================================================
// Administración de servicios (solo administrador): alta, edición y baja lógica.
// No permite nombres repetidos. Un servicio de baja oculta sus documentos y encuestas del portal.
// ============================================================
require_once __DIR__ . '/_vista.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Categoria.php';
exigirRol('admin');

$servicios = [];
try {
    $servicios = (new Categoria((new Conexion())->getConexion()))->listarConConteo();
} catch (PDOException $e) {
    error_log('Error al listar servicios: ' . $e->getMessage());
    guardarMensaje('No se pudieron leer los servicios.', 'danger');
}
$pagina = paginar(count($servicios), POR_PAGINA * 2, $_GET['pagina'] ?? 1);
$servicios = array_slice($servicios, $pagina['desplazamiento'], $pagina['por_pagina']);
$datosForm = datosForm();
$reabrir = !empty($_SESSION['errores_form']);

$tituloPagina = 'Servicios';
$paginaActiva = 'servicios';
require __DIR__ . '/_cabecera.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h5 class="fw-semibold mb-0">Servicios del hospital</h5>
    <button class="btn btn-hc btn-sm" onclick="abrirServicio()"><i class="bi bi-plus-lg"></i> Nuevo servicio</button>
</div>
<div class="alert alert-light border py-2" style="font-size:13px;">Si un servicio se da de baja, sus documentos y encuestas dejan de verse en el portal del paciente (no se borran: si se reactiva, vuelven a aparecer).</div>
<div class="card border shadow-sm"><div class="table-responsive">
<table class="table table-hover mb-0">
    <thead><tr><th>Servicio</th><th>Documentos</th><th>Encuestas</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($servicios as $s): ?>
        <tr class="<?= $s['activo'] ? '' : 'text-secondary' ?>">
            <td><?= e($s['nombre']) ?></td>
            <td><?= (int) $s['documentos'] ?></td>
            <td><?= (int) $s['encuestas'] ?></td>
            <td><?= $s['activo'] ? '<span class="badge bg-success bg-opacity-10 text-success">Activo</span>' : '<span class="badge bg-secondary">De baja</span>' ?></td>
            <td class="text-end text-nowrap">
                <button class="btn btn-sm btn-outline-secondary" onclick="editarServicio(<?= (int) $s['id'] ?>, this)" data-nombre="<?= e($s['nombre']) ?>"><i class="bi bi-pencil"></i></button>
                <?php if ($s['activo']): ?>
                    <?php botonPost('../negocio/cambiar_estado_servicio.php', ['id' => $s['id'], 'activo' => 0], '<i class="bi bi-eye-slash"></i>', 'btn btn-sm btn-outline-danger',
                        '¿Dar de baja "' . $s['nombre'] . '"? Se ocultarán sus ' . (int) $s['documentos'] . ' documento(s) y ' . (int) $s['encuestas'] . ' encuesta(s) del portal.'); ?>
                <?php else: ?>
                    <?php botonPost('../negocio/cambiar_estado_servicio.php', ['id' => $s['id'], 'activo' => 1], '<i class="bi bi-eye"></i>', 'btn btn-sm btn-outline-success', ''); ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div></div>
<div class="mt-2"><?php mostrarPaginacion($pagina); ?></div>

<div class="modal fade" id="modalServicio" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="../negocio/guardar_servicio.php" id="formServicio" novalidate>
    <?= campoCsrf() ?><input type="hidden" name="id" id="sId">
    <div class="modal-header"><h6 class="modal-title" id="sTitulo">Nuevo servicio</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <?php mostrarErroresForm(); ?>
        <label class="form-label">Nombre *</label><input class="form-control" name="nombre" id="sNombre" maxlength="80">
        <div class="text-danger small mt-2 d-none" id="sErrorJs"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-hc">Guardar</button></div>
</form></div></div>
<?php
$datosJs = json_encode($datosForm ?: new stdClass());
$scriptExtra = '<script>
var modalS = new bootstrap.Modal(document.getElementById("modalServicio"));
function llenarS(id, nombre) {
    document.getElementById("sId").value = id || ""; document.getElementById("sNombre").value = nombre || "";
    document.getElementById("sTitulo").textContent = id ? "Editar servicio" : "Nuevo servicio";
}
function abrirServicio() { llenarS("", ""); modalS.show(); }
function editarServicio(id, b) { llenarS(id, b.getAttribute("data-nombre")); modalS.show(); }
document.getElementById("formServicio").addEventListener("submit", function (ev) {
    var mal = document.getElementById("sNombre").value.trim().length < 3, caja = document.getElementById("sErrorJs");
    caja.textContent = mal ? "El nombre debe tener al menos 3 caracteres." : ""; caja.classList.toggle("d-none", !mal);
    if (mal) ev.preventDefault();
});
' . ($reabrir ? 'var previo = ' . $datosJs . '; llenarS(previo.id, previo.nombre); modalS.show();' : '') . '
</script>';
require __DIR__ . '/_pie.php'; ?>
