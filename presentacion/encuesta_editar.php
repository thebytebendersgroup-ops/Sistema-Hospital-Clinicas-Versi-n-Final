<?php
// ============================================================
// Constructor de encuestas: datos generales, preguntas (4 tipos), orden y vista previa.
// Solo se edita en estado BORRADOR. Si ya está publicada o cerrada, se muestra solo para ver
// y se puede crear una nueva versión (así no se pierde el historial de respuestas).
// ============================================================
require_once __DIR__ . '/_vista.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Encuesta.php';
require_once __DIR__ . '/../datos/Pregunta.php';
require_once __DIR__ . '/../datos/Categoria.php';
require_once __DIR__ . '/../datos/CategoriaEncuesta.php';
exigirLogin();

$id = enteroPositivo($_GET['id'] ?? '');
try {
    $pdo = (new Conexion())->getConexion();
    $enc = $id ? (new Encuesta($pdo))->cargarCompleta($id) : false;
    $servicios = (new Categoria($pdo))->listar(true);
    // Categorías de encuesta: las activas, más la que la encuesta ya tiene (aunque esté de baja)
    $modeloCat = new CategoriaEncuesta($pdo);
    $categoriasEnc = $modeloCat->listar(true);
    if ($enc && $enc['id_categoria_encuesta'] && !$enc['categoria_encuesta_activa']) {
        $categoriasEnc[] = ['id' => $enc['id_categoria_encuesta'], 'nombre' => $enc['categoria_encuesta'] . ' (de baja)'];
    }
} catch (PDOException $e) {
    error_log('Error al abrir la encuesta: ' . $e->getMessage());
    $enc = false;
}
if (!$enc) {
    guardarMensaje('La encuesta no existe.', 'danger');
    redirigir('encuestas.php');
}
$editable  = $enc['estado'] === 'borrador';
$datosForm = datosForm();
$reabrir   = !empty($_SESSION['errores_form']) && isset($datosForm['texto']);
$tipos = ['unica' => 'Opción única', 'multiple' => 'Opción múltiple', 'valoracion' => 'Valoración 1 a 5', 'texto' => 'Texto breve'];

$tituloPagina = 'Editar encuesta';
$paginaActiva = 'encuestas';
require __DIR__ . '/_cabecera.php';
?>
<a href="encuestas.php" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Volver a encuestas</a>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 my-2">
    <h5 class="fw-semibold mb-0"><?= e($enc['titulo']) ?> <small class="text-secondary">v<?= (int) $enc['version'] ?></small> <?= etiquetaEstado($enc['estado']) ?></h5>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalVista"><i class="bi bi-eye"></i> Vista previa</button>
        <?php if ($editable): ?>
            <?php botonPost('../negocio/cambiar_estado_encuesta.php', ['id' => $enc['id'], 'accion' => 'publicar', 'volver' => 'editar'], '<i class="bi bi-send"></i> Publicar', 'btn btn-success btn-sm', '¿Publicar la encuesta? Desde ese momento no se podrán cambiar sus preguntas.'); ?>
        <?php else: ?>
            <?php if ($enc['estado'] === 'publicada') botonPost('../negocio/cambiar_estado_encuesta.php', ['id' => $enc['id'], 'accion' => 'cerrar', 'volver' => 'editar'], '<i class="bi bi-lock"></i> Cerrar', 'btn btn-dark btn-sm', '¿Cerrar la encuesta? Ya no recibirá respuestas.'); ?>
            <?php botonPost('../negocio/duplicar_encuesta.php', ['id' => $enc['id'], 'modo' => 'version'], '<i class="bi bi-layers"></i> Nueva versión', 'btn btn-hc btn-sm', 'Se creará una nueva versión en borrador. ¿Continuar?'); ?>
        <?php endif; ?>
    </div>
</div>

