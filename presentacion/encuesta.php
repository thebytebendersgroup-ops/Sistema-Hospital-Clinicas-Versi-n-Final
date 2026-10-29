<?php
// ============================================================
// Encuesta de satisfacción ANÓNIMA (pública, sin iniciar sesión): encuesta.php?id=N
// Tipos de pregunta: opción única, múltiple, valoración 1–5 y texto breve.
// Se muestra solo si la encuesta está publicada y su servicio está activo.
// Protecciones: token de un solo uso, campo trampa y tiempo mínimo (anti-robots).
// ============================================================
require_once __DIR__ . '/../negocio/sesion.php';
require_once __DIR__ . '/../negocio/ayudas.php';
require_once __DIR__ . '/../negocio/antibots.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Encuesta.php';
require_once __DIR__ . '/../datos/Pregunta.php';
require_once __DIR__ . '/_vista.php';

$id = enteroPositivo($_GET['id'] ?? '');
$enc = false;
try {
    $enc = $id ? (new Encuesta((new Conexion())->getConexion()))->cargarCompleta($id) : false;
} catch (PDOException $e) {
    error_log('Error al leer la encuesta: ' . $e->getMessage());
}
if ($enc && ($enc['estado'] !== 'publicada' || ($enc['id_categoria'] !== null && !$enc['servicio_activo']))) {
    $enc = false;
}

$previos = [];
if ($enc) {
    $previos = datosForm();                         // lo que ya había marcado si hubo errores
    $token = nuevoTokenUnico('encuesta_' . $enc['id']);
    marcarInicioFormulario('encuesta_' . $enc['id']);
} else {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Encuesta — Hospital de Clínicas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f4f6f9; }
        .estrellas input { display:none; }
        .estrellas label { font-size:2rem; color:#d1d5db; cursor:pointer; }
        .estrellas { display:flex; flex-direction:row-reverse; justify-content:flex-end; }
        .estrellas input:checked ~ label, .estrellas label:hover, .estrellas label:hover ~ label { color:#f59e0b; }
    </style>
</head>
<body>
<div class="container py-3" style="max-width:480px;">
    <div class="card border-0 shadow-sm"><div class="card-body p-4">
    <?php mostrarMensaje(); ?>
    <?php if (!$enc): ?>
        <h5 class="text-primary">Encuesta no disponible</h5>
        <p class="text-secondary">La encuesta no existe o ya no está disponible.</p>
    <?php else: ?>
        <h5 class="text-primary mb-1"><?= e($enc['titulo']) ?></h5>
        <?php if ($enc['descripcion']): ?><p class="text-secondary small"><?= e($enc['descripcion']) ?></p><?php endif; ?>
        <p class="text-secondary small">Es anónima: no se guarda quién responde. Los campos con <span class="text-danger">*</span> son obligatorios.</p>
        <?php mostrarErroresForm(); ?>
        <div class="alert alert-danger py-2 d-none" id="errorJs" style="font-size:14px;"></div>

        <form action="../negocio/enviar_encuesta.php" method="POST" id="formEncuesta" novalidate>
            <input type="hidden" name="id_encuesta" value="<?= (int) $enc['id'] ?>">
            <input type="hidden" name="token_unico" value="<?= e($token) ?>">
            <?= campoTrampa() ?>
            <?php foreach ($enc['lista_preguntas'] as $q): $campo = 'p_' . (int) $q['id']; $previo = $previos[$campo] ?? null; ?>
                <fieldset class="mb-4" data-tipo="<?= e($q['tipo']) ?>" data-obligatoria="<?= $q['obligatoria'] ? 1 : 0 ?>" data-texto="<?= e($q['texto']) ?>">
                    <legend class="fs-6 fw-semibold"><?= e($q['texto']) ?><?= $q['obligatoria'] ? ' <span class="text-danger">*</span>' : '' ?></legend>
                    <?php if ($q['tipo'] === 'unica'): ?>
                        <?php foreach ($q['opciones'] as $o): ?>
                            <div class="form-check"><input class="form-check-input" type="radio" name="<?= $campo ?>" id="o<?= (int) $o['id'] ?>" value="<?= (int) $o['id'] ?>" <?= (string) $previo === (string) $o['id'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="o<?= (int) $o['id'] ?>"><?= e($o['texto']) ?></label></div>
                        <?php endforeach; ?>
                    <?php elseif ($q['tipo'] === 'multiple'): ?>
                        <?php foreach ($q['opciones'] as $o): ?>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="<?= $campo ?>[]" id="o<?= (int) $o['id'] ?>" value="<?= (int) $o['id'] ?>" <?= is_array($previo) && in_array((string) $o['id'], $previo, true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="o<?= (int) $o['id'] ?>"><?= e($o['texto']) ?></label></div>
                        <?php endforeach; ?>
                    <?php elseif ($q['tipo'] === 'valoracion'): ?>
                        <div class="estrellas">
                            <?php for ($n = 5; $n >= 1; $n--): ?>
                                <input type="radio" name="<?= $campo ?>" id="<?= $campo ?>_<?= $n ?>" value="<?= $n ?>" <?= (string) $previo === (string) $n ? 'checked' : '' ?>>
                                <label for="<?= $campo ?>_<?= $n ?>" title="<?= $n ?> de 5">★</label>
                            <?php endfor; ?>
                        </div>
                        <div class="form-text">1 = muy malo · 5 = excelente</div>
                    <?php else: ?>
                        <textarea class="form-control" name="<?= $campo ?>" rows="3" maxlength="300"><?= e(is_string($previo) ? $previo : '') ?></textarea>
                    <?php endif; ?>
                </fieldset>
            <?php endforeach; ?>
            <?php if (!$enc['lista_preguntas']): ?><p class="text-secondary">Esta encuesta todavía no tiene preguntas.</p>
            <?php else: ?><button type="submit" class="btn btn-primary w-100" id="botonEnviar">Enviar opinión</button><?php endif; ?>
        </form>
    <?php endif; ?>
    </div></div>
    <p class="text-center mt-3"><a href="portal-paciente.php?tab=encuestas" class="text-decoration-none">&larr; Volver al portal</a></p>
</div>
<script>
// Validación en JavaScript (el servidor vuelve a validar todo)
var formulario = document.getElementById('formEncuesta');
if (formulario) {
    formulario.addEventListener('submit', function (ev) {
        var faltan = [];
        formulario.querySelectorAll('fieldset').forEach(function (f) {
            if (f.dataset.obligatoria !== '1') { return; }
            var ok;
            if (f.dataset.tipo === 'texto') { ok = f.querySelector('textarea').value.trim() !== ''; }
            else { ok = f.querySelector('input:checked') !== null; }
            if (!ok) { faltan.push(f.dataset.texto); }
        });
        var caja = document.getElementById('errorJs');
        if (faltan.length) {
            ev.preventDefault();
            caja.textContent = 'Falta responder: ' + faltan.join(' · ');
            caja.classList.remove('d-none');
            window.scrollTo(0, 0);
            return;
        }
        document.getElementById('botonEnviar').disabled = true;   // evita el doble clic
    });
}
</script>
</body>
</html>
