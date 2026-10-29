<?php
// ============================================================
// Inicio del panel: indicadores generales del sistema.
// ============================================================
require_once __DIR__ . '/_vista.php';
require_once __DIR__ . '/../datos/conexion.php';
require_once __DIR__ . '/../datos/Documento.php';
require_once __DIR__ . '/../datos/Encuesta.php';
require_once __DIR__ . '/../datos/Pregunta.php';
require_once __DIR__ . '/../datos/Resultado.php';
require_once __DIR__ . '/../datos/Registro.php';
exigirLogin();

$ind = ['documentos' => 0, 'servicios' => 0, 'encuestas' => 0, 'envios' => 0, 'promedio' => 0];
$ultimas = [];
try {
    $pdo = (new Conexion())->getConexion();
    $doc = new Documento($pdo);
    $res = (new Resultado($pdo))->indicadores();
    $ind = [
        'documentos' => $doc->contarActivos(),
        'servicios'  => $doc->contarServiciosConDocumentos(),
        'encuestas'  => (new Encuesta($pdo))->contarPublicadas(),
        'envios'     => $res['envios'],
        'promedio'   => $res['promedio'],
    ];
    if (esAdmin()) {
        $ultimas = (new Registro($pdo))->listar('', '', 0, 5, 0);
    }
} catch (PDOException $e) {
    error_log('Error en el inicio: ' . $e->getMessage());
    guardarMensaje('No se pudieron leer los indicadores.', 'danger');
}

$tituloPagina = 'Inicio';
$paginaActiva = 'inicio';
require __DIR__ . '/_cabecera.php';

$tarjetas = [
    ['Documentos activos',        $ind['documentos'], 'bi-file-earmark-text'],
    ['Servicios con documentos',  $ind['servicios'],  'bi-hospital'],
    ['Encuestas publicadas',      $ind['encuestas'],  'bi-clipboard-check'],
    ['Respuestas recibidas',      $ind['envios'],     'bi-chat-square-text'],
    ['Valoración promedio (1–5)', $ind['promedio'],   'bi-star'],
];
?>
<h5 class="fw-semibold mb-1">Hola, <?= e($_SESSION['usuario']['nombre']) ?></h5>
<p class="text-secondary" style="font-size:14px;">Resumen del sistema.</p>

<div class="row g-3 mb-4">
    <?php foreach ($tarjetas as $t): ?>
        <div class="col-6 col-lg">
            <div class="card border shadow-sm h-100"><div class="card-body">
                <i class="bi <?= e($t[2]) ?> text-primary" style="font-size:20px;"></i>
                <div class="fs-3 fw-semibold"><?= e($t[1]) ?></div>
                <div class="text-secondary" style="font-size:12px;"><?= e($t[0]) ?></div>
            </div></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-md-6"><div class="card border shadow-sm h-100"><div class="card-body">
        <h6 class="fw-semibold">Accesos rápidos</h6>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-hc btn-sm" href="documentos.php"><i class="bi bi-upload"></i> Cargar documento</a>
            <a class="btn btn-outline-primary btn-sm" href="encuestas.php"><i class="bi bi-plus-lg"></i> Nueva encuesta</a>
            <a class="btn btn-outline-primary btn-sm" href="resultados.php"><i class="bi bi-bar-chart"></i> Ver resultados</a>
            <a class="btn btn-outline-primary btn-sm" href="qr.php"><i class="bi bi-qr-code"></i> Mostrar el QR</a>
        </div>
    </div></div></div>
    <?php if (esAdmin()): ?>
    <div class="col-md-6"><div class="card border shadow-sm h-100"><div class="card-body">
        <h6 class="fw-semibold">Últimas acciones <a href="registro.php" class="small fw-normal ms-2">ver todas</a></h6>
        <?php foreach ($ultimas as $b): ?>
            <div style="font-size:13px;" class="border-bottom py-1">
                <span class="text-secondary"><?= e(date('d/m H:i', strtotime($b['fecha']))) ?></span>
                <strong><?= e($b['usuario'] ?? '—') ?></strong> · <?= e($b['accion']) ?>
            </div>
        <?php endforeach; ?>
        <?php if (!$ultimas): ?><p class="text-secondary mb-0" style="font-size:13px;">Todavía no hay acciones.</p><?php endif; ?>
    </div></div></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/_pie.php'; ?>
