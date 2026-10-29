<?php
// ============================================================
// Documentos: listado con búsqueda, filtro por servicio y paginación.
// Se agrupan por servicio. Se puede cargar, editar, reemplazar el archivo,
// publicar/ocultar y (solo admin) eliminar y ver el historial de cambios.
// ============================================================
require_once __DIR__ . '/_vista.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Documento.php';
require_once __DIR__ . '/../datos/Categoria.php';
exigirLogin();

$buscar = trim($_GET['q'] ?? '');
$idCat  = enteroPositivo($_GET['servicio'] ?? '') ?: '';
$idCat  = $idCat === '' ? '' : (string) $idCat;

$documentos = [];
$servicios  = [];
$p = paginar(0, POR_PAGINA, 1);
try {
    $pdo = (new Conexion())->getConexion();
    $modelo = new Documento($pdo);
    $servicios = (new Categoria($pdo))->listar(true);
    $p = paginar($modelo->contarPanel($buscar, $idCat), POR_PAGINA, $_GET['pagina'] ?? 1);
    $documentos = $modelo->listarPanel($buscar, $idCat, $p['por_pagina'], $p['desplazamiento']);
} catch (PDOException $e) {
    error_log('Error al listar documentos: ' . $e->getMessage());
    guardarMensaje('No se pudieron leer los documentos.', 'danger');
}

// Si el guardado falló, se vuelve a abrir la ventana con lo que se había escrito
$datosForm = datosForm();
$reabrir = !empty($_SESSION['errores_form']);

$porServicio = [];
foreach ($documentos as $d) { $porServicio[$d['categoria']][] = $d; }

$tituloPagina = 'Documentos';
$paginaActiva = 'documentos';
require __DIR__ . '/_cabecera.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h5 class="fw-semibold mb-0">Documentos</h5>
    <button class="btn btn-hc btn-sm" onclick="abrirDocumento()"><i class="bi bi-plus-lg"></i> Nuevo documento</button>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-12 col-md-5"><input type="search" name="q" value="<?= e($buscar) ?>" class="form-control" placeholder="Buscar por título..." maxlength="100"></div>
    <div class="col-8 col-md-4">
        <select name="servicio" class="form-select">
            <option value="">Todos los servicios</option>
            <?php foreach ($servicios as $s): ?>
                <option value="<?= (int) $s['id'] ?>" <?= (string) $s['id'] === $idCat ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-4 col-md-3 d-flex gap-2">
        <button class="btn btn-outline-primary flex-fill">Filtrar</button>
        <a href="documentos.php" class="btn btn-outline-secondary" title="Limpiar"><i class="bi bi-x-lg"></i></a>
    </div>
</form>

<?php if (!$documentos): ?>
    <div class="alert alert-light border text-center">No hay documentos para mostrar.</div>
<?php endif; ?>