<?php if (!$editable): ?>
    <div class="alert alert-info py-2" style="font-size:14px;">Esta encuesta está <?= e($enc['estado']) ?> y tiene <?= (int) $enc['envios'] ?> respuesta(s): no se puede modificar. Para cambiarla, cree una <strong>nueva versión</strong>; la actual y sus respuestas se conservan.</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border shadow-sm"><div class="card-body">
            <h6 class="fw-semibold">Datos generales</h6>
            <?php if ($editable && !$reabrir) mostrarErroresForm(); ?>
            <form method="POST" action="../negocio/guardar_encuesta.php" novalidate>
                <?= campoCsrf() ?><input type="hidden" name="id" value="<?= (int) $enc['id'] ?>">
                <div class="mb-2"><label class="form-label">Título *</label><input class="form-control" name="titulo" maxlength="120" value="<?= e($enc['titulo']) ?>" <?= $editable ? '' : 'disabled' ?>></div>
                <div class="mb-2"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion" rows="3" maxlength="300" <?= $editable ? '' : 'disabled' ?>><?= e($enc['descripcion']) ?></textarea></div>
                <div class="mb-3"><label class="form-label">Servicio</label>
                    <select class="form-select" name="id_categoria" <?= $editable ? '' : 'disabled' ?>>
                        <option value="">General (para todo el hospital)</option>
                        <?php foreach ($servicios as $s): ?><option value="<?= (int) $s['id'] ?>" <?= (string) $enc['id_categoria'] === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="mb-3"><label class="form-label">Categoría de la encuesta</label>
                    <select class="form-select" name="id_categoria_encuesta" <?= $editable ? '' : 'disabled' ?>>
                        <option value="">Sin categoría</option>
                        <?php foreach ($categoriasEnc as $ce): ?><option value="<?= (int) $ce['id'] ?>" <?= (string) $enc['id_categoria_encuesta'] === (string) $ce['id'] ? 'selected' : '' ?>><?= e($ce['nombre']) ?></option><?php endforeach; ?>
                    </select>
                    <?php if ($editable): ?><a href="categorias_encuesta.php" class="small text-decoration-none">Administrar categorías</a><?php endif; ?></div>
                <?php if ($editable): ?><button class="btn btn-hc btn-sm">Guardar datos</button><?php endif; ?>
            </form>
        </div></div>
    </div>

    <div class="col-lg-8" id="preguntas">
        <div class="card border shadow-sm"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-semibold mb-0">Preguntas (<?= count($enc['lista_preguntas']) ?>)</h6>
                <?php if ($editable): ?><button class="btn btn-hc btn-sm" onclick="abrirPregunta()"><i class="bi bi-plus-lg"></i> Agregar pregunta</button><?php endif; ?>
            </div>
            <?php if (!$enc['lista_preguntas']): ?><p class="text-secondary mb-0" style="font-size:14px;">Todavía no hay preguntas.</p><?php endif; ?>
            <?php $total = count($enc['lista_preguntas']); foreach ($enc['lista_preguntas'] as $i => $q): ?>
                <div class="border rounded p-2 mb-2 d-flex gap-2 align-items-start">
                    <span class="badge bg-primary mt-1"><?= $i + 1 ?></span>
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="fw-medium" style="font-size:14px;"><?= e($q['texto']) ?></div>
                        <div class="text-secondary" style="font-size:12px;"><?= e($tipos[$q['tipo']]) ?> · <?= $q['obligatoria'] ? 'obligatoria' : 'opcional' ?>
                            <?php if ($q['opciones']): ?> · <?= e(implode(' / ', array_column($q['opciones'], 'texto'))) ?><?php endif; ?></div>
                    </div>
                    <?php if ($editable): ?>
                    <div class="text-nowrap">
                        <?php if ($i > 0) botonPost('../negocio/mover_pregunta.php', ['id' => $q['id'], 'direccion' => 'arriba'], '<i class="bi bi-arrow-up"></i>', 'btn btn-sm btn-outline-secondary'); ?>
                        <?php if ($i < $total - 1) botonPost('../negocio/mover_pregunta.php', ['id' => $q['id'], 'direccion' => 'abajo'], '<i class="bi bi-arrow-down"></i>', 'btn btn-sm btn-outline-secondary'); ?>
                        <button class="btn btn-sm btn-outline-primary" onclick="editarPregunta(this)"
                            data-pregunta="<?= e(json_encode(['id' => $q['id'], 'texto' => $q['texto'], 'tipo' => $q['tipo'], 'obligatoria' => $q['obligatoria'],
                                'opciones' => implode("\n", array_column($q['opciones'], 'texto'))])) ?>"><i class="bi bi-pencil"></i></button>
                        <?php botonPost('../negocio/eliminar_pregunta.php', ['id' => $q['id']], '<i class="bi bi-trash"></i>', 'btn btn-sm btn-outline-danger', '¿Eliminar esta pregunta?'); ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>
</div>

