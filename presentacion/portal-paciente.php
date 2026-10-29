<?php
// ============================================================
// Portal del paciente (PÚBLICO: no hace falta iniciar sesión; el paciente no puede modificar nada).
// Se llega escaneando el QR del hospital (el mismo para todos).
// Muestra los documentos PUBLICADOS (con búsqueda y filtro por servicio) y las encuestas disponibles.
// ============================================================
require_once __DIR__ . '/../negocio/sesion.php';
require_once __DIR__ . '/../negocio/ayudas.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Documento.php';
require_once __DIR__ . '/../datos/Encuesta.php';
require_once __DIR__ . '/../datos/Pregunta.php';
require_once __DIR__ . '/../datos/CategoriaEncuesta.php';
require_once __DIR__ . '/_vista.php';

$buscar = trim($_GET['q'] ?? '');
$idCat  = enteroPositivo($_GET['servicio'] ?? '');
$idTipo = enteroPositivo($_GET['categoria'] ?? '');   // categoría de encuesta
$tab    = ($_GET['tab'] ?? '') === 'encuestas' ? 'encuestas' : 'docs';

$documentos = []; $encuestas = []; $servicios = []; $categoriasEnc = [];
try {
    $pdo = (new Conexion())->getConexion();
    $documentos = (new Documento($pdo))->listarPublicos();
    $encuestas  = (new Encuesta($pdo))->listarPublicadas();
    $categoriasEnc = (new CategoriaEncuesta($pdo))->listarConEncuestasPublicadas();
} catch (PDOException $e) {
    error_log('Error en el portal: ' . $e->getMessage());
    guardarMensaje('No se pudo cargar la información. Intente nuevamente.', 'danger');
}

// Servicios que aparecen en el filtro (los que tienen algo para mostrar)
foreach ($documentos as $d) { $servicios[$d['id_categoria']] = $d['categoria']; }
foreach ($encuestas as $en) { if ($en['id_categoria']) { $servicios[$en['id_categoria']] = $en['servicio']; } }
asort($servicios);

// Filtros (en PHP, sobre las listas públicas)
$coincide = function ($titulo, $servicio, $idServicio) use ($buscar, $idCat) {
    if ($idCat && (int) $idServicio !== $idCat) { return false; }
    return $buscar === '' || mb_stripos($titulo . ' ' . $servicio, $buscar) !== false;
};
$documentos = array_values(array_filter($documentos, function ($d) use ($coincide) { return $coincide($d['titulo'], $d['categoria'], $d['id_categoria']); }));
$encuestas  = array_values(array_filter($encuestas, function ($en) use ($buscar, $idCat, $idTipo) {
    if ($idCat && (int) $en['id_categoria'] !== $idCat) { return false; }
    if ($idTipo && (int) $en['id_categoria_encuesta'] !== $idTipo) { return false; }
    // la búsqueda mira el título, la descripción, el servicio y la categoría de la encuesta
    $texto = $en['titulo'] . ' ' . $en['descripcion'] . ' ' . $en['servicio'] . ' ' . $en['categoria_encuesta'];
    return $buscar === '' || mb_stripos($texto, $buscar) !== false;
}));