<?php foreach ($porServicio as $servicio => $lista): ?>
    <div class="card border shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold" style="font-size:14px;"><i class="bi bi-folder2-open text-primary me-1"></i><?= e($servicio) ?> <span class="badge bg-primary bg-opacity-10 text-primary"><?= count($lista) ?></span></div>
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Título</th><th>Emisión</th><th class="d-none d-md-table-cell">Cargado por</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($lista as $d): ?>
                <tr>
                    <td><?= e($d['titulo']) ?>
                        <?php if ($d['descripcion']): ?><div class="text-secondary" style="font-size:12px;"><?= e($d['descripcion']) ?></div><?php endif; ?></td>
                    <td><?= e(date('d/m/Y', strtotime($d['fecha_emision']))) ?></td>
                    <td class="d-none d-md-table-cell" style="font-size:12px;"><?= e($d['autor']) ?><br><span class="text-secondary"><?= e(date('d/m/Y H:i', strtotime($d['fecha_carga']))) ?></span></td>
                    <td>
                        <?php if ($d['publicado']): ?><span class="badge bg-success bg-opacity-10 text-success">Publicado</span>
                        <?php else: ?><span class="badge bg-secondary bg-opacity-25 text-secondary">Oculto</span><?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="ver_documento_mobile.php?id=<?= (int) $d['id'] ?>&vista=panel" target="_blank" title="Ver"><i class="bi bi-eye"></i></a>
                        <button class="btn btn-sm btn-outline-secondary" title="Editar"
                            data-doc="<?= e(json_encode(['id' => $d['id'], 'titulo' => $d['titulo'], 'descripcion' => $d['descripcion'],
                                'id_categoria' => $d['id_categoria'], 'fecha_emision' => $d['fecha_emision'], 'publicado' => $d['publicado']])) ?>"
                            onclick="editarDocumento(this)"><i class="bi bi-pencil"></i></button>
                        <?php botonPost('../negocio/cambiar_publicacion_documento.php', ['id' => $d['id'], 'publicado' => $d['publicado'] ? 0 : 1],
                            $d['publicado'] ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye-fill"></i>', 'btn btn-sm btn-outline-secondary',
                            $d['publicado'] ? '¿Ocultar este documento a los pacientes?' : ''); ?>
                        <?php if (esAdmin()): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="registro.php?entidad=documento&id=<?= (int) $d['id'] ?>" title="Historial de cambios"><i class="bi bi-clock-history"></i></a>
                            <?php botonPost('../negocio/eliminar_documento.php', ['id' => $d['id']], '<i class="bi bi-trash"></i>', 'btn btn-sm btn-outline-danger', '¿Eliminar el documento "' . $d['titulo'] . '"?'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
<?php endforeach; ?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
    <span class="text-secondary" style="font-size:13px;"><?= (int) $p['total'] ?> documento(s)</span>
    <?php mostrarPaginacion($p); ?>
</div>

<!-- Ventana para cargar / editar un documento -->
<div class="modal fade" id="modalDoc" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="../negocio/guardar_documento.php" enctype="multipart/form-data" id="formDoc" novalidate>
    <?= campoCsrf() ?>
    <input type="hidden" name="id" id="docId">
    <div class="modal-header"><h6 class="modal-title" id="docTitulo">Nuevo documento</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <?php mostrarErroresForm(); ?>
        <div class="mb-3"><label class="form-label">Título *</label><input class="form-control" name="titulo" id="docNombre" maxlength="120" required></div>
        <div class="mb-3"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion" id="docDescripcion" rows="2" maxlength="500"></textarea></div>
        <div class="row g-2 mb-3">
            <div class="col-sm-7"><label class="form-label">Servicio *</label>
                <select class="form-select" name="id_categoria" id="docCategoria" required>
                    <option value="">Elegir...</option>
                    <?php foreach ($servicios as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['nombre']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-sm-5"><label class="form-label">Fecha de emisión *</label><input type="date" class="form-control" name="fecha_emision" id="docFecha" required></div>
        </div>
        <div class="mb-3"><label class="form-label" id="docArchivoEtiqueta">Archivo (PDF, JPG o PNG, máx. 2 MB) *</label>
            <input type="file" class="form-control" name="archivo" id="docArchivo" accept=".pdf,.jpg,.jpeg,.png">
            <div class="form-text d-none" id="docArchivoAyuda">Si elige un archivo nuevo, reemplaza al actual.</div></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="publicado" id="docPublicado" value="1" checked><label class="form-check-label" for="docPublicado">Publicado (lo ven los pacientes)</label></div>
        <div class="text-danger small mt-2 d-none" id="docErrorJs"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-hc">Guardar</button></div>
</form></div></div>

<?php
$datosJs = json_encode($datosForm ?: new stdClass());
$scriptExtra = '<script>
var modalDoc = new bootstrap.Modal(document.getElementById("modalDoc"));
function poner(id, valor) { document.getElementById(id).value = valor == null ? "" : valor; }
function abrirDocumento() {
    document.getElementById("formDoc").reset();
    poner("docId", ""); document.getElementById("docTitulo").textContent = "Nuevo documento";
    document.getElementById("docArchivoAyuda").classList.add("d-none");
    document.getElementById("docPublicado").checked = true;
    modalDoc.show();
}
function editarDocumento(boton) {
    var d = JSON.parse(boton.getAttribute("data-doc"));
    cargarDatos(d); modalDoc.show();
}
function cargarDatos(d) {
    poner("docId", d.id); poner("docNombre", d.titulo); poner("docDescripcion", d.descripcion);
    poner("docCategoria", d.id_categoria); poner("docFecha", d.fecha_emision);
    document.getElementById("docPublicado").checked = (d.publicado == 1);
    document.getElementById("docTitulo").textContent = d.id ? "Editar documento" : "Nuevo documento";
    document.getElementById("docArchivoAyuda").classList.toggle("d-none", !d.id);
}
// Validación en JavaScript (PHP vuelve a validar)
document.getElementById("formDoc").addEventListener("submit", function (ev) {
    var msg = "";
    if (document.getElementById("docNombre").value.trim().length < 3) msg = "El título debe tener al menos 3 caracteres.";
    else if (!document.getElementById("docCategoria").value) msg = "Elija un servicio.";
    else if (!document.getElementById("docFecha").value) msg = "Indique la fecha de emisión.";
    else if (!document.getElementById("docId").value && !document.getElementById("docArchivo").files.length) msg = "Elija un archivo.";
    else if (document.getElementById("docArchivo").files.length && document.getElementById("docArchivo").files[0].size > 2*1024*1024) msg = "El archivo supera los 2 MB.";
    var caja = document.getElementById("docErrorJs");
    caja.textContent = msg; caja.classList.toggle("d-none", msg === "");
    if (msg) ev.preventDefault();
});
' . ($reabrir ? 'var previos = ' . $datosJs . '; cargarDatos(previos); modalDoc.show();' : '') . '
</script>';
require __DIR__ . '/_pie.php'; ?>
