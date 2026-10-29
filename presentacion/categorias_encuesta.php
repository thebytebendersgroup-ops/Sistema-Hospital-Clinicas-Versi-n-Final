<?php
// ============================================================
// Categorías de encuestas (todos los funcionarios): alta, edición y baja lógica.
// Ejemplos: Satisfacción, Atención al paciente, Instalaciones. Sirven para agrupar las encuestas
// y para que el paciente las pueda filtrar en el portal.
// ============================================================
require_once __DIR__ . '/_vista.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/CategoriaEncuesta.php';
exigirLogin();

$categorias = [];
try {
    $categorias = (new CategoriaEncuesta((new Conexion())->getConexion()))->listarConConteo();
} catch (PDOException $e) {
    error_log('Error al listar categorías de encuestas: ' . $e->getMessage());
    guardarMensaje('No se pudieron leer las categorías.', 'danger');
}
$pagina = paginar(count($categorias), POR_PAGINA * 2, $_GET['pagina'] ?? 1);
$categorias = array_slice($categorias, $pagina['desplazamiento'], $pagina['por_pagina']);
$datosForm = datosForm();
$reabrir = !empty($_SESSION['errores_form']);

$tituloPagina = 'Categorías de encuestas';
$paginaActiva = 'encuestas';
require __DIR__ . '/_cabecera.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="fw-semibold mb-0">Categorías de encuestas</h5>
        <a href="encuestas.php" class="text-decoration-none" style="font-size:13px;"><i class="bi bi-arrow-left"></i> Volver a encuestas</a>
    </div>
    <button class="btn btn-hc btn-sm" onclick="abrirCategoria()"><i class="bi bi-plus-lg"></i> Nueva categoría</button>
</div>
<div class="alert alert-light border py-2" style="font-size:13px;">Las categorías agrupan las encuestas (por ejemplo «Satisfacción» o «Instalaciones»). El paciente puede filtrar las encuestas por categoría. Si da de baja una categoría, ya no se ofrece al crear encuestas, pero las que la tenían la conservan.</div>
<div class="card border shadow-sm"><div class="table-responsive">
<table class="table table-hover mb-0">
    <thead><tr><th>Categoría</th><th>Encuestas</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($categorias as $c): ?>
        <tr class="<?= $c['activo'] ? '' : 'text-secondary' ?>">
            <td><?= e($c['nombre']) ?></td>
            <td><a href="encuestas.php?categoria=<?= (int) $c['id'] ?>" class="text-decoration-none"><?= (int) $c['encuestas'] ?></a></td>
            <td><?= $c['activo'] ? '<span class="badge bg-success bg-opacity-10 text-success">Activa</span>' : '<span class="badge bg-secondary">De baja</span>' ?></td>
            <td class="text-end text-nowrap">
                <button class="btn btn-sm btn-outline-secondary" onclick="editarCategoria(<?= (int) $c['id'] ?>, this)" data-nombre="<?= e($c['nombre']) ?>"><i class="bi bi-pencil"></i></button>
                <?php if ($c['activo']): ?>
                    <?php botonPost('../negocio/cambiar_estado_categoria_encuesta.php', ['id' => $c['id'], 'activo' => 0], '<i class="bi bi-eye-slash"></i>', 'btn btn-sm btn-outline-danger',
                        '¿Dar de baja "' . $c['nombre'] . '"? Ya no se podrá elegir al crear encuestas.'); ?>
                <?php else: ?>
                    <?php botonPost('../negocio/cambiar_estado_categoria_encuesta.php', ['id' => $c['id'], 'activo' => 1], '<i class="bi bi-eye"></i>', 'btn btn-sm btn-outline-success', ''); ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$categorias): ?><tr><td colspan="4" class="text-center text-secondary py-4">Todavía no hay categorías.</td></tr><?php endif; ?>
    </tbody>
</table></div></div>
<div class="mt-2"><?php mostrarPaginacion($pagina); ?></div>

<div class="modal fade" id="modalCategoria" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="../negocio/guardar_categoria_encuesta.php" id="formCategoria" novalidate>
    <?= campoCsrf() ?><input type="hidden" name="id" id="cId">
    <div class="modal-header"><h6 class="modal-title" id="cTitulo">Nueva categoría</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <?php mostrarErroresForm(); ?>
        <label class="form-label">Nombre *</label><input class="form-control" name="nombre" id="cNombre" maxlength="60" placeholder="Ej.: Atención al paciente">
        <div class="text-danger small mt-2 d-none" id="cErrorJs"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-hc">Guardar</button></div>
</form></div></div>
<?php
$datosJs = json_encode($datosForm ?: new stdClass());
$scriptExtra = '<script>
var modalC = new bootstrap.Modal(document.getElementById("modalCategoria"));
function llenarC(id, nombre) {
    document.getElementById("cId").value = id || ""; document.getElementById("cNombre").value = nombre || "";
    document.getElementById("cTitulo").textContent = id ? "Editar categoría" : "Nueva categoría";
}
function abrirCategoria() { llenarC("", ""); modalC.show(); }
function editarCategoria(id, b) { llenarC(id, b.getAttribute("data-nombre")); modalC.show(); }
document.getElementById("formCategoria").addEventListener("submit", function (ev) {
    var mal = document.getElementById("cNombre").value.trim().length < 3, caja = document.getElementById("cErrorJs");
    caja.textContent = mal ? "El nombre debe tener al menos 3 caracteres." : ""; caja.classList.toggle("d-none", !mal);
    if (mal) ev.preventDefault();
});
' . ($reabrir ? 'var previo = ' . $datosJs . '; llenarC(previo.id, previo.nombre); modalC.show();' : '') . '
</script>';
require __DIR__ . '/_pie.php'; ?>