<?php if ($editable): ?>
<!-- Ventana: agregar / editar pregunta -->
<div class="modal fade" id="modalPregunta" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="../negocio/guardar_pregunta.php" id="formPregunta" novalidate>
    <?= campoCsrf() ?><input type="hidden" name="id_encuesta" value="<?= (int) $enc['id'] ?>"><input type="hidden" name="id" id="pId">
    <div class="modal-header"><h6 class="modal-title" id="pTitulo">Agregar pregunta</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <?php if ($reabrir) mostrarErroresForm(); ?>
        <div class="mb-3"><label class="form-label">Pregunta *</label><input class="form-control" name="texto" id="pTexto" maxlength="200"></div>
        <div class="mb-3"><label class="form-label">Tipo *</label>
            <select class="form-select" name="tipo" id="pTipo" onchange="mostrarOpciones()">
                <?php foreach ($tipos as $k => $v): ?><option value="<?= $k ?>"><?= e($v) ?></option><?php endforeach; ?>
            </select></div>
        <div class="mb-3" id="bloqueOpciones"><label class="form-label">Opciones * <span class="text-secondary">(una por línea, entre 2 y 10)</span></label>
            <textarea class="form-control" name="opciones" id="pOpciones" rows="4"></textarea></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="obligatoria" id="pObligatoria" value="1" checked><label class="form-check-label" for="pObligatoria">Obligatoria</label></div>
        <div class="text-danger small mt-2 d-none" id="pErrorJs"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-hc">Guardar pregunta</button></div>
</form></div></div>
<?php endif; ?>

<!-- Vista previa (así la ve el paciente; no se envía) -->
<div class="modal fade" id="modalVista" tabindex="-1"><div class="modal-dialog modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title">Vista previa</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <h6 class="text-primary"><?= e($enc['titulo']) ?></h6>
        <?php if ($enc['descripcion']): ?><p class="text-secondary small"><?= e($enc['descripcion']) ?></p><?php endif; ?>
        <?php foreach ($enc['lista_preguntas'] as $i => $q): ?>
            <div class="mb-3">
                <label class="form-label fw-medium mb-1"><?= $i + 1 ?>. <?= e($q['texto']) ?><?= $q['obligatoria'] ? ' <span class="text-danger">*</span>' : '' ?></label>
                <?php if ($q['tipo'] === 'unica' || $q['tipo'] === 'multiple'): ?>
                    <?php foreach ($q['opciones'] as $o): ?>
                        <div class="form-check"><input class="form-check-input" type="<?= $q['tipo'] === 'unica' ? 'radio' : 'checkbox' ?>" disabled><label class="form-check-label"><?= e($o['texto']) ?></label></div>
                    <?php endforeach; ?>
                <?php elseif ($q['tipo'] === 'valoracion'): ?>
                    <div class="text-warning fs-5">☆ ☆ ☆ ☆ ☆ <span class="text-secondary small">(1 a 5)</span></div>
                <?php else: ?>
                    <textarea class="form-control" rows="2" disabled></textarea>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (!$enc['lista_preguntas']): ?><p class="text-secondary">Todavía no hay preguntas.</p><?php endif; ?>
    </div>
</div></div></div>
<?php
$datosJs = json_encode($datosForm ?: new stdClass());
$scriptExtra = $editable ? '<script>
var modalP = new bootstrap.Modal(document.getElementById("modalPregunta"));
function mostrarOpciones() {
    var t = document.getElementById("pTipo").value;
    document.getElementById("bloqueOpciones").classList.toggle("d-none", t !== "unica" && t !== "multiple");
}
function llenar(d) {
    document.getElementById("pId").value = d.id || "";
    document.getElementById("pTexto").value = d.texto || "";
    document.getElementById("pTipo").value = d.tipo || "unica";
    document.getElementById("pOpciones").value = d.opciones || "";
    document.getElementById("pObligatoria").checked = (d.obligatoria === undefined || d.obligatoria == 1);
    document.getElementById("pTitulo").textContent = d.id ? "Editar pregunta" : "Agregar pregunta";
    mostrarOpciones();
}
function abrirPregunta() { llenar({}); modalP.show(); }
function editarPregunta(b) { llenar(JSON.parse(b.getAttribute("data-pregunta"))); modalP.show(); }
document.getElementById("formPregunta").addEventListener("submit", function (ev) {
    var msg = "", t = document.getElementById("pTipo").value;
    if (document.getElementById("pTexto").value.trim().length < 3) msg = "Escriba la pregunta (mínimo 3 caracteres).";
    else if (t === "unica" || t === "multiple") {
        var lineas = document.getElementById("pOpciones").value.split(/\r?\n/).map(function (x) { return x.trim(); }).filter(Boolean);
        if (lineas.length < 2 || lineas.length > 10) msg = "Escriba entre 2 y 10 opciones, una por línea.";
    }
    var caja = document.getElementById("pErrorJs");
    caja.textContent = msg; caja.classList.toggle("d-none", msg === "");
    if (msg) ev.preventDefault();
});
mostrarOpciones();
' . ($reabrir ? 'llenar(' . $datosJs . '); modalP.show();' : '') . '
</script>' : '';
require __DIR__ . '/_pie.php'; ?>