$porCategoria = [];
foreach ($documentos as $d) { $porCategoria[$d['categoria']][] = $d; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Información para pacientes — Hospital de Clínicas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .bg-hc { background-color: #1B3A5C; }
        .btn-hc { --bs-btn-bg: #2563EB; --bs-btn-border-color: #2563EB; --bs-btn-color: #fff;
                  --bs-btn-hover-bg: #1D4ED8; --bs-btn-hover-border-color: #1D4ED8; --bs-btn-hover-color: #fff; }
        .doc-item { padding: 6px 4px; border-bottom: 1px solid #F3F4F6; }
        .doc-item:last-child { border-bottom: none; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100" style="background:#F5F6F8;">
    <nav class="navbar bg-hc text-white px-3">
        <div class="container-fluid px-0" style="max-width:820px;">
            <a class="navbar-brand text-white" href="portal-paciente.php">
                <span class="fw-semibold d-block" style="font-size:15px;">Hospital de Clínicas</span>
                <span class="opacity-50 d-block" style="font-size:11px;">Portal del Paciente</span>
            </a>
        </div>
    </nav>

    <main class="flex-grow-1 px-3 py-4" style="max-width:820px; margin:0 auto; width:100%;">
        <?php mostrarMensaje(); ?>
        <h5 class="fw-semibold mb-1">Información para pacientes</h5>
        <p class="text-secondary mb-3" style="font-size:14px;">Indicaciones, instructivos y folletos de los servicios del hospital, y encuestas para darnos su opinión.</p>

        <form method="GET" class="row g-2 mb-3">
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <div class="col-12 <?= $tab === 'encuestas' ? 'col-sm-4' : 'col-sm-6' ?>"><input type="search" class="form-control" name="q" value="<?= e($buscar) ?>" placeholder="<?= $tab === 'encuestas' ? 'Buscar encuestas...' : 'Buscar documentos...' ?>" maxlength="100"></div>
            <?php if ($tab === 'encuestas'): ?>
            <div class="col-6 col-sm-3">
                <select name="categoria" class="form-select" aria-label="Categoría de encuesta">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categoriasEnc as $ce): ?><option value="<?= (int) $ce['id'] ?>" <?= (int) $ce['id'] === $idTipo ? 'selected' : '' ?>><?= e($ce['nombre']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="<?= $tab === 'encuestas' ? 'col-6 col-sm-3' : 'col-8 col-sm-4' ?>">
                <select name="servicio" class="form-select">
                    <option value="">Todos los servicios</option>
                    <?php foreach ($servicios as $idS => $nombreS): ?><option value="<?= (int) $idS ?>" <?= (int) $idS === $idCat ? 'selected' : '' ?>><?= e($nombreS) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="<?= $tab === 'encuestas' ? 'col-12 col-sm-2' : 'col-4 col-sm-2' ?>"><button class="btn btn-hc w-100">Buscar</button></div>
        </form>

        <ul class="nav nav-tabs mb-3">
            <li class="nav-item"><a class="nav-link <?= $tab === 'docs' ? 'active' : '' ?>" href="<?= e(urlCon(['tab' => 'docs'])) ?>"><i class="bi bi-file-earmark-text me-1"></i>Documentos (<?= count($documentos) ?>)</a></li>
            <li class="nav-item"><a class="nav-link <?= $tab === 'encuestas' ? 'active' : '' ?>" href="<?= e(urlCon(['tab' => 'encuestas'])) ?>"><i class="bi bi-clipboard-data me-1"></i>Encuestas (<?= count($encuestas) ?>)</a></li>
        </ul>

        <?php if ($tab === 'docs'): ?>
            <?php if (!$documentos): ?><div class="text-center text-muted py-4" style="font-size:14px;">No se encontraron documentos.</div><?php endif; ?>
            <?php $n = 0; foreach ($porCategoria as $categoria => $lista): $n++; ?>
                <div class="mb-2">
                    <button class="btn w-100 text-start d-flex justify-content-between align-items-center py-2 px-3 shadow-sm bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#cat<?= $n ?>" style="font-size:15px;">
                        <span><i class="bi bi-folder2-open text-primary me-2"></i><?= e($categoria) ?> <span class="badge bg-primary bg-opacity-10 text-primary ms-2"><?= count($lista) ?></span></span>
                        <i class="bi bi-chevron-down text-muted" style="font-size:12px;"></i>
                    </button>
                    <div class="collapse show" id="cat<?= $n ?>"><div class="ps-3 pe-1 py-2">
                        <?php foreach ($lista as $d): ?>
                            <div class="doc-item d-flex justify-content-between align-items-center gap-2">
                                <div style="min-width:0;">
                                    <span class="d-block text-truncate" style="font-size:14px;"><i class="bi bi-file-earmark-text text-danger me-1"></i><?= e($d['titulo']) ?></span>
                                    <span class="text-muted" style="font-size:12px;">Emitido el <?= e(date('d/m/Y', strtotime($d['fecha_emision']))) ?></span>
                                </div>
                                <a class="btn btn-outline-primary btn-sm flex-shrink-0" href="ver_documento_mobile.php?id=<?= (int) $d['id'] ?>"><i class="bi bi-eye me-1"></i>Ver</a>
                            </div>
                        <?php endforeach; ?>
                    </div></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-secondary" style="font-size:13px;">Las encuestas son anónimas y voluntarias.</p>
            <?php if (!$encuestas): ?><div class="text-center text-muted py-4" style="font-size:14px;">No hay encuestas disponibles.</div><?php endif; ?>
            <?php foreach ($encuestas as $en): ?>
                <div class="card border shadow-sm mb-2"><div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-semibold" style="font-size:15px;"><i class="bi bi-emoji-smile text-primary me-1"></i><?= e($en['titulo']) ?></div>
                        <div class="text-secondary" style="font-size:13px;">
                            <?php if ($en['categoria_encuesta']): ?><span class="badge bg-primary bg-opacity-10 text-primary me-1"><?= e($en['categoria_encuesta']) ?></span><?php endif; ?>
                            <?= $en['servicio'] ? e($en['servicio']) : 'General' ?> · <?= (int) $en['preguntas'] ?> pregunta(s)</div>
                        <?php if ($en['descripcion']): ?><div class="text-secondary" style="font-size:13px;"><?= e($en['descripcion']) ?></div><?php endif; ?>
                    </div>
                    <a class="btn btn-hc flex-shrink-0" href="encuesta.php?id=<?= (int) $en['id'] ?>">Responder</a>
                </div></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <footer class="border-top mt-auto py-3 px-3">
        <div class="d-flex justify-content-between" style="max-width:820px;margin:0 auto;">
            <span class="text-muted" style="font-size:12px;">Hospital de Clínicas — DTI</span>
            <span class="text-muted" style="font-size:12px;">S.I.G.S.M.</span>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
